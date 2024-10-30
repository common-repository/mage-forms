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
?>
<?php
if (!defined('MAGECAST_FORMS')) exit;
//$comp = mage_get_option('forms','mage_form_compatibility','0') != 1? '' : 'mage_';
$comp = '';
add_shortcode($comp.'form', 'mage_form');
add_shortcode($comp.'label', 'mage_form_label');
add_shortcode($comp.'text', 'mage_form_text');
add_shortcode($comp.'textarea', 'mage_form_textarea');
add_shortcode($comp.'select', 'mage_form_select');
add_shortcode($comp.'radio', 'mage_form_radio');
add_shortcode($comp.'checkbox', 'mage_form_checkbox');
add_shortcode($comp.'upload', 'mage_form_upload');
add_shortcode($comp.'attachments', 'mage_form_attachments');
add_shortcode($comp.'multicheck', 'mage_form_tags');
add_shortcode($comp.'submit', 'mage_form_submit');	
add_shortcode('mage_edit', 'mage_form_edit');
//add_shortcode('delete', 'mage_form_del');
add_shortcode('mage_cf', 'mage_get_custom_field');
function mage_get_custom_field( $atts, $content = null ) {
extract(shortcode_atts(mage_default_atts(array('id'=>0,'name'=>'','wrap'=>'div','itemprop'=>'','raw'=>0)), $atts));
global $post, $mage_form_settings;
$id = $id == 0? $post->ID : $id;
$settings = array('raw' => $raw , 'wrap' => $wrap, 'class' => $class,'style' => $style,'itemprop'=> $itemprop);
return mage_return_custom_field($id, $name, $settings);
}
function mage_form_edit( $atts, $content = null ){
	$args = shortcode_atts(mage_default_atts(array('blog'=>0, 'id'=>0),'link'), $atts);
	global $post;
	$type = $post->post_type;
	$post_type_object = get_post_type_object( $type );
	
	$current_user = wp_get_current_user();
  	if ($post->post_author != $current_user->ID) {
	if (!current_user_can( $post_type_object->cap->edit_post, $post->ID )) return;
  	}
	$name = !empty($args['name'])? $args['name'] : 'edit-'.$type;
	$url = ($args['id'] != 0)? get_permalink( $args['id'] ): get_permalink(get_page_by_path( $name ));
	$args['href'] = wp_nonce_url($url.'?pid='.$post->ID);
	$args['class'] = magex($args['class'],'',' mage-edit',' mage-edit');
	$content = empty($content)? 'Edit' : do_shortcode($content);	
	$link = bind($content,$args);
	return $link;
}
function mage_custom_features( $atts, $content = null ){
	extract(shortcode_atts(mage_default_atts(array('rows'=>5,'wrap'=>'ul','append'=>'','prepend'=>'')), $atts));
	global $post;
	$style=magex($style,'style="','" ');
	$class = magex($class,'class="','" ');
	$output = '';
	$fields = (array) maybe_unserialize(get_post_meta($post->ID,'features',true));
	$row = 0;
	if (empty($fields)) return '';
	foreach($fields as $field => $var){
		if (!empty($field) && !empty($var) && mage_prefix($field)){
			$row++;
			$field = str_replace('mage_','',$field);
			$output .= '<li><strong>'.$field.':</strong> '.$var.'</li>';
			if ($row == $rows || $row % $rows == 0) $output .='</'.$wrap.'><'.$wrap.'>';
		}
	}
	if (!empty($output)) return $prepend.'<'.$wrap.' '.$class.$style.'>'.$output.'</'.$wrap.'>'.$append;
}
function mage_form( $atts, $content = null ){
extract(shortcode_atts(mage_default_atts(array('id'=>'','name' => '','action'=>'','nonce'=>'','method'=>'post','submit'=>'','title'=>'')), $atts));
global $userdata, $post, $mage_form_settings;
$output = $classes = $nonce_field = '';
$update = 0;
$attr = array();
$user = wp_get_current_user();
do_action('before_mage_form_element');
$mage_form = !empty($id)? get_post($id,'OBJECT','raw') : false;
if (is_object($mage_form)) {
	$mage_form_settings['id'] = $id;
	$cast = (array) maybe_unserialize(get_post_meta($id,'mage_forms',true));
	$type = isset($cast['mage_form_type'])? (string) trim($cast['mage_form_type']) : 'pform';
	$mage_form_settings['show_title'] = isset($cast['mage_form_title'])? $cast['mage_form_title'] : true;
	$form_title = $mage_form_settings['show_title']? $mage_form->post_title: '';
	$title = empty($form_title)? $title: $form_title;
	$mage_form_settings['custom_fields'] = isset($cast['mage_form_custom_fields']) ? $cast['mage_form_custom_fields'] : 0;
	$mage_form_settings['upload_count'] = isset($cast['mage_form_upload_count']) ? $cast['mage_form_upload_count'] : 5;
	$output .= $mage_form->post_content;
	$slug = $name = $mage_form->post_name;
	$update = isset($_REQUEST['pid'])? (int) $_REQUEST['pid']: (int) 0;
	$nonce = empty($nonce)? 'nonce_'.$slug : $nonce;
	$submit = empty($submit)? 'submit_'.$id:$submit;
	$name = empty($name)? 'form_'.$slug:$name;
	$attr['post_type'] = isset($cast['mage_form_post_type'])? $cast['mage_form_post_type'] : 'post';
	$attr['post_status'] = isset($cast['mage_form_post_status'])? $cast['mage_form_post_status'] : 'pending';
	$attr['post_author'] = isset($cast['mage_form_post_author'])? $cast['mage_form_post_author'] : 0;
	$attr['user_role'] = isset($cast['mage_form_post_permission'])? $cast['mage_form_post_permission'] : 'administrator';
	$attr['post_redirect'] = isset($cast['mage_form_post_redirect'])? $cast['mage_form_post_redirect'] : 0;
	$attr['post_email'] = isset($cast['mage_form_post_email']) ? $cast['mage_form_post_email'] : '';
	$attr['post_custom_fields'] = isset($cast['mage_form_custom_fields']) ? $cast['mage_form_custom_fields'] : 1;
	//$attr['upload_count'] = isset($cast['mage_form_upload_count']) ? $cast['mage_form_upload_count'] : 5;
	if ($attr['user_role'] !== 'public'){
		if (!is_user_logged_in()) return __('This page is restricted. Please login to view this page.','mage-forms');
		if (!mage_form_permission_cap($attr['user_role'])) return __('You do not have sufficient permission to use this form.','mage-forms');
	}
	$attr = apply_filters('mage_forms_attr',$attr,$cast);
} elseif (!empty($content)) $output .= $content;
do_action('after_mage_form_element');
if (empty($output)) return '';
$nonce_field = wp_nonce_field($nonce,'_wpnonce',true,false);
$form = $_POST;

if ($update !== 0){
	$data = get_post( $update );
	if (!$data) return 'Invalid post';
	if (!current_user_can( 'edit_post',$update))return 'You are not allowed to edit';
	if (isset($_REQUEST['action']) && $_REQUEST['action'] == "del" ) {
		check_admin_referer( 'mage_attach_del' );
		$attach_id = (int) $_REQUEST['attach_id'];
		if ($attach_id) wp_delete_attachment($attach_id);     
	}
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($form[$submit]))wp_die( __( 'Submit button and form mismatch.' ));  
if (isset($form[$submit])) {
	$verify = $form['_wpnonce'];
	if (!wp_verify_nonce($verify,$nonce))wp_die( __( 'An error occurred while trying to verify ('.$verify.') with '.$nonce.' your submission. Please try again.' ));  
	$args = mage_solve_form($id, $output, $form);    
	$result = mage_submit_form($args,$attr,$update);
	if ($result !== 'update'){
		return mage_alerts($result);
	}
}
$style=magex($style,'style="','" ');
$title = !empty($title)? '<legend class="mage-form-legend">'.$title.'</legend>': '';
$class = magex($class,'class="',' mage-form-'.$type.' '.$classes.'" ', 'class="form-horizontal mage-form mage-form-'.$type.' '.$classes.'" ');
return '<form action="'.$action.'" id="'.$name.'" method="'.$method.'" '.$class.$style.' enctype="multipart/form-data">'.$title.'<fieldset class="mage-form-fieldset">'.$nonce_field.do_shortcode($output).'</fieldset></form>';
}
function mage_form_label( $atts, $content = null ){extract(shortcode_atts(mage_default_atts(array('name' => '','req'=>0, 'for'=>'')), $atts));
	$style=magex($style,'style="','" ');
	$class = magex($class,'class="',' mage-form-label" ','class="mage-form-label"');
	$req = ($req!=0)? '<sup>*</sup>':'';
	$label = $name;
	if (!empty($content)) {
		$content = do_shortcode($content);
		$name = empty($name) ? cog($content) : cog($name);
		$label = $content;
	}
	$for = empty($for) ? cog($name) : cog($for);
	return '<label for="'.$for.'" '.$class.$style.'>'.$label.$req.'</label>';
}
function mage_form_text( $atts, $content = null ){
	$attr = shortcode_atts(mage_default_atts(array('type'=>'text','taxonomy'=>''),'form'), $atts);
	global $post, $mage_form_settings;
	$attr['wp'] = $mage_form_settings['custom_fields'];
	//return $attr['wp'];
	$attr['value'] = fill($attr);
	if (($attr['name'] == 'tax_input') && !empty($attr['taxonomy']) && !empty($atts['value'])) {
		$attr['name'] = 'tax_input['.$attr['taxonomy'].'][]';
		return '<input type="hidden" name="'.$attr['name'].'" value='.$atts['value'].' />';
	}	
	$output = vessel($content,$attr, $post);
	return $output;
}
//message textfield
function mage_form_textarea( $atts, $content = null ){
$attr = shortcode_atts(mage_default_atts(array(
'rows'=>8,
'cols'=>40,
'rich'=>1,
'wpautop'=>true,
'media_buttons'=>false,					
'tabindex' => -1,
'teeny' => false,
'dfw' => false,
'tinymce' => false,
'quicktags'=>true, ),'form'), $atts);
global $post;
$attr['type'] = 'textarea';
$attr['value'] = fill($attr);
return vessel($content,$attr, $post);
}

