<?php
/*
Mage Forms
*/
/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

/* Basic plugin definitions */
/*
 * @level 		Casting
 * @author		Mage Cast 
 * @url			http://magecast.com
 * @license   	http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
 */
?>
<?php
if (!defined('MAGECAST_FORMS')) exit;
add_action('init', 'summon_magecast_forms');
add_action( 'wp_enqueue_scripts', 'enqueue_scribe_scripts');
add_action('admin_init', 'summon_scrolls');
function enqueue_scribe_scripts() {
	global $post, $bp;
	$id = $action = '';
	if (!is_object($post)) return;
	if (!has_shortcode($post->post_content,'form'))return;
	if (is_multisite())require_once ABSPATH . '/wp-admin/includes/ms.php';
	wp_enqueue_style('mage-form',MAGECAST_FORMS_SOURCE.'css/mage-forms.css');
	wp_enqueue_script('select',MAGECAST_FORMS_SOURCE.'js/select2.min.js', array('jquery'));
	wp_enqueue_script('mage-components',MAGECAST.'js/bootstrap.min.js',array('jquery'),'3.0.0',true);
		
  	wp_enqueue_script('plupload-handlers');
	wp_enqueue_script('jquery-ui-sortable');				 	
	if (wp_script_is('plupload-handlers')){	
		$plup = array(			
			'runtimes' => 'html5,silverlight,flash,html4',
			'browse_button' => 'mage-form-upload-button',
			'file_data_name' => 'mage_img_upload',
			'max_file_size' => wp_max_upload_size() . 'b',
			'url' => admin_url( 'admin-ajax.php' ) . '?action=mage_img_upload&nonce=' . wp_create_nonce( 'mage_img_upload' ),
           	'flash_swf_url' => includes_url( 'js/plupload/plupload.flash.swf' ),
        	'silverlight_xap_url' => includes_url( 'js/plupload/plupload.silverlight.xap' ),
       		'filters' => array(array('title' => __( 'Allowed Files' ), 'extensions' => '*')),
      		'multipart' => true,
       		'urlstream_upload' => true,
       	);
		$atts = array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'mage_status_submit' => __('Submitting','mage-forms'),
			'mage_status_confirm' => __('Are you sure you want to delete this image?','mage-forms'),
			'nonce' => wp_create_nonce( 'scribe_nonce' ),
			'plupload' => $plup
    	);
		wp_enqueue_script('scribe', MAGECAST_FORMS_SOURCE.'js/scribe.js', array('jquery'));
    	wp_localize_script('scribe', 'scribe', $atts);	
		
		wp_enqueue_script('mage_attachment', MAGECAST_FORMS_SOURCE.'js/attachment.js', array('jquery'));
		wp_localize_script( 'mage_attachment', 'mage_attachment', array(
         	'nonce' => wp_create_nonce( 'mage_attachment' ),
            'number' => 5,
			'mage_status_confirm' => __('Are you sure you want to delete this file?','mage-forms'),
            'attachment_enabled' => true,
            'plupload' => array(
				'runtimes' => 'html5,flash,html4',
				'browse_button' => 'mage-form-attach-button',
				'container' => 'mage-form-attachments-wrap',
				'file_data_name' => 'mage_attachments',
				'max_file_size' => wp_max_upload_size() . 'b',
				'url' => admin_url( 'admin-ajax.php' ) . '?action=mage_upload_files&nonce=' . wp_create_nonce( 	'mage_upload_files' ),
				'flash_swf_url' => includes_url( 'js/plupload/plupload.flash.swf' ),
				'filters' => array(array('title' => __( 'Allowed Files' ), 'extensions' => '*')),
				'multipart' => true,
				'urlstream_upload' => true,
            )
       	));
	}
}

