<?php
/**
 * Handle AJAX request to send manual email
 */
public function ajax_send_manual_email() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cf7_send_email_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed'));
        return;
    }
    
    // Check required data
    if (!isset($_POST['template_id']) || !isset($_POST['submission_id']) || !isset($_POST['recipient'])) {
        wp_send_json_error(array('message' => 'Missing required data'));
        return;
    }
    
    $template_id = sanitize_text_field($_POST['template_id']);
    $submission_id = intval($_POST['submission_id']);
    $recipient = sanitize_email($_POST['recipient']);
    
    // Validate email
    if (!is_email($recipient)) {
        wp_send_json_error(array('message' => 'Invalid recipient email address'));
        return;
    }
    
    // Test action logging before sending email
    $test_result = CF7_Artist_Submissions_Action_Log::test_logging();
    if (is_wp_error($test_result)) {
        wp_send_json_error(array('message' => 'Logging system not working: ' . $test_result->get_error_message()));
        return;
    }
    
    // Send the email
    $result = $this->send_email($template_id, $submission_id, $recipient);
    
    if (is_wp_error($result)) {
        wp_send_json_error(array('message' => $result->get_error_message()));
    } else {
        wp_send_json_success(array('message' => 'Email sent successfully'));
    }
}

/**
 * Send an email using a template
 */
public function send_email($template_id, $submission_id, $to_email) {
    // Get template
    $templates = get_option('cf7_artist_submissions_email_templates', array());
    if (!isset($templates[$template_id])) {
        return new WP_Error('invalid_template', 'Email template not found');
    }
    
    $template = $templates[$template_id];
    if (!$template['enabled']) {
        return new WP_Error('template_disabled', 'Email template is disabled');
    }
    
    // Get email settings
    $options = get_option('cf7_artist_submissions_email_options', array());
    $from_name = isset($options['from_name']) ? $options['from_name'] : get_bloginfo('name');
    $from_email = isset($options['from_email']) ? $options['from_email'] : get_option('admin_email');
    
    // Process merge tags
    $subject = $this->process_merge_tags($template['subject'], $submission_id);
    $body = $this->process_merge_tags($template['body'], $submission_id);
    
    // Convert line breaks to HTML in the body
    $body = wpautop($body);
    
    // Check if we should use WooCommerce template
    $use_wc_template = isset($options['use_wc_template']) && $options['use_wc_template'] && class_exists('WooCommerce');
    
    try {
        // Apply WooCommerce template if needed
        if ($use_wc_template) {
            // Make sure WooCommerce is properly initialized
            require_once(WC_ABSPATH . 'includes/class-wc-emails.php');
            require_once(WC_ABSPATH . 'includes/emails/class-wc-email.php');
            
            $wc_emails = new WC_Emails();
            $wc_emails->init();
            
            // Create a generic WC_Email object to access template methods
            $email = new WC_Email();
            
            // Format email with WooCommerce template
            $body = $this->format_woocommerce_email($body, $subject, $email);
        }
        
        // Set up headers
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>'
        );
        
        // Send the email
        $mail_sent = wp_mail($to_email, $subject, $body, $headers);
        
        // Log the email sent
        if ($mail_sent) {
            // Get template name for logging
            $template_name = isset($this->triggers[$template_id]) ? $this->triggers[$template_id]['name'] : $template_id;
            
            // Create log data
            $log_data = array(
                'template_id' => $template_id,
                'template_name' => $template_name,
                'recipient' => $to_email,
                'subject' => $subject
            );
            
            // Log the email action
            $log_result = CF7_Artist_Submissions_Action_Log::log_action(
                $submission_id,
                'email_sent',
                json_encode($log_data)
            );
            
            if ($log_result === false) {
                error_log('CF7 Artist Submissions: Failed to log email sent action');
            }
            
            return true;
        } else {
            return new WP_Error('email_failed', 'Failed to send email');
        }
    } catch (Exception $e) {
        return new WP_Error('email_error', $e->getMessage());
    }
}