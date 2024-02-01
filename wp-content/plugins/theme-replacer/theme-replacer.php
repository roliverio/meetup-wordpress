<?php
/*
Plugin Name: Theme Replacer
Description: Plugin to replace the theme with a zip file from a URL.
Version: 1.0
Author: Miguel Useche
*/

// Create an admin menu item
function ctr_create_admin_menu() {
    add_menu_page(
        'Theme Updater',
        'Theme Updater',
        'manage_options',
        'theme-replacer',
        'ctr_render_admin_page'
    );
}
add_action('admin_menu', 'ctr_create_admin_menu');

// Render the admin page
function ctr_render_admin_page() {
    ?>
    <div class="wrap">
        <h1>Theme Replacer</h1>
        <p>Click the button below to download and update the theme with the content of the remote zip file.</p>
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <input type="hidden" name="action" value="ctr_replace_theme">
            <?php wp_nonce_field('ctr_replace_theme', 'ctr_replace_theme_nonce'); ?>
            <?php submit_button('Update Theme'); ?>
        </form>
    </div>
    <?php
}

// Handle the form submission
function ctr_replace_theme() {
    if (!current_user_can('manage_options')) {
        wp_die('You are not allowed to perform this action.');
    }

    // Verify the nonce
    if (!isset($_POST['ctr_replace_theme_nonce']) || !wp_verify_nonce($_POST['ctr_replace_theme_nonce'], 'ctr_replace_theme')) {
        wp_die('Invalid nonce.');
    }

    // Download the zip file
    $theme_folder_name = get_template();
    $zip_url = sprintf('https://example.com/%s.zip', $theme_folder_name);
    $zip_contents = file_get_contents($zip_url);

    // Extract the contents to the themes directory
    $theme_dir = get_theme_root() . '/' . $theme_folder_name;
    $zip = new ZipArchive;
    if ($zip->open($theme_dir . '/' . $theme_folder_name) === true) {
        $zip->extractTo($theme_dir);
        $zip->close();

        // Activate the newly replaced theme
        switch_theme($theme_folder_name);
        wp_redirect(admin_url('themes.php'));
        exit;
    } else {
        wp_die('Failed to extract the zip file.');
    }
}
add_action('admin_post_ctr_replace_theme', 'ctr_replace_theme');

