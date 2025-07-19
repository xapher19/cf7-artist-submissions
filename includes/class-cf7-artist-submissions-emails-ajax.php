<?php
/**
 * AJAX Handlers for CF7 Artist Submissions Emails
 */
class CF7_Artist_Submissions_Emails_Ajax {
    
    /**
     * Handle AJAX request to preview an email
     */
    public static function preview_email() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cf7_send_email_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Check required data
        if (!isset($_POST['template_id']) || !isset($_POST['submission_id'])) {
            wp_send_json_error(array('message' => 'Missing required data'));
            return;
        }
        
        $template_id = sanitize_text_field($_POST['template_id']);
        $submission_id = intval($_POST['submission_id']);
        
        // Get template
        $templates = get_option('cf7_artist_submissions_email_templates', array());
        if (!isset($templates[$template_id])) {
            wp_send_json_error(array('message' => 'Template not found'));
            return;
        }
        
        $template = $templates[$template_id];
        
        // Get the email processor
        $emails = new CF7_Artist_Submissions_Emails();
        
        // Process merge tags
        $subject = $emails->process_merge_tags($template['subject'], $submission_id);
        $body = $emails->process_merge_tags($template['body'], $submission_id);
        
        // Convert line breaks to HTML in the body
        $body = wpautop($body);
        
        wp_send_json_success(array(
            'subject' => $subject,
            'body' => $body
        ));
    }
    
    /**
     * Handle AJAX request to send an email
     */
    public static function send_manual_email() {
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
        
        // Get the email processor
        $emails = new CF7_Artist_Submissions_Emails();
        
        // Send the email
        $result = $emails->send_email($template_id, $submission_id, $recipient);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        } else {
            wp_send_json_success(array('message' => 'Email sent successfully'));
        }
    }
}