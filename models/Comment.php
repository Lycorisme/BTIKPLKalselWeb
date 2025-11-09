<?php
/**
 * Comment Model
 */

class Comment extends Model {
    protected $table = 'comments';
    
    /**
     * Get comments by model (with threading support)
     */
    public function getCommentsByModel($model_type, $model_id, $parent_id = null) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE model_type = ? AND model_id = ? AND is_approved = 1";
        
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
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM {$this->table} 
            WHERE model_type = ? AND model_id = ? AND is_approved = 1
        ");
        $stmt->execute([$model_type, $model_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }
    
    /**
     * Add comment
     */
    public function addComment($data) {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} 
            (model_type, model_id, parent_id, author_name, author_email, content, ip_address, user_agent, is_approved) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)
        ");
        
        return $stmt->execute([
            $data['model_type'],
            $data['model_id'],
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
}
