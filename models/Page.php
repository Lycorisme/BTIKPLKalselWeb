<?php
/**
 * Page Model
 */

class Page extends Model {
    protected $table = 'pages';
    
    /**
     * Get page by slug
     */
    public function getBySlug($slug) {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table} 
            WHERE slug = ? AND deleted_at IS NULL
        ");
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get page by ID
     */
    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table} 
            WHERE id = ? AND deleted_at IS NULL
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all active pages
     */
    public function getAllPages() {
        $stmt = $this->db->query("
            SELECT * FROM {$this->table} 
            WHERE deleted_at IS NULL 
            ORDER BY title ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Increment page view count
     */
    public function incrementViewCount($id) {
        $stmt = $this->db->prepare("
            UPDATE {$this->table} 
            SET view_count = view_count + 1 
            WHERE id = ?
        ");
        return $stmt->execute([$id]);
    }
}
