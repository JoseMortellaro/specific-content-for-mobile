<?php
/*
Plugin Name: Specific Content for Mobile
Description: It allows you to create specific content for the mobile version.
Author: Jose Mortellaro
Author URI: https://josemortellaro.com/
Plugin URI: https://specific-content-for-mobile.com/
Text Domain: specific-content-for-mobile
Domain Path: /languages/
Version: 0.5.3
*/
/*  This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
*/

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

// Definitions.
define( 'EOS_SCFM_PLUGIN_DIR', untrailingslashit( dirname( __FILE__ ) ) );
define( 'EOS_SCFM_PLUGIN_URL', untrailingslashit( plugins_url( '', __FILE__ ) ) );
define( 'EOS_SCFM_PLUGIN_VERSION', '0.5.2' );
define( 'EOS_SCFM_PLUGIN_BASE_NAME', untrailingslashit( plugin_basename( __FILE__ ) ) );

if( isset( $_GET['scfm-mobile'] ) ) {
	unset( $_GET['scfm-mobile'] );
}
if( isset( $_REQUEST['scfm-mobile'] ) ) {
	unset( $_REQUEST['scfm-mobile'] );
}

if( 
	isset( $_REQUEST['ct_builder'] ) 
	|| isset( $_REQUEST['zionbuilder-preview'] ) 
	|| ( isset( $_REQUEST['action'] ) && is_string( $_REQUEST['action'] ) && in_array( $_REQUEST['action'],array( 'oxy_render_nav_menu','zion_builder_active','elementor', 'elementor-preview' ) ) ) 
	|| ( isset( $_REQUEST['vcv-source-id'] ) )
	|| ( isset( $_REQUEST['elementor-preview'] ) )
) {
	return;
}

if( is_admin() ){
	//Backend
	if( apply_filters( 'scfm_exclude_backend',false ) ) return;
	require EOS_SCFM_PLUGIN_DIR.'/admin/scfm-admin.php';
}
else{
	//Frontend
	if( ( isset( $_GET['uxb_iframe'] ) && isset( $_GET['post_id'] ) ) || apply_filters( 'scfm_exclude_frontend',false ) ) return;
	add_action( 'template_redirect','eos_scfm_redirect_to_desktop', 10 );
	add_action( 'template_redirect','eos_scfm_post_content_replacement', 20 );
}
if(
	defined( 'DOING_AJAX' )
	&& DOING_AJAX
	&& isset( $_REQUEST['action'] )
	&& is_string( $_REQUEST['action'] ) && false !== strpos( sanitize_text_field( $_REQUEST['action'] ),'eos_scfm' )
){
	//Ajax activities
	require_once EOS_SCFM_PLUGIN_DIR.'/inc/scfm-ajax.php';
}

// Redirect to the desktop page if 
function eos_scfm_redirect_to_desktop() {
	global $post;
	if( $post && is_object( $post ) && isset( $_SERVER['REQUEST_URI'] ) && false !== strpos( $_SERVER['REQUEST_URI'], '-' . apply_filters( 'scfm_mobile_slug','mobile' ) ) ) {
		$desktop_post_id = eos_scfm_related_desktop_id( $post->ID );
		if( $desktop_post_id > 0 && $post->ID !== $desktop_post_id && function_exists( 'is_preview' ) && ! is_preview() && ! is_customize_preview() ){
			wp_safe_redirect( get_permalink( $desktop_post_id ),301 );
			exit;
		}
	}
}


//It replaces the post content with the mobile version
function eos_scfm_post_content_replacement(){
	if(
		( isset( $_SERVER['HTTP_REFERER'] )
			&& false !== strpos( $_SERVER['HTTP_REFERER'],'wp-admin/post.php' )
			&& ( isset( $_GET['action'] )&& 'edit' !== $_GET['action'] )
		)
		|| isset( $_GET['elementor-preview'] )
		|| isset( $_GET['vc_editable'] )
	){
		return;
	}
	if( isset( $_REQUEST['eos_dp_preview'] ) ) return;
	global $post;
	if( !is_singular() || !is_object( $post ) ) return;

	if( !scfm_wp_is_mobile() ) return;
	$mobile_post_id = eos_scfm_related_mobile_id( $post-> ID );
	if( $mobile_post_id > 0 ){
		$GLOBALS['desktop_id'] = $post->ID;
		$GLOBALS['mobile_id'] = $mobile_post_id;
		$mobile_post = get_post( $mobile_post_id );
		if( $mobile_post && is_object( $mobile_post ) && 'trash' !== $mobile_post->post_status ){
			$post->ID = $mobile_post_id;
			$post->post_content = $mobile_post->post_content;
			if( apply_filters( 'scfm_'.$post->post_type.'_excerpt',true ) ){
				$post->post_excerpt = $mobile_post->post_excerpt;
			}
			$post->post_content_filtered = $mobile_post->post_content;
		}
	}
}

add_filter( 'single_template','eos_scfm_single_template' );
add_filter( 'page_template','eos_scfm_single_template' );
//Filter the single_template for the mobile preview
function eos_scfm_single_template( $template ){
	global $post;
	if( is_object( $post ) && in_array( $post->post_type,eos_scfm_post_types() ) ){
		$desktop_id = eos_scfm_related_desktop_id( $post->ID );
		if( $desktop_id > 0 ){
			if( !scfm_wp_is_mobile() && isset( $_GET['preview'] ) && 'true' === $_GET['preview'] && isset( $_GET['preview_nonce'] ) && function_exists( 'is_preview' ) && is_preview() && !isset( $_REQUEST['scfm_preview'] ) ){
				return EOS_SCFM_PLUGIN_DIR.'/templates/scfm-preview.php';
			}
		}
	}
    return $template;
}

add_action( 'init','eos_scfm_remove_admin_top_bar_on_mobile_preview' );
//Remove admin top bar on mobile preview
function eos_scfm_remove_admin_top_bar_on_mobile_preview(){
	if( isset( $_REQUEST['preview'] ) && 'true' === $_REQUEST['preview'] && isset( $_REQUEST['preview_id'] ) && isset( $_REQUEST['preview_nonce'] ) && wp_verify_nonce( esc_attr( $_REQUEST['preview_nonce'] ),'post_preview_'.esc_attr( $_REQUEST['preview_id'] ) ) && isset( $_REQUEST['scfm_preview'] ) ){
		add_filter( 'show_admin_bar', '__return_false' );
		add_action( 'wp_head','eos_scfm_add_mobile_style' );
	}
}
//Add style to simulate a mobile device
function eos_scfm_add_mobile_style(){
	?>
	<style id="scfm-preview-css" type="text/css">
	body::-webkit-scrollbar{
		display:none;
	}
	body{
		-ms-overflow-style:none;
		scrollbar-width:none
	}
	</style>
	<?php
}

add_filter( 'body_class','eos_scfm_body_class' );
// Add body class on mobile.
function eos_scfm_body_class( $classes ){
	$classes[] = 'scfm';
	$arr = eos_scfm_data_array();
	if( isset( $arr['desktop_id'] ) ){
		$classes[] = 'scfm-desktop-'.esc_attr( $arr['desktop_id'] );
	}
	if( isset( $arr['mobile_id'] ) ){
		$classes[] = 'scfm-mobile-'.esc_attr( $arr['mobile_id'] );
	}
	if( isset( $arr['device'] ) ){
		$classes[] = 'eos-scfm-d-'.esc_attr( $arr['device'] ) . '-device';
	}
	if( isset( $arr['microtime'] ) ){
		$classes[] = 'eos-scfm-t-'.esc_attr( str_replace( '.', '-', $arr['microtime'] ) ) . '-timestamp';
	}
	return $classes;
}

// Return array of data.
function eos_scfm_data_array() {
	$arr = array( 'time' => date( 'd M Y h:i:s a',time() ),'microtime' => microtime(1),'device' => isset( $GLOBALS['desktop_id'] ) ? 'mobile' : 'desktop' );
	if( isset( $GLOBALS['desktop_id'] ) ){
		$arr['desktop_id'] = absint( $GLOBALS['desktop_id'] );
	}
	if( isset( $GLOBALS['mobile_id'] ) ){
		$arr['mobile_id'] = absint( $GLOBALS['mobile_id'] );
	}
	return $arr;
}

add_action( 'wp_footer',function(){
	$arr = eos_scfm_data_array();
	?>
	<script id="scfm-js">var scfm = <?php echo wp_json_encode( $arr ); ?></script>
	<?php
} );

add_action( 'wp_head',function(){
	?>
	<script id="scfm-url-js">
	if (window.location.search.includes('scfm-mobile=1')) {
		const url = new URL(window.location.href);
		const searchParams = url.searchParams;
		searchParams.delete('scfm-mobile');
		const newUrl = url.origin + url.pathname + (searchParams.toString() ? "?" + searchParams.toString() : "") + url.hash;
		window.history.replaceState(null, "", newUrl);
	}
	</script>
	<?php
} );

if( scfm_wp_is_mobile() ){
	add_filter( 'option_page_on_front','eos_scfm_mobile_front_page',0,1 );
	add_filter( 'option_page_for_posts','eos_scfm_mobile_page_for_posts',0,1 );
}
//It replace the front page id with the mobile front page id.
function eos_scfm_mobile_front_page( $id ){
	$mobile_id = get_option( 'page_on_front_mobile' );
	$homeUrlArr = explode( '//',home_url() );
	if( $mobile_id && isset( $homeUrlArr[1] ) ){
		$url = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		if( str_replace( '/','',$url ) === str_replace( '/','',$homeUrlArr[1] ) ){
			add_filter( 'get_post_status','eos_scfm_filter_post_status' );
			return $mobile_id;
		}
	}
	return $id;
}

//It returns the publish post status if homepage mobile
function eos_scfm_filter_post_status(  $post_status ){
	return 'publish';
}

add_action( 'pre_get_posts', 'eos_scfm_filter_pre_get_posts' );
//Exclude mobile versions of posts from archives
function eos_scfm_filter_pre_get_posts( $query ) {
	if ( ( ! is_singular() || $query->is_home() ) && !is_admin() ){
		if( method_exists( $query,'set' ) ){
			$opts = eos_scfm_get_main_options_array();
			$posts_not_in = $query->get( 'post__not_in' );
			if( !scfm_wp_is_mobile() ){
				if( isset( $opts['mobile_ids'] ) && !empty( $opts['mobile_ids'] ) ){
					$posts_not_in = array_merge( $posts_not_in,$opts['mobile_ids'] );
					$query->set( 'post__not_in',$posts_not_in );
				}
			}
			else{
				if( isset( $opts['desktop_ids'] ) && !empty( $opts['desktop_ids'] ) ){
					$posts_not_in = array_merge( $posts_not_in,$opts['desktop_ids'] );
					$query->set( 'post__not_in',$posts_not_in );
				}
			}
		}
	}
}

add_filter( 'post_link','scfm_mobile_permalink',10,2 );
//Replace permalink with desktop permalink on mobile
function scfm_mobile_permalink( $permalink,$post ){
	if( scfm_wp_is_mobile() ){
		$desktop_id = eos_scfm_related_desktop_id( $post->ID );
		if( $desktop_id > 0 ){
			$permalink = get_the_permalink( $desktop_id );
		}
	}
	return $permalink;
}

//It replace the front page id with the mobile front page id.
function eos_scfm_mobile_page_for_posts( $id ){
	$mobile_id = get_option( 'page_for_posts_mobile' );
	if( $mobile_id ){
		add_filter( 'get_post_status','eos_scfm_filter_post_status' );
		return $mobile_id;
	}
	return $id;
}
add_filter( 'private_title_format', 'eos_scfm_remove_private_title' );
//If mobile it returns only the title portion as defined by %s, not the additional private prefix
function eos_scfm_remove_private_title( $title ) {
	global $post;
	if( is_object( $post ) ){
		$desktop_post_id = eos_scfm_related_mobile_id( $post->ID );
		if( $desktop_post_id > 0 ){
			// 'Private: ' as added in core
			return "%s";
		}
	}
	return $title;
}

//It returns the post ID related to the desktop version given the original post ID
function eos_scfm_related_desktop_id( $post_id ){
	return absint( get_post_meta( apply_filters( 'eos_scfm_desktop_post_id', $post_id ), 'eos_scfm_desktop_post_id', true ) );
}

//It returns the post ID related to the mobile version given the original post ID
function eos_scfm_related_mobile_id( $post_id ){
	return absint( get_post_meta( apply_filters( 'eos_scfm_mobile_post_id', $post_id ), 'eos_scfm_mobile_post_id', true ) );
}

//It returns all post IDs of mobile versions
function eos_scfm_get_mobile_ids() {
	$ids = array();
	$query_args = array(
		'post_type'      => eos_scfm_post_types(),
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'posts_per_page' => -1,
		'meta_query'     => array(
			array(
				'key'   => 'eos_scfm_desktop_post_id',
				'value'   => array(''),
				'compare' => 'NOT IN'
			),
		)
	);
	$query = new WP_Query( $query_args );
	if ( $query->posts ) {
		foreach ( $query->posts as $key => $post_id ) {
			$ids[] = $post_id;
		}
	}
	return $ids;
}

add_action( 'template_redirect','scfm_template_redirect' );
//Actions and filters on template redirect
function scfm_template_redirect(){
	//Integration with WooCommerce
	add_filter( 'woocommerce_get_shop_page_id','eos_scfm_woo_shop_id_filter' );
}

//It replaces the shop ID with the ID related to the mobile version if any
function eos_scfm_woo_shop_id_filter( $id ){
	if( scfm_wp_is_mobile() ){
		$mobile_post_id = eos_scfm_related_mobile_id( $id );
		if( $mobile_post_id > 0 ){
			return $mobile_post_id;
		}
	}
	return $id;
}

// Our filter callback function
function eos_scfm_default_post_types( $post_types ){
    return $post_types;
}
add_filter( 'eos_scfm_post_types','eos_scfm_default_post_types',10,1 );


//We give the possibility to external plugins to add support for more post types
function eos_scfm_post_types(){
	do_action( 'scfm_before_post_types' );
	$options = eos_scfm_get_main_options_array();
	$active_post_types = isset( $options['active_post_types'] ) ? $options['active_post_types'] : array( 'post','page' );
	if( !is_array( $active_post_types ) || empty( $active_post_types ) ) $active_post_types = array( 'post','page' );
	return apply_filters( 'eos_scfm_post_types',$active_post_types );
}



add_filter( 'comments_template_query_args','eos_scfm_replace_post_id_before_comments' );
//Replace post id before displaying comments
function eos_scfm_replace_post_id_before_comments( $comment_args ){
	global $post;
	if( is_object( $post ) ){
		$desktop_id = eos_scfm_related_desktop_id( absint( $post->ID ) );
		if( $desktop_id ){
			$comment_args['post_id'] = $desktop_id;
			$post->ID = $desktop_id;
			$GLOBALS['desktop_id'] = $desktop_id;
			add_action( 'comment_form_after','scfm_restore_post_id_after_comments' );
		}
	}
	return $comment_args;
}

//Restore desktop post ID after comment form
function scfm_restore_post_id_after_comments(){
	if( isset( $GLOBALS['desktop_id'] ) && absint( $GLOBALS['desktop_id'] ) > 0 ){
		global $post;
		$post->ID =absint( $GLOBALS['desktop_id'] );
	}
}
if( !eos_scfm_is_tablet_mobile() ){
	add_filter( 'wp_is_mobile','eos_scfm_exclude_ipad_and_tablets',1,99999999 );
}
//Exclude tablets to be considered mobile
function eos_scfm_exclude_ipad_and_tablets( $is_mobile ) {
	if( !isset( $_SERVER['HTTP_USER_AGENT'] ) ) return false;
  if(
		false !== strpos( strtolower( $_SERVER['HTTP_USER_AGENT'] ),'ipad' )
		|| false !== strpos( strtolower( $_SERVER['HTTP_USER_AGENT'] ),'tablet' )
		|| false !== strpos( strtolower( $_SERVER['HTTP_USER_AGENT'] ),'playbook' )
		|| false !== strpos( strtolower( $_SERVER['HTTP_USER_AGENT'] ),'mobi|opera mini' )
	){
        return false;
    }
    return $is_mobile;
}
//Check if tablets are defined as mobile
function eos_scfm_is_tablet_mobile(){
	$is_tablet_mobile = !defined( 'SCFM_EXCLUDE_TABLETS' ) || !SCFM_EXCLUDE_TABLETS;
	return apply_filters( 'scfm_is_tablet_mobile',$is_tablet_mobile );
}

add_filter( 'preprocess_comment' , 'eos_scfm_mobile_to_desktop_comment' );
//Convert comment from mobile to comment for desktop
function eos_scfm_mobile_to_desktop_comment( $commentdata ){
	if( isset( $commentdata['comment_post_ID'] ) ){
		$desktop_id = eos_scfm_related_desktop_id( $commentdata['comment_post_ID'] );
		if( $desktop_id ){
			$commentdata['comment_post_ID'] = absint( $desktop_id );
		}
	}
    return $commentdata;
}
//It returns the plugin options array
function eos_scfm_get_main_options_array(){
	if( !is_multisite() ){
		return get_option( 'eos_scfm_main' );
	}
	else{
		return get_blog_option( get_current_blog_id(),'eos_scfm_main' );
	}
}

register_activation_hook( __FILE__, 'eos_scfm_initialize_plugin' );
//Actions triggered after plugin activation
function eos_scfm_initialize_plugin( $networkwide ){
	$options = eos_scfm_get_main_options_array();
	$last_version = isset( $options['version'] ) ? $options['version'] : false;
	if( !$last_version || version_compare( $last_version,EOS_SCFM_PLUGIN_VERSION,'<' ) ){
		$mobile_ids = eos_scfm_get_mobile_ids();
		if( is_array( $mobile_ids ) && !empty( $mobile_ids ) ){
			$options['mobile_ids'] = array_map( 'absint',$mobile_ids );
		}
	}
	$options['last_version'] = EOS_SCFM_PLUGIN_VERSION;
	update_site_option( 'eos_scfm_main',$options );
	flush_rewrite_rules();
}

register_deactivation_hook( __FILE__,'eos_scfm_plugin_deactivation' );
//Actions triggered after plugin deactivation
function eos_scfm_plugin_deactivation( $networkwide ){
	flush_rewrite_rules();
}

add_filter( 'template_include', 'eos_scfm_override_templates', 99 );
//Override theme templates with your custom templates if they are prensent
function eos_scfm_override_templates( $template ) {
	if( scfm_wp_is_mobile() ){
		$dirname = dirname( $template );
		$new_template = str_replace( $dirname,$dirname.'/scfm',$template );
		if( file_exists( $new_template ) ){
			return $new_template;
		}
		$new_template = str_replace( $dirname,WP_CONTENT_DIR.'/scfm',$template );
		if( file_exists( $new_template ) ){
			return $new_template;
		}
	}
	return $template;
}

add_action( 'get_header','eos_scfm_get_header',10 );
//Replace theme header.php with the mobile version if it exists
function eos_scfm_get_header( $name ){
	$device = eos_scfm_get_device();
	if ( locate_template( array( 'scfm/header-'.$device.'.php' ),true,true ) ) {
		return;
	}
	return;
}

add_action( 'get_footer','eos_scfm_get_footer',10 );
//Replace theme footer.php with the mobile version if it exists
function eos_scfm_get_footer( $name ){
	$device = eos_scfm_get_device();
	if ( locate_template( array( 'scfm/footer-'.$device.'.php' ),true,true ) ) {
		return;
	}
	return;
}

//Return device
function eos_scfm_get_device(){
	if( !isset( $_SERVER['HTTP_USER_AGENT'] ) ) return 'desktop';
	if(
		false !== strpos( strtolower( $_SERVER['HTTP_USER_AGENT'] ),'ipad' )
		|| false !== strpos( strtolower( $_SERVER['HTTP_USER_AGENT'] ),'tablet' )
		|| false !== strpos( strtolower( $_SERVER['HTTP_USER_AGENT'] ),'playbook' )
		|| false !== strpos( strtolower( $_SERVER['HTTP_USER_AGENT'] ),'mobi|opera mini' )
	){
        return 'tablet';
    }
	if(
		false !== strpos( strtolower( $_SERVER['HTTP_USER_AGENT'] ),'mobile' )
        || false !== strpos( strtolower( $_SERVER['HTTP_USER_AGENT'] ),'android' )
        || false !== strpos( strtolower( $_SERVER['HTTP_USER_AGENT'] ),'silk/' )
        || false !== strpos( strtolower( $_SERVER['HTTP_USER_AGENT'] ),'kindle' )
        || false !== strpos( strtolower( $_SERVER['HTTP_USER_AGENT'] ),'blackberry' )
        || false !== strpos( strtolower( $_SERVER['HTTP_USER_AGENT'] ),'opera mini' )
        || false !== strpos( strtolower( $_SERVER['HTTP_USER_AGENT'] ),'opera mobi' )
	){
		return 'mobile';
	}
	return 'desktop';
}

//Detect if the device is mobile
function scfm_wp_is_mobile() {
    if( !isset( $_SERVER['HTTP_USER_AGENT'] ) || empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
        $is_mobile = false;
    }
		elseif( stripos( $_SERVER['HTTP_USER_AGENT'],'Mobile' ) !== false
        || stripos( $_SERVER['HTTP_USER_AGENT'],'Android' ) !== false
        || stripos( $_SERVER['HTTP_USER_AGENT'],'Silk/' ) !== false
        || stripos( $_SERVER['HTTP_USER_AGENT'],'Kindle' ) !== false
        || stripos( $_SERVER['HTTP_USER_AGENT'],'BlackBerry' ) !== false
        || stripos( $_SERVER['HTTP_USER_AGENT'],'Opera Mini' ) !== false
        || stripos( $_SERVER['HTTP_USER_AGENT'],'Opera Mobi' ) !== false ) {
            $is_mobile = true;
    }
		else {
        $is_mobile = false;
    }
    return apply_filters( 'wp_is_mobile',$is_mobile );
}

add_filter( 'mod_rewrite_rules', function( $rewrite_rules ) {
	if( isset( $_REQUEST['action'] ) && 'deactivate' === sanitize_text_field( $_REQUEST['action'] ) ) {
		return $rewrite_rules;
	}
	$scfm_rules = '';
	if( false === strpos( $rewrite_rules, 'Specific Content For Mobile' ) && apply_filters( 'scfm_add_mobile_query_string', true ) ) {
		$scfm_rules = "\n# BEGIN Specific Content For Mobile\n";
		$scfm_rules .= "<IfModule mod_rewrite.c>\n";
		$scfm_rules .= "RewriteEngine On\n";
		$scfm_rules .= "RewriteCond %{HTTP_USER_AGENT} Mobile|Android|Silk/|Kindle|BlackBerry|Opera\ Mini|Opera\ Mobi [NC]\n";
		$scfm_rules .= "RewriteCond %{REQUEST_METHOD} !=POST\n";
		$scfm_rules .= "RewriteCond %{QUERY_STRING} !scfm-mobile\n";
		$scfm_rules .= "RewriteCond %{QUERY_STRING} !wc-ajax\n";
		$scfm_rules .= "RewriteCond %{QUERY_STRING} !^$\n";
		$scfm_rules .= "RewriteCond %{REQUEST_URI} !\.\n";
		$scfm_rules .= "RewriteRule ^(.*)$ %{REQUEST_SCHEME}://%{HTTP_HOST}%{REQUEST_URI}\?%{QUERY_STRING}\&scfm-mobile=1 [L,NS,R=301]\n";
		$scfm_rules .= "RewriteCond %{HTTP_USER_AGENT} Mobile|Android|Silk/|Kindle|BlackBerry|Opera\ Mini|Opera\ Mobi [NC]\n";
		$scfm_rules .= "RewriteCond %{REQUEST_METHOD} !=POST\n";
		$scfm_rules .= "RewriteCond %{QUERY_STRING} !scfm-mobile\n";
		$scfm_rules .= "RewriteCond %{QUERY_STRING} !wc-ajax\n";
		$scfm_rules .= "RewriteCond %{QUERY_STRING} ^$\n";
		$scfm_rules .= "RewriteCond %{REQUEST_URI} !\.\n";
		$scfm_rules .= "RewriteRule ^(.*)$ %{REQUEST_SCHEME}://%{HTTP_HOST}%{REQUEST_URI}\?scfm-mobile=1 [L,NS,R=301]\n";
		$scfm_rules .= "</IfModule>\n";
		$scfm_rules .= "# END Specific Content For Mobile\n\n";
	}
	return $scfm_rules . $rewrite_rules;
} );
