<?php
/**
 * CF7 Artist Submissions - Debug Existing Files Processing
 * 
 * This file helps debug why existing files aren't being converted.
 * Place this file in your WordPress root directory and visit it in your browser
 * while logged in as an admin to see detailed debugging information.
 * 
 * IMPORTANT: Remove this file after debugging to avoid security issues.
 */

// Load WordPress
require_once('wp-config.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('Access denied. You must be logged in as an administrator.');
}

echo "<h1>CF7 Artist Submissions - Debug Existing Files</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.debug-section { margin: 20px 0; padding: 15px; border: 1px solid #ccc; background: #f9f9f9; }
.success { color: green; }
.error { color: red; }
.warning { color: orange; }
.info { color: blue; }
pre { background: #f0f0f0; padding: 10px; overflow-x: auto; }
</style>";

global $wpdb;

// 1. Check if plugin is active
echo "<div class='debug-section'>";
echo "<h2>1. Plugin Status</h2>";
if (class_exists('CF7_Artist_Submissions_Media_Converter')) {
    echo "<p class='success'>✅ CF7_Artist_Submissions_Media_Converter class exists</p>";
} else {
    echo "<p class='error'>❌ CF7_Artist_Submissions_Media_Converter class NOT found</p>";
    echo "<p>Make sure the plugin is activated.</p>";
}
echo "</div>";

// 2. Check database tables
echo "<div class='debug-section'>";
echo "<h2>2. Database Tables</h2>";

$files_table = $wpdb->prefix . 'cf7as_files';
$jobs_table = $wpdb->prefix . 'cf7as_conversion_jobs';

$files_exists = $wpdb->get_var("SHOW TABLES LIKE '$files_table'");
$jobs_exists = $wpdb->get_var("SHOW TABLES LIKE '$jobs_table'");

if ($files_exists) {
    echo "<p class='success'>✅ Files table exists: $files_table</p>";
    $file_count = $wpdb->get_var("SELECT COUNT(*) FROM $files_table");
    echo "<p class='info'>📊 Total files in table: $file_count</p>";
} else {
    echo "<p class='error'>❌ Files table missing: $files_table</p>";
}

if ($jobs_exists) {
    echo "<p class='success'>✅ Jobs table exists: $jobs_table</p>";
    $job_count = $wpdb->get_var("SELECT COUNT(*) FROM $jobs_table");
    echo "<p class='info'>📊 Total jobs in table: $job_count</p>";
} else {
    echo "<p class='error'>❌ Jobs table missing: $jobs_table</p>";
}
echo "</div>";

// 3. Check AWS settings
echo "<div class='debug-section'>";
echo "<h2>3. AWS Configuration</h2>";
$options = get_option('cf7_artist_submissions_options', array());

$settings_to_check = array(
    'enable_media_conversion' => 'Media Conversion Enabled',
    'aws_access_key' => 'AWS Access Key',
    'aws_secret_key' => 'AWS Secret Key',
    'aws_region' => 'AWS Region',
    's3_bucket' => 'S3 Bucket',
    'lambda_function_name' => 'Lambda Function Name'
);

foreach ($settings_to_check as $key => $label) {
    if (isset($options[$key]) && !empty($options[$key])) {
        if ($key === 'aws_secret_key') {
            echo "<p class='success'>✅ $label: SET (" . strlen($options[$key]) . " characters)</p>";
        } else {
            echo "<p class='success'>✅ $label: " . htmlspecialchars($options[$key]) . "</p>";
        }
    } else {
        echo "<p class='error'>❌ $label: NOT SET</p>";
    }
}
echo "</div>";

// 4. Check files that need processing
if ($files_exists) {
    echo "<div class='debug-section'>";
    echo "<h2>4. Files Analysis</h2>";
    
    // Get files that need processing
    $query = "
        SELECT id, original_name, mime_type, has_converted_versions, created_at
        FROM $files_table 
        WHERE (has_converted_versions IS NULL OR has_converted_versions = 0)
        AND (mime_type LIKE 'image/%' OR mime_type LIKE 'video/%' OR mime_type LIKE 'audio/%')
        ORDER BY created_at DESC 
        LIMIT 10
    ";
    
    echo "<h3>Files that need processing (first 10):</h3>";
    $files_to_process = $wpdb->get_results($query);
    
    if (empty($files_to_process)) {
        echo "<p class='warning'>⚠️ No files found that need processing</p>";
        
        // Show what files we do have
        $all_files = $wpdb->get_results("
            SELECT id, original_name, mime_type, has_converted_versions, created_at
            FROM $files_table 
            ORDER BY created_at DESC 
            LIMIT 5
        ");
        
        if (!empty($all_files)) {
            echo "<h3>Recent files in database (any status):</h3>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>Filename</th><th>MIME Type</th><th>Converted</th><th>Created</th></tr>";
            foreach ($all_files as $file) {
                $converted_status = $file->has_converted_versions === null ? 'NULL' : ($file->has_converted_versions ? 'YES' : 'NO');
                echo "<tr>";
                echo "<td>{$file->id}</td>";
                echo "<td>" . htmlspecialchars($file->original_name) . "</td>";
                echo "<td>{$file->mime_type}</td>";
                echo "<td>{$converted_status}</td>";
                echo "<td>{$file->created_at}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='error'>❌ No files found in database at all</p>";
        }
    } else {
        echo "<p class='info'>📋 Found " . count($files_to_process) . " files that need processing</p>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Filename</th><th>MIME Type</th><th>Converted</th><th>Created</th></tr>";
        foreach ($files_to_process as $file) {
            $converted_status = $file->has_converted_versions === null ? 'NULL' : ($file->has_converted_versions ? 'YES' : 'NO');
            echo "<tr>";
            echo "<td>{$file->id}</td>";
            echo "<td>" . htmlspecialchars($file->original_name) . "</td>";
            echo "<td>{$file->mime_type}</td>";
            echo "<td>{$converted_status}</td>";
            echo "<td>{$file->created_at}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";
}

// 5. Test conversion method manually
if (class_exists('CF7_Artist_Submissions_Media_Converter') && $files_exists) {
    echo "<div class='debug-section'>";
    echo "<h2>5. Manual Test</h2>";
    
    if (isset($_GET['test_conversion']) && $_GET['test_conversion'] === '1') {
        echo "<h3>Running conversion test...</h3>";
        echo "<pre>";
        
        // Enable error reporting for this test
        $old_error_reporting = error_reporting(E_ALL);
        $old_display_errors = ini_get('display_errors');
        ini_set('display_errors', 1);
        
        try {
            $converter = new CF7_Artist_Submissions_Media_Converter();
            $results = $converter->process_existing_files(3); // Process 3 files max
            
            echo "Conversion test results:\n";
            print_r($results);
            
        } catch (Exception $e) {
            echo "Error during conversion test: " . $e->getMessage() . "\n";
            echo "Stack trace: " . $e->getTraceAsString() . "\n";
        }
        
        // Restore error reporting
        error_reporting($old_error_reporting);
        ini_set('display_errors', $old_display_errors);
        
        echo "</pre>";
    } else {
        echo "<p><a href='?test_conversion=1' style='background: #0073aa; color: white; padding: 10px 15px; text-decoration: none; border-radius: 3px;'>🧪 Run Conversion Test</a></p>";
        echo "<p class='warning'>⚠️ This will attempt to process up to 3 existing files. Check your error logs after running.</p>";
    }
    echo "</div>";
}

// 6. Recent error logs (if accessible)
echo "<div class='debug-section'>";
echo "<h2>6. WordPress Error Logs</h2>";
$error_log_path = ini_get('error_log');
if ($error_log_path && file_exists($error_log_path) && is_readable($error_log_path)) {
    echo "<p class='info'>📝 Error log path: $error_log_path</p>";
    echo "<h3>Recent CF7AS errors (last 50 lines):</h3>";
    $log_content = file_get_contents($error_log_path);
    $lines = explode("\n", $log_content);
    $cf7as_lines = array_filter($lines, function($line) {
        return strpos($line, 'CF7AS') !== false;
    });
    $recent_lines = array_slice($cf7as_lines, -50);
    
    if (!empty($recent_lines)) {
        echo "<pre style='max-height: 400px; overflow-y: auto;'>";
        foreach ($recent_lines as $line) {
            echo htmlspecialchars($line) . "\n";
        }
        echo "</pre>";
    } else {
        echo "<p class='warning'>⚠️ No CF7AS-related errors found in recent logs</p>";
    }
} else {
    echo "<p class='warning'>⚠️ Error log not accessible or not configured</p>";
    echo "<p>Consider adding this to wp-config.php:</p>";
    echo "<pre>define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);</pre>";
}
echo "</div>";

echo "<div style='margin-top: 30px; padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7;'>";
echo "<h3>🔍 Debugging Tips:</h3>";
echo "<ul>";
echo "<li>Check that 'Enable Media Conversion' is turned ON in the AWS settings</li>";
echo "<li>Verify all AWS credentials are properly configured</li>";
echo "<li>Make sure you have image files uploaded that haven't been converted yet</li>";
echo "<li>Check WordPress error logs for detailed conversion process information</li>";  
echo "<li>Try the manual conversion test above to see real-time errors</li>";
echo "<li><strong>Remember to delete this debug file when done!</strong></li>";
echo "</ul>";
echo "</div>";
?>
