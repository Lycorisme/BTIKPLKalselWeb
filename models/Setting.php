<?php
/**
 * Setting Model
 */

class Setting extends Model {
    protected $table = 'settings';
    
    /**
     * Get all settings as key-value pairs
     */
    public function getAllSettings() {
        $stmt = $this->db->query("SELECT `key`, `value` FROM {$this->table}");
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    
    /**
     * Get setting by key
     */
    public function getSetting($key, $default = '') {
        $stmt = $this->db->prepare("SELECT `value` FROM {$this->table} WHERE `key` = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['value'] : $default;
    }
    
    /**
     * Update or create setting
     */
    public function setSetting($key, $value) {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (`key`, `value`) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE `value` = ?
        ");
        return $stmt->execute([$key, $value, $value]);
    }
    
    /**
     * Get settings by group
     */
    public function getSettingsByGroup($group) {
        $stmt = $this->db->prepare("
            SELECT `key`, `value` 
            FROM {$this->table} 
            WHERE `group` = ?
        ");
        $stmt->execute([$group]);
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
}
