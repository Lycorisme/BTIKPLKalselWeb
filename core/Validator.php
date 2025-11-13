<?php
/**
 * Validator Class (Merged A + B)
 * Gabungan fitur Validator A dan B
 * - Mendukung method chaining
 * - Mendukung validasi database (unique)
 */

class Validator {
    private $data;
    private $errors = [];

    public function __construct($data = []) {
        $this->data = $data;
    }

    /** Required */
    public function required($field, $label = null) {
        $label = $label ?: ucfirst($field);
        if (!isset($this->data[$field]) || empty(trim($this->data[$field]))) {
            $this->errors[$field] = "{$label} wajib diisi";
        }
        return $this;
    }

    /** Email */
    public function email($field, $label = null) {
        $label = $label ?: ucfirst($field);
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (!filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
                $this->errors[$field] = "{$label} tidak valid";
            }
        }
        return $this;
    }

    /** URL */
    public function url($field, $label = null) {
        $label = $label ?: ucfirst($field);
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (!filter_var($this->data[$field], FILTER_VALIDATE_URL)) {
                $this->errors[$field] = "{$label} tidak valid";
            }
        }
        return $this;
    }

    /** Min length (original method) */
    public function minLength($field, $min, $label = null) {
        $label = $label ?: ucfirst($field);
        if (isset($this->data[$field]) && strlen($this->data[$field]) < $min) {
            $this->errors[$field] = "{$label} minimal {$min} karakter";
        }
        return $this;
    }

    /** Min (alias untuk minLength - untuk kompatibilitas) */
    public function min($field, $min, $label = null) {
        return $this->minLength($field, $min, $label);
    }

    /** Max length (original method) */
    public function maxLength($field, $max, $label = null) {
        $label = $label ?: ucfirst($field);
        if (isset($this->data[$field]) && strlen($this->data[$field]) > $max) {
            $this->errors[$field] = "{$label} maksimal {$max} karakter";
        }
        return $this;
    }

    /** Max (alias untuk maxLength - untuk kompatibilitas) */
    public function max($field, $max, $label = null) {
        return $this->maxLength($field, $max, $label);
    }

    /** Numeric */
    public function numeric($field, $label = null) {
        $label = $label ?: ucfirst($field);
        if (isset($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->errors[$field] = "{$label} harus berupa angka";
        }
        return $this;
    }

    /** Match (password confirmation) */
    public function match($field, $matchField, $label = null) {
        $label = $label ?: ucfirst($field);
        if (($this->data[$field] ?? '') !== ($this->data[$matchField] ?? '')) {
            $this->errors[$field] = "{$label} tidak cocok";
        }
        return $this;
    }

    /** In (enum) */
    public function in($field, $values, $label = null) {
        $label = $label ?: ucfirst($field);
        if (!in_array($this->data[$field] ?? '', $values)) {
            $this->errors[$field] = "{$label} tidak valid";
        }
        return $this;
    }

    /** Unique (check database) */
    public function unique($field, $table, $excludeId = null, $label = null) {
        $label = $label ?: ucfirst($field);
        $value = $this->data[$field] ?? '';

        try {
            $db = Database::getInstance()->getConnection();
            $sql = "SELECT COUNT(*) FROM {$table} WHERE {$field} = ?";
            $params = [$value];

            if ($excludeId) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                $this->errors[$field] = "{$label} sudah digunakan";
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
        }

        return $this;
    }

    /** Add custom error */
    public function addError($field, $message) {
        $this->errors[$field] = $message;
        return $this;
    }

    /** Pass/fail check */
    public function passes() {
        return empty($this->errors);
    }

    public function fails() {
        return !$this->passes();
    }

    /** Get specific error */
    public function getError($field) {
        return $this->errors[$field] ?? null;
    }

    /** Get all errors */
    public function getErrors() {
        return $this->errors;
    }

    /** Get first error */
    public function getFirstError() {
        return !empty($this->errors) ? reset($this->errors) : null;
    }

    /** Check if any errors exist */
    public function hasErrors() {
        return !empty($this->errors);
    }
}