function summon_magecast_forms(){	
	if (current_user_can('switch_themes')) {	
		add_action('admin_menu', 'summon_magecast_forms_dashboard');				
	}
}
function summon_magecast_forms_dashboard(){
	global $themename, $shortname, $submenu, $menu;
	//$mage_forms_page = add_menu_page('Mage Forms Settings','Mage Forms','manage_options','mage_forms', 'edit.php?post_type=mage_form',MAGECAST_FORMS_SOURCE.'img/icon.png','27.9');
	do_action('summon_mage_forms_menu_first');
	$mage_forms_help = add_submenu_page('edit.php?post_type=mage_form', 'Mage Forms Settings', 'Help', 'manage_options', 'mage_forms_help', 'mage_forms_page');
	$submenu['edit.php?post_type=mage_form'][5][0] = 'Forms';
	add_action('admin_print_scripts-'.$mage_forms_help, 'mage_load_admin_scripts');			
	add_action('admin_print_styles-'.$mage_forms_help, 'mage_load_admin_styles' );
	add_action('admin_print_scripts-post.php', 'mage_form_scripts' );
	add_action('admin_print_scripts-post-new.php', 'mage_form_scripts' );	
	do_action('summon_mage_forms_menu_last');
}
function mage_form_scripts(){
	global $post;
	if ($post->post_type == 'mage_form'){
		wp_enqueue_script('alembic', MAGECAST_FORMS_SOURCE.'js/mage-forms.js', array('jquery'));
		wp_enqueue_script('mage-components',MAGECAST.'js/bootstrap.min.js',array('jquery'),'3.0.0',true);
		//add_filter('user_can_richedit' ,'__return_false', 50);
	}
}
function mage_forms_page() {
	global $craft;
?>
<div id="mage-wrap">
<?php settings_errors(); ?>
<div id="container" class="row">  
    <form id="mage-form" method="post" class="form-horizontal" action="options.php">
		<?php settings_fields('mage_forms_help'); ?>
		<div id="magecast-content" class="magecast-content tab-content"><?php mage_fields(mage_forms_help(),'form_help'); ?></div>
    </form>
</div>
</div><?php
}
function mage_forms_help(){
	//global $wp_roles;
	//$mage_roles = (array) $wp_roles->get_names();
	//$mage_roles['public'] = 'Public';
	//$pages = array(0=>'Redirect to Post') + mage_get_pages();
	//$users = array(0=>'Submitting User') + mage_get_users($args=array('role'=>'administrator'));
	$options = array();				
	$options[] = array('name' => 'Help','icon' => 'star','type' => 'heading');	
	$options[] = array('name' => __('General','mage-forms'),'parent' => 'help','type' => 'subheading');
	/*
	$options[] = array(
		'name' => __('Default Form Page','mage-forms'),
		'desc' => __('Choose the page where you will be placing your main [form] shortcode to use as the default edit page.','mage-forms'),
		'id' => 'mage_form_default_edit_page',		
		'type' => 'select',
		'options'=>mage_get_pages());
		/*
	$options[] = array(
		'name' => __('Shortcode Compatibility','mage-reviews'),
		'desc' => __('Prepend shortcodes with <code>mage_</code> for compatibility with other plugins.','mage-reviews'),
		'id' => 'mage_form_compatibility',		
		'type' => 'checkbox',
		'std' => '0');
		*/
	$options[] = array(		
		'content' => '<div class="alert alert-info" role="alert"><strong>Tip</strong> You may start by <a href="edit.php?post_type=mage_form">adding a form</a>.</div>',
		'type'=>'html');
	$options = apply_filters('mage_forms_attributes_general',$options);	
	/*
	$options[] = array(
		'name' => __('Wording', 'mage-forms'),
		'desc' => __('Edit alert phrases throughout the form process.', 'mage-forms'),
		'type' => 'legend');
	$options[] = array(
		'name' => __('Submission Updated', 'mage-google-maps'),
		'id' => 'mage_forms_success',
		'std' => __('Your submission has been updated succesfully.','mage-forms'),
		'type' => 'text');		
	$options[] = array(
		'type' => 'html',
		'for'=>'legend');		
		
	$options[] = array('name' => __('Help','mage-forms'),'parent' => 'forms','type' => 'subheading');	
	*/
	$options[] = array(
		'name' => __('Form Element Shortcode','mage-forms'),
		'type' => 'legend');
	$options[] = array(		
		'content' => '<p>All Form elements share the following parameters.</p><table class="table">
          <thead>
            <tr>
              <th>'.__("Parameter",'mage-forms').'</th>
              <th>'.__("Type",'mage-forms').'</th>
			  <th>'.__("Description",'mage-forms').'</th>
              <th>'.__("Default",'mage-forms').'</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><code>name</code></td>
              <td><div class="label label-success">string</div></td>
			  <td>'.__("The form elements unique name. This is required for all form elements.",'mage-forms').'</td>
              <td>'.__('empty','mage-forms').'</td>
            </tr>
			<tr>
              <td><code>class</code></td>
              <td><div class="label label-success">string</div></td>
			  <td>'.__('Additional CSS classes to add to the form element.','mage-forms').'</td>
              <td>'.__('empty','mage-forms').'</td>
            </tr>
			<tr>
              <td><code>style</code></td>
              <td><div class="label label-success">string</div></td>
			  <td>'.__('This parameter behaves identical to that of any HTML tags "style" parameter. Use this to implement inline CSS styles directly into the element. This parameter is ignored by <code>[textarea]</code> when in <strong>rich editor mode</strong>.','mage-forms').'</td>
              <td>'.__('empty','mage-forms').'</td>
            </tr>
			<tr>
              <td><code>label</code></td>
              <td><div class="label label-success">string</div></td>
			  <td>'.__("Prepend the form element with a label. This can also be done manually, without the usage of the shortcode parameter. You may also use this parameter to change the default <code>[submit]</code> button text.",'mage-forms').'</td>
              <td>'.__('empty','mage-forms').'</td>
            </tr> 
			<tr>
              <td><code>req</code></td>
              <td><div class="label label-warning">bool</div></td>
			  <td>'.__("Set to 1 to make a form field required. Required element labels will be marked with <strong>*</strong>, as well as pass through validation requirements accordingly. This parameter is ignored by <code>[submit]</code>.",'mage-forms').'</td>
              <td>0</td>
            </tr>
			<tr>
              <td><code>placeholder</code></td>
              <td><div class="label label-success">string</div></td>
			  <td>'.__('A form elements placeholder value. Applies only to input fields.','mage-forms').'</td>
              <td>'.__('empty','mage-forms').'</td>
            </tr>
			<tr>
              <td><code>maxlength</code></td>
              <td><div class="label label-primary">int</div></td>
			  <td>'.__('The maximum character length of a field. Applies only to input fields.','mage-forms').'</td>
              <td>'.__('empty','mage-forms').'</td>
            </tr>
			<tr>
              <td><code>disabled</code></td>
              <td><div class="label label-warning">bool</div></td>
			  <td>'.__('Set to "1" to disabled a form field. This parameter is ignored by <code>[textarea]</code> when in <strong>rich editor mode</strong>.','mage-forms').'</td>
              <td>'.__('empty','mage-forms').'</td>
            </tr>
			<tr>
              <td><code>readonly</code></td>
              <td><div class="label label-warning">bool</div></td>
			  <td>'.__('Set a form field to readonly. This parameter is ignored by <code>[textarea]</code> when in <strong>rich editor mode</strong>.','mage-forms').'</td>
              <td>'.__('empty','mage-forms').'</td>
            </tr>
			</tbody>
        </table><p><strong>'.__('Usage','mage-forms').':</strong><br /><code>[text name="company" label="Business Name:" req=1 class="alignright" style="margin-top:10px; margin-bottom:10px;" placeholder="Sample Media"]</code></p>',
		'type' => 'html');
		$options[] = array(
		'type' => 'html',
		'for'=>'legend');
		$options[] = array(
		'name' => __('[textarea] Shortcode','mage-forms'),
		'type' => 'legend');
		$options[] = array(		
		'content' => '<p>The <code>[textarea]</code> shortcode is set to <strong>Rich Editor mode</strong> by default, which ignores some of the above listed parameters. Set the <code>rich</code> parameter to 0 for a standard HTML textarea and to accomodate more of the above options. Below are additional options for this element.</p><table class="table">
          <thead>
            <tr>
              <th>'.__("Parameter",'mage-forms').'</th>
              <th>'.__("Type",'mage-forms').'</th>
			  <th>'.__("Description",'mage-forms').'</th>
              <th>'.__("Default",'mage-forms').'</th>
            </tr>
          </thead>
          <tbody>
			<tr>
              <td><code>rows</code></td>
              <td><div class="label label-primary">int</div></td>
			  <td>'.__('Determines the starting rows and height of the textarea, upon initial loading only.','mage-forms').'</td>
              <td>'.__('8','mage-forms').'</td>
            </tr>
            <tr>
              <td><code>rich</code></td>
              <td><div class="label label-warning">bool</div></td>
			  <td>'.__('When active, the textarea is replaced by the <strong>WP Editor</strong>, allowing further customization with its inherited parameters. The following parameters are available when in <strong>Rich Editor mode</strong>: <ul>
			  <li><code>wpautop</code> (default: true)</li>
			  <li><code>media_buttons</code> (default: false)</li>
			  <li><code>teeny</code> (default: false)</li>
			  <li><code>dfw</code> (default: false)</li>
			  <li><code>tincymce</code> (default: false)</li>
			  <li><code>quicktags</code> (default: true)</li></ul>
			  Visit <a href="https://codex.wordpress.org/Function_Reference/wp_editor" target="_blank" rel="external nofollow">WordPress Codex: WP_editor</a> for more information.','mage-forms').'</td>
             <td>'.__('1','mage-forms').'</td>
            </tr>
          </tbody>
        </table>
		<p><strong>'.__('Simple Usage','mage-forms').':</strong><br /><code>[textarea name="post_content" label="Content" req=1 class="my-textarea"]</code></p>
		<p><strong>'.__('Custom Usage #1','mage-forms').':</strong><br /><code>[textarea rich=0 style="margin-bottom:20px; box-shadow: 0px 0px 5px #484848;" name="custom_content" class="custom-textarea-class" rows=15]Additional Info[/textarea]</code></p>
		<p><strong>'.__('Custom Usage #2','mage-forms').':</strong><br /><code>[textarea name="custom_content" req=0 rows=30]Custom placeholder text here.[/textarea]</code></p>',
		'type' => 'html');	
	$options[] = array(
		'type' => 'html',
		'for'=>'legend');
		$options[] = array(
		'name' => __('[select] Shortcode','mage-forms'),
		'type' => 'legend');
		$options[] = array(		
		'content' => '<p>Parameters specific for <code>[select]</code> fields.</p><table class="table">
          <thead>
            <tr>
              <th>'.__("Parameter",'mage-forms').'</th>
              <th>'.__("Type",'mage-forms').'</th>
			  <th>'.__("Description",'mage-forms').'</th>
              <th>'.__("Default",'mage-forms').'</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><code>name</code></td>
              <td><div class="label label-success">string</div></td>
			  <td>'.__('For a dynamic taxonomy dropdown, use "post_category" for categories or "tax_input" for custom taxonomies. Use any other name for an ordinary select dropdown.','mage-forms').'</td>
             <td>'.__('empty','mage-forms').'</td>
            </tr>
			<tr>
              <td><code>taxonomy</code></td>
              <td><div class="label label-success">string</div></td>
			  <td>'.__('If <strong>name</strong> is set to "tax_input", use the taxonomy slug here.','mage-forms').'</td>
              <td>'.__('empty','mage-forms').'</td>
            </tr>
            <tr>
              <td><code>show_option_none</code></td>
              <td><div class="label label-success">string</div></td>
			  <td>'.__("The text to display when no option is selected.",'mage-forms').'</td>
             <td>'.__('empty','mage-forms').'</td>
            </tr>
 		
			<tr>
              <td><code>options</code></td>
              <td><div class="label label-success">string</div></td>
			  <td>'.__('Comma separated list of options.','mage-forms').'</td>
              <td>'.__('empty','mage-forms').'</td>
            </tr>
			<tr>
              <td><code>selected</code></td>
              <td><div class="label label-primary">int</div></td>
			  <td>'.__('A term ID when using a taxonomy dropdown.','mage-forms').'</td>
             <td>'.__('empty','mage-forms').'</td>
            </tr>
          </tbody>
        </table><p><strong>'.__('Usage','mage-forms').':</strong><br /><code>[select name="tax_input" taxonomy="city" label="City:" req=1 show_option_none="Choose an Area" selected=7]</code></p>',
		'type' => 'html');	
	$options[] = array(
		'type' => 'html',
		'for'=>'legend');
		$options[] = array(
		'name' => __('[submit] Shortcode','mage-forms'),
		'type' => 'legend');
		$options[] = array(		
		'content' => '<p>The <code>[submit]</code> shortcode contains many default form element parameters listed above, including <code>style</code>, <code>class</code> and others. Here are parameters specific for this shortcode only.</p><table class="table">
          <thead>
            <tr>
              <th>'.__("Parameter",'mage-forms').'</th>
              <th>'.__("Type",'mage-forms').'</th>
			  <th>'.__("Description",'mage-forms').'</th>
              <th>'.__("Default",'mage-forms').'</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><code>name</code></td>
              <td><div class="label label-success">string</div></td>
			  <td>'.__('The name (id) of the form to submit, prepended with "submit_". This parameter can be left out if only one form is display on the page.','mage-forms').'</td>
             <td>'.__('submit_#','mage-forms').'</td>
            </tr>
			<tr>
              <td><code>wrap</code></td>
              <td><div class="label label-success">string</div></td>
			  <td>'.__('The submit buttons html tag, which can be "button" or "input". Although setting this parameter to "a" is possible, it would likely malfunction without further customizations.','mage-forms').'</td>
              <td>'.__('button','mage-forms').'</td>
            </tr>
          </tbody>
        </table><p><strong>'.__('Simple Usage','mage-forms').':</strong><br /><code>[submit label="Publish Post" wrap="input"]</code></p>
		<p><strong>'.__('Custom Usage #1','mage-forms').':</strong><br /><code>[submit]<em>Publish</em> <strong>NOW!</strong>[/submit]</code></p>
		<p><strong>'.__('Custom Usage #2','mage-forms').':</strong><br /><code>[submit class="custom-img-submit" style="padding:5px;margin-top:10px;"]&lt;img src=&quot;/submit.png&quot; /&gt;[/submit]</code></p>',
		'type' => 'html');	
	$options[] = array(
		'type' => 'html',
		'for'=>'legend');
	$options = apply_filters('mage_form_help_general_after',$options); 
	$options[] = array('name' => __('Custom Fields','mage-forms'),'parent' => 'help','type' => 'subheading');
	$options[] = array(
		'name' => __('Custom Field Shortcode & Template Tag','mage-forms'),
		'type' => 'legend');
	$options[] = array(		
		'content' => '<p>A shortcode and template tag are available for easy displaying of custom meta field content, regardless whether they are created using a Mage Form or not. The below features can retrieve a value from any existing custom fields:</p>
		<h3>Shortcode</h3>
		<code>[mage_cf name="custom_field_name"]</code>
		<table class="table">
          <thead>
            <tr>
              <th>'.__("Parameter",'mage-forms').'</th>
              <th>'.__("Type",'mage-forms').'</th>
			  <th>'.__("Description",'mage-forms').'</th>
              <th>'.__("Default",'mage-forms').'</th>
            </tr>
          </thead>
          <tbody>
			<tr>
              <td><code>id</code></td>
              <td><div class="label label-primary">int</div></td>
			  <td>'.__('Use this to retrieve the meta value of a different post, or when outside of the loop. Otherwise, the ID of the current post is used by default.','mage-forms').'</td>
             <td>'.__('Current Post ID','mage-forms').'</td>
            </tr>
			<tr>
              <td><code>name</code></td>
              <td><div class="label label-success">string</div></td>
			  <td>'.__('<strong>Required:</strong> The custom meta field name to retrieve the custom field value from.','mage-forms').'</td>
              <td>'.__('empty','mage-forms').'</td>
            </tr>
			<tr>
				<td><code>raw</code></td>
				<td><div class="label label-primary">int</div></td>
				<td>'.__('Set to 1 to return only the value of the custom field without any additional html.','mage-forms').'</td>
				<td>'.__('0','mage-forms').'</td>
			</tr>
			<tr>
				<td><code>wrap</code></td>
				<td><div class="label label-success">string</div></td>
				<td>'.__('Sets the HTML tag that is wrapped around the value.','mage-forms').'</td>
				<td>'.__('div','mage-forms').'</td>
			</tr>
			<tr>
				<td><code>class</code></td>
				<td><div class="label label-success">string</div></td>
				<td>'.__('Add custom classes to the HTML tag wrapping the value.','mage-forms').'</td>
				<td>'.__('empty','mage-forms').'</td>
			</tr>
			<tr>
				<td><code>style</code></td>
				<td><div class="label label-success">string</div></td>
				<td>'.__('Add custom styles to the HTML tag wrapping the value.','mage-forms').'</td>
				<td>'.__('empty','mage-forms').'</td>
			</tr>
			<tr>
				<td><code>itemprop</code></td>
				<td><div class="label label-success">string</div></td>
				<td>'.__('Add an itemprop value for rich snippet meta purposes..','mage-forms').'</td>
				<td>'.__('empty','mage-forms').'</td>
			</tr>
          </tbody>
        </table>
		<h3>Template Tag</h3>
		<code>&lt;?php echo mage_return_custom_field( $id, $name, $settings = array() ); ?&gt;</code>
		<table class="table">
          <thead>
            <tr>
              <th>'.__("Parameter",'mage-forms').'</th>
              <th>'.__("Type",'mage-forms').'</th>
			  <th>'.__("Description",'mage-forms').'</th>
              <th>'.__("Default",'mage-forms').'</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><code>$id</code></td>
              <td><div class="label label-primary">int</div></td>
			  <td>'.__('<strong>Required:</strong> The template tag requires an <strong>ID</strong> of the post to retrieve the custom meta value from.','mage-forms').'</td>
             <td>'.__('empty','mage-forms').'</td>
            </tr>
			<tr>
              <td><code>$name</code></td>
              <td><div class="label label-success">string</div></td>
			  <td>'.__('<strong>Required:</strong> The custom meta field name to retrieve the custom field value from.','mage-forms').'</td>
              <td>'.__('empty','mage-forms').'</td>
            </tr>
			<tr>
              <td><code>$settings</code></td>
              <td><div class="label label-info">array</div></td>
			  <td>'.__('<p>An array of settings for further customization of the returned value.</p>
					<table class="table">
						<thead>
							<tr>
								<th>'.__("Parameter",'mage-forms').'</th>
								<th>'.__("Type",'mage-forms').'</th>
								<th>'.__("Description",'mage-forms').'</th>
								<th>'.__("Default",'mage-forms').'</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td><code>raw</code></td>
								<td><div class="label label-primary">int</div></td>
								<td>'.__('Set to 1 to return only the value of the custom field without any additional html.','mage-forms').'</td>
								<td>'.__('0','mage-forms').'</td>
							</tr>
							<tr>
								<td><code>wrap</code></td>
								<td><div class="label label-success">string</div></td>
								<td>'.__('Sets the HTML tag that is wrapped around the value.','mage-forms').'</td>
								<td>'.__('div','mage-forms').'</td>
							</tr>
							<tr>
								<td><code>class</code></td>
								<td><div class="label label-success">string</div></td>
								<td>'.__('Add custom classes to the HTML tag wrapping the value.','mage-forms').'</td>
								<td>'.__('empty','mage-forms').'</td>
							</tr>
							<tr>
								<td><code>style</code></td>
								<td><div class="label label-success">string</div></td>
								<td>'.__('Add custom styles to the HTML tag wrapping the value.','mage-forms').'</td>
								<td>'.__('empty','mage-forms').'</td>
							</tr>
							<tr>
								<td><code>itemprop</code></td>
								<td><div class="label label-success">string</div></td>
								<td>'.__('Add an itemprop value for rich snippet meta purposes..','mage-forms').'</td>
								<td>'.__('empty','mage-forms').'</td>
							</tr>
						</tbody>
					</table>
				','mage-forms').'</td>
              <td>'.__('','mage-forms').'</td>
            </tr>
          </tbody>
        </table>',
		'type'=>'html');
	$options[] = array(
		'type' => 'html',
		'for'=>'legend');
	$options = apply_filters('mage_form_help_custom_fields_after',$options); 
	return $options;	
}


