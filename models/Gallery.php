<?php
/**
 * Gallery Model
 */

class Gallery extends Model {
    protected $table = 'gallery_albums';
    
    /**
     * Get all albums with photo count
     */
    public function getAllAlbums($limit = null) {
        $sql = "SELECT a.*, 
                (SELECT COUNT(*) FROM gallery_photos WHERE album_id = a.id AND deleted_at IS NULL) as photo_count,
                (SELECT image_path FROM gallery_photos WHERE album_id = a.id AND deleted_at IS NULL ORDER BY display_order ASC LIMIT 1) as cover_image
                FROM {$this->table} a
                WHERE a.deleted_at IS NULL
                ORDER BY a.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get album by slug
     */
    public function getAlbumBySlug($slug) {
        $stmt = $this->db->prepare("
            SELECT a.*, 
                   (SELECT COUNT(*) FROM gallery_photos WHERE album_id = a.id AND deleted_at IS NULL) as photo_count
            FROM {$this->table} a
            WHERE a.slug = ? AND a.deleted_at IS NULL
        ");
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get album by ID
     */
    public function getAlbumById($id) {
        $stmt = $this->db->prepare("
            SELECT a.*, 
                   (SELECT COUNT(*) FROM gallery_photos WHERE album_id = a.id AND deleted_at IS NULL) as photo_count
            FROM {$this->table} a
            WHERE a.id = ? AND a.deleted_at IS NULL
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get photos by album ID
     */
    public function getPhotosByAlbumId($album_id) {
        $stmt = $this->db->prepare("
            SELECT * FROM gallery_photos 
            WHERE album_id = ? AND deleted_at IS NULL 
            ORDER BY display_order ASC, created_at DESC
        ");
        $stmt->execute([$album_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get photo by ID
     */
    public function getPhotoById($id) {
        $stmt = $this->db->prepare("
            SELECT * FROM gallery_photos 
            WHERE id = ? AND deleted_at IS NULL
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