function mage_form_radio( $atts, $content = null ){
	$attr = shortcode_atts(mage_default_atts(array('options' => '','default'=>'','taxonomy'=>''),'form'), $atts);
	global $post;
	$attr['type'] = 'radio';
	$attr['value'] = fill($attr);
	return vessel($content,$attr, $post);
}
function mage_form_checkbox( $atts, $content = null ){
	$attr = shortcode_atts(mage_default_atts(array('options' => '','default'=>'','taxonomy'=>''),'form'), $atts);
	global $post;
	$attr['type'] = 'checkbox';
	$attr['value'] = fill($attr);
	return vessel($content,$attr, $post);
}	
function mage_form_submit($atts, $content = null) {
$args = shortcode_atts(mage_default_atts(array('wrap'=>'button','label'=>'Submit'),'link'), $atts);
$args['class'] = magex($args['class'],'',' button mage-form-element mage-form-submit','button mage-form-element mage-form-submit');
$args['type'] = 'submit';
$content = magex($content,'','',$args['label']);
$args['name'] = cog($args['name']);
if(empty($args['name'])) {
	global $mage_form_settings;
	$args['name'] = 'submit_'.$mage_form_settings['id'];
}
if ($args['wrap'] == 'input'){
	$style = !empty($args['style'])? 'style="'.$args['style'].'" ' : '' ;
	$class = !empty($args['class'])? 'class="'.$args['class'].'" ' : '' ;
	$button = '<input '.$class.$style.' value="'.$content.'" id="'.$args['name'].'" name="'.$args['name'].'" type="'.$args['type'].'" />';
} else {
	$button = bind($content,$args);
}
	return $button.'<span class="help-block mage-help-block">Please fill out the required fields.</span>';
}
function mage_form_upload( $atts, $content = null ){
$attr = shortcode_atts(mage_default_atts(array('label'=>'','button'=>'Upload','name'=>'post_thumbnail'),'form'), $atts);
global $post;
$attr['type'] = $attr['id'] =  'upload';
$attr['class'] = magex($attr['class'],'',' btn', 'btn');
$attr['value'] = fill($attr);
return vessel($content,$attr, $post);
}
function mage_form_attachments( $atts, $content = null ){
$attr = shortcode_atts(mage_default_atts(array('label'=>'','button'=>'Upload Attachment','name'=>'post_attachments','limit'=>''),'form'), $atts);
global $post, $mage_form_settings;
$attr['type'] = $attr['id'] =  'attachments';
$attr['class'] = magex($attr['class'],'',' btn', 'btn');
$attr['value'] = fill($attr);
$attr['limit'] = empty($attr['limit'])? $mage_form_settings['upload_count']: $attr['limit'];
return vessel($content,$attr, $post).'<input type="hidden" id="mage_upload_limit" name="mage_upload_limit" value="'.$attr['limit'].'" />';
}

function mage_form_select($atts, $content = null) {	
$edit = $output = $value = $set = '';
global $post;
$args = shortcode_atts(array(
	'get'=>'',
	'request'=>'',
	'post'=>'',
	'action'=>'',
	'val'=>'',
	'value'=>'',
	'value_field' => 'term_id',
	'multiple'=>0,
	'input'=>'select',
	'show_option_all'    => '',
	'show_option_none'   => '',
	'orderby'            => 'ID', 
	'order'              => 'ASC',
	'show_count'         => 0,
	'hide_empty'         => 0, 
	'child_of'           => 0,
	'exclude'            => '',	
	'selected'           => 0,
	'hierarchical'       => 0, 
	'name'               => 'cat',
	'id'                 => '',
	'class'              => 'postform mage-form-element',
	'depth'              => 0,
	'tab_index'          => 0,
	'options'          => '',
	'taxonomy'           => '',
	'label'=>'',
	'sub_label'=>false,
	'req' => 0,
	'hide_if_empty'      => false), $atts);	
	$args['echo'] = 0;	
	$special_select = false;
	if ($args['req'] == 1)$args['class'] .= ' mage-required';
	$args['id'] = empty($args['id'])? $args['name'] : $args['id'];
	if ((($args['name'] == 'tax_input') && !empty($args['taxonomy'])) || ($args['name'] == 'post_category')) {
		if ($args['name'] == 'post_category'){
			$args['taxonomy'] = 'category';
		}
		$args['name'] = 'tax_input['.$args['taxonomy'].'][]';
		$edit .= '<input type="hidden" id="select-taxonomy" name="select-'.$args['taxonomy'].'" value="'.$args['taxonomy'].'" />';
		$special_select = true;
	}
	if (!empty($args['get'])) $value = isset($_GET[$args['get']])? $_GET[$args['get']]:'';	
	if (!empty($args['post'])) $value = isset($_POST[$args['post']])? $_POST[$args['post']]:'';	
	if (!empty($args['request'])) $value = isset($_REQUEST[$args['request']])? $_REQUEST[$args['request']]:'';
	$value = !empty($args['value'])? $args['value'] : $value;
	if($special_select) { 
		do_action('before_mage_form_element');
		if (isset($_REQUEST['pid']) && !empty($_REQUEST['pid'])){
			$pid = $_REQUEST['pid'];
			$terms = get_the_terms($pid,$args['taxonomy']);
			if (is_wp_error($terms)) return '';
			if ($terms !== false) {
				$count = count($terms);
				if ($count > 1){
					$args['selected'] = ($terms[0]->parent == 0) ? $terms[0]->term_id: $terms[1]->term_id;
				} else {
					$args['selected'] = $terms[0]->term_id;
				}
				if ($args['selected'] != 0 && ($count > 1)){
					$id = ($terms[1]->parent != 0)? $terms[1]->term_id : $terms[0]->term_id;
					$child_1 = $child_2 = $args;
					$child_1['child_of'] = $args['selected'];
					$child_1['selected'] = $id;
					$edit .= '<div class="mage-clear"></div><label class="control-label mage-form-label"></label><div class="mage-form-select-container mage-'.$args['id'].'-container mage-'.$args['id'].'-container-child-1" data-level="1">'.wp_dropdown_categories($child_1).'</div>';
					if ($count > 2 && isset($terms[2])){
						$child_2['child_of'] = $id;
						$child_2['selected'] = $terms[2]->term_id;
						$edit .= '<div class="mage-clear"></div><label class="control-label mage-form-label"></label><div class="mage-form-select-container mage-'.$args['id'].'-container mage-'.$args['id'].'-container-child-2" data-level="2">'.wp_dropdown_categories($child_2).'</div>';
					}
				}
			}
		}				
		$selectbox = wp_dropdown_categories( $args );
		$req = ($args['req'] != 0)? '<sup>*</sup>':'';
		$label = !empty($args['label'])? '<label class="control-label mage-form-label" for="'.$args['name'].'">'.$args['label'].$req.'</label>' : '';
		$sub_label = $args['sub_label']? $args['sub_label']: '';
		if ($args['multiple']) $selectbox = preg_replace("#<select([^>]*)>#", "<select$1 multiple='multiple'>", $selectbox);
		
		$output = '<div class="mage-form-'.$args['id'].'-wrap mage-form-select-wrap mage-form-group" >'.$label.'<div class="mage-'.$args['id'].'-container '.$args['id'].'-0 mage-form-select-container" data-level="0">'.$selectbox.'</div>'.$edit.'<span class="mage-loading"><img src="'.MAGECAST_FORMS_SOURCE.'img/gear.png"  width="32" height="32" alt="Gear" /></span>
				<script type="text/javascript">jQuery(document).ready(function() {
					jQuery(".mage-form-'.$args['id'].'-wrap select").select2({
						adaptContainerCssClass: function (clazz) {
							return \'\';
						}							
					});
					jQuery(".mage-form-'.$args['id'].'-wrap").on("change", "select", function(){
						var select = jQuery(this);
						var first = select.val();
						var children = select.parent().parent().find("#term-child").val();
						var level = select.parent().data("level")+1;
						jQuery.ajax({
							type: "post",
							url: "'.admin_url( 'admin-ajax.php' ).'",
							data: {
								action: "mage_summon_terms",
								term: first,
								child: children,
								tax: "'.$args['taxonomy'].'",
								nonce: "'.wp_create_nonce( 'scribe_nonce' ).'",
								level: level
							},
							beforeSend: function() {
								select.parent().next(".mage-loading").addClass("mage-loading-active");
								select.select2("enable", false);
							},
							complete: function() {
								select.parent().next(".mage-loading").removeClass("mage-loading-active");
								select.select2("enable", true);	
							},
							success: function(html) {
								select.parent().nextAll(".mage-'.$args['id'].'-container").each(function(){
									jQuery(this).remove();
								});
								if(html != "") {									
									select.parent().addClass("mage-form-select-has-child").parent().append("<div class=\'mage-clear\'></div><label class=\'control-label mage-form-label\' for=\'term-child-"+level+"\'>'.$sub_label.'</label><div class=\'mage-'.$args['id'].'-container mage-form-select-container\' id=\'term-child-level-"+level+"\' data-level=\'"+level+"\'></div>");
									select.parent().parent().find("#term-child-level-"+level).html(html).slideDown("fast");
									jQuery("#term-child-"+level).select2();
								}
							}
						});
					});
            	});</script></div>';
				do_action('after_mage_form_element');	
		} elseif($args['action'] == 'get-terms'){
			$terms = get_the_terms($value,$args['taxonomy']);
			if (is_wp_error($terms)) return '';
			if ($terms == false) return $args['taxonomy'];
			$count = count($terms);
			if ($count > 1){
       			$args['selected'] = ($terms[0]->parent == 0) ? $terms[0]->term_id: $terms[1]->term_id;
			} else {
				$args['selected'] = $terms[0]->term_id;
			}
			if ($args['selected'] != 0 && ($count > 1)){ 
				$id = ($terms[1]->parent != 0)? $terms[1]->term_id : $terms[0]->term_id;
				$edit .= '<input type="hidden" id="term-child" name="taxonomy-child" value="'.$id.'" />';
			}
			$output = wp_dropdown_categories( $args ).$edit.'<div class="loading"></div>';
		} else {
			$args['type'] = 'select';
			$output = vessel($content,$args,$post);		
		}		
	return $output;		
}
function mage_form_tags($atts, $content = null) {
	$attr = shortcode_atts(mage_default_atts(array('type'=>'hidden','create'=>0,'value'=>'','prepend'=>'','append'=>'','icon'=>'', 'disabled'=> 0,'label'=>'Upload Attachment','id'=>0),'form'), $atts);
//extract(shortcode_atts(mage_default_atts(array('create'=>0,'value'=>'','prepend'=>'','append'=>'','icon'=>'', 'disabled'=> 0,'label'=>'Upload Attachment','type'=>'','id'=>0),'form'), $atts));
	//$class= magex($class,', containerCssClass:\'','\'');
	//$style=magex($style,'style="','" ','style="width:100%"');
	global $post;
	$class= isset($attr['class'])? magex($attr['class'],', containerCssClass:\'','\'') : '';
	$pid = $blog_id = $input = $saved = '';
	if (isset($_REQUEST['pid']) && !empty($_REQUEST['pid'])){
			$pid = $_REQUEST['pid'];
	}
	$name = cog($attr['name']);
	$id = empty($attr['id'])? $name : $attr['id'];
	do_action('before_mage_form_element');
	$tags = !empty($pid)? wp_get_post_tags($pid) : '';
	if (!empty($tags)){ 
		$tag_arr = array();
		foreach ($tags as $tag){
			$tag_arr[] = $tag->name;
		}
		$input = implode(',',$tag_arr);
	} 
	$attr['value'] = $input;
	$attr['type'] = 'hidden';
	$output = vessel($content,$attr, $post);	
	$output .= '<script type="text/javascript">
								jQuery(document).ready(function() {
									jQuery("#'.$id.'").select2({
									adaptContainerCssClass: function (clazz) {
										return \'\';
									},	
									initSelection : function (element, callback) {
										var data = [];
										jQuery(element.val().split(",")).each(function () {
											data.push({id: this, text: this});
										});
										callback(data);
									},
									tags:['. mageform_get_tags().'],
                      				//tokenSeparators: [","]'.$class.'
									});
									'.$saved.'
                                });</script>';
	do_action('after_mage_form_element');
  	return $output;
}
function mage_form_custom_fields( $atts, $content = null ){
	extract(shortcode_atts(mage_default_atts(array('date'=>0),'form'), $atts));
	global $post;
	$output = $sep = '';
	$row = 0;
	$request = isset($_REQUEST['pid'])? $_REQUEST['pid']:'';
	$cast = array();
	// date starts here
	$attr = array('type'=>'text');
	//$fields = !empty($request)?(array) maybe_unserialize(get_post_meta($request,'features',true)):'';
	$cast = maybe_unserialize(get_post_meta($request,'features',true));
	$field_key = 'custom_field';
	$sep = ':';
	if (!empty($cast)){
		foreach($cast as $field => $var){
			if (!empty($field) && mage_prefix($field)){			
				$field = str_replace('mage_','',$field);
				if (!empty($field)){
					$row++;
					$attr['name'] = $field_key.'_key_'.$row;
					$attr['value'] = $field;
					$attr['readonly'] = 1;				
					$key = vessel($content,$attr, $post);
					$attr['name'] = $field_key.'_value_'.$row;
					$attr['value'] = $var;
					$attr['readonly'] = 0;
					$val = vessel($content,$attr, $post);
					$output .= $key.$sep.$val.'<br />';
				}
			}
		}
	}
	if (empty($output)) {
		$row = 1;
		$attr['value'] = '';	
		$attr['name'] = $field_key.'_key_'.$row;	
		$attr['readonly'] = 0;		
		$key = vessel($content,$attr, $post);
		$attr['name'] = $field_key.'_value_'.$row;	
		$label = $date? '<label class="mage-form-label">From:</label>' : '';
		$val = vessel($content,$attr, $post);
		$output .= $label.$key.$sep.$val.'<br />';
		
	}
	$args = array('onClick'=>'return add_custom_field();','id'=>'add-custom-field','wrap'=>'button','wrap'=>'button', 'data-row'=>$row,'type'=>'','class'=>'','name'=>'');
	$button = bind('<i class="icon-plus"></i>',$args, true);
	return '<div id="custom-fields-wrap">'.$output.'</div>'.$button.'
			<script type="text/javascript">
			function add_custom_field(){							
				var custom_fields = jQuery(\'#custom-fields-wrap\');	
				var add_field = jQuery(\'#add-custom-field\');
				var field_num = add_field.data("row")+1;				
				add_field.data("row",field_num);
				add_field.attr("data-row",field_num);
				custom_fields.append(\'<input type="text" id="'.$field_key.'_key_\'+field_num+\'" name="'.$field_key.'_key_\'+field_num+\'" value="">'.$sep.'<input type="text" id="'.$field_key.'_value_\'+field_num+\'" name="'.$field_key.'_value_\'+field_num+\'" value=""><br />\');
				return false;
			}</script>';
}