function mage_scroll_settings() {
	global $post,$wp_roles;	
	$post_form = array(	
	'shortcode'=>array(
			'type'=>'shortcode',
			'code'=>'form',
			'label'=>'Shortcode'
		),
	);
	summon_mage_form_fields(array('id'=>$post->ID,'nonce'=>'mage_form_save'), $post_form, 2);
	$mage_roles = (array) $wp_roles->get_names();
	$mage_roles['public'] = 'Public';
	$rpages = mage_get_pages();
	// Prepend page names with Page:
	$npages = array();
	foreach ($rpages as $rp => $p){
		$npages[$rp] = 'Page: '.$p;
	}
	// add modified page list to select options
	$pages = array(0=>'Redirect to Post') + $npages;
	$users = array(0=>'Submitting User') + mage_get_users($args=array('role'=>'administrator'));
	$pform = array(	
		'form_post_type' => array(
			'name'=> 'Post Type',
			'type'=>'select', 
			'options'=>mage_post_type_options(),
		),	
		'form_post_author' => array(
			'name'=> 'Post Author',
			'type'=>'select', 
			'options'=> $users
		),		
		'form_post_permission' => array(
			'name'=> 'Minimum Role',
			'type'=>'select', 
			'options'=> $mage_roles
		),
		'form_post_status' => array(
			'name'=> 'Post Submit Status',
			'type'=>'select', 
			'options'=> array(
                    'publish' => 'Publish',
                    'draft' => 'Draft',
                    'pending' => 'Pending',
					'private' => 'Private'
					)
		),
		'form_post_redirect' => array(
			'name'=> 'Form Submit Redirect',
			'type'=>'select', 
			'options'=> $pages
		),	
		'form_post_email' => array(
			'name'=> 'Post Notification Email',
			'type'=>'email',
			'std'=>get_option('admin_email'),
		),
		'form_custom_fields' => array(
			'name'=> 'Use WordPress Custom Fields',
			'type'=>'checkbox',
			'std' => '1'
		)
	);			
	summon_mage_form_fields(array('id'=>$post->ID,'title'=>'Mage Form Settings','class'=>'pform'), $pform,2);
	$pform = array(	
		'form_upload_count' => array(
			'name'=> 'Upload Limit',
			'type'=>'number', 
			'std'=>'5',
			'min'=>'1',
			'max'=>'99'
		),		
	);			
	summon_mage_form_fields(array('id'=>$post->ID,'title'=>'Media Upload Settings','class'=>'pform'), $pform,2);
	do_action('after_mage_scroll_settings');
	$editing = array(	
	'shortcode'=>array(
			'type'=>'shortcode',
			'code'=>'mage_edit',
			'label'=>'Shortcode'
		),
	);
	summon_mage_form_fields(array('id'=>$post->ID,'title'=>'Edit Link','class'=>'pform','content'=>__('Requires the ID of the page that the [form] is placed in, not the ID of the form itself. Should be placed within loop.','mage-forms')),$editing,2);
}
function mage_scroll_cast() {
	global $post;
	//$default = mage_get_option('email_handle') ? mage_get_option('email_handle'):get_option('admin_email');
	$craft = array(
		'form_email'=>array(
			'name'=>'Contact E-mail',
			'type'=>'email',
			//'std'=>$default,
			'req'=>'1',
			'size'=>'20'
		),
		'form_email_subject'=>array(
			'name'=>'Subject',
			'type'=>'text',
			'req'=>'1',
			'size'=>20
		),
		'form_email_body'=>array(
			'name'=>'Body',
			'type'=>'textarea',
			'req'=>'1',
			'rows' => '8'
		)			
	);		
	//summon_mage_form_fields($post->ID, $craft, 'form_save','cform');	
	$craft = array(
		'post_title'=>array(
			'name'=>'Title',
			'type'=>'shortcode',
			'std'=>'post_title',
			'code'=>'text'
		),
		'post_content'=>array(
			'name'=>'Content',
			'type'=>'shortcode',
			'std'=>'post_content'
		),
		'submit' => array(
			'label'=>'Submit Button',
			'type'=>'shortcode',
			'std'=>'submit',
			'code'=>'submit'
		),
	);
	summon_mage_form_fields(array('id'=>$post->ID,'title'=>'Required Elements','class'=>'pform','content'=>'If this is a Front End <strong>Post</strong> submission form, the following fields are required. Required fields may vary for pages or custom post type submission forms.'), $craft);
	$craft = array('post_excerpt'=>array(
			'name'=>'Excerpt',
			'type'=>'shortcode',
			'std'=>'post_excerpt'
		),
		'post_thumbnail'=>array(
			'name'=>'Featured Image',
			'type'=>'shortcode',
			'std'=>'post_thumbnail',
			'code' => 'upload'
		),
		/*'post_format'=>array(
			'name'=>'Post Format',
			'type'=>'shortcode',
			'std'=>'post_format',
			'code'=>'radio'
		),*/
		'post_category'=>array(
			'name'=>'Category',
			'type'=>'shortcode',
			'std'=>'post_category',
			'code'=>'select'
		),
		'tags_input'=>array(
			'name'=>'Tags',
			'type'=>'shortcode',
			'std'=>'tags_input',
			'code'=>'multicheck'
		),
		'post_attachments'=>array(
			'name'=>'Attachments',
			'type'=>'shortcode',
			'std'=>'post_attachments',
			'code' => 'attachments'
		),
		
		
		
	);		
	summon_mage_form_fields(array('id'=>$post->ID,'class'=>'pform','title'=>'Optional Elements','content'=>'Below are some commonly used fields for posts and pages that can be included for post types that support them.'), $craft);	
	$cast = array(
		'tax_input'=>array(
			'name'=>'Custom Taxonomy',
			'type'=>'shortcode',
			'std'=>'tax_input',
			'code'=>'select',
			'parameters'=>'taxonomy="taxonomy_name"',
		),		
	);		
	summon_mage_form_fields(array('id'=>$post->ID,'class'=>'pform','title'=>'Custom Taxonomy','content'=>'For custom taxonomies, simply place the taxonomy slug within the <strong>taxonomy</strong> parameter. Front-end display options are available on the Mage Forms <strong>Help</strong> page.'), $cast);	
	$cast = array('custom_field_name'=>array(
			'name'=>'Custom Meta Field',
			'type'=>'shortcode',
			'std'=>'custom_field_name',
			'code'=>'text'
		),	
	);		
	summon_mage_form_fields(array('id'=>$post->ID,'class'=>'pform','title'=>'Custom Meta','content'=>'Simply type in a custom meta fields name using the <strong>name</strong> parameter in an element to update a custom meta field, or create it if it doesn\'t exist. Currently only works with <strong>[text]</strong> elements.'), $cast);	
	$cast = array(
			'post_category'=>array(
			'name'=>'',
			'type'=>'shortcode',
			'std'=>'post_category',
			'label' => 'Default Category',
			'code'=>'text',
			'parameters'=>'value="1" type="hidden"',
		),	'submit_custom_meta'=>array(
			'name'=>'',
			'type'=>'shortcode',
			'std'=>'submit_custom_meta',
			'label' => 'Adding Meta Values',
			'code'=>'text',
			'parameters'=>'value="Sent with Form #2" type="hidden"',
		),	
	);		
	summon_mage_form_fields(array('id'=>$post->ID,'class'=>'pform','title'=>'Setting Default Category & Values','content'=>'If you want a form to submit content into specific categories by default, or would like to include specific values without user input, you can set the value in a <strong>[text]</strong> element and simply hide it. Here is how you would set a form to always submit posts into category, using the category ID in the value.'), $cast);
	do_action('after_mage_scroll_cast');
	//echo !empty($form)? mage_solve_form(false, $form, array(),'dump') : '';
}

