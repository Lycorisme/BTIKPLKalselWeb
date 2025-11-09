<?php
/**
 * File Model (Downloadable Files)
 */

class File extends Model {
    protected $table = 'downloadable_files';
    
    /**
     * Get all active files
     */
    public function getAllFiles($category_id = null, $limit = null) {
        $sql = "SELECT f.*, c.name as category_name 
                FROM {$this->table} f
                LEFT JOIN file_categories c ON f.category_id = c.id
                WHERE f.is_active = 1 AND f.deleted_at IS NULL";
        
        if ($category_id) {
            $sql .= " AND f.category_id = " . (int)$category_id;
        }
        
        $sql .= " ORDER BY f.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get file by ID
     */
    public function getFileById($id) {
        $stmt = $this->db->prepare("
            SELECT f.*, c.name as category_name 
            FROM {$this->table} f
            LEFT JOIN file_categories c ON f.category_id = c.id
            WHERE f.id = ? AND f.is_active = 1 AND f.deleted_at IS NULL
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get file by slug
     */
    public function getBySlug($slug) {
        $stmt = $this->db->prepare("
            SELECT f.*, c.name as category_name 
            FROM {$this->table} f
            LEFT JOIN file_categories c ON f.category_id = c.id
            WHERE f.slug = ? AND f.is_active = 1 AND f.deleted_at IS NULL
        ");
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Increment download count
     */
    public function incrementDownloadCount($id) {
        $stmt = $this->db->prepare("
            UPDATE {$this->table} 
            SET download_count = download_count + 1 
            WHERE id = ?
        ");
        return $stmt->execute([$id]);
    }
    
    /**
     * Get file categories
     */
    public function getCategories() {
        $stmt = $this->db->query("
            SELECT c.*, COUNT(f.id) as file_count
            FROM file_categories c
            LEFT JOIN {$this->table} f ON f.category_id = c.id AND f.is_active = 1 AND f.deleted_at IS NULL
            WHERE c.deleted_at IS NULL
            GROUP BY c.id
            ORDER BY c.name ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get files by category ID
     */
    public function getFilesByCategoryId($category_id) {
        $stmt = $this->db->prepare("
            SELECT f.*, c.name as category_name 
            FROM {$this->table} f
            LEFT JOIN file_categories c ON f.category_id = c.id
            WHERE f.category_id = ? AND f.is_active = 1 AND f.deleted_at IS NULL
            ORDER BY f.created_at DESC
        ");
        $stmt->execute([$category_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
