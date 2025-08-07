<?php
/**
 * Email Template: Curator Login Link
 *
 * This template is used for sending secure login links to guest curators.
 * 
 * Variables available:
 * - $curator_name: Name of the curator
 * - $login_link: The secure login URL
 * - $site_name: WordPress site name
 * - $expires_text: Expiration time text
 *
 * @package CF7_Artist_Submissions
 * @since 1.3.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?><!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php printf(__('Curator Access - %s', 'cf7-artist-submissions'), $site_name); ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .email-container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e1e4e8;
        }
        .header h1 {
            color: #2c3e50;
            font-size: 24px;
            margin: 0;
        }
        .content {
            margin-bottom: 30px;
        }
        .content p {
            margin-bottom: 15px;
        }
        /* Enhanced button styles for better email client compatibility */
        .login-button {
            text-align: center;
            margin: 30px 0;
        }
        .login-button a {
            display: inline-block !important;
            padding: 15px 30px !important;
            background-color: #007cba !important;
            color: #ffffff !important;
            text-decoration: none !important;
            border-radius: 6px !important;
            font-weight: 500 !important;
            font-size: 16px !important;
            border: 2px solid #007cba !important;
            mso-padding-alt: 15px 30px; /* Outlook fix */
            font-family: Arial, sans-serif !important;
        }
        .login-button a:hover,
        .login-button a:visited,
        .login-button a:active {
            background-color: #005a87 !important;
            color: #ffffff !important;
            text-decoration: none !important;
        }
        /* Fallback for clients that don't support the button */
        .fallback-link {
            text-align: center;
            margin: 15px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 4px;
            border: 1px solid #e1e4e8;
        }
        .fallback-link p {
            margin: 5px 0 !important;
            font-size: 14px;
        }
        .fallback-link a {
            color: #007cba !important;
            text-decoration: underline !important;
            word-break: break-all;
            font-weight: normal !important;
        }
        .security-note {
            background: #f8f9fa;
            border-left: 4px solid #007cba;
            padding: 15px;
            margin: 20px 0;
            border-radius: 0 4px 4px 0;
        }
        .security-note p {
            margin: 0;
            font-size: 14px;
            color: #555;
        }
        .footer {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #e1e4e8;
            color: #666;
            font-size: 14px;
        }
        .footer a {
            color: #007cba;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1><?php echo esc_html($site_name); ?></h1>
        </div>
        
        <div class="content">
            <p><?php printf(__('Hello %s,', 'cf7-artist-submissions'), esc_html($curator_name)); ?></p>
            
            <p><?php _e('You have been invited to review artist submissions as a guest curator. Click the secure link below to access your curator portal:', 'cf7-artist-submissions'); ?></p>
            
            <div class="login-button">
                <!--[if mso]>
                <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="<?php echo esc_url($login_link); ?>" style="height:50px;v-text-anchor:middle;width:250px;" arcsize="12%" strokecolor="#007cba" fillcolor="#007cba">
                <w:anchorlock/>
                <center style="color:#ffffff;font-family:Arial, sans-serif;font-size:16px;font-weight:bold;">Access Curator Portal</center>
                </v:roundrect>
                <![endif]-->
                <!--[if !mso]><!-->
                <a href="<?php echo esc_url($login_link); ?>" style="display: inline-block !important; padding: 15px 30px !important; background-color: #007cba !important; color: #ffffff !important; text-decoration: none !important; border-radius: 6px !important; font-weight: 500 !important; font-size: 16px !important; border: 2px solid #007cba !important; font-family: Arial, sans-serif !important;"><?php _e('Access Curator Portal', 'cf7-artist-submissions'); ?></a>
                <!--<![endif]-->
            </div>
            
            <div class="fallback-link">
                <p><strong><?php _e('If the button above doesn\'t work, copy this link:', 'cf7-artist-submissions'); ?></strong></p>
                <p style="font-size: 12px; color: #007cba; background: #fff; padding: 10px; border: 1px solid #007cba; border-radius: 4px; word-break: break-all; font-family: monospace;"><a href="<?php echo esc_url($login_link); ?>" style="color: #007cba !important; text-decoration: none !important;"><?php echo esc_html($login_link); ?></a></p>
                <p style="font-size: 11px; color: #666;"><em><?php _e('Copy the entire link above and paste it into your browser address bar.', 'cf7-artist-submissions'); ?></em></p>
            </div>
            
            <div class="security-note">
                <p><strong><?php _e('Security Information:', 'cf7-artist-submissions'); ?></strong></p>
                <p><?php echo esc_html($expires_text); ?></p>
                <p><?php _e('This link is unique to you and should not be shared with others.', 'cf7-artist-submissions'); ?></p>
            </div>
            
            <p><?php _e('Once you access the portal, you will be able to:', 'cf7-artist-submissions'); ?></p>
            <ul>
                <li><?php _e('View assigned submissions', 'cf7-artist-submissions'); ?></li>
                <li><?php _e('Rate artwork using our 5-star system', 'cf7-artist-submissions'); ?></li>
                <li><?php _e('Add notes and comments', 'cf7-artist-submissions'); ?></li>
                <li><?php _e('Review high-resolution images and files', 'cf7-artist-submissions'); ?></li>
            </ul>
            
            <p><?php _e('If you have any questions or need assistance, please don\'t hesitate to contact us.', 'cf7-artist-submissions'); ?></p>
            
            <p><?php _e('Thank you for your participation as a guest curator.', 'cf7-artist-submissions'); ?></p>
        </div>
        
        <div class="footer">
            <p><?php printf(__('© %s %s', 'cf7-artist-submissions'), date('Y'), esc_html($site_name)); ?></p>
            <p><a href="<?php echo esc_url(home_url()); ?>"><?php echo esc_url(home_url()); ?></a></p>
        </div>
    </div>
</body>
</html>
