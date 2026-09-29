<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.interactive-design.gr
 * @since             1.0.0
 * @package           Weberp
 *
 * @wordpress-plugin
 * Plugin Name:       WebERP
 * Plugin URI:        https://www.interactive-design.gr
 * Description:       Companion plugin to integrate with a WebERP API instance
 * Version:           1.5.0
 * Author:            George Nikolopoulos
 * Author URI:        https://www.interactive-design.gr
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       weberp
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'WEBERP_VERSION', '1.4.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-weberp-activator.php
 */
function activate_weberp()
{
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-weberp-activator.php';
	Weberp_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-weberp-deactivator.php
 */
function deactivate_weberp()
{
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-weberp-deactivator.php';
	Weberp_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_weberp' );
register_deactivation_hook( __FILE__, 'deactivate_weberp' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-weberp.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_weberp()
{
	$plugin = new Weberp();
	$plugin->run();
}

run_weberp();
