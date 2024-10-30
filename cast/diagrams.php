<?php
/*
Mage Reviews
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
add_action('wp_ajax_mage_upload_files','mage_upload_files');
add_action('wp_ajax_mage_delete_file','mage_delete_file');
add_action('wp_ajax_mage_img_delete', 'mage_img_delete');
add_action( 'wp_ajax_mage_img_upload', 'mage_img_upload');

add_action('wp_ajax_nopriv_summon_children', 'summon_children');
add_action('wp_ajax_summon_children', 'summon_children');
add_action('wp_ajax_nopriv_mage_summon_terms', 'mage_summon_terms');
add_action('wp_ajax_mage_summon_terms', 'mage_summon_terms');

function mage_img_delete() {
	check_ajax_referer( 'scribe_nonce', 'nonce' );
    $attach_id = isset($_POST['attach_id']) ? intval($_POST['attach_id']) : 0;
	$image = get_post( $attach_id );
	if ( get_current_user_id() == $image->post_author || current_user_can( 'delete_private_pages' ) ) {
    	wp_delete_attachment( $attach_id, true );
        echo 'success';
  	}
    exit;
}
function mage_delete_file() {
  	check_ajax_referer( 'mage_attachment', 'nonce' );
	$attach_id = isset( $_POST['attach_id'] ) ? intval( $_POST['attach_id'] ) : 0;
	$attachment = get_post( $attach_id );
 	//post author or editor role
	if ( get_current_user_id() == $attachment->post_author || current_user_can( 'delete_private_pages' ) ) {
    	wp_delete_attachment( $attach_id, true );
        echo 'success';
  	}
    exit;
}
function mage_summon_terms() {
        $parent = $_POST['term'];
		$blog_id = isset($_POST['blog_id'])? $_POST['blog_id']: 0;
        $result = '';
		$taxonomy = isset($_POST['tax'])? $_POST['tax'] : 0;		
		$level = isset($_POST['level'])? $_POST['level'] : 0;		
        if ( $parent < 1 ) die( $result );
		$selected = isset($_POST['child'])? $_POST['child'] : 0;
		do_action('before_mage_form_element');
        if ( get_categories( 'taxonomy='.$taxonomy.'&child_of=' . $parent . '&hide_empty=0' ) ) {
            $result .= wp_dropdown_categories( 'show_option_none=' . __( '-- Select --', 'mage-forms' ) . '&orderby=name&name=tax_input['.$taxonomy.'][]&id=term-child-'.$level.'&order=ASC&hide_empty=0&hierarchical=1&taxonomy='.$taxonomy.'&depth=1&echo=0&child_of=' . $parent .'&selected='.$selected);
        } else {
            die( '' );
        }		
		do_action('after_mage_form_element');
        die( $result );
}
function mage_delete_post() {
        global $userdata;        
        $nonce = $_REQUEST['_wpnonce'];
        if ( !wp_verify_nonce( $nonce, 'mage_delete_post' ) ) {
            die( "Security check" );
        }
        //check, if the requested user is the post author
        $maybe_delete = get_post( $_REQUEST['pid'] );
        if ( ($maybe_delete->post_author == $userdata->ID) || current_user_can( 'delete_others_pages' ) ) {
            wp_delete_post( $_REQUEST['pid'] );
            //redirect
            $redirect = add_query_arg( array('msg' => 'deleted'), get_permalink() );
            wp_redirect( $redirect );
        } else {
            echo '<div class="error">' . __( 'You are not the post author. Cheeting huh!', 'mage' ) . '</div>';
        }
}
function mage_clean_tags( $string ) {
    $string = preg_replace( '/\s*,\s*/', ',', rtrim( trim( $string ), ' ,' ) );
    return $string;
}
function mage_send_email( $user, $post_id ) {
    $blogname = get_bloginfo( 'name' );
    $to = get_bloginfo( 'admin_email' );
    $permalink = get_permalink( $post_id );

    $headers = sprintf( "From: %s <%s>\r\n", $blogname, $to );
    $subject = sprintf( __( '[%s] New Post Submission' ), $blogname );

    $msg = sprintf( __( 'A new post has been submitted on %s' ), $blogname ) . "\r\n\r\n";
    $msg .= sprintf( __( 'Author : %s' ), $user->display_name ) . "\r\n";
    $msg .= sprintf( __( 'Author Email : %s' ), $user->user_email ) . "\r\n";
    $msg .= sprintf( __( 'Title : %s' ), get_the_title( $post_id ) ) . "\r\n";
    $msg .= sprintf( __( 'Permalink : %s' ), $permalink ) . "\r\n";
    $msg .= sprintf( __( 'Edit Link : %s' ), admin_url( 'post.php?action=edit&post=' . $post_id ) ) . "\r\n";
    wp_mail( $to, $subject, $msg, $headers );
}
function mage_mime_types( $mime ) {
    $unset = array('exe', 'swf', 'tsv', 'wp|wpd', 'onetoc|onetoc2|onetmp|onepkg', 'class', 'htm|html', 'mdb', 'mpp');
    foreach ($unset as $val) { unset( $mime[$val] ); }
    return $mime;
}
function mage_upload_attachments($post_id) {
    if (!isset($_FILES['mage_attachments'])) return false;
	$count = count($_FILES['file']['name']);
    for ($i = 0; $i < $count; $i++) {
        $file_name = basename( $_FILES['mage_attachments']['name'][$i] );
        if ( $file_name ) {
			$upload = array(
				'name' => $_FILES['mage_attachments']['name'][$i],
				'type' => $_FILES['mage_attachments']['type'][$i],
				'tmp_name' => $_FILES['mage_attachments']['tmp_name'][$i],
				'error' => $_FILES['mage_attachments']['error'][$i],
				'size' => $_FILES['mage_attachments']['size'][$i]
			);
			$attach_id = mage_handle_upload( $upload);				
        }// end for
    }
}
function scribe_sanitize_upload() {
    $errors = array();
    $mime = get_allowed_mime_types();
	$size = wp_max_upload_size();
    //$size_limit = (int) (mage_get_option('forms','attachment_max_size' ) * 1024);
	$count = count($_FILES['file']['name']);
    for ($i = 0; $i < $count; $i++) {
        $tmp_name = basename( $_FILES['mage_attachments']['tmp_name'][$i] );
        $file_name = basename( $_FILES['mage_attachments']['name'][$i] );

        //if file is uploaded
        if ( $file_name ) {
            $attach_type = wp_check_filetype( $file_name );
            $attach_size = $_FILES['mage_attachments']['size'][$i];
            //check file size
            if ( $attach_size > $size)$errors[] = __( "Attachment file is too big" );
            //check file type
            if ( !in_array( $attach_type['type'], $mime ) ) {
                $errors[] = __( "Invalid attachment file type" );
            }
        } // if $filename
    }// endfor

    return $errors;
}
function mage_edit_attachments( $post_id ) {
    $att_list = array();
    $args = array(
        'post_type' => 'attachment',
        'numberposts' => -1,
        'post_status' => null,
        'post_parent' => $post_id,
        'order' => 'ASC',
        'orderby' => 'menu_order'
    );
    $attachments = get_posts( $args );
    foreach ($attachments as $attachment) {
        $att_list[] = array(
            'id' => $attachment->ID,
            'title' => $attachment->post_title,
            'url' => wp_get_attachment_url( $attachment->ID ),
            'mime' => $attachment->post_mime_type
        );
    }
    return $att_list;
}
function mage_edit_attachment( $post_id ) {
    $attach = mage_edit_attachments( $post_id );
    if ( $attach ) {
        $count = 1;
        foreach ($attach as $a) {
            echo 'Attachment ' . $count . ': <a href="' . $a['url'] . '">' . $a['title'] . '</a>';
            echo "<form name=\"mage_edit_attachment\" id=\"mage_edit_attachment_{$post_id}\" action=\"\" method=\"POST\">";
            echo "<input type=\"hidden\" name=\"attach_id\" value=\"{$a['id']}\" />";
            echo "<input type=\"hidden\" name=\"action\" value=\"del\" />";
            wp_nonce_field( 'mage_attach_del' );
            echo '<input class="mage_attachment_delete" type="submit" name="mage_attachment_delete" value="delete" onclick="return confirm(\'Are you sure to delete this attachment?\');">';
            echo "</form>";
            echo "<br>";
            $count++;
        }
    }
}
function mage_image_output( $attach_id ) {
	$type = get_post_mime_type($attach_id);
    $post = get_post( $attach_id );
	$html = '<div class="mage-file thumbnail" id="attachment-'.$attach_id.'">';
	if (strpos($type,'image') !== false){
		$image = wp_get_attachment_image_src( $attach_id, 'thumbnail' );
		$image = $image[0];
	} else {
		$image = wp_mime_type_icon($type);
	}	
  	$html .= '<img src="'.$image.'" alt="'.$post->post_title.'" /><a class="mage-delete-image btn btn-mini btn-danger halflings trash" href="#" title="Remove Image" data-id="'.$attach_id.'"><i></i></a><input type="hidden" name="mage_img_upload" value="'.$attach_id.'" class="mage-form-element" />';
  	$html .= '</div>';
    return $html;
}

