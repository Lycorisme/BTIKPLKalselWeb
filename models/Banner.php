<?php
/**
 * Banner Model
 */

class Banner extends Model {
    protected $table = 'banners';
    
    /**
     * Get all active banners
     */
    public function getActiveBanners($limit = null) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE is_active = 1 AND deleted_at IS NULL 
                ORDER BY display_order ASC, created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get banner by ID
     */
    public function getBannerById($id) {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table} 
            WHERE id = ? AND deleted_at IS NULL
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get banner by slug
     */
    public function getBySlug($slug) {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table} 
            WHERE slug = ? AND deleted_at IS NULL
        ");
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
