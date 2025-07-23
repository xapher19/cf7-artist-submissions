<?php
/**
 * CF7 Artist Submissions - Metadata Manager
 *
 * Handles file metadata storage and retrieval in the database for the
 * CF7 Artist Submissions plugin with S3 integration.
 *
 * @package CF7_Artist_Submissions
 * @subpackage MetadataManager
 * @since 1.1.0
 */

/**
 * Metadata Manager Class
 * 
 * Manages file metadata in the database including S3 keys, thumbnails,
 * and file information for the artist submission system.
 * 
 * @since 1.1.0
 */
class CF7_Artist_Submissions_Metadata_Manager {
    
    /**
     * Initialize the metadata manager
     */
    public static function init() {
        // Hook into WordPress init if needed
        add_action('init', array(__CLASS__, 'setup_hooks'));
    }
    
    /**
     * Setup WordPress hooks
     */
    public static function setup_hooks() {
        // Add any hooks here if needed
    }
    
    private $table_name;
    
    /**
     * Initialize metadata manager
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'cf7as_files';
    }
    
    /**
     * Create the files table
     */
    public static function create_files_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cf7as_files';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            submission_id varchar(255) NOT NULL,
            original_name text NOT NULL,
            s3_key text NOT NULL,
            mime_type varchar(255) NOT NULL,
            file_size int(11) NOT NULL,
            thumbnail_url text,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY submission_id (submission_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Store file metadata
     * 
     * @param array $file_data File metadata array
     * @return int|false File ID or false on failure
     */
    public function store_file_metadata($file_data) {
        global $wpdb;
        
        $required_fields = array('submission_id', 'original_name', 's3_key', 'mime_type', 'file_size');
        foreach ($required_fields as $field) {
            if (!isset($file_data[$field])) {
                return false;
            }
        }
        
        $insert_data = array(
            'submission_id' => sanitize_text_field($file_data['submission_id']),
            'original_name' => sanitize_text_field($file_data['original_name']),
            's3_key' => sanitize_text_field($file_data['s3_key']),
            'mime_type' => sanitize_text_field($file_data['mime_type']),
            'file_size' => intval($file_data['file_size'])
        );
        
        if (isset($file_data['thumbnail_url'])) {
            $insert_data['thumbnail_url'] = esc_url_raw($file_data['thumbnail_url']);
        }
        
        $result = $wpdb->insert(
            $this->table_name,
            $insert_data,
            array(
                '%s', // submission_id
                '%s', // original_name
                '%s', // s3_key
                '%s', // mime_type
                '%d', // file_size
                '%s'  // thumbnail_url
            )
        );
        
        if ($result === false) {
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get file metadata by ID
     * 
     * @param int $file_id File ID
     * @return array|null File metadata or null if not found
     */
    public function get_file_metadata($file_id) {
        global $wpdb;
        
        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE id = %d",
                $file_id
            ),
            ARRAY_A
        );
        
        return $result;
    }
    
    /**
     * Get all files for a submission
     * 
     * @param string $submission_id Submission ID
     * @return array Array of file metadata
     */
    public function get_submission_files($submission_id) {
        global $wpdb;
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE submission_id = %s ORDER BY created_at ASC",
                $submission_id
            ),
            ARRAY_A
        );
        
        return $results ? $results : array();
    }
    
    /**
     * Update file metadata
     * 
     * @param int $file_id File ID
     * @param array $update_data Data to update
     * @return bool Success status
     */
    public function update_file_metadata($file_id, $update_data) {
        global $wpdb;
        
        $allowed_fields = array('thumbnail_url', 'original_name');
        $sanitized_data = array();
        $format = array();
        
        foreach ($update_data as $field => $value) {
            if (in_array($field, $allowed_fields)) {
                if ($field === 'thumbnail_url') {
                    $sanitized_data[$field] = esc_url_raw($value);
                } else {
                    $sanitized_data[$field] = sanitize_text_field($value);
                }
                $format[] = '%s';
            }
        }
        
        if (empty($sanitized_data)) {
            return false;
        }
        
        $result = $wpdb->update(
            $this->table_name,
            $sanitized_data,
            array('id' => $file_id),
            $format,
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Delete file metadata
     * 
     * @param int $file_id File ID
     * @return bool Success status
     */
    public function delete_file_metadata($file_id) {
        global $wpdb;
        
        $result = $wpdb->delete(
            $this->table_name,
            array('id' => $file_id),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Delete all files for a submission
     * 
     * @param string $submission_id Submission ID
     * @return bool Success status
     */
    public function delete_submission_files($submission_id) {
        global $wpdb;
        
        $result = $wpdb->delete(
            $this->table_name,
            array('submission_id' => $submission_id),
            array('%s')
        );
        
        return $result !== false;
    }
    
    /**
     * Get files by MIME type
     * 
     * @param string $submission_id Submission ID
     * @param string $mime_type_prefix MIME type prefix (e.g., 'image/', 'video/')
     * @return array Array of file metadata
     */
    public function get_files_by_type($submission_id, $mime_type_prefix) {
        global $wpdb;
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE submission_id = %s AND mime_type LIKE %s ORDER BY created_at ASC",
                $submission_id,
                $mime_type_prefix . '%'
            ),
            ARRAY_A
        );
        
        return $results ? $results : array();
    }
    
    /**
     * Get total file size for a submission
     * 
     * @param string $submission_id Submission ID
     * @return int Total file size in bytes
     */
    public function get_submission_total_size($submission_id) {
        global $wpdb;
        
        $result = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(file_size) FROM {$this->table_name} WHERE submission_id = %s",
                $submission_id
            )
        );
        
        return $result ? intval($result) : 0;
    }
    
    /**
     * Get file count for a submission
     * 
     * @param string $submission_id Submission ID
     * @return int File count
     */
    public function get_submission_file_count($submission_id) {
        global $wpdb;
        
        $result = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE submission_id = %s",
                $submission_id
            )
        );
        
        return $result ? intval($result) : 0;
    }
    
    /**
     * Update submission ID for temporary files
     * 
     * @param string $temp_submission_id Temporary submission ID
     * @param string $final_submission_id Final submission ID
     * @return bool Success status
     */
    public function update_submission_id($temp_submission_id, $final_submission_id) {
        global $wpdb;
        
        $result = $wpdb->update(
            $this->table_name,
            array('submission_id' => $final_submission_id),
            array('submission_id' => $temp_submission_id),
            array('%s'),
            array('%s')
        );
        
        return $result !== false;
    }
    
    /**
     * Check if table exists
     * 
     * @return bool True if table exists
     */
    public function table_exists() {
        global $wpdb;
        
        $result = $wpdb->get_var(
            $wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $this->table_name
            )
        );
        
        return $result === $this->table_name;
    }
}
