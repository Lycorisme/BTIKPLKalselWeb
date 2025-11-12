<?php
/**
 * Comment Model
 * (FIXED: Menggunakan 'commentable_type' & 'commentable_id'
 * dan 'status' column)
 */

require_once dirname(__DIR__) . '/core/Model.php';

class Comment extends Model {
    protected $table = 'comments';
    
    /**
     * Get comments by model (with threading support)
     */
    public function getCommentsByModel($model_type, $model_id, $parent_id = null) {
        // PERBAIKAN: Menggunakan 'commentable_type', 'commentable_id', dan 'status'
        $sql = "SELECT * FROM {$this->table} 
                WHERE commentable_type = ? AND commentable_id = ? AND status = 'approved'";
        
        if ($parent_id === null) {
            $sql .= " AND parent_id IS NULL";
        } else {
            $sql .= " AND parent_id = " . (int)$parent_id;
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$model_type, $model_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get comment count by model
     */
    public function getCommentCount($model_type, $model_id) {
        // PERBAIKAN: Menggunakan 'commentable_type', 'commentable_id', dan 'status'
        // Ini adalah baris 38 yang menyebabkan error
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM {$this->table} 
            WHERE commentable_type = ? AND commentable_id = ? AND status = 'approved'
        ");
        $stmt->execute([$model_type, $model_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }
    
    /**
     * Add comment
     */
    public function addComment($data) {
        // PERBAIKAN: Menggunakan 'commentable_type', 'commentable_id', dan 'status'
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} 
            (commentable_type, commentable_id, parent_id, author_name, author_email, content, ip_address, user_agent, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        
        return $stmt->execute([
            $data['model_type'],      // <-- Nama variabel di PHP (model_type)
            $data['model_id'],        // <-- Nama variabel di PHP (model_id)
            $data['parent_id'] ?? null,
            $data['author_name'],
            $data['author_email'],
            $data['content'],
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }

    /**
     * Get comment by ID
     */
    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table} 
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Anda bisa tambahkan fungsi lain di sini jika perlu,
    // misalnya untuk CRUD Komentar di admin panel (getAll, updateStatus, delete, etc.)
}