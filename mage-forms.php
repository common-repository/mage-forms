<?php
/**
 * Plugin Name: Mage Front End Forms
 * Plugin URI:  http://www.maximusbusiness.com/plugins/mage-forms-pro/
 * Description: Create multiple Front End Post Forms for posts, pages and custom post types with complete custumizable form elements.
 * Author:      Mage Cast
 * Author URI:  http://www.maximusbusiness.com/plugins/mage-forms-pro/
 * Version:     1.1.4
 * Text Domain: mage-forms
 * Domain Path: /lang/
 * License:     GPLv2 or later (license.txt)
 */
?>
<?php
if (!defined('ABSPATH')) exit;
define('MAGECAST_FORMS_VER', '1.1.4');
define('MAGECAST_FORMS', dirname( __FILE__ ). '/');
define('MAGECAST_FORMS_URL',plugins_url('/',__FILE__));
define('MAGECAST_FORMS_SOURCE',MAGECAST_FORMS_URL.'source/');
add_action('after_setup_theme','load_magecast_forms');
register_deactivation_hook( __FILE__, 'mage_forms_deactivation' );
register_activation_hook( __FILE__, 'mage_forms_activation' );
function load_magecast_forms(){	
	if (!defined('MAGECAST')) require_once MAGECAST_FORMS.'core/mage-cast.php';
	require_once MAGECAST_FORMS.'cast/mage-forms.php';
	require_once MAGECAST_FORMS.'cast/attributes.php';
	require_once MAGECAST_FORMS.'cast/diagrams.php';
	if (file_exists(MAGECAST_FORMS.'addons/mage-summon.php')) require_once MAGECAST_FORMS.'addons/mage-summon.php';
	
	require_once MAGECAST_FORMS.'cast/craft.php';
	add_filter( 'plugin_action_links_'.plugin_basename( __FILE__ ), 'mage_forms_settings_link' );
}
function mage_forms_activation() {
     add_option( 'mage_forms_activation','activated' );
}
function mage_forms_settings_link( $links ) {
   $links[] = '<a href="'. get_admin_url(null, 'edit.php?post_type=mage_form&page=mage_forms') .'">Settings</a>';
   return $links;
}
function mage_forms_deactivation() {
    flush_rewrite_rules();
}
