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
add_action('init', 'create_mage_forms');
//add_filter( 'mage_capture_types', 'mage_type_support_mage_form', 10);
//add_filter( 'mage_capture_mage_form', 'mage_process_form', 10, 2 );
add_filter('mage_default_atts', 'mage_form_default_atts', 10, 1 );
add_action( 'init', 'mage_wp_redirect_start' );
add_action( 'save_post', 'mage_capture_mage_forms', 10, 2 );
function mage_capture_mage_forms( $id, $post ){
	global $pagenow;	
	$post_type = $post->post_type;	
	$post_obj = get_post_type_object($post_type);
	if ( 'post.php' != $pagenow )return $id;
	if (!current_user_can($post_obj->cap->edit_post, $id) || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE))return $id;	
	$nonce = 'mage_'.$post_type.'_save';	
	if (!isset($_POST['_wpnonce_'.$nonce]) || !wp_verify_nonce($_POST['_wpnonce_'.$nonce ], $nonce)) return $id;	
	$form = $_POST;
	// form settings
	$cast = array();
	$fields = array(//'form_type'=>array('type'=>'text'),
		'form_post_status'=>array('type'=>'text'),
		'form_post_type'=>array('type'=>'text'),
		'form_post_permission'=>array('type'=>'text'),
		'form_post_author'=>array('type'=>'text'),
		'form_post_email'=>array('type'=>'email'),
		'form_post_redirect'=>array('type'=>'text'),
		'form_upload_count' =>array('type'=>'text'),
		'form_custom_fields'=>array('type'=>'checkbox'), // temporary feature
	
		//'form_post_edit'=>array('type'=>'checkbox'), // unknown
	);
	$fields = apply_filters('mage_capture_mage_forms_fields',$fields);
	foreach($fields as $field => $val) {
		if ($val['type'] == 'checkbox'){
			if (isset($form['mage_'.$field])){
				$cast['mage_'.$field] = $form['mage_'.$field] == '1'? '1':'0';
			} else {
				$cast['mage_'.$field] = '';
			}
		} else {
			$cast['mage_'.$field] = isset($form['mage_'.$field])? $form['mage_'.$field]:'';
		}
	}
	update_post_meta($id,'mage_forms',$cast);
}
function mage_form_permission_cap($cap = false){
	$caps = array(
		'administrator'=>'manage_options',
		'editor'=>'edit_others_posts',
		'author'=>'publish_posts',
		'contributor'=>'edit_posts'
		);
	if($cap === 'subscriber' && is_user_logged_in()) return true;
	if(isset($caps[$cap])) return current_user_can($caps[$cap]);
	return current_user_can($cap);
}
function mage_wp_redirect_start() {
    ob_start();
}
function replace_mage_upload_text($translated_text, $text, $domain) { 
    if ('Insert into Post' == $text) { 
        $referer = wp_get_referer(); 
        if ( $referer != '' ) {return __('Use This Image', 'mage-core'); } 
    }  
    return $translated_text;  
}
function summon_mage_form_fields($args = array('id'=>'','nonce'=>'','class'=>'','style'=>'','title'=>'','content'=>''), $craft = array(), $pos=1){
	$fields = array();
	global $post;
	$id = empty($args['id'])? $post->ID : $args['id'];	
	$cast = (array) maybe_unserialize(get_post_meta($id,'mage_forms',true));
	foreach ($craft as $key => $field){
		$var = 'mage_'.$key;
		//$fields[$var] = isset($cast[$var])? $cast[$var]:'';
		if (isset($cast[$var])){
			$fields[$var] = $cast[$var];
		} else {
			$fields[$var] = isset($field['std'])? $field['std'] : '';
		}
	}	
	$output = !empty($args['nonce'])? wp_nonce_field('mage_'.$args['nonce'], '_wpnonce_mage_'.$args['nonce'], true, false ): ''; 
	$style= !empty($args['style'])? magex($args['style'],'style="','" '):'';
	$class = !empty($args['class'])? magex($args['class'],'class="','" '):'';	      
    $output .= '<div '.$style.$class.'>';
	$output .= !empty($args['title'])? '<legend class="mage-form-legend">'.$args['title'].'</legend>' :'';
	$output .= !empty($args['content'])? '<p class="mage-form-content">'.$args['content'].'</p>' :'';
	foreach ($craft as $key => $field){			
			$sc= 'style="color: #000; font-weight: bold; margin-right:15px; display: block; width: 100px;" ';
			$var = 'mage_'.$key;
			$type = isset($field['type'])? $field['type']:'';
			$class = isset($field['class'])? $field['class']:'';
			$default = isset($field['std'])? $field['std']:'';	
			$name = isset($field['group'])? $field['group']:$key;	
			$options = isset($field['options'])? $field['options']:array();	
			// if !isset()?
			//$value = !empty($fields[$var])? $fields[$var]:'';
			$value = $fields[$var];
			$label_class = $pos==1?'class="col-lg-2 control-label"':'class="mage-side-label" ';
			$input_class = $pos==1?'':'class="form-control" ';
			$row_class = $pos==1?'class="form-group '.$class.'" ':'class="form-group mage-form-row '.$class.'" ';
			$label = '';
			if (isset($field['label']) && !empty($field['label'])){
				$label = $field['label'] == false? '' : esc_html($field['label']);
			} elseif (isset($field['name'])) {
				$label = ucfirst(esc_html($field['name']));
			}
			$output .= '<div '.$row_class.'>';
			$output .= !empty($label)? '<label for="'.$var.'" '.$label_class.'>'.$label.'</label>':'';
			if (in_array($type, array('text','email','password','number'))){	
				//$value = !empty($value)? $value:$default;
				if ($type == 'email'){				
					$output .= '<div class="input-group"><span class="input-group-addon">@</span>';
				}
				$number_atts = '';
				if ($type == 'number') {
					$number_atts = 'min="'.$field['min'].'" max="'.$field['max'].'" ';
				}
				//$maxlength = ($type == 'number')? 'maxlength="2"' : '';
				$output .= '<input type="'.$type.'" '.$input_class.' value="'.$value.'" id="'.$var.'" name="'.$var.'" '.$number_atts.'/>';
				if ($type == 'email'){
					$output .= '</div>';
				}
			} elseif ($type=='radio') {
				$output .= '<div class="btn-group" data-toggle="buttons">';
					foreach ($field['options'] as $key => $option) {	
						//$value = !empty($value)? $value:$default;
						$checked=($value== $key)? 'checked="checked"' :'';		
						$active =($value== $key)? ' active' :'';
						$output .= '<label class="btn btn-xs btn-silver '.$class.$active.'" for="' .esc_attr($var). '"><input class="of-input of-radio" type="radio" name="' . esc_attr($var) . '" id="' . esc_attr($key) . '" value="'. esc_attr( $key ) . '" '.$checked.' />' .$option. '</label>';
					}
				$output .= '</div>';		
			} elseif ($type=='checkbox') {				
				if (isset($field['options']) && is_array($field['options'])){
					foreach ($options as $key => $option) {	
						//$value = !empty($value)? $value:$default;
						if (is_array($value)) {
							if (in_array($key,$value)) {
								$checked = 'checked="checked"' ;	
								$active = ' active';	
							}
						} else {
							if ($key == $value) {
								$checked = 'checked="checked"' ;	
								$active = ' active';	
							}
						}
						$output .= '<button type="button" class="options btn '.$class.$active.'" for="' . esc_attr($var). '"><input class="of-input mage-checkbox" type="checkbox" name="' . esc_attr($var) . '" id="' . esc_attr($key) . '" value="'. esc_attr( $key ) . '" '.$checked.' />' .$option. '</button>';
					}
				} else {
					//$value = !empty($value)? $value:'';	
					$checked= $value? 'checked="checked"' :'';	
					$active= $value? ' active' :'';	
					$output .= '<div class="mage-switch"><input type="checkbox" name="' . esc_attr($var) . '" id="' . esc_attr($var) . '" '.$checked.' value="1"><label><i></i></label></div>';
				}
			} elseif ($type=='select') {			
				$output .= '<select '.$input_class.' name="' . esc_attr($var) . '" id="' . esc_attr($key) . '">';
					if (!is_array($options) && (strpos($options,',') !== false)) {
						$options = explode(',',$options);
						$new_options = array();
						foreach ($options as $option){
							$new_options[cog($option)] =$option;
						}
						$options = $new_options;
					}
					if (is_array($options)){
						foreach ($options as $key => $option) {	
							//$value = !empty($value)? $value:$default;
							$selected = ($value== $key)? ' selected="selected"' :'';
							$output .= '<option'. $selected .' value="'. esc_attr( $key ) . '" >' .esc_html( $option ). '</option>';
						}
					}
				$output .= '</select>';		
			} elseif ($type=='textarea'){
				$rows = isset($field['rows'])? $field['rows'] : '8';
				$val = stripslashes($value);
				$output .= '<textarea id="' . esc_attr($var) . '" class="of-input span8" name="' . esc_attr($var) . '" rows="' . $rows . '">' . esc_textarea( $val ) . '</textarea>';
				
			} elseif ($type=='shortcode'){
				$code = isset($field['code'])? $field['code'] : 'textarea';
				//$comp = mage_get_option('forms','mage_form_compatibility','0') != 1? '' : 'mage_';
				$comp = '';
				$output .= '<code>['.$comp.$code;
				if ($code == 'form'){
					$output .= ' id='.$id;
				} elseif($code == 'submit') {
					$output .= ' name="submit_'.$post->ID.'"';
				} elseif($code == 'mage_edit') {
					$output .= ' id=#';
				} else {
					$output .= ' name="'.$default.'"';
				}
				$output .= !empty($field['name'])?  ' label="'.$field['name'].'"' : '';
				if(in_array($default, array('post_title','post_content'))){
					$output .= ' req=1';
				}
				$output .= isset($field['parameters'])? ' '.$field['parameters'] :'';
				$output .= ']</code>';
			} elseif ($type=='verify'){
				$req = (isset($field['req']) && $field['req']==1)? '<code>*</code>':'';
				$output .= '<label><strong>'.$field['name'].'</strong> ('.$default.')'.$req.':</label>name: <code>'.$key.'</code>';				
			}
			$output .= '</div>';
		}
	$output .= '</div>';
	echo $output;
}
function vessel($content='',$args=array(), $post = null) {
	if (empty($args)) return '';		
	$args = mage_default_atts($args,'form');	
	$output = $attributes = $icon = $prepend = $append = $add = '';	
	$input = trim($args['type']);	
	$value = !empty($args['value'])?  $args['value'] : do_shortcode($content);
	$action = !empty($args['action'])? $args['action'] : '';
	$default = !empty($args['default'])? $args['default'] : '';
	$options = !empty($args['options'])? $args['options'] : '';
	$label = !empty($args['label'])? $args['label'] : '';	
	// new test
	$color = !empty($args['color'])? 'btn-'.$args['color'] : 'btn-red';	
	// test end
	$name = $args['name'] = !empty($args['name'])? cog($args['name']): cog($label);	
	$args['id'] = empty($args['id'])? $name : $args['id'];
	if (isset($_GET[$name])) $value = $_GET[$name];
	elseif (isset($_POST[$name])) $value = $_POST[$name];
	elseif (isset($_REQUEST[$name])) $value = $_REQUEST[$name];
	$req = isset($args['req']) && $args['req']? '<sup>*</sup>' : '';
	if ($args['req'] == 1)$args['class'] .= ' mage-required';
	if ($args['disabled'] == 1 || $args['readonly'] == 1)$args['class'] .= ' uneditable-input disabled';
	$args['class'] .= ' form-control mage-form-element';
	foreach($args as $arg => $val){
		if (!empty($val)):
		$val = trim($val);
		if (in_array($arg,array('title','maxlength','onClick','name','style','class','id','rows','cols','disabled','readonly','min','max'))) { 
			if (in_array($arg,array('disabled','readonly'))){
				$attributes .= magex($arg,$arg.'="','" ');
			} elseif (in_array($arg,array('id'))){
				if ($input !== 'radio') $attributes .= magex($val,$arg.'="','" ');
			} elseif (in_array($arg,array('name'))){
				if ($input !== 'checkbox') $attributes .= magex($val,$arg.'="','" ');
			} else {
				$attributes .= magex($val,$arg.'="','" ');
			}
		} elseif (in_array($arg,array('get','post','request'))) {
			if ($arg == 'get') $value = isset($_GET[$val])? $_GET[$val]:'';
			if ($arg == 'post') $value = isset($_POST[$val])? $_POST[$val]:'';
			if ($arg == 'request') $value = isset($_REQUEST[$val])? $_REQUEST[$val]:'';
		} elseif ($arg == 'icon') {
			$icon = magex($val,'<i class="icon-','"></i>');
		} elseif ($arg == 'placeholder') {
			$attributes .= $placeholder = magex($val,'placeholder="','" ');
			$attributes .= magex($placeholder,'data-',' ');
		} elseif (in_array($arg,array('prepend','append'))) {
			if ($val != 'icon')$icon = $val;
			if ($arg == 'prepend'){
					$add .= 'input-prepend ';
					$prepend = '<span class="add-on">'.$icon.'</span>';
			} 
			if ($arg == 'append'){
					$add .= 'input-append ';
					$append = '<span class="add-on">'.$icon.'</span>';
			}			
			$add .= $args['name'].' ';
			$prepend = '<div class="'.$add.'">'.$prepend;
			$append = $append.'</div>';			
		}		
		endif;
	}
	if (!empty($action)) $value = mage_output_actions($action, $value, $args['name'],$args['val'],$args['exclude']);
	$label = !empty($label)? '<label class="control-label mage-form-label" for="'.$args['name'].'">'.$args['label'].$req.'</label>' : '';
	$output = '<div id="mage-form-'.$args['id'].'-wrap" class="mage-form-group mage-form-'.$input.'-wrap">'.$label;
	if (in_array($input, array('text','hidden','email','password','number'))) {
		$type = magex($input,'type="','" ');
		$value = stripslashes_deep( $value );
		$value = empty($value)? 'value="" ' : 'value="'.do_shortcode($value).'" ';
		$output .= $prepend.'<input '.$type.$attributes.$value.' />'.$append;
	} else {
	switch($input){
		case 'textarea';
			$value = stripslashes_deep( $value );
			if ($args['rich'] != 1){
				$output .= '<textarea '.$attributes.'>'.$value.'</textarea>';
			} else {
				$settings = array(
					'wpautop'=> (bool) $args['wpautop'], 
					'media_buttons'=> (bool)$args['media_buttons'],
					'textarea_name' => $args['name'],					
					'textarea_rows' => $args['rows'],
					'tabindex' => $args['tabindex'], 
					'editor_css' => $args['style'], 
					'editor_class' => $args['class'], 
					'teeny' => (bool) $args['teeny'],
					'dfw' => (bool) $args['dfw']
					);
				$settings['tinymce'] = (is_string($args['tinymce']) && ($args['tinymce'] != 0) && (strpos($args['tinymce'],',') !== false)) ?(array)implode(',',$args['tinymce']) : (bool) $args['tinymce'];
				$settings['quicktags'] = (is_string($args['quicktags']) && ($args['quicktags'] != 0) && (strpos($args['quicktags'],',') !== false)) ?(array)implode(',',$args['quicktags']) : (bool) $args['quicktags'];
				ob_start();
				$output .= '<div class="mage-form-textarea">';
				wp_editor($value, $args['id'], $settings);
				$output .= ob_get_clean();
				$output .= '</div>';
			}		
		break;
		case 'upload';
			$attributes .= empty($value)? '' : 'style="display:none;" ';
			$output .= '<button id="mage-form-upload-button" '.$attributes.'>'.$args['button'].'</button><div class="mage-form-upload-thumbnail">'.$value.'</div>';
		break;
		case 'attachments';
			//$hide_attachmnent = !empty($value)? '' : 'style="display:none;" ';
			$output .= '<button id="mage-form-attach-button" '.$attributes.'>'.$args['button'].'</button><span class="help-block mage-form-info">'.__('Attachment upload limit reached.','mage-forms').'</span><ul class="mage-attachment-list"><script>window.MageFileCount = 0;</script>'.$value.'</ul>';
		break;
		case 'select';
			if (strpos($options,',') !== false) {
				$options = explode(',',$options);
				$value = array();
				foreach ($options as $option){
					$value[cog($option)] =$option;
				}
			}
			if(is_array($value)){
				$options = empty($args['placeholder'])? '' : '<option></option>';
				foreach ($value as $key => $val){
					$selected = ( $key == $args['selected'] ) ? ' selected="selected"' : '';
					$options .= '<option value="' . $key . '"' . $selected . '>' .$val.'</option>';
				}
				$value = $options;
			}
			$output .= '<select '.$attributes.'>'.$value.'</select>';
		break;
		case 'checkbox';	
			$veri ='';
			$args['selected'] = !empty($value)? $value:$default;
			$selected = array();
			if (!is_array($args['selected'])) {
				if (strpos($args['selected'],',') !== false)$args['selected'] = explode(',',$args['selected']);
			}
			if (is_array($args['selected'])) foreach ($args['selected'] as $select) $selected[] = cog($select);
			if (strpos($options,',') !== false) {
				$options = explode(',',$options);
				$value = array();
				foreach ($options as $option)$value[cog($option)] = $option;				
				if(is_array($value)){
					$options = '';
					foreach ($value as $key => $val){
						$checked = $active = '';
						$key = cog($key);						
						if (is_array($args['selected'])) {
							if (in_array($key,$selected)) {
								$checked = 'checked="checked"' ;	
								$active = ' active';	
							}
						} else {
							if ($key == $args['selected']) {
								$checked = 'checked="checked"' ;	
								$active = ' active';	
							}
						}
						$options .= '<label type="button" class="activator btn btn-small '.$color.$active.'" for="'.$name.'-' . esc_attr($key). '"><input '.$attributes.' name="'.$name.'[]" id="'.$name.'-' . esc_attr($key). '" style="top:0;left:0;width:100%;height:100%;opacity:0;position:absolute;" type="checkbox" value="'. esc_attr( $key ) . '" '.$checked.' />' .$val. '</label>';
					}	
					$value = $options;
					$output .= '<div class="btn-group mage-btn-group">'.$value.'</div>';
				}				
			} else {
					$value = !empty($value)? $value:false;	
					$checked= $value? 'checked="checked"' :'';	
					$option = '<input '.$attributes.' type="checkbox" '.$checked.' name="'.$name.'" value="yes" />';
					$label = !empty($args['label'])? '<label class="mage-switch-label">'.$args['label'].'</label>' : '';
					$value = '<div class="row-fluid"><div class="switch switch-'.$name.'">'.$option.'<label><i></i></label></div>'.$label.'</div>' ;
					$output .= $value;					
			}
			
		break;
		case 'radio';	
			$args['selected'] = !empty($value)? $value:$default;
			if (empty($options)) $options = !empty($value) ? $value : $content;				
			if (strpos($options,',') !== false)$options = explode(',',$options);			
			if($name == 'post_format' && current_theme_supports( 'post-formats' )){
				$post_formats = get_theme_support( 'post-formats' );
				$args['selected'] = !empty($value)? $value: (int) 0;
				$options = is_array($post_formats[0])? $post_formats[0] : array();
			}
			$value = array(0=>"Standard");
			foreach ($options as $option) $value[cog($option)] = ucfirst($option);	
			$radios = '';
			foreach ($value as $key => $val) {	
				$checked = $args['selected'] !== $key? '' : ' checked="checked"';		
				$active = $args['selected'] !== $key? '' : ' active' ;		
				
				$radios .= '<label class="btn btn-silver mage-form-element '.$active.'" for="' .esc_attr($name). '"><input class="mage-form-radio" type="radio" name="'.esc_attr($name).'" id="'.$name.'-'.esc_attr($key).'" value="'.esc_attr($key).'" '.$checked.' />' .$val. '</label>';
			}
			$output .= '<div class="btn-group mage-form-radio-buttons" id="mage_form_'.$name.'-opts" data-toggle="buttons">'.$radios.'</div>';	
		break;
		}
	}
	$output .= '</div>';
	return apply_filters('mage_forms_vessel_output',$output,$args);	
}
function mage_get_forms() {
	$args = array('post_type' => 'mage_form','numberposts'=>-1); 
    $array = array();
    $pages = get_posts($args);
    if ($pages) {
        foreach ($pages as $page) { $array[$page->ID] = $page->post_title; }
    }
    return $array;
}
function mage_solve_form($id, $content, $form=array(), $for = 'fields') {	
	$cast = array();	
	$fields = '/\[(?P<type>textarea|select|radio|text|checkbox|multicheck).*?(name=["\'](?P<name>.*?)["\'].*?|label=["\'](?P<label>.*?)["\'].*?|options=["\'](?P<options>.*?)["\'].*?|req=(?P<req>0|1).*?)+\]/i';
	$fields = apply_filters('mage_solve_formula', $fields);
	if (preg_match_all($fields, $content, $names)) {
		$i = 0;		
		foreach ($names['type'] as $field) {
			$name = $names['name'][$i];
			$attribute = $names['label'][$i];
			$label = $names['label'][$i];
			$options = $names['options'][$i];
			$req = $names['req'][$i];	
			if ($for == 'fields') {
				$cast[$name] = array('name'=>$name,'type'=>$field,'req'=>$req,'label'=>$label,'options'=>$options);
			} elseif ($for == 'save') {
				$cast['mage_'.$name] = isset($form['mage_'.$name])? $form['mage_'.$name]:'';
			} elseif ($for == 'post') {
				$cast['mage_'.$name] = isset($form[$name])? $form[$name]:'';
			} elseif ($for == 'verify') {
				$cast[$name] = array('name'=>$name,'std'=>$field,'type'=>'verify','req'=>$req);
			} 
			$i++;
		}
		return $cast;
	}
	return false;
}
function create_mage_forms() {
	register_post_type('mage_form', 
		array('label' => 'Forms',
		'public' => false,
		'menu_icon'=>MAGECAST_FORMS_SOURCE.'img/icon.png',
		'publicly_queryable' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'capability_type' => 'post',
		'capabilities' => array(
        	'publish_posts' => 'manage_options',
       		'edit_posts' => 'manage_options',
        	'edit_others_posts' => 'manage_options',
        	'delete_posts' => 'manage_options',
        	'delete_others_posts' => 'manage_options',
        	'read_private_posts' => 'manage_options',
        	'edit_post' => 'manage_options',
        	'delete_post' => 'manage_options',
        	'read_post' => 'manage_options',
    	),
		'has_archive' => false,
		'hierarchical' => false,
		'query_var' => true,
		'menu_position' => 27.1,
		'exclude_from_search' => true,
		'supports' => array('title','editor','revisions'),
		'register_meta_box_cb' => 'summon_scrolls',
		'labels' => array (
			'name' => 'Forms',
			'singular_name' => 'Form',
			'menu_name' => 'Mage Forms',
			'add_new' => 'Add Form',
			'add_new_item' => 'Add New Form',
			'edit' => 'Edit',
			'edit_item' => 'Edit Form',
			'new_item' => 'New Form',
			'view' => 'View Form',
			'view_item' => 'View Form',
			'search_items' => 'Search Forms',
			'not_found' => 'No Forms Found',
			'not_found_in_trash' => 'No Forms Here')
		) 
	);
	if ( is_admin() && get_option('mage_forms_activation') == 'activated' ) {
		$args = array(
			'post_title' => 'Submit A Post',
			'post_status'=>'publish',
			'post_type'=>'mage_form',
			'post_content' => '[text name="post_title" label="Title" req=1]
[textarea name="post_content" label="Content" req=1]
[upload name="post_thumbnail" label="Featured Image"]
[attachments name="post_attachments" label="Attachments"]
[select name="post_category" label="Category" req=1 show_option_none="Select a Category"]
[multicheck name="tags_input" label="Tags" req=1]
[submit]');
		$post_id = wp_insert_post($args);
		if ($post_id){
			$cast = array(
			'mage_form_post_status'=>'draft',
			'mage_form_post_type'=>'post',
			'mage_form_post_permission'=>'administrator',
			'mage_form_post_author'=>0,
			'mage_form_post_email'=>'',
			'mage_form_post_redirect'=>0,
			'mage_form_custom_fields' => 1,
			'mage_form_upload_count' =>'5',
			);
			add_post_meta($post_id,'mage_forms',$cast);
		}
        delete_option('mage_forms_activation');
    }
}
function mage_alerts( $errors ) {
	if (empty($errors)) return'';
	if (is_array($errors)){
    $alert = '<div class="mage-form-alert alert alert-error"><strong>'.__('Missing Fields','mage-forms').'</strong>';
	$alerts = array();
    foreach ($errors as $error)if ( !empty( $error ) ) $alerts[] = $error;
	$alert .= '<div class="mage-form-alert-errors">'.implode(', ', $alerts).'</div>';
	$alert .= '</div>';
	} else {
		$alert = '<div class="mage-form-alert alert alert-error">'.$errors.'</div>';
	}
    return $alert;
}
function mage_submit_form($fields,$attr,$update=0) {
	$user = wp_get_current_user();
	global $mage_form_settings;
	$data = array('custom_fields' => array(), 'errors'=>array(),'attr'=>$attr);        
	if (!empty($_FILES['mage_attachments']))$data['errors'] = scribe_sanitize_upload();
	$values = array(
		'post_title' => '',
		'post_content' => '',
		'post_excerpt' => '',
		'post_format'=>'',
		'post_name'=>'',
        'post_category' => array(),
        'tags_input' => '',
		'tax_input' => array()
	);
	$args = array();
	foreach ($values as $value => $val){
		if (isset($_POST[$value]) && !empty($_POST[$value])){
			if (!is_array($_POST[$value])){
				$args[$value] = trim(strip_tags($_POST[$value]));
			} else {
				foreach($_POST[$value] as $name => $input){
					$args[$value][$name] = !is_array($input)? trim(strip_tags($input)) : implode(',',$input);
				}
			}
			unset($fields[$value]);
		}
	}
	$format = !empty($args['post_format'])? $args['post_format']:'';
	$args['post_status'] = $attr['post_status'];
	$args['post_type'] = $attr['post_type'];	
	if (is_array($fields)) {
		foreach ($fields as $field) {
			$val = '';
			if ($field['type'] == 'checkbox'){					
				$val = $_POST[$field['name']];
				if ($val !== 'yes') $val = '';
				$data['custom_fields'][$field['name']] = $val;
			} elseif (array_key_exists($field['name'], $_POST)) {
				$val = $_POST[$field['name']];
				$val = is_array($val)? implode(',',$val): trim(strip_tags($val));     
				if (($field['req'] == 1 ) && empty($val)) {
					$name = is_array($field['name'])? implode(',',$field['name']): trim(strip_tags($field['name'])); 
					$data['errors'][] = $name;
				} else {
					$data['custom_fields'][$field['name']] = $val;
				}
			} //array_key_exists
		} //foreach
	}
	$data = apply_filters('mage_forms_submit_data',$data);
	if ($data['errors']) return $data['errors'];
	$attach_id = isset( $_POST['mage_img_upload'] ) ? $_POST['mage_img_upload']: false;
    do_action('before_mage_form_element');
	if ($update !== 0){
		$args['ID'] = $update;
		unset($args['post_type']);
		unset($args['post_status']);
	} else {		
		if ($data['attr']['post_author'] == 0 && $data['attr']['user_role'] == 'public') return __('Error: A Front End Form set to public must select a user for post author within the Forms settings.','mage-forms');
		if ($data['attr']['post_author'] !== 0) $user == get_user_by( 'id',$data['attr']['post_author']);
		if ($data['attr']['user_role'] !== 'public'){
			if (!is_user_logged_in()) return __('You must be logged in to post.','mage-forms');
			if ($data['attr']['post_author'] == 0 && !mage_form_permission_cap($data['attr']['user_role'])) return __('You do not have sufficient permissions to submit this post.','mage-forms');
		}
		if (!is_object($user)) return __('No user assigned.','mage-forms');
		$args['post_author'] = apply_filters('mage_form_post_user_id',$data['attr']['post_author']);
	}
	if (!isset($args['ID'])){
		if (empty($args['post_name'])) unset($args['post_name']);
		$post_id = wp_insert_post($args, mage_get_option('forms','mage_form_debug',false));
        if (!is_wp_error($post_id) && $post_id !== 0){
           	mage_upload_attachments($post_id);
			//if (!empty($format)) set_post_format( $id , $format);
           	if (isset($data['attr']['post_email']) && !empty($data['attr']['post_email'])) mage_form_send_email($data['attr']['post_email'],$user,$post_id );            
        	if (!empty($data['custom_fields'])) mage_add_post_meta($data['custom_fields'],$post_id, $data['attr']['post_custom_fields']);	
           	if ($attach_id) set_post_thumbnail( $post_id, $attach_id );
           	attach_file_to_post($post_id);
			$post_id = $data['attr']['post_redirect'] == 0? $post_id : $data['attr']['post_redirect'];
			wp_redirect(get_permalink( $post_id ));
			exit;
		} else {
			$error_string = is_wp_error($post_id) ? $post_id->get_error_message() : __('Your submission failed.', 'mage-forms');
			return $error_string;
			//return '<div class="alert alert-error">' . $error_string . '</div>';
		}
	} else {
		$post_id = wp_update_post($args);
		if ( $post_id ) {
			mage_upload_attachments($post_id);
			//if (!empty($format)) set_post_format( $id , $format);
			if ( $data['custom_fields'] ) mage_update_post_meta($data['custom_fields'],$post_id, $data['attr']['post_custom_fields']);	
            if ( $attach_id ) set_post_thumbnail( $post_id, $attach_id );
			attach_file_to_post($post_id);				
			echo '<div class="alert alert-success">'.__('Your submission has been updated succesfully.','mage-forms').'</div>';
			return 'update';
		}			
	}
	do_action('after_mage_form_element');
}
function mage_form_send_email($email,$user,$post_id ) {	
    $blogname = get_bloginfo( 'name' );
    $to = $email;
    $permalink = get_permalink( $post_id );
	$type = get_post_type($post_id);
    $headers = sprintf( "From: %s <%s>\r\n", $blogname, $to );
    $subject = sprintf( __( '[%s] New %s Submission' ), $blogname,$type );

    $msg = sprintf( __( 'A new %s has been submitted on %s' ),$type, $blogname ) . "\r\n\r\n";
	if (is_object($user)){
		$msg .= sprintf( __( 'Author : %s' ), $user->display_name ) . "\r\n";
		$msg .= sprintf( __( 'Author Email : %s' ), $user->user_email ) . "\r\n";
	} else {
		$msg .= sprintf( __( 'Author Email : %s' ), $user) . "\r\n";
	}
    $msg .= sprintf( __( 'Title : %s' ), get_the_title( $post_id ) ) . "\r\n";
    $msg .= sprintf( __( 'Permalink : %s' ), $permalink ) . "\r\n";
    wp_mail( $to, $subject, $msg, $headers );
}
function fill($attr) {	
	$request = isset($_REQUEST['pid']) && !empty($_REQUEST['pid'])? $_REQUEST['pid']:false;	
	if ($request && !empty($attr['name'])){
			if (in_array($attr['name'], array('post_title','post_content','post_excerpt'))) {
				return mage_output_actions('get-post', $request, $attr['name']);
			} elseif(in_array($attr['name'], array('post_thumbnail','post_attachments'))) { 
				return mage_output_actions('get-attachments', $request, $attr['name']);
			} else { 
				global $mage_form_settings;
				//if ($mage_form_settings['custom_fields'] == 1) {
				if (isset($attr['wp']) && $attr['wp'] ==1){
					return get_post_meta($request,$attr['name'],true);
				} else {
					return mage_output_actions('get-post-meta', $request, $attr['name']);
				}
			}	
	} elseif (empty($attr['request']) && !empty($attr['name']) && in_array($attr['action'], array('current-site','id-to-blogname'))) {
		return isset($_REQUEST['blog_id'])? $_REQUEST['blog_id']:'';
	} 
}
function mage_form_default_atts($types){
	$types['form']=array(
		'exclude'=>'',
		'name'=>'',
		'id'=>'',
		'get' => '',
		'post'=>'',
		'request'=>'',
		'maxlength'=>'',
		'type'=>'',	
		'req' => 0,
		'value'=>'',
		'placeholder'=>'',
		'disabled'=> 0,
		'label'=>'',
		'val'=>'',
		'rich'=>1,
		'readonly'=>0,
		'wpautop'=>true,
		'media_buttons'=>true,					
		'tabindex' => -1,
		'teeny' => true,
		'dfw' => false,
		'tinymce' => true,
		'quicktags'=>true
		);
	return $types;
}
function mage_output_actions($action = '', $value = '', $name = '',$val = '',$ex=null,$extra=0){
	if (empty($action)) return '';
	switch ($action) {
		case 'blogname':
		case 'current-site':
		case 'id-to-blogname':
			if (empty($value)) return '';
        	$details = is_object(get_blog_details($value))? get_blog_details($value): false;
			return ($details)? $details->blogname:'';
        break; 
		case 'get-post':     
				if (empty($value)) return '';  		
				$core = get_post($value, ARRAY_A);
				return isset($core[$name])? $core[$name]:'';			
        break;
		case 'get-post-meta':    
				if (empty($value)) return '';   		
				$cast = (array) maybe_unserialize( get_post_meta($value,'mage_forms',true) );
				return isset($cast['mage_'.$name])? $cast['mage_'.$name] : '';
        break;
		case 'get-attachments':       
				if (empty($value)) return '';		
				if ($name == 'post_thumbnail'){
					if (has_post_thumbnail($value)) {
						$id = get_post_thumbnail_id($value);
                        return mage_image_output($id);
					}
				} elseif ($name == 'post_attachments'){
					$attachments =  (array) mage_edit_attachments($value);
					$output = '';
					if (!empty($attachments)) {
						foreach ($attachments as $attach) {
							$output .= attach_html($attach['id']). '<script>window.MageFileCount += 1;</script>';
    					}
					return $output;
					}
				}		
        break;
	}
}
// Custom Meta handling functions
function mage_add_post_meta($fields,$id,$cf){
	//global $mage_form_settings;
	if ($fields) {
		if ($cf == 1){
			foreach ($fields as $key => $val) { 
				add_post_meta($id,$key, $val, true);
			}
		} else {
			$cast = array();	
			foreach ($fields as $key => $val) {
			$cast['mage_'.$key] = $val;                    
			}
		add_post_meta($id,'mage_forms', $cast, true);
		}
	}	
}
function mage_update_post_meta($fields,$id, $cf){
	//global $mage_form_settings;
	if ($fields) {
		if ($cf == 1){
			foreach ($fields as $key => $val) { 
				update_post_meta($id,$key, $val);
			}
		} else {
			$cast = array();	
			$cast = maybe_unserialize(get_post_meta($id,'mage_forms',true));
			foreach ($fields as $key => $val) {
				$cast['mage_'.$key] = $val;                    
			}
			update_post_meta($id, 'mage_forms', $cast);
		}
	}	
}
function mage_get_post_meta($id,$single=true){
	$cast = array();
	$cast = maybe_unserialize(get_post_meta($id,'mage_forms',$single));
	return $cast;
}
function mage_return_custom_field($id, $name, $settings = array('raw' => 0 , 'wrap' => 'div', 'class' => '','style' => '','itemprop'=>'')) {
if (empty($id) || is_null($id) || empty($name))return '';
global $mage_form_settings;
extract($settings);
if ($mage_form_settings['custom_fields'] == 1){
	$field  = maybe_unserialize(get_post_meta($id,$name,true));
} else {
	$cast = array();
	$cast = maybe_unserialize(get_post_meta($id,'mage_forms',true));
	$field = isset( $cast['mage_'.$name] ) ? $cast['mage_'.$name] : '';
}
$attributes = magex($itemprop,'itemprop="','" ');
$attributes .= magex($class,'class="','" ');
$attributes .= magex($style,'style="','" ');
if (empty($field)) return '';
return $raw != 1? '<'.$wrap.' '.$attributes.'>'.$field.'</'.$wrap.'>':$field;			
}
function mageform_get_tags() {
	$result = array();
	$tags = get_tags('hide_empty=0');
	foreach ( $tags as $tag ) {
		$result[] = '"'.$tag->name.'"';
	}
	$result = implode(',',$result);
	return !empty($result)? $result :false;
}