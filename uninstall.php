<?php
if( !defined( 'WP_UNINSTALL_PLUGIN') ){
    die;
}
delete_post_meta_by_key( 'eos_scfm_desktop_post_id' );
delete_post_meta_by_key( 'eos_scfm_mobile_post_id' );
delete_site_option( 'page_on_front_mobile' );
delete_site_option( 'page_for_posts_mobile' );
delete_site_option( 'eos_scfm_main' );
