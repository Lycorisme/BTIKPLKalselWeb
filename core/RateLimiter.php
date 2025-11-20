<?php
/**
 * Rate Limiter Class
 * Mencegah spam dan brute force attacks
 * 
 * @author Lycorisme
 * @version 1.0
 */

class RateLimiter {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Check apakah aksi diizinkan
     * 
     * @param string $identifier Email atau IP address
     * @param string $actionType comment|like|contact|download
     * @param int $maxAttempts Maksimal percobaan dalam window
     * @param int $timeWindowMinutes Durasi window dalam menit
     * @return array ['allowed' => bool, 'attempts_left' => int, 'reset_time' => timestamp, 'message' => string]
     */
    public function check($identifier, $actionType, $maxAttempts = 5, $timeWindowMinutes = 15) {
        try {
            // Call stored procedure
            $stmt = $this->db->prepare("CALL sp_check_rate_limit(?, ?, ?, ?, @is_allowed, @attempts_left, @reset_time)");
            $stmt->execute([$identifier, $actionType, $maxAttempts, $timeWindowMinutes]);
            
            // Get output parameters
            $result = $this->db->query("SELECT @is_allowed as allowed, @attempts_left as attempts_left, @reset_time as reset_time")->fetch(PDO::FETCH_ASSOC);
            
            $isAllowed = (bool)$result['allowed'];
            $attemptsLeft = (int)$result['attempts_left'];
            $resetTime = $result['reset_time'];
            
            // Generate user-friendly message
            $message = '';
            if (!$isAllowed) {
                if ($resetTime) {
                    $resetTimeFormatted = date('H:i', strtotime($resetTime));
                    $message = "Terlalu banyak percobaan. Silakan coba lagi pada pukul {$resetTimeFormatted}.";
                } else {
                    $message = "Aksi dibatasi untuk sementara waktu.";
                }
            } else {
                if ($attemptsLeft <= 2 && $attemptsLeft > 0) {
                    $message = "{$attemptsLeft} percobaan tersisa.";
                }
            }
            
            return [
                'allowed' => $isAllowed,
                'attempts_left' => $attemptsLeft,
                'reset_time' => $resetTime,
                'message' => $message
            ];
            
        } catch (PDOException $e) {
            error_log("RateLimiter Check Error: " . $e->getMessage());
            // Fallback: allow action jika error
            return [
                'allowed' => true,
                'attempts_left' => $maxAttempts,
                'reset_time' => null,
                'message' => ''
            ];
        }
    }
    
    /**
     * Record aksi user
     * 
     * @param string $identifier Email atau IP address
     * @param string $actionType comment|like|contact|download
     * @param int $timeWindowMinutes Durasi window dalam menit
     * @return bool Success status
     */
    public function record($identifier, $actionType, $timeWindowMinutes = 15) {
        try {
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            // Call stored procedure
            $stmt = $this->db->prepare("CALL sp_record_action(?, ?, ?, ?, ?)");
            $stmt->execute([
                $identifier,
                $actionType,
                $ipAddress,
                $userAgent,
                $timeWindowMinutes
            ]);
            
            return true;
            
        } catch (PDOException $e) {
            error_log("RateLimiter Record Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get statistics untuk debugging/monitoring
     * 
     * @param string $identifier Email atau IP address
     * @param string $actionType comment|like|contact|download
     * @return array Statistics
     */
    public function getStats($identifier, $actionType) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    action_count,
                    window_start,
                    window_end,
                    is_blocked,
                    blocked_until,
                    block_reason
                FROM rate_limits
                WHERE identifier = ?
                  AND action_type = ?
                  AND (window_end >= NOW() OR is_blocked = 1)
                ORDER BY created_at DESC
                LIMIT 1
            ");
            $stmt->execute([$identifier, $actionType]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            
        } catch (PDOException $e) {
            error_log("RateLimiter GetStats Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Unblock user manually (untuk admin)
     * 
     * @param string $identifier Email atau IP address
     * @param string $actionType comment|like|contact|download
     * @return bool Success status
     */
    public function unblock($identifier, $actionType = null) {
        try {
            if ($actionType) {
                // Unblock specific action type
                $stmt = $this->db->prepare("
                    UPDATE rate_limits 
                    SET is_blocked = 0, 
                        blocked_until = NULL,
                        block_reason = NULL
                    WHERE identifier = ? 
                      AND action_type = ?
                ");
                $stmt->execute([$identifier, $actionType]);
            } else {
                // Unblock all action types
                $stmt = $this->db->prepare("
                    UPDATE rate_limits 
                    SET is_blocked = 0, 
                        blocked_until = NULL,
                        block_reason = NULL
                    WHERE identifier = ?
                ");
                $stmt->execute([$identifier]);
            }
            
            return true;
            
        } catch (PDOException $e) {
            error_log("RateLimiter Unblock Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cleanup expired rate limit records
     * Jalankan via cron job setiap hari
     * 
     * @return int Jumlah record yang dihapus
     */
    public function cleanup() {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM rate_limits 
                WHERE window_end < DATE_SUB(NOW(), INTERVAL 7 DAY)
                  AND is_blocked = 0
            ");
            $stmt->execute();
            
            return $stmt->rowCount();
            
        } catch (PDOException $e) {
            error_log("RateLimiter Cleanup Error: " . $e->getMessage());
            return 0;
        }
    }
}