function summon_scrolls() {
	add_meta_box('mage_scroll_cast', esc_html__( 'Form Elements', 'mage-forms' ), 'mage_scroll_cast', 'mage_form', 'normal','high');
	add_meta_box('mage_scroll_settings', esc_html__( 'Form Settings', 'mage-forms' ), 'mage_scroll_settings', 'mage_form', 'side','high');
	remove_meta_box('wpseo_meta', 'mage_form', 'normal');
	if (has_action('edit_form_advanced',array('post_type_generator', 'edit_form'))){
		remove_action('edit_form_advanced',array('post_type_generator', 'edit_form'));		
	}
	add_filter('manage_mage_form_posts_columns', 'mage_forms_column_titles');  	
	add_filter( 'manage_mage_form_posts_custom_column', 'mage_forms_column_data', 10, 2);
	if( is_admin() && get_option('mage_forms_activation') == 'activated') {
		delete_option('mage_forms_activation');
		flush_rewrite_rules();
	}	
	
}
function mage_forms_column_titles($columns) {
	unset($columns['date']);
    $columns['shortcode'] = 'Shortcode';
	$columns['form_type'] = 'Form Post Type';
	$columns['form_role'] = 'Role';
    return $columns;  
}
function mage_forms_column_data($column, $post_ID) {
	$cast = (array) maybe_unserialize( get_post_meta($post_ID,'mage_forms',true) );
	switch ($column) {
        case 'shortcode':
           $out = '<input type="text" value="[form id='.$post_ID.']" />';	
      	break;       
		case 'form_type':
			$post_type = isset($cast['mage_form_post_type'])? $cast['mage_form_post_type'] : '';
			$out = !empty($post_type)?'<strong>'.$post_type.'</strong>': '';	    	
      	break;		
		case 'form_role':
			$out = isset($cast['mage_form_post_type'])? $cast['mage_form_post_permission'] : '';	    	
      	break;	
    } 
	if (isset($out)) echo $out; 
} 
add_filter('mage_forms_vessel_output','mage_forms_hide_upload_fields',10,2);	
function mage_forms_hide_upload_fields($output,$args){
	if (in_array($args['type'],array('upload','attachments'))){
		if (!is_user_logged_in()) return '';
	}
	return $output;
}