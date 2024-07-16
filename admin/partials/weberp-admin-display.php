<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://www.interactive-design.gr
 * @since      1.0.0
 *
 * @package    Weberp
 * @subpackage Weberp/admin/partials
 */
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="wrap">
    <h2>WebERP Settings</h2>
    <form method="post" action="options.php">
        <?php
            settings_fields( 'weberp_fields' );
            do_settings_sections( 'weberp_fields' );
            submit_button();
        ?>
    </form>
</div>