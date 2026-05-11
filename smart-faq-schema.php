<?php
/**
 * Plugin Name: Smart FAQ Schema
 * Plugin URI:  https://wordpress.org/plugins/smart-faq-schema/
 * Description: Add beautiful FAQ sections with FAQPage schema to any post, page, or product. Elementor-safe, mobile-optimized, with 5 accordion UI styles.
 * Version:     1.1.0
 * Author:      Your Name
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: smart-faq-schema
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Constants
define( 'SFAQ_VERSION',     '1.1.0' );
define( 'SFAQ_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'SFAQ_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'SFAQ_PLUGIN_FILE', __FILE__ );

// Load all includes
require_once SFAQ_PLUGIN_DIR . 'includes/class-sfaq-activator.php';
require_once SFAQ_PLUGIN_DIR . 'includes/class-sfaq-settings.php';
require_once SFAQ_PLUGIN_DIR . 'includes/class-sfaq-meta-box.php';
require_once SFAQ_PLUGIN_DIR . 'includes/class-sfaq-frontend.php';
require_once SFAQ_PLUGIN_DIR . 'includes/class-sfaq-schema.php';
require_once SFAQ_PLUGIN_DIR . 'includes/class-sfaq-shortcode.php';
require_once SFAQ_PLUGIN_DIR . 'includes/class-sfaq-bulk-manager.php';
require_once SFAQ_PLUGIN_DIR . 'includes/class-sfaq-admin-menu.php';
require_once SFAQ_PLUGIN_DIR . 'includes/class-sfaq-cache.php';

// Activation / Deactivation
register_activation_hook( __FILE__,  array( 'SFAQ_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'SFAQ_Activator', 'deactivate' ) );

// Boot the plugin
function sfaq_init() {
	SFAQ_Settings::init();
	SFAQ_Meta_Box::init();
	SFAQ_Frontend::init();
	SFAQ_Schema::init();
	SFAQ_Shortcode::init();
	SFAQ_Bulk_Manager::init();
	SFAQ_Admin_Menu::init();
}
add_action( 'plugins_loaded', 'sfaq_init' );