function summon_children() {
        $parent = $_POST['catID'];
        $result = '';
		$taxonomy = isset($_POST['tax'])? $_POST['tax'] : 0;		
        if ( $parent < 1 ) die( $result );
		$selected = isset($_POST['termID'])? $_POST['termID'] : 0;
        if ( get_categories( 'taxonomy='.$taxonomy.'&child_of=' . $parent . '&hide_empty=0' ) ) {
            $result .= wp_dropdown_categories( 'show_option_none=' . __( '-- Select --', 'wpuf' ) . '&class=dropdownlist&orderby=name&name=tax_input['.$taxonomy.'][]&id=term-child-1&order=ASC&hide_empty=0&hierarchical=1&taxonomy='.$taxonomy.'&depth=1&echo=0&child_of=' . $parent .'&selected='.$selected);
        } else {
            die( '' );
        }		
        die( $result );
}
function mage_upload_files() {
	check_ajax_referer( 'mage_upload_files', 'nonce' );
	$upload = array(
		'name' => $_FILES['mage_attachments']['name'],
    	'type' => $_FILES['mage_attachments']['type'],
       	'tmp_name' => $_FILES['mage_attachments']['tmp_name'],
        'error' => $_FILES['mage_attachments']['error'],
        'size' => $_FILES['mage_attachments']['size']
  	);
  	$attach_id = mage_handle_upload($upload);
    if ( $attach_id ) {
		$html = attach_html( $attach_id );
     	$response = array(
			'success' => true,
           	'html' => $html,
        );
		echo json_encode( $response );
        exit;
	}
   	$response = array('success' => false);
	echo json_encode( $response );
   	exit;
}
function attach_html( $attach_id ) {
	$attachment = get_post( $attach_id );
    $html = '<li class="mage-form-file">
	<div class="input-prepend input-append"><span class="add-on handle  btn btn-info halflings resize-vertical"><i></i></span><span class="add-on mage-form-attachment-name">'.$attachment->post_title.'</span><input type="text" name="mage-file-title[]" value="'.$attachment->post_title.'" placeholder="Add Label" class="mage-form-element mage-form-file-input" /><span class="add-on"><a href="#" class="mage-form-del btn btn-danger halflings trash" data-attach_id="'.$attach_id.'" title="Delete Image"><i></i></a></span></div><input type="hidden" name="mage_file_id[]" value="'.$attach_id.'" /></li>';
   	return $html;
}
function attach_file_to_post( $post_id ) {
	$posted = $_POST;
  	if ( isset( $posted['mage_file_id'] ) ) {
		foreach ( $posted['mage_file_id'] as $index => $attach_id) {
    		$attachment = array(
         		'ID' => $attach_id,
             	'post_title' => $posted['mage-file-title'][$index],
              	'post_parent' => $post_id,
          		'menu_order' => $index
         	);
           	wp_update_post($attachment);
    	}
	}
}
function mage_handle_upload( $upload_data) {
    $uploaded_file = wp_handle_upload( $upload_data, array('test_form' => false) );
   	if ( isset( $uploaded_file['file'] ) ) {
       	$file_loc = $uploaded_file['file'];
        $file_name = basename( $upload_data['name'] );
        $file_type = wp_check_filetype( $file_name );
       	$attachment = array(
            'post_mime_type' => $file_type['type'],
            'post_title' => preg_replace( '/\.[^.]+$/', '', basename( $file_name ) ),
           	'post_content' => '',
            'post_status' => 'inherit'
        );		
        $attach_id = wp_insert_attachment($attachment,$file_loc);
        $attach_data = wp_generate_attachment_metadata($attach_id,$file_loc);
        wp_update_attachment_metadata($attach_id,$attach_data);		
        return $attach_id;		
    }
    return false;
	//return $uploaded_file;
}
function mage_communicate(){
	/*
	if (isset($_GET['claim']) && ($id == $_GET['claim'])) { 
		$username = bp_core_get_username(bbp_get_current_user_id());
		$userpage = bbp_get_user_profile_url( bbp_get_current_user_id());
		$info = isset($_GET['information'])? $_GET['information'] :  '';
		$via = isset($_GET['via'])? $_GET['via'] :  '';
		$ip = isset($_SERVER['HTTP_X_FORWARD_FOR'])? $_SERVER['HTTP_X_FORWARD_FOR'] :  $_SERVER['REMOTE_ADDR'];
		$email = (mage_get_option('email_handle')) ? mage_get_option('email_handle'):get_option('admin_email');
$subject = 'DogSniffer.com '.$city.' Business Claim';
$body = '<p><strong>The User <a href="'.$userpage.'">'.$username.'</a> is claiming ownership for <a href="'.$url.'">'.$title.'</a> on DogSniffer '.$city.'.</strong></p><h4>User Details</h4><p><strong>Username:</strong> '.$username.'</p><p><strong>Users Profile:</strong> '.$userpage.'</p><p><strong>Users IP:</strong> '.$ip.'</p><p><strong>Preferred Verification Method:</strong> '.$via.'</p><p><strong>Other Info:</strong> '.$info.'</p>';
$headers = 'From: Dog Sniffer '.$city.' <'.$email.'>' . "\r\n" . 'No-Reply';
add_filter( 'wp_mail_content_type', 'set_html_content_type' );
wp_mail($email, $subject, $body, $headers);
remove_filter( 'wp_mail_content_type', 'set_html_content_type' );
*/
}
function mage_img_upload() {
        check_ajax_referer( 'mage_img_upload', 'nonce' );		
        $upload_data = array(
            'name' => $_FILES['mage_img_upload']['name'],
            'type' => $_FILES['mage_img_upload']['type'],
            'tmp_name' => $_FILES['mage_img_upload']['tmp_name'],
            'error' => $_FILES['mage_img_upload']['error'],
            'size' => $_FILES['mage_img_upload']['size']
        );				
        $attach_id = mage_handle_upload( $upload_data);
        if ($attach_id) {
			$html = mage_image_output( $attach_id );		            
            $response = array(
                'success' => true,
                'html' => $html,
            );
            echo json_encode( $response );
            exit;
        }		
        $response = array('success' => false);
        echo json_encode( $response );
        exit;		
}
