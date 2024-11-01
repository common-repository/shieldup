<?php

/*
	Plugin Name: ShieldUp
	Plugin URI: http://wordpress.org/extend/plugins/shieldup
	Description: ShieldUp will help you indentify and combat threats like bad bots, scrapers, hackers which will improve website security and performance by reducing the load on your server resources for a snappy website and great user experience.
	Author: ShieldUp.me
	Version: 1.0.1
  Requires at least: 5.3.2
  Requires PHP: 7.2.1
	Author URI: https://www.shieldup.me/
  License: GPL v2 or later
  License URI: http://www.gnu.org/licenses/gpl-2.0.txt
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

define('SHIELD_UP_ABS_PATH', __DIR__ );
define('SHIELD_UP_PLUGIN_SLUG', plugin_basename( __DIR__ ));
define('SHIELD_UP_PLUGIN_MAIN_FILE', plugin_basename( __FILE__ ));
define('SHIELD_UP_PLUGIN_TYPE', 'Free');
define('SHIELD_UP_PLUGIN_NAME', 'ShieldUp');
define('SHIELD_UP_VERSION', '1.0.1');
define('SHIELD_UP_DB_VERSION', '1.1');


global $wpdb;

include_once(SHIELD_UP_ABS_PATH.'/includes/functions.php');
include_once(SHIELD_UP_ABS_PATH.'/includes/ajax.php');

register_activation_hook( __FILE__, 'shieldup_db_install' );
register_uninstall_hook( __FILE__, 'shieldup_db_uninstall' );

add_filter('plugin_action_links_' . SHIELD_UP_PLUGIN_MAIN_FILE, 'shieldup_title_links');
add_filter('plugin_row_meta', 'shieldup_desc_links', 10, 2);
add_action('init','shieldup_get_stuff');
add_action('plugins_loaded', 'shieldup_db_update');
add_action('init','shieldup_set_crons');

// Enqueue scripts and styles for each page/only our plugin
if (isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] == 'su_dashboard') {
  function shieldup_my_admin_scripts() {
    wp_enqueue_style( 'shieldup_datatables',  plugins_url( 'includes/style/datatables.min.css', __FILE__ ));
    wp_enqueue_style( 'shieldup_daterangepicker',  plugins_url( 'includes/style/daterangepicker.css', __FILE__ ));
    wp_enqueue_style( 'shieldup_style',  plugins_url( 'includes/style/su_style.css', __FILE__ ));
    wp_enqueue_style( 'shieldup_font_awesome',  plugins_url( 'includes/style/fontawsomefree.min.css', __FILE__ ));

    wp_enqueue_script('jquery');
    wp_enqueue_script( 'shieldup-js-bootstrap-bundle', plugins_url( 'includes/js/bootstrap.bundle.min.js', __FILE__ ));
    wp_enqueue_script( 'shieldup-js-datatables', plugins_url( 'includes/js/datatables.min.js', __FILE__ ));
    wp_enqueue_script( 'shieldup-js-moment', plugins_url( 'includes/js/moment.min.js', __FILE__ ));
    wp_enqueue_script( 'shieldup-js-daterangepicker', plugins_url( 'includes/js/daterangepicker.js', __FILE__ ));
    wp_enqueue_script( 'shieldup-js-apexcharts', plugins_url( 'includes/js/apexcharts.min.js', __FILE__ ));
    wp_enqueue_script( 'shieldup-js-shieldup', plugins_url( 'includes/js/shieldup.js', __FILE__ ));

    wp_localize_script('shieldup-js-shieldup', 'ajax_var', array(
         'url' => admin_url('admin-ajax.php'),
         'nonce' => wp_create_nonce('ajax-nonce')
     ));
  }
  add_action( 'admin_enqueue_scripts', 'shieldup_my_admin_scripts' );
}

if ( (isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] == 'su_settings') || (isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] == 'su_how_to') ) {
  function shieldup_my_admin_scripts2() {
    wp_enqueue_style( 'shieldup_datatables',  plugins_url( 'includes/style/datatables.min.css', __FILE__ ));
    wp_enqueue_style( 'shieldup_style',  plugins_url( 'includes/style/su_style.css', __FILE__ ));

    wp_enqueue_script( 'shieldup-js-bootstrap-bundle', plugins_url( 'includes/js/bootstrap.bundle.min.js', __FILE__ ));
    wp_enqueue_script('jquery');
  }
  add_action( 'admin_enqueue_scripts', 'shieldup_my_admin_scripts2' );
}

// add menus and sub menus
add_action( 'admin_menu', 'shieldup_register_menu_page' );
add_action( 'admin_head', 'shieldup_edit_menu_link' );

function shieldup_register_menu_page(){
	add_menu_page( 'ShieldUp', SHIELD_UP_PLUGIN_NAME, 'manage_options', 'su_main', '', 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZlcnNpb249IjEuMSIgeGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHhtbG5zOnN2Z2pzPSJodHRwOi8vc3ZnanMuY29tL3N2Z2pzIiB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiB2aWV3Qm94PSI1IDAgOTAgMTAwIj4KICA8ZyBmaWxsPSIjM2I4MmY2Ij4KICAgIDxwYXRoIGQ9Ik01MCA5Ny41YTMuMTEyIDMuMTEyIDAgMCAxLTEuNjMyLS40NjJsLS4zNDItLjIxMUM2LjY5MyA3MS45MTYgOC44MDggMTcuMjI2IDguOTE4IDE0LjkxYTMuMTEzIDMuMTEzIDAgMCAxIDMuMTA5LTIuOTY1aC4wMjRsLjYwNy4wMDJjMTkuNDI1IDAgMzEuMTAyLTYuOTggMzUuMjIyLTkuOTgyLjQzMy0uMzE1IDEuMDg4LS43OTMgMi4xMTgtLjc5MyAxLjAzMSAwIDEuNjg4LjQ3OCAyLjEyMi43OTQgNC4xMiAzLjAwMSAxNS43OTkgOS45ODEgMzUuMjE5IDkuOThsLjYwOC0uMDAyYTMuMDk5IDMuMDk5IDAgMCAxIDMuMTM0IDIuOTY1Yy4xMSAyLjMxNiAyLjIyNiA1Ny4wMDYtMzkuMTIxIDgyLjE3NmwtLjMyOC4yMDJBMy4xMDEgMy4xMDEgMCAwIDEgNTAgOTcuNXpNMTUuMTI2IDE4LjE0QzE1LjMxNyAyOS40NTkgMTguMjU4IDcwLjYxNyA1MCA5MC45NjhjMzEuNzQtMjAuMzYgMzQuNjgzLTYxLjUxIDM0Ljg3My03Mi44MjhDNjcuMDQxIDE3LjYyNSA1NS4zNzYgMTEuNjE2IDUwIDguMDdjLTUuMzc2IDMuNTQ3LTE3LjA0IDkuNTUzLTM0Ljg3NCAxMC4wN3oiPjwvcGF0aD4KICAgIDxwYXRoIGQ9Ik01MCA4MS4wNjVhMy4xMTUgMy4xMTUgMCAwIDEtMS42MjMtLjQ1N2wtLjIyNS0uMTM4QzIwLjUwNiA2My42NDIgMjEuOTE2IDI3LjE2NyAyMS45OSAyNS42MjJhMy4xMTUgMy4xMTUgMCAwIDEgMy4xMS0yLjk2NmguMDIzbC4zODkuMDAyYzEyLjQyMi0uMDAxIDE5Ljg1LTQuNDM0IDIyLjQ2Ny02LjM0LjM0OC0uMjU0Ljk5Ni0uNzI2IDIuMDItLjcyNiAxLjAyMiAwIDEuNjc0LjQ3MiAyLjAyMi43MjcgMi42MiAxLjkwNiAxMC4wNTMgNi4zMzkgMjIuNDY2IDYuMzM5bC4zOS0uMDAyaC4wMjJhMy4xMTMgMy4xMTMgMCAwIDEgMy4xMSAyLjk2NmMuMDc0IDEuNTQ0IDEuNDgyIDM4LjAxOS0yNi4xNjYgNTQuODVsLS4yMjEuMTM2YTMuMTEgMy4xMSAwIDAgMS0xLjYyMi40NTd6Ij48L3BhdGg+CiAgPC9nPgo8L3N2Zz4K', 99 );
	add_submenu_page('su_main', 'Dashboard', 'Dashboard', 'manage_options', 'su_dashboard', 'shieldup_dashboard_page_call' );
  add_submenu_page('su_main', 'Settings', 'Settings', 'manage_options', 'su_settings', 'shieldup_settings_page_call' );
  add_submenu_page('su_main', 'How Tos', 'How Tos', 'manage_options', 'https://www.shieldup.me/how_tos_free' );
  add_submenu_page('su_main', 'ShieldUp Pro', '<span style="color:#fcdc25">ShieldUp Pro</span>', 'manage_options', 'https://www.shieldup.me' );

	global $submenu;
  unset( $submenu[ 'su_main' ][ 0 ] );
}

function shieldup_dashboard_page_call() {
	include ('backend/dashboard.php');
}
function shieldup_settings_page_call() {
	include ('backend/settings.php');
}
?>
