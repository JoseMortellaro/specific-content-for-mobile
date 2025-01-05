<?php
defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

add_action( 'wp_ajax_eos_scfm_suggest_page','eos_scfm_suggest_page' );
//Suggest page to the input field
function eos_scfm_suggest_page(){
	$results = array();
	$s = wp_unslash( sanitize_text_field( $_REQUEST['q'] ) );
	$comma = _x( ',','page delimiter' );
	if ( ',' !== $comma ) $s = str_replace( $comma, ',', $s );
	if ( false !== strpos( $s, ',' ) ) {
			$s = explode( ',', $s );
			$s = $s[count( $s ) - 1];
	}
	$s = trim( $s );
	$term_search_min_chars = 2;
	$the_query = new WP_Query(
			array(
					's' => $s,
					'posts_per_page' => 5,
					'post__not_in' => array( absint( $_REQUEST['id'] ) ),
					'post_type' => isset( $_REQUEST['post_type'] ) ? esc_attr( $_REQUEST['post_type'] ) : 'page'
				)
			);
	if ( $the_query->have_posts() ) {
			while ( $the_query->have_posts() ) {
					$the_query->the_post();
					$results[] = '<span class="scfm-page" data-id="'.esc_attr( get_the_id() ).'">'.esc_html( get_the_title() ).'</span>';
			}
			wp_reset_postdata();
	}
	else {
			$results = 'No results';
	}
	echo join( "\n",  $results ); //phpcs:ignore WordPress.Security.EscapeOutput -- The escaping was already applied while filling $results.
	wp_die();
}

add_action( 'wp_ajax_eos_scfm_save_settings','eos_scfm_save_settings' );
//It saves the main settings
function eos_scfm_save_settings(){
	if( !current_user_can( 'manage_options' ) || !isset( $_POST['data'] ) ) return;
	$data = json_decode( stripslashes( $_POST['data'] ),true );
	if( isset( $data['nonce'] ) ){
		if( !wp_verify_nonce( esc_attr( $data['nonce'] ),'eos_scfm_nonce_saving' ) ){
			die();
			exit; //no code anymore
		}
	}
	unset( $data['nonce'] );
	$options = eos_scfm_get_main_options_array();
	$other_meta = is_array( $options ) && isset( $options['other_meta'] ) ? $options['other_meta'] : array();
	foreach( $data as $name => $value ){
		$other_meta[$name] = sanitize_text_field( $value );
	}
	$options['other_meta'] = $other_meta;
	$post_types = get_post_types( array( 'publicly_queryable' => true,'public' => true ),'names','or' );
	$active_post_types = array();
	foreach( $post_types as $post_type ){
		if( isset( $data[$post_type.'-activation'] ) && true === $data[$post_type.'-activation'] && !in_array( $post_type,$active_post_types ) ){
			$active_post_types[] = $post_type;
		}
	}
	$options['active_post_types'] = $active_post_types;
	echo update_site_option( 'eos_scfm_main',$options ) ? esc_html__( 'Options Saved','specific-content-for-mobile' ) : esc_html__( 'Nothing changed','specific-content-for-mobile' );
	die();
	exit; //no code anymore
}
