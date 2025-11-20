<?php
/**
 * Page View Tracker Class
 * Track page views dengan deduplication per session
 * 
 * @author Lycorisme
 * @version 1.0
 */

class PageViewTracker {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Track page view dengan deduplication (1 view per session per page)
     * 
     * @param string $type post|page|service
     * @param int $id ID dari content
     * @return bool Success status
     */
    public function track($type, $id) {
        try {
            // Validasi parameter
            if (empty($type) || empty($id)) {
                return false;
            }
            
            // Validasi viewable_type
            $validTypes = ['post', 'page', 'service'];
            if (!in_array($type, $validTypes)) {
                error_log("Invalid viewable_type: $type");
                return false;
            }
            
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $referer = $_SERVER['HTTP_REFERER'] ?? '';
            $sessionId = session_id();
            
            // Jika session belum start, tidak bisa track
            if (empty($sessionId)) {
                error_log("Page View Tracker: Session not started");
                return false;
            }
            
            // Check jika sudah pernah view dalam session ini (prevent multiple count)
            // Time window: 30 menit (untuk handle refresh/back button)
            $stmt = $this->db->prepare("
                SELECT id FROM page_views 
                WHERE viewable_type = ? 
                  AND viewable_id = ? 
                  AND session_id = ? 
                  AND created_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE)
                LIMIT 1
            ");
            $stmt->execute([$type, $id, $sessionId]);
            
            if ($stmt->fetch()) {
                // Already tracked in this session recently
                return false;
            }
            
            // Insert new page view
            $stmt = $this->db->prepare("
                INSERT INTO page_views (
                    viewable_type, 
                    viewable_id, 
                    ip_address, 
                    user_agent, 
                    referer, 
                    session_id,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $result = $stmt->execute([
                $type, 
                $id, 
                $ipAddress, 
                $userAgent, 
                $referer, 
                $sessionId
            ]);
            
            return $result;
            
        } catch (PDOException $e) {
            error_log("Page View Tracker Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get view statistics untuk content tertentu
     * 
     * @param string $type post|page|service
     * @param int $id ID dari content
     * @param int $days Jumlah hari ke belakang (default 30)
     * @return array Statistics
     */
    public function getStats($type, $id, $days = 30) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_views,
                    COUNT(DISTINCT ip_address) as unique_visitors,
                    COUNT(DISTINCT DATE(created_at)) as days_with_views
                FROM page_views
                WHERE viewable_type = ?
                  AND viewable_id = ?
                  AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$type, $id, $days]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ?: [
                'total_views' => 0, 
                'unique_visitors' => 0, 
                'days_with_views' => 0
            ];
            
        } catch (PDOException $e) {
            error_log("Page View Stats Error: " . $e->getMessage());
            return [
                'total_views' => 0, 
                'unique_visitors' => 0, 
                'days_with_views' => 0
            ];
        }
    }
    
    /**
     * Get view trend (views per day) untuk chart
     * 
     * @param string $type post|page|service
     * @param int $id ID dari content
     * @param int $days Jumlah hari ke belakang
     * @return array Array of ['date' => 'YYYY-MM-DD', 'views' => count]
     */
    public function getViewTrend($type, $id, $days = 7) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    DATE(created_at) as view_date,
                    COUNT(*) as view_count
                FROM page_views
                WHERE viewable_type = ?
                  AND viewable_id = ?
                  AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(created_at)
                ORDER BY view_date ASC
            ");
            $stmt->execute([$type, $id, $days]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            
        } catch (PDOException $e) {
            error_log("Page View Trend Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get top referrers untuk content tertentu
     * 
     * @param string $type post|page|service
     * @param int $id ID dari content
     * @param int $limit Jumlah top referrers
     * @return array Array of ['referer' => url, 'count' => count]
     */
    public function getTopReferrers($type, $id, $limit = 10) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    referer,
                    COUNT(*) as count
                FROM page_views
                WHERE viewable_type = ?
                  AND viewable_id = ?
                  AND referer IS NOT NULL
                  AND referer != ''
                GROUP BY referer
                ORDER BY count DESC
                LIMIT ?
            ");
            $stmt->execute([$type, $id, $limit]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            
        } catch (PDOException $e) {
            error_log("Page View Referrers Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Cleanup old page views (untuk maintenance)
     * Jalankan via cron job bulanan
     * 
     * @param int $days Hapus data lebih dari X hari
     * @return int Jumlah records yang dihapus
     */
    public function cleanup($days = 90) {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM page_views 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$days]);
            
            return $stmt->rowCount();
            
        } catch (PDOException $e) {
            error_log("Page View Cleanup Error: " . $e->getMessage());
            return 0;
        }
    }
}
