<?php
/**
 * Simple test script to verify audit log functionality
 * This file can be run from the WordPress admin to test audit logging
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

echo "<h2>CF7 Artist Submissions Audit Log Test</h2>";

// Test 1: Create audit log table
echo "<h3>Test 1: Creating/Updating Audit Log Table</h3>";
if (class_exists('CF7_Artist_Submissions_Action_Log')) {
    $table_created = CF7_Artist_Submissions_Action_Log::create_log_table();
    $table_updated = CF7_Artist_Submissions_Action_Log::update_table_schema();
    
    echo "<p>✅ Table creation: " . ($table_created ? 'Success' : 'Failed') . "</p>";
    echo "<p>✅ Schema update: " . ($table_updated ? 'Success' : 'Failed') . "</p>";
    
    // Test 2: Test logging functionality
    echo "<h3>Test 2: Testing Logging Functions</h3>";
    
    $test_result = CF7_Artist_Submissions_Action_Log::test_logging();
    if (is_wp_error($test_result)) {
        echo "<p>❌ Logging test failed: " . $test_result->get_error_message() . "</p>";
    } else {
        echo "<p>✅ Basic logging test: Success</p>";
    }
    
    // Test 3: Test new logging methods
    echo "<h3>Test 3: Testing Enhanced Logging Methods</h3>";
    
    $test_submission_id = 999; // Fake submission ID for testing
    
    // Test status change logging
    $status_log = CF7_Artist_Submissions_Action_Log::log_status_change(
        $test_submission_id, 
        'pending', 
        'approved'
    );
    echo "<p>✅ Status change logging: " . ($status_log ? 'Success' : 'Failed') . "</p>";
    
    // Test action creation logging
    $action_log = CF7_Artist_Submissions_Action_Log::log_action_created(
        123, 
        $test_submission_id, 
        'Test Action', 
        get_current_user_id()
    );
    echo "<p>✅ Action creation logging: " . ($action_log ? 'Success' : 'Failed') . "</p>";
    
    // Test conversation clearing logging
    $conversation_log = CF7_Artist_Submissions_Action_Log::log_conversation_cleared(
        $test_submission_id, 
        5, 
        'Test clearing'
    );
    echo "<p>✅ Conversation clearing logging: " . ($conversation_log ? 'Success' : 'Failed') . "</p>";
    
    // Test settings change logging
    $settings_log = CF7_Artist_Submissions_Action_Log::log_setting_changed(
        'test_setting', 
        'old_value', 
        'new_value', 
        'general'
    );
    echo "<p>✅ Settings change logging: " . ($settings_log ? 'Success' : 'Failed') . "</p>";
    
    // Test 4: Show recent log entries
    echo "<h3>Test 4: Recent Log Entries</h3>";
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'cf7_action_log';
    $recent_logs = $wpdb->get_results(
        "SELECT * FROM $table_name ORDER BY date_created DESC LIMIT 10"
    );
    
    if (!empty($recent_logs)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Submission</th><th>Action Type</th><th>Artist</th><th>User</th><th>Date</th></tr>";
        foreach ($recent_logs as $log) {
            $user = get_userdata($log->user_id);
            echo "<tr>";
            echo "<td>" . esc_html($log->id) . "</td>";
            echo "<td>" . esc_html($log->submission_id) . "</td>";
            echo "<td>" . esc_html($log->action_type) . "</td>";
            echo "<td>" . esc_html($log->artist_name ?: 'N/A') . "</td>";
            echo "<td>" . esc_html($user ? $user->display_name : 'Unknown') . "</td>";
            echo "<td>" . esc_html($log->date_created) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No log entries found.</p>";
    }
    
} else {
    echo "<p>❌ CF7_Artist_Submissions_Action_Log class not found!</p>";
}

echo "<hr><p><em>Test completed at " . date('Y-m-d H:i:s') . "</em></p>";
?>
