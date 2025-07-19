<?php
// Basic WordPress load
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

// Security check
if (!current_user_can('manage_options')) {
    die('Access denied');
}

// Check for form submission
if (isset($_POST['debug_test_submit'])) {
    // Simulate a basic form submission to see if it works
    $result = WPCF7_Submission::get_instance();
    echo "<pre>";
    var_dump($result);
    echo "</pre>";
    die('Test submission complete');
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>CF7 Debug Tool</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; }
        .button { background: #0073aa; color: #fff; padding: 10px 15px; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <h1>CF7 Debug Tool</h1>
    
    <h2>CF7 Plugin Status</h2>
    <pre>
    <?php 
        echo "Contact Form 7 Plugin Active: " . (class_exists('WPCF7') ? 'Yes' : 'No') . "\n";
        echo "Drag and Drop Plugin Active: " . (class_exists('Codedropz_Upload') ? 'Yes' : 'No') . "\n";
        
        // List active plugins
        echo "\nActive Plugins:\n";
        $active_plugins = get_option('active_plugins');
        foreach ($active_plugins as $plugin) {
            echo "- " . $plugin . "\n";
        }
    ?>
    </pre>
    
    <h2>Test Form Submission</h2>
    <form method="post">
        <p>Click the button to test a basic form submission:</p>
        <button type="submit" name="debug_test_submit" class="button">Test Submission</button>
    </form>
    
    <h2>Fix Form Submissions</h2>
    <p>If your forms are not submitting, you can try the following fixes:</p>
    
    <h3>Option 1: Disable Custom Form Handler</h3>
    <p>This will disable the custom form handler temporarily to see if it's causing the issue:</p>
    <form method="post">
        <?php
        if (isset($_POST['disable_handler'])) {
            // Create a temporary file that removes the handler
            $file_content = "<?php
            // Temporary fix to disable custom form handler
            add_action('plugins_loaded', function() {
                if (class_exists('CF7_Artist_Submissions_Form_Handler')) {
                    remove_action('wpcf7_before_send_mail', array(CF7_Artist_Submissions_Form_Handler, 'capture_submission'));
                    remove_filter('wpcf7_posted_data', array(CF7_Artist_Submissions_Form_Handler, 'process_drag_drop_files'));
                }
            }, 1);";
            
            $upload_dir = wp_upload_dir();
            $fix_file = $upload_dir['basedir'] . '/cf7-fix.php';
            file_put_contents($fix_file, $file_content);
            
            // Add to mu-plugins if it exists
            if (is_dir(WPMU_PLUGIN_DIR)) {
                copy($fix_file, WPMU_PLUGIN_DIR . '/cf7-fix.php');
                echo "<div style='background:green; color:white; padding:10px;'>Fix installed in mu-plugins directory!</div>";
            } else {
                echo "<div style='background:orange; color:white; padding:10px;'>Created fix file at: " . $fix_file . " - Please copy this to wp-content/mu-plugins/ (create directory if needed)</div>";
            }
        }
        ?>
        <button type="submit" name="disable_handler" class="button">Disable Custom Handler</button>
    </form>
</body>
</html>