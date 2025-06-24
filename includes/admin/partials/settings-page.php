<?php
/**
 * Settings Page Template
 */
if(!defined('WPINC'))die;
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <form action="options.php" method="post">
        <?php 
        settings_fields('sspu_settings_group');
        do_settings_sections('sspu-settings');
        submit_button('Save Settings');
        ?>
    </form>
</div>