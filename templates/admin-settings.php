<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <form action="options.php" method="post">
        <?php
        settings_fields('cf7_artist_submissions_options');
        do_settings_sections('cf7-artist-submissions');
        submit_button();
        ?>
    </form>
</div>