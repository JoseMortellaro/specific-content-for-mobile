<?php
defined( 'ABSPATH' ) || exit; // Exit if accessed directly

//It returns an array including information about other plugins meta data
function eos_scfm_meta_integration_array( $options ){
	$other_meta = isset( $options['other_meta'] ) ? $options['other_meta'] : array();
	$default = array(
		'wpseo_meta' => array(
			'is_active' => defined( 'WPSEO_FILE' ),
			'args' => array(
				'title' => __( 'Yoast SEO Meta synchronization','specific-content-for-mobile' ),
				'type' => 'select',
				'value' => isset( $other_meta['wpseo_meta'] ) ? esc_attr( $other_meta['wpseo_meta'] ) : 'synchronized',
				'options' => array(
					'synchronized' => __( 'Synchronize desktop and mobile metadata','specific-content-for-mobile' ),
					'separated' => __( 'Allow mobile versions having their own metadata','specific-content-for-mobile' )
				),
			),
			'prefix' => array( '_yoast' ),
			'default' => 'synchronized'
		),
		'aiosp' => array(
			'is_active' => defined( 'AIOSEO_PLUGIN_DIR' ),
			'args' => array(
				'title' => __( 'All in One Seo Pack Meta synchronization','specific-content-for-mobile' ),
				'type' => 'select',
				'value' => isset( $other_meta['aiosp'] ) ? esc_attr( $other_meta['aiosp'] ) : 'synchronized',
				'options' => array(
					'synchronized' => __( 'Synchronize desktop and mobile metadata','specific-content-for-mobile' ),
					'separated' => __( 'Allow mobile versions having their own metadata','specific-content-for-mobile' )
				),
			),
			'prefix' => array( '_aioseop' ),
			'default' => 'synchronized'
		),
		'tsf_inpost_box' => array(
			'is_active' => defined( 'THE_SEO_FRAMEWORK_BOOTSTRAP_PATH' ),
			'args' => array(
				'title' => __( 'The SEO Framework Meta synchronization','specific-content-for-mobile' ),
				'type' => 'select',
				'value' => isset( $other_meta['tsf_inpost_box'] ) ? esc_attr( $other_meta['tsf_inpost_box'] ) : 'synchronized',
				'options' => array(
					'synchronized' => __( 'Synchronize desktop and mobile metadata','specific-content-for-mobile' ),
					'separated' => __( 'Allow mobile versions having their own metadata','specific-content-for-mobile' )
				),
			),
			'prefix' => array( '_genesis','_open_graph','_social','_twitter' ),
			'default' => 'synchronized'
		),
		'seopress_cpt' => array(
			'is_active' => defined( 'SEOPRESS_VERSION' ),
			'args' => array(
				'title' => __( 'The SEOPress Meta synchronization','specific-content-for-mobile' ),
				'type' => 'select',
				'value' => isset( $other_meta['seopress_cpt'] ) ? esc_attr( $other_meta['seopress_cpt'] ) : 'synchronized',
				'options' => array(
					'synchronized' => __( 'Synchronize desktop and mobile metadata','specific-content-for-mobile' ),
					'separated' => __( 'Allow mobile versions having their own metadata','specific-content-for-mobile' )
				),
			),
			'prefix' => array( '_seopress' ),
			'default' => 'synchronized'
		)
	);
	$return = apply_filters( 'eos_scfm_meta_integration_array',$default,$other_meta );
	if( !is_array( $return ) || !isset( $return['eos_fdp'] ) ) return $default;
	return $return;
}
