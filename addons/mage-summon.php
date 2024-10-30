<?php
/**
 * Plugin Name: Mage Front End Forms
 * Plugin URI:  http://www.maximusbusiness.com/plugins/mage-forms-pro/
 * Description: Create multiple Front End Post Forms for posts, pages and custom post types with complete custumizable form elements.
 * Author:      Mage Cast
 * Author URI:  http://www.maximusbusiness.com/plugins/mage-forms-pro/
 * Version:     1.0.5
 * Text Domain: mage-forms
 * Domain Path: /lang/
 * License:     GPLv2 or later (license.txt)
 */
?>
<?php
if (!defined('MAGECAST_FORMS')) exit;
define('MAGECAST_FORMS_ADDONS', MAGECAST_FORMS. 'addons/');
define('MAGECAST_FORMS_ADDONS_URL', MAGECAST_FORMS_URL. 'addons/');
global $addons;
$addons = array(
	'mage_forms_pro'=>array(
		'name'=>'Mage Forms',
		'desc'=>'<p>Enhanced form management and registration features for users. Required for all other addons.</p>
		<ul>
		<li><strong>Features</strong></li>
		<li>Frontend delete option for submitted posts</li>
		<li>Customizable frontend dashboard to view/edit/delete submissions</li>
		<li>Auto-signup users after posting</li>
		<li>New form fields for user registration</li>
		</ul>',
		'class' => 'col-md-6 mage-forms-pro',
		'version'=>'Pro',
		'pro'=>'http://www.maximusbusiness.com/plugins/mage-forms-pro/'),
	'mage_listings'=>array(
		'name'=>'Mage Listings',
		'desc'=>'Coming Soon.'),
	'mage_reviews'=>array(
		'name'=>'Mage Reviews',
		'desc'=>'Coming Soon.'),
	'mage_events'=>array(
		'name'=>'Mage Events',
		'desc'=>'Coming Soon.'),
	'mage_products'=>array(
		'name'=>'Mage Products',
		'desc'=>'Coming Soon.')
	);
foreach($addons as $slug => $addon) if (file_exists(MAGECAST_FORMS_ADDONS.$slug.'/init.php')) require_once MAGECAST_FORMS_ADDONS.$slug.'/init.php';


class MageCraft {  
    public function __construct(){ 	}  
	public function options() {
		global $addons;
		$options = $catalog = array();	
		$addons = apply_filters('mage_core_plugin_addons',$addons);
		$options[] = array('name' => __('Dashboard','mage-core'),'icon' => 'tasks','type' => 'heading');		
		$options[] = array('name' => __('Plugins','mage-core'),'parent' => 'dashboard','type' => 'subheading');
		$options[] = array('type' => 'catalog', 'options'=>$addons);
		return $options;	
	}
}  
global $craft;
$craft = new MageCraft();

add_action('summon_mage_forms_menu_last','mage_forms_summon_addons');
function mage_forms_summon_addons(){
	$mage_forms_addons = add_submenu_page('edit.php?post_type=mage_form', 'Mage Forms Settings', 'Addons', 'manage_options', 'mage_forms_addons', 'mage_forms_addons');
	//add_action('admin_print_scripts-'.$mage_forms_addons, 'mage_load_admin_scripts');			
	add_action('admin_print_styles-'.$mage_forms_addons, 'mage_load_admin_styles' );
}

function mage_forms_addons() {
	global $craft;
?>
<div id="mage-wrap">
<?php settings_errors(); ?>
<div id="container" class="row">  
    <form id="mage-form" method="post" class="form-horizontal" action="options.php">
		<?php settings_fields('magecast'); ?>
		<div id="magecast-content" class="magecast-content tab-content"><?php mage_fields($craft->options(),'cast'); ?></div>        
    </form>
</div>
</div><?php
}