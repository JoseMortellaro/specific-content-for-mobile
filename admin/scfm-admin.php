<?php
defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

// Require file including information to integrate other plugins metadata synchronization.
require_once EOS_SCFM_PLUGIN_DIR.'/inc/scfm-integration-meta.php';
$scfm_post_types = eos_scfm_post_types();

// It checks if the theme supports the blog page mobile version.
function eos_scfm_is_posts_page_supported( $feature ){
	$arr = get_theme_support( 'specific_content_form_mobile' );
	if( is_array( $arr ) ){
		$arr = $arr[0];
		return isset( $arr[$feature] ) && $arr[$feature];
	}
	return false;
}

add_filter( 'load_textdomain_mofile', 'eos_scfm_load_translation_file',99,2 ); //loads plugin translation files
// Filter function to read plugin translation files.
function eos_scfm_load_translation_file( $mofile, $domain ) {
	if ( 'specific-content-for-mobile' === $domain ) {
		$loc = function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
		$mofile = EOS_SCFM_PLUGIN_DIR.'/languages/specific-content-for-mobile-' . $loc . '.mo';
	}
	return $mofile;
}
load_plugin_textdomain( 'specific-content-for-mobile',false,EOS_SCFM_PLUGIN_DIR.'/languages/' );

add_action( 'in_admin_header','eos_scfm_remove_mobile_permalink' );
// It removes the permalink for the mobile version.
function eos_scfm_remove_mobile_permalink(){
	global $post;
	if( is_object( $post ) ){
		$desktop_id = eos_scfm_related_desktop_id( $post->ID );
		if( $desktop_id > 0 ){
			add_filter( 'get_sample_permalink_html', '__return_false' );
		}
	}
}

add_action( 'admin_enqueue_scripts', 'eos_scfm_enqueue_scripts' );
// It enqueues the admin scripts and style.
function eos_scfm_enqueue_scripts(){
	wp_enqueue_style( 'specific-content-for-mobile',EOS_SCFM_PLUGIN_URL.'/admin/assets/css/scfm-backend.css' );
}
add_action( 'admin_action_eos_scfm_duplicate_post_as_draft', 'eos_scfm_duplicate_post_as_draft' );
// It creates post duplicate as a draft and redirects then to the edit post screen.
function eos_scfm_duplicate_post_as_draft(){
	global $wpdb;
	if (! ( isset( $_GET['post'] ) || ( isset($_REQUEST['action']) && 'eos_scfm_duplicate_post_as_draft' === $_REQUEST['action'] ) ) ) {
		wp_die( esc_html__( 'No post for mobile has been supplied!','specific-content-for-mobile' ) );
	}
	$post_id = absint( $_GET['post'] );
	if( 0 === $post_id ){
		wp_die( esc_html__( 'No post for mobile has been supplied!','specific-content-for-mobile' ) );
	}
	/*
	 * and all the original post data then
	 */
	$post = get_post( $post_id );
	$current_user = wp_get_current_user();
	$new_post_author = $current_user->ID;
	/*
	 * if post data exists, create the post duplicate
	 */
	if( isset( $post ) && $post != null ){
		/*
		 * new post data array
		 */
		$args = array(
			'comment_status' => $post->comment_status,
			'ping_status'    => $post->ping_status,
			'post_author'    => $new_post_author,
			'post_content'   => $post->post_content,
			'post_excerpt'   => $post->post_excerpt,
			'post_name'      => $post->post_name.'-'.apply_filters( 'scfm_mobile_slug','mobile' ),
			'post_parent'    => $post->post_parent,
			'post_password'  => $post->post_password,
			'post_status'    => 'draft',
			'post_title'     => $post->post_title,
			'post_type'      => $post->post_type,
			'to_ping'        => $post->to_ping,
			'menu_order'     => $post->menu_order
		);
		/*
		 * insert the post by wp_insert_post() function
		 */
		$new_post_id = wp_insert_post( $args );
		update_post_meta( $post_id,'eos_scfm_mobile_post_id',$new_post_id );
		update_post_meta( $new_post_id,'eos_scfm_desktop_post_id',$post_id );
		if( absint( get_option( 'page_on_front' ) ) === $post_id ){
			update_option( 'page_on_front_mobile',absint( $new_post_id ) );
		}
		if( get_option( 'page_for_posts' ) === $post_id ){
			update_option( 'page_for_posts_mobile',absint( $new_post_id ) );
		}
		/*
		 * get all current post terms ad set them to the new post draft
		 */
		$taxonomies = get_object_taxonomies($post->post_type); // returns array of taxonomy names for post type, ex array("category", "post_tag");
		foreach ($taxonomies as $taxonomy) {
			$post_terms = wp_get_object_terms( $post_id,$taxonomy,array( 'fields' => 'slugs' ) );
			wp_set_object_terms( $new_post_id,$post_terms,$taxonomy, false );
		}
		/*
		 * duplicate all post meta just in two SQL queries
		 */
		$post_meta_infos = $wpdb->get_results("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$post_id");
		if ( count( $post_meta_infos)!== 0 ) {
			$sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
			foreach( $post_meta_infos as $meta_info ){
				$meta_key = $meta_info->meta_key;
				$meta_value = addslashes($meta_info->meta_value);
				$sql_query_sel[]= "SELECT $new_post_id, '$meta_key', '$meta_value'";
			}
			$sql_query.= implode(" UNION ALL ", $sql_query_sel);
			$wpdb->query( $sql_query );
		}
		/*
		 * finally, redirect to the edit post screen for the new draft
		 */
		wp_redirect( admin_url( 'post.php?action=edit&post=' . $new_post_id ) );
		exit; //no code after redirection;
	} else {
		wp_die( sprintf( esc_html__( 'Mobile version failed, could not find original post: %s','specific-content-for-mobile' ), esc_html( $post_id ) ) );
	}
}
add_filter( 'post_row_actions', 'eos_scfm_duplicate_post_link', 10, 2 );
add_filter( 'page_row_actions', 'eos_scfm_duplicate_post_link', 10, 2 );

// Add the duplicate link to action list for post_row_actions.
function eos_scfm_duplicate_post_link( $actions,$post ) {
	if ( current_user_can('edit_posts') && in_array( $post->post_type,eos_scfm_post_types() ) ){
		$mobile_post_id = eos_scfm_related_mobile_id( $post->ID );
		$desktop_post_id = eos_scfm_related_desktop_id( $post->ID,true );
		if( $mobile_post_id  > 0 && 0 === $desktop_post_id ){
			$actions['duplicate_for_mobile'] = '<a href="'.admin_url( 'post.php?action=edit&amp;post=' . $mobile_post_id ).'" title="'.esc_attr__( 'Edit mobile','specific-content-for-mobile' ).'" rel="permalink">'.esc_html__( 'Edit mobile','specific-content-for-mobile' ).'</a>';
		}
		else{
			if( 0 === $desktop_post_id ){
				$actions['edit_mobile'] = '<a href="admin.php?action=eos_scfm_duplicate_post_as_draft&amp;post=' .esc_attr( $post->ID ). '" title="'.esc_attr__( ' Create mobile version','specific-content-for-mobile' ).'" rel="permalink">'.esc_html__( 'Create mobile version','specific-content-for-mobile' ).'</a>';
			}
			elseif( $desktop_post_id > 0 ){
				if( isset( $actions['view'] ) ){
					$args = array();
					$preview_link = get_preview_post_link( $mobile_post_id );
					if( $preview_link ){
						$nonce = wp_create_nonce( 'post_preview_'.$mobile_post_id );
						$args['preview_id'] = $mobile_post_id;
						$args['preview_nonce'] = $nonce;
						$args['preview'] = 'true';
						$preview_link = add_query_arg( $args,$preview_link );
						$actions['view'] = '<a href="'.esc_url( $preview_link ).'" title="'.esc_attr__( 'Preview mobile','specific-content-for-mobile' ).'" rel="permalink" target="wp-preview-'.$mobile_post_id.'">'.esc_html__( 'Preview mobile','specific-content-for-mobile' ).'</a>';
					}
				}
			}
		}
	}
	return $actions;
}

add_filter( 'display_post_states','eos_scfm_post_status', 2, 99 );
// It adds the mobile version status in the posts table.
function eos_scfm_post_status( $states,$post ){
	$desktop_post_id = eos_scfm_related_desktop_id( $post->ID,true );
	if( $desktop_post_id ){
		$desktop_post = get_post( $desktop_post_id );
		if( !is_object( $desktop_post ) ) return $states;
		if( isset( $states['private'] ) ){
			unset( $states['private'] );
		}
		$states[] = sprintf( esc_html__( '%s Mobile Version of %s','specific-content-for-mobile' ),'<span class="dashicons dashicons-smartphone"> </span>',$desktop_post->post_title );
	}
	return $states;
}
foreach( eos_scfm_post_types() as $post_type ){
	add_filter( 'manage_'.$post_type.'_posts_columns', 'eos_scfm_post_columns_head' );
	add_action( 'manage_'.$post_type.'s_custom_column', 'eos_scfm_post_columns_content', 10, 2 );
}

// Add new column to posts table list.
function eos_scfm_post_columns_head( $columns ){
	$cb = $columns['cb'];
	unset( $columns['cb'] );
	$title = $columns['title'];
	unset( $columns['title'] );
	$GLOBALS['scfm_is_blog_page_supported'] = eos_scfm_is_posts_page_supported( 'posts_page' );
	$GLOBALS['scfm_posts_page'] = get_option( 'page_for_posts' );
    return array_merge( array(
			'cb' => $cb,
			'title' => $title,
			'eos_scfm_device' => '<span class="dashicons dashicons-desktop"></span>|<span class="dashicons dashicons-smartphone"></span>',
		),$columns
	);
}
//Set the content for the added column in the posts table lists
function eos_scfm_post_columns_content( $column_name, $post_ID ) {
    if( $column_name === 'eos_scfm_device' ) {
		$desktop_id = eos_scfm_related_desktop_id( $post_ID );
		$mobile_id = eos_scfm_related_mobile_id( $post_ID );
		$desktop_id_link = $desktop_id > 0 ? $desktop_id : $post_ID;
		$mobile_id_link = $mobile_id > 0 ? $mobile_id : '';
		$desktop_link = '' !== $desktop_id_link ? array( '<a title="'.esc_attr__( 'Edit Desktop version','specific-content-for-mobile' ).'" href="'.get_edit_post_link( $desktop_id_link ).'">','</a>' ) : array( '','' );
		$mobile_link = $mobile_id > 0 || $desktop_id > 0 ? array( '<a title="'.esc_attr__( 'Edit Mobile version','specific-content-for-mobile' ).'" href="'.get_edit_post_link( $mobile_id_link ).'">','</a>' ) : array( '','' );
		$mobile_display = $desktop_id > 0 || $mobile_id > 0 ? 'inline-block !important' : 'none !important';
		$desktop_opacity  = $desktop_id_link > 0 || $mobile_id > 0 ? 1 : 0.4;
		$mobile_new = 'none !important' === $mobile_display ? '<a href="admin.php?action=eos_scfm_duplicate_post_as_draft&amp;post='.absint( $post_ID ).'" title="'.esc_html__( ' Create mobile version','specific-content-for-mobile' ).'" rel="permalink"><span class="dashicons dashicons-plus"></span></a>' : '';
		echo wp_kses_post( $desktop_link[0].'<span style="opacity:'.esc_attr( $desktop_opacity ).'" class="dashicons dashicons-desktop"></span>'.$desktop_link[1].$mobile_link[0].'<span style="display:'.esc_attr( $mobile_display ).'" class="dashicons dashicons-smartphone"></span>'.$mobile_link[1].$mobile_new );
		global $scfm_is_blog_page_supported,$scfm_posts_page;
		if( !$scfm_is_blog_page_supported && $post_ID === absint( $scfm_posts_page ) ){
			?>
			<span class="dashicons dashicons-info" title="<?php esc_attr_e( "Your theme doesn't declare full support for the blog page mobile version, something may not work as expected on the blog mobile version","specific-content-for-mobile" ); ?>"></span>
			<?php
			return;
		}
    }
}
add_action( 'wp_trash_post','eos_scfm_before_post_deletion' );
// It manages the mobile and desktop versions when a post or page is deleted.
function eos_scfm_before_post_deletion( $post_id ){
	static $called = false;
	if( $called ) return;
	$called = true;
	global $post_type;
	if( !in_array( $post_type,eos_scfm_post_types() ) ) return;
	$desktop_id = eos_scfm_related_desktop_id( $post_id );
	$mobile_id = eos_scfm_related_mobile_id( $post_id );
	if( $desktop_id > 0 ){
		delete_post_meta( $desktop_id,'eos_scfm_mobile_post_id' );
	}
	if( $mobile_id > 0 ){
		wp_trash_post( $mobile_id );
	}
	$page_for_posts_mobile = get_option( 'page_for_posts_mobile' );
	$page_on_front_mobile = get_option( 'page_on_front_mobile' );
	if( $page_for_posts_mobile == $mobile_id || $page_for_posts_mobile == $post_id ){
		delete_option( 'page_for_posts_mobile' );
	}
	if( $page_on_front_mobile == $mobile_id || $page_on_front_mobile == $post_id ){
		delete_option( 'page_on_front_mobile' );
	}
}

add_action( 'untrash_post','eos_scfm_after_post_untrash' );
// It reassign the mobile ID to the desktop post if no other mobile versions were created.
function eos_scfm_after_post_untrash( $post_id ){
	static $called = false;
	if( $called ) return;
	$called = true;
	$desktop_id = eos_scfm_related_desktop_id( $post_id );
	if( $desktop_id > 0 ){
		$actualMobileId = get_post_meta( $desktop_id,'eos_scfm_mobile_post_id',true );
		if( $desktop_id == get_option( 'page_for_posts' ) ){
			update_option( 'page_for_posts_mobile',$post_id );
		}
		if( $desktop_id == get_option( 'page_on_front' ) ){
			update_option( 'page_on_front_mobile',$post_id );
		}
		if( !$actualMobileId ){
			update_post_meta( $desktop_id,'eos_scfm_mobile_post_id',$post_id );
		}
		else{
			delete_post_meta( $post_id,'eos_scfm_desktop_post_id' );
		}
	}
}
add_action( 'add_meta_boxes', 'eos_scfm_add_meta_box' );
// It adds the meta box to the page and post screen.
function eos_scfm_add_meta_box(){
    add_meta_box(
        'specific-content-for-mobile',
        esc_attr__( 'Mobile version','specific-content-for-mobile' ),
        'eos_scfm_metabox_callback',
        eos_scfm_post_types(),
        'side',
        'high'
    );
}
// Callback for the metabox.
function eos_scfm_metabox_callback( $post ){
	$desktop_id = eos_scfm_related_desktop_id( $post->ID );
	$posts = get_posts( array( 'post_type' => $post->post_type,'posts_per_page' => -1,'post_status' => 'any' ) );
	wp_nonce_field( 'eos_scfm_metabox','eos_scfm_metabox' );
  	wp_enqueue_script( 'scfm',EOS_SCFM_PLUGIN_URL.'/admin/assets/js/scfm-admin-single.js',array( 'jquery','suggest' ),EOS_SCFM_PLUGIN_VERSION,true );
	wp_localize_script( 'scfm','scfm',array( 'id' => $post->ID,'post_type' => $post->post_type,'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
	$headers = function_exists( 'getallheaders' ) ? getallheaders() : false;
	if( $headers ) echo '<input type="hidden" id="scfm_headers" name="scfm_headers" value="'.esc_attr( wp_json_encode( $headers ) ).'" />';
	if( $desktop_id > 0 ){
		// It's a mobile version.
		$selection_desktop = '<input type="text" class="eos-scfm-suggest-page" value="'.esc_attr( get_the_title( $desktop_id ) ).'" />';
		$selection_desktop .= '<input type="hidden" name="eos_scfm_desktop_post_id" id="eos_scfm_desktop_post_id" class="eos-scfm-suggest-page-id" value="'.esc_attr( $desktop_id ).'" placeholder="'.esc_attr__( 'Start typing...','specific-content-for-mobile' ).'" />';
		?><p>
		<span class="dashicons dashicons-laptop scfm-meta-desktop"></span>
		<?php printf( esc_html__( 'Related desktop version %s','specific-content-for-mobile' ), $selection_desktop ); //phpcs:ignore WordPress.Security.EscapeOutput -- The escaping was already applied while filling $selection_desktop. ?></p>
		<?php
		eos_scfm_plugins_on_mobile_warning();
		return;
	}
	else{
		$mobile_id = eos_scfm_related_mobile_id( $post->ID );
		$mobile_title = $mobile_id > 0 ? get_the_title( $mobile_id ) : '';
		$selection_mobile = '<input type="text" class="eos-scfm-suggest-page" value="'.esc_attr( $mobile_title ).'" placeholder="'.esc_attr__( 'Start typing...','specific-content-for-mobile' ).'" />';
		$selection_mobile .= '<input type="hidden" name="eos_scfm_mobile_post_id" id="eos_scfm_mobile_post_id" class="eos-scfm-suggest-page-id" value="'.esc_attr( $mobile_id ).'"/>';
		if( $mobile_id > 0 ){
			// It's a desktop version that has a mmobile version.
			?><p>
			<span class="dashicons dashicons-smartphone scfm-meta-mobile"></span>
			<?php printf( esc_html__( 'Related mobile version: %s','specific-content-for-mobile' ), $selection_mobile ); //phpcs:ignore WordPress.Security.EscapeOutput -- The escaping was already applied while filling $selection_mobile. ?></p>
			<?php
		}
	}
	$actions = eos_scfm_duplicate_post_link( array(),$post );
	if( !empty( $actions ) ){
		foreach( $actions as $action ){
			echo '<span class="sfc-button button">' . wp_kses_post( $action ) . '</span>';
		}
	}
}

add_action( 'save_post', 'eos_scfm_save_metabox', 1, 2 );

// Save SCFM meta data.
function eos_scfm_save_metabox( $post_id, $post ) {
	do_action( 'scfm_before_save_metabox',$post_id,$post );
	delete_site_transient( 'scfm_debug' );
	if (
		( !isset( $_POST['eos_scfm_metabox'] ) || !wp_verify_nonce( $_POST['eos_scfm_metabox'],'eos_scfm_metabox' ) )
		|| ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		|| ( defined( 'DOING_AJAX' ) && DOING_AJAX )
		|| ( defined( 'DOING_CRON' ) && DOING_CRON )
		|| ( !current_user_can( 'edit_posts',$post_id ) )
		|| ( 'revision' === get_post_type( $post ) )
	){
		return;
	}
	$options = eos_scfm_get_main_options_array();
	if( isset( $_POST['eos_scfm_desktop_post_id'] ) ){
		// $post_id is the ID of a mobile version.
		$actual_desktop_id = eos_scfm_related_desktop_id( $post_id );
		if( absint( $_POST['eos_scfm_desktop_post_id'] ) > 0 ){
			if( $actual_desktop_id > 0 && absint( $_POST['eos_scfm_desktop_post_id'] ) !== $actual_desktop_id ){
				$actual_mobile_id = eos_scfm_related_mobile_id( $actual_desktop_id );
				if( $actual_mobile_id > 0 ){
					delete_post_meta( $actual_mobile_id,'eos_scfm_desktop_post_id' ); //unlink the desktop page from the older mobile version
				}
				delete_post_meta( $actual_desktop_id,'eos_scfm_mobile_post_id' ); //unlink the mobile page from the older desktop version
				update_post_meta( $post_id,'eos_scfm_desktop_post_id',absint( $_POST['eos_scfm_desktop_post_id'] ) ); //link new desktop page to the mobile page
				$desktop_ids = isset( $options['desktop_ids'] ) ? array_map( 'absint',$options['desktop_ids'] ) : array();
				$desktop_ids[] = absint( $_POST['eos_scfm_desktop_post_id'] );
				$nd = array_search( $actual_desktop_id,$desktop_ids );
				if( $nd ){
					unset( $desktop_ids[$nd]  );
				}
				$options['desktop_ids'] = count( $desktop_ids ) > 0 ? array_unique( $desktop_ids ) : $desktop_ids;
				$mobile_ids = isset( $options['mobile_ids'] ) ? array_map( 'absint',$options['mobile_ids'] ) : array();
				$mobile_ids[] = $post_id;
				$nm = array_search( $actual_mobile_id,$mobile_ids );
				if( $nm ){
					unset( $mobile_ids[$nm]  );
				}
				$options['mobile_ids'] = count( $mobile_ids ) > 0 ? array_unique( $mobile_ids ) : $mobile_ids;
				update_post_meta( absint( $_POST['eos_scfm_desktop_post_id'] ),'eos_scfm_mobile_post_id',$post_id );
			}
		}
		else{
			// We unlink desktop and mobile versions.
			delete_post_meta( $actual_desktop_id,'eos_scfm_mobile_post_id' );
			delete_post_meta( $post_id,'eos_scfm_desktop_post_id' );
		}
	}
	elseif( isset( $_POST['eos_scfm_mobile_post_id'] ) ){
		// $post_id is the ID of a desktop version.
		$actual_mobile_id = eos_scfm_related_mobile_id( $post_id );
		if( absint( $_POST['eos_scfm_mobile_post_id'] ) > 0 ){
			if( $actual_mobile_id > 0 && absint( $_POST['eos_scfm_mobile_post_id'] ) !== $actual_mobile_id ){
				$actual_desktop_id = eos_scfm_related_desktop_id( $actual_mobile_id );
				if( $actual_desktop_id > 0 ){
					delete_post_meta( $actual_desktop_id,'eos_scfm_mobile_post_id' );
				}
				delete_post_meta( $actual_mobile_id,'eos_scfm_desktop_post_id' );
				update_post_meta( $post_id,'eos_scfm_mobile_post_id',absint( $_POST['eos_scfm_mobile_post_id'] ) );
				update_post_meta( absint( $_POST['eos_scfm_mobile_post_id'] ),'eos_scfm_desktop_post_id',$post_id );
				$mobile_ids = isset( $options['mobile_ids'] ) ? array_map( 'absint',$options['mobile_ids'] ) : array();
				$mobile_ids[] = absint( $_POST['eos_scfm_mobile_post_id'] );
				$nm = array_search( $actual_mobile_id,$mobile_ids );
				if( $nm ){
					unset( $mobile_ids[$nm]  );
				}
				$options['mobile_ids'] = count( $mobile_ids ) > 0 ? array_unique( $mobile_ids ) : $mobile_ids;

				$desktop_ids = isset( $options['desktop_ids'] ) ? array_map( 'absint',$options['desktop_ids'] ) : array();
				$desktop_ids[] = $post_id;
				$nd = array_search( $actual_desktop_id,$desktop_ids );
				if( $nd ){
					unset( $desktop_ids[$nd]  );
				}
				$options['desktop_ids'] = count( $desktop_ids ) > 0 ? array_unique( $desktop_ids ) : $desktop_ids;


			}
		}
		else{
			// We unlink desktop and mobile versions.
			delete_post_meta( $actual_mobile_id,'eos_scfm_desktop_post_id' );
			delete_post_meta( $post_id,'eos_scfm_mobile_post_id' );
		}
	}
	update_option( 'eos_scfm_main',$options );
	if( isset( $actual_desktop_id ) && $actual_desktop_id && absint( $actual_desktop_id ) > 0 ){
		$debug = scfm_debug_post( $actual_desktop_id );
		if( $debug && is_array( $debug ) ){
			if( !(  isset( $debug['device_on_desktop'] ) && isset( $debug['device_on_mobile'] ) && isset( $debug['cache'] ) && $debug['device_on_desktop'] && $debug['device_on_mobile'] && $debug['cache'] ) ){
				$doc_url = 'https://wordpress.org/support/topic/the-caching-plugin-if-any-must-distinguish-between-mobile-and-desktop/';
				$forum_url = 'https://wordpress.org/support/plugin/specific-content-for-mobile/';
				$msg = '<p><b>'.esc_html__( 'ISSUE DETECTED!','scfm' ).'</b></p>';
				$msgA = array(
					'device_on_desktop' => __( 'Desktop not detected simulating a desktop device.','scfm' ),
					'device_on_mobile' => __( 'Mobile not detected simulating a mobile device.','scfm' ),
					'cache' => __( 'It looks the page is served by cache but without distinguishing between desktop and mobile.','scfm' )
				);
				foreach( $debug as $k => $v ){
					$msg .= false === $v ? '<p>'.esc_html( $msgA[$k] ).'</p>' : '';
				}
				$msg .= '<p>'.wp_kses(
					sprintf(
						__( 'Read %shere%s. It can help to understand what is going on.','scfm' ),
						'<a href="'.$doc_url.'" target="_blank">',
						'</a>'
					),
					array( 'a' => array( 'href' => array(),'target' => array() ) )
					).'</p>';
				$msg .= '<p>'.wp_kses( sprintf( __( "If it's still not clear, open a thread on the %ssupport forum%s",'scfm' ),'<a href="'.$forum_url.'" target="_blank">','</a>' ),array( 'a' => array( 'href' => array(),'target' => array() ) ) ).'</p>';
				set_site_transient( 'scfm_debug',$msg,60*60*24*7 );
			}
		}
	}
}

// Debug post, check if there are any issues.
function scfm_debug_post( $actual_desktop_id ){
	$url = get_permalink( $actual_desktop_id );
	if( $url ){
		$args = array();
		$cookies = array();
		foreach ( $_COOKIE as $name => $value ) {
			$cookies[sanitize_key( $name )] = sanitize_text_field( $value );
		}
		$headers = false;
		if( isset( $_POST['scfm_headers'] ) && !empty( $_POST['scfm_headers'] ) ){
			$headers = json_decode( sanitize_text_field( stripslashes( $_POST['scfm_headers'] ) ),true );
		}
		if( $headers ){
			$args['headers'] = $headers;
		}
		else{
			if( isset( $_SERVER['HTTP_AUTHORIZATION'] ) && !empty( $_SERVER['HTTP_AUTHORIZATION'] ) ){
				$args['headers'] = array(
						'Authorization' => sanitize_text_field( $_SERVER['HTTP_AUTHORIZATION'] )
				);
			}
			elseif( isset( $_SERVER['PHP_AUTH_USER'] ) && isset( $_SERVER['PHP_AUTH_PW'] ) && !empty( $_SERVER['PHP_AUTH_USER'] ) ){
				$credentials = base64_encode( $_SERVER['PHP_AUTH_USER'].':'.$_SERVER['PHP_AUTH_PW'] );
				$args['headers'] = array(
					'Authorization' => sanitize_text_field( 'Basic '.$credentials )
				);
			}
		}
		$args['headers']['Accept-Encoding'] = 'gzip, deflate';
		$args['cookies'] = $cookies;
		$response_desktop = wp_remote_get( $url,$args );
		if( ! is_wp_error( $response_desktop ) ){
			$body_desktop = wp_remote_retrieve_body( $response_desktop );
			$debug_desktop = scfm_get_debug_from_body( $body_desktop );
			$user_agent = 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/99.0.4844.51 Mobile Safari/537.36';
			$args['user-agent'] = $user_agent;
			$args['headers']['User-Agent'] = $user_agent;

			$response_mobile = wp_remote_get( add_query_arg( 'user_device', 'mobile', $url ), $args );
  			if( ! is_wp_error( $response_mobile ) ){
				$body_mobile = wp_remote_retrieve_body( $response_mobile );
				$debug_mobile = scfm_get_debug_from_body( $body_mobile );
				return array(
					'device_on_desktop' => isset( $debug_desktop['device'] ) && 'desktop' === $debug_desktop['device'],
					'device_on_mobile' => isset( $debug_mobile['device'] ) && 'mobile' === $debug_mobile['device'],
					'cache' => isset( $debug_desktop['microtime'] ) && isset( $debug_mobile['microtime'] ) && $debug_desktop['microtime'] !== $debug_mobile['microtime']
				);
			}
		}
	}
	return false;
}

// Warn the user if some issues are detected.
add_action( 'admin_notices',function() {
	?>
	<script id="scfm-notices-js">
	function eos_scfm_dismiss_notice(e,action_name,nonce_name){
		if(e.target.className.indexOf("notice-dismiss") > -1){
			var req = new XMLHttpRequest(),fd=new FormData();
			req.onload = function(e){console.log(e.target.responseText);};
			fd.append("nonce",document.getElementById(nonce_name).value);
			req.open("POST","<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>?action=" + action_name, true);
			req.send(fd);
		}
	}
	</script>
	<?php
	$offer = true || get_user_meta( get_current_user_id(), 'scfm_offer', false ); // we don't show any notice.
	if( ! $offer && ( ! defined( 'SCFM_OFFER_NOTICE' ) || false !== SCFM_OFFER_NOTICE ) ) {
		wp_nonce_field( 'scfm_dismiss_offer_nonce','scfm_dismiss_offer_nonce' );
	?>
	<style id="scfm-offer-css">
	#eos_scfm_dismiss_offer p,#eos_scfm_dismiss_offer span{color:#fff !important}
	#eos_scfm_dismiss_offer .notice-dismiss{
		width: 40px;
    	height: 35px;
	}
	#eos_scfm_dismiss_offer .notice-dismiss:before{
		width:100%;
		text-align:<?php echo is_rtl() ? 'left' : 'right'; ?>;
		color:#fff;
		font-size:1.5rem
	}
	#eos_scfm_dismiss_offer strong{
		text-transform: uppercase;
		letter-spacing: 4px;
		font-size: 1rem;
	}
	#eos_scfm_dismiss_offer .button:hover,
	#eos_scfm_dismiss_offer .notice-dismiss:hover {
		opacity: 0.7
	}
	</style>
	<div id="eos_scfm_dismiss_offer" class="gold notice notice-error is-dismissible" style="background:#000;border-bottom-color:transparent !important;border-top-color:transparent !important;border-left-color:transparent !important;border-right-color:transparent !important;padding:20px">
		<p><span>We don't agree with WordPress's recent actions. Something will change with Specific Content For Mobile.</span></p>
		<p class="coupon-paragraph" style="margin-top:20px"><span>If you want to be updated subscribe to our newsletter. You will find the opt-in in the footer of our <a href="https://specific-content-for-mobile.com" rel="noopener" target="_blank">website</a></span></p>
		<p class="coupon-paragraph" style="margin-top:20px"><span>After closing this notice, we will not be able to contact you if you aren't subscribed.</span></p>
	</div>
	<script id="scfm-offer-js">
	document.getElementById("eos_scfm_dismiss_offer").addEventListener("click",function(e){eos_scfm_dismiss_notice(e,"eos_scfm_dismiss_offer","scfm_dismiss_offer_nonce")});
	</script>
	<?php
	}
	if( defined( 'EPC_VERSION' ) || class_exists( 'Endurance_Page_Cache' ) ){
		?>
		<div id="scfm-endurance-cache" class="notice notice-error" style="padding:20px;font-size:25px">
			<p><?php printf( esc_html__( 'Specific Content For Mobile is not compatible with Endurance Cache because it does not distinguish between mobile and desktop!','specific-content-for-mobile' ) ); ?></p>
			<p><?php printf( esc_html__( 'If you want to use SCFM, clear all the cache generated by Endurance Cache, and then remove it via FTP from the folder wp-content/mu-plugins/','specific-content-for-mobile' ) ); ?></p>
			<p><?php printf( esc_html__( 'You have to chose between SCFM and Endurance Cache. No solution will work if you want to keep both of them.','specific-content-for-mobile' ) ); ?></p>
		</div>
		<?php
	}
	$debug = get_site_transient( 'scfm_debug' );
	if( $debug && apply_filters( 'scfm_debug_notice',true ) && ( !defined( 'SCFM_DEBUG_NOTICE' ) || false !== SCFM_DEBUG_NOTICE ) ){
		?>
		<script id="scfm-dismiss-warning">
			document.getElementById("eos_scfm_dismiss_warnings").addEventListener("click",function(e){eos_scfm_dismiss_notice(e,"eos_scfm_dismiss_warnings","scfm_dismiss_warning_nonce")});
		</script>
		<div id="eos_scfm_dismiss_warnings" class="notice notice-error is-dismissible" style="padding:20px;font-size:25px">
			<?php echo wp_kses( $debug,array( 'p' => array(),'b' => array(),'a' => array( 'href' => array(),'target' => array() ) ) ); ?>
		</div>
		<?php
		wp_nonce_field( 'scfm_dismiss_warning_nonce','scfm_dismiss_warning_nonce' );
	}
} );

// Retrieves debugging data from HTML.
function scfm_get_debug_from_body( $body ){
	preg_match( '/eos\-scfm\-d\-(.*)\-device/', $body, $device_match );
	preg_match( '/eos\-scfm\-t\-(.*)\-timestamp/', $body, $timestamp_match );
	$arr = array();
	if( $device_match && isset( $device_match[1] ) ){
		$arr['device'] = $device_match[1];
	}
	if( $timestamp_match && isset( $timestamp_match[1] ) ){
		$arr['microtime'] = str_replace( '-', '.', $timestamp_match[1] );
	}
	return $arr;
}

add_filter( 'plugin_action_links_'.EOS_SCFM_PLUGIN_BASE_NAME,'eos_scfm_plugin_add_settings_link' );
// It adds a link to the action links in the plugins page.
function eos_scfm_plugin_add_settings_link( $links ){
    $settings_link = ' | <a class="eos-wh-setts" href="' . esc_url( admin_url( 'admin.php?page=eos_scfm' ) ) . '">'. esc_html__( 'Settings','specific-content-for-mobile' ). '</a>';
    $settings_link .= ' | <a class="eos-wh-setts" href="' . esc_url( admin_url( 'edit.php?post_type=page' ) ) . '">'. esc_html__( 'Pages','specific-content-for-mobile' ). '</a>';
    $settings_link .= ' | <a class="eos-wh-setts" href="' . esc_url( admin_url( 'edit.php?post_type=post' ) ) . '">'. esc_html__( 'Posts','specific-content-for-mobile' ). '</a>';
    $settings_link .= ' | <a class="eos-wh-setts" style="color:#B07700;font-weight:bold" target="_scfm_pro" rel="noopener" href="https://specific-content-for-mobile.com/">'. __( 'Upgrade','specific-content-for-mobile' ). ' <span style="position:relative;top:-10px;' . ( is_rtl() ? 'right' : 'left' ) . ':-6px;display:inline-block">👑</span></a>';
	array_push( $links, $settings_link );
  	return $links;
}


add_action( 'admin_menu','eos_scfm_add_menu_pages' );
// It adds all needed menu pages.
function eos_scfm_add_menu_pages(){
	add_menu_page( __( 'Specific Content For Mobile','specific-content-for-mobile' ),__( 'Specific Content For Mobile','specific-content-for-mobile' ),'manage_options','eos_scfm','eos_scfm_main_settings_do_page','dashicons-smartphone',60 );
}
// It outputs the main settings page.
function eos_scfm_main_settings_do_page(){
	if( isset( $_GET['page'] ) && 'eos_scfm' === $_GET['page'] ){
		wp_enqueue_script( 'specific-content-for-mobile',EOS_SCFM_PLUGIN_URL.'/admin/assets/js/scfm-admin.js',array(),EOS_SCFM_PLUGIN_VERSION,true );
		wp_localize_script( 'specific-content-for-mobile','eos_scfm',array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
		require_once EOS_SCFM_PLUGIN_DIR.'/admin/templates/pages/scfm-main-settings.php';
	}
}

// It prints a table of options given an array.
function eos_scfm_options_table( $args ){
?>
	<table class="form-table" role="presentation">
		<tbody>
		<?php foreach( $args as $name => $arr ){
			$title = $arr['title'];
			$value = $arr['value'];
			$type = $arr['type'];
			?>
			<tr>
				<th scope="row">
					<label for="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $title ); ?></label>
				</th>
				<td>
					<?php
					if( !isset( $arr['callback'] ) ){
						if( 'select' !== $arr['type'] ){
						?>
						<input class="eos-scfm-option" type="<?php echo esc_attr( $type ); ?>" id="scfm-<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" class="eos-scfm-option<?php echo isset( $arr['class'] ) ? ' '.esc_attr( $arr['class'] ) : ''; ?>" value="<?php echo esc_attr( $value ); ?>" <?php echo 'checkbox' === $type && $value ? ' checked' : ''; ?><?php echo isset( $arr['attr'] ) ? ' '.esc_attr( $arr['attr'] ) : ''; ?>/>
						<?php
						}
						else{
							if( isset( $arr['options'] ) ){
								?><select id="scfm-<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" class="eos-scfm-option"><?php
								foreach( $arr['options'] as $key => $option ){
								?><option value="<?php echo esc_attr( $key ); ?>"<?php echo $key === $arr['value'] ? ' selected' : ''; ?>><?php echo esc_html( $option ); ?></option><?php
								}
								?></select><?php
							}
						}
					}else{
						if( function_exists( $arr['callback'] ) ){
							call_user_func( sanitize_key( $arr['callback'] ) );
						}
					}
					?>
				</td>
			</tr>
		<?php } ?>
		</tbody>
	</table>
<?php
}
// It displays the save button and related messages.
function eos_scfm_save_button(){
	wp_nonce_field( 'eos_scfm_nonce_saving','eos_scfm_nonce_saving' );
	$page = isset( $_GET['page'] ) ? $_GET['page'] : '';
	?>
	<div class="eos-scfm-btn-wrp">
		<input id="eos-scfm-save-options" type="submit" name="submit" class="eos-scfm-save-<?php echo esc_attr( $page ); ?> button button-primary submit-vr-opts" value="<?php esc_attr_e( 'Save changes','specific-content-for-mobile' ); ?>"  />
		<?php eos_scfm_ajax_loader_img(); ?>
		<div style="margin-<?php echo is_rtl() ? 'left' : 'right'; ?>:30px">
			<div id="eos-scfm-opts-msg" class="notice eos-hidden" style="padding:10px;margin:10px;"></div>
		</div>
	</div>
<?php
}
//It displays the ajax loader gif
function eos_scfm_ajax_loader_img(){
	?>
	<img id="eos-scfm-ajax-loader" alt="<?php esc_attr_e( 'Ajax loader','specific-content-for-mobile' ); ?>" class="ajax-loader-img eos-not-visible" width="30" height="30" src="<?php echo esc_url( EOS_SCFM_PLUGIN_URL . '/admin/assets/img/ajax-loader.gif' ); ?>" />
	<?php
}
foreach( $scfm_post_types as $post_type ){
	add_action( 'update_'.sanitize_key( $post_type ).'_meta','eos_scfm_manage_metaboxes',9999,4 );
}
// It manage metaboxes values according to the plugin options.
function eos_scfm_manage_metaboxes( $meta_id,$object_id,$meta_key,$_meta_value ){
	if( in_array( $meta_key,array( 'eos_scfm_desktop_post_id','eos_scfm_mobile_post_id','_eos_deactive_plugins_key' ) ) ) return;
	static $called_keys = array();
	if( in_array( $meta_key,$called_keys ) ) return;
	$called_keys[] = $meta_key;
	$meta_keys = apply_filters( 'eos_scfm_untouchable_metaboxes',array() );
	if( in_array( $meta_key,$meta_keys ) ) return;
	$options = eos_scfm_get_main_options_array();
	$mobile_id = eos_scfm_related_mobile_id( $object_id );
	$desktop_id = eos_scfm_related_desktop_id( $object_id );
	if( eos_scfm_is_meta_key_synchronized( $meta_key,$options ) ){
		if( $desktop_id && $object_id !== $desktop_id ){
			update_post_meta( $desktop_id,$meta_key,$_meta_value );
		}
		if( $mobile_id  && $object_id !== $mobile_id ){
			update_post_meta( $mobile_id,$meta_key,$_meta_value );
		}
	}
	if( $mobile_id > 0 ){
		$mobile_ids = isset( $options['mobile_ids'] ) ? array_map( 'absint',$options['mobile_ids'] ) : array();
		$mobile_ids[] = absint( $mobile_id );
		$options['mobile_ids'] = count( $mobile_ids ) > 0 ? array_unique( $mobile_ids ) : $mobile_ids;
	}
	if( $desktop_id > 0 ){
		$desktop_ids = isset( $options['desktop_ids'] ) ? array_map( 'absint',$options['desktop_ids'] ) : array();
		$desktop_ids[] = absint( $desktop_id );
		$options['desktop_ids'] = count( $desktop_ids ) > 0 ? array_unique( $desktop_ids ) : $desktop_ids;
	}
	update_option( 'eos_scfm_main',$options );
}
// Given the meta key, it checks if the post meta is synchronized.
function eos_scfm_is_meta_key_synchronized( $meta_key,$options ){
	$meta_integrations = eos_scfm_meta_integration_array( $options );
	$other_meta = isset( $options['other_meta'] ) ? $options['other_meta'] : array();
	foreach( $meta_integrations as $key => $arr ){
		if( isset( $arr['prefix'] ) ){
			$args = $arr['args'];
			foreach( $arr['prefix'] as $prefix ){
				if( false !== strpos( $meta_key,$prefix ) ){
					if( isset( $args['value'] ) && 'synchronized' === $args['value'] ){
						return true;
					}
					else{
						return false;
					}
				}
			}
		}
	}
	return isset( $other_meta['metaboxes'] ) && 'synchronized' === $other_meta['metaboxes'];

}

add_filter( 'bulk_actions-edit-post','eos_scfm_unlink_mobile_versions_bulk_item' );
add_filter( 'bulk_actions-edit-page','eos_scfm_unlink_mobile_versions_bulk_item' );
// Add bulk action item in the bulk actions dropdown list to unlink mobile versions.
function eos_scfm_unlink_mobile_versions_bulk_item( $bulk_actions ){
	$bulk_actions['scfm-unlink-mobile-version'] = __( 'Unlink mobile version','specific-content-for-mobile' );
	return $bulk_actions;
}

add_filter( 'handle_bulk_actions-edit-post','eos_scfm_unlink_mobile_versions',10,3 );
add_filter( 'handle_bulk_actions-edit-page','eos_scfm_unlink_mobile_versions',10,3 );
// Handler to execute the bulk action to unlink the mobile versions.
function eos_scfm_unlink_mobile_versions( $redirect_url,$action,$ids ){
	if( $action === 'scfm-unlink-mobile-version') {
		$options = eos_scfm_get_main_options_array();
		$mobile_ids = $options['mobile_ids'];
		$desktop_ids = $options['desktop_ids'];
		foreach( $ids as $id ){
			$mobile_id = eos_scfm_related_mobile_id( $id );
			$desktop_id = eos_scfm_related_desktop_id( $id );
			if( $mobile_id > 0 ){
				if( isset( $mobile_ids[$id] ) ){
					unset( $mobile_ids[array_search( $id,$mobile_ids )] );
				}
				delete_post_meta( $mobile_id,'eos_scfm_desktop_post_id' );
				delete_post_meta( $id,'eos_scfm_mobile_post_id' );
			}
			if( $desktop_id > 0 ){
				if( isset( $desktop_ids[$id] ) ){
					unset( $desktop_ids[array_search( $id,$desktop_ids )] );
				}
				delete_post_meta( $desktop_id,'eos_scfm_mobile_post_id' );
				delete_post_meta( $id,'eos_scfm_desktop_post_id' );
			}
		}
		$nonce = wp_create_nonce( 'scfm-unlink-mobile-version-nonce', 'scfm-unlink-mobile-version-nonce' );
		$redirect_url = add_query_arg( array( 'scfm-unlink-mobile-version' => count( $ids ), 'scfm-unlink-mobile-version-nonce' => $nonce ), $redirect_url );
	}
	return $redirect_url;
}

add_action('admin_notices','eos_scfm_admin_notices',999999999999 );
// Notice after unlinking the mobile versions.
function eos_scfm_admin_notices(){
	if( isset( $_GET['page'] ) && 'eos_scfm' === $_GET['page'] ){
		remove_all_actions( 'admin_notices' );
	}
	if( 
		isset( $_REQUEST['scfm-unlink-mobile-version-nonce'] ) 
		&& wp_verify_nonce( sanitize_text_field( $_REQUEST['scfm-unlink-mobile-version-nonce'] ), 'scfm-unlink-mobile-version-nonce') 
		&& isset( $_REQUEST['scfm-unlink-mobile-version'] ) 
		&& ! empty( $_REQUEST['scfm-unlink-mobile-version'] ) ){
		?>
		<div id="message" class="updated notice is-dismissable">
			<p><?php echo wp_kses_post( sprintf( esc_html__( 'Unlinked %s pages.','specific-content-for-mobile' ), sanitize_text_field( $_REQUEST['scfm-unlink-mobile-version'] ) ) ); ?></p>
		</div>
		<?php
	}
}

add_filter( 'admin_body_class','eos_scfm_admin_body_class' );
//Add a body class in the backend
function eos_scfm_admin_body_class( $classes ){
	if( isset( $_GET['post'] ) & isset( $_GET['action'] ) && 'edit' === $_GET['action'] ){
		if( function_exists( 'get_current_screen' ) ){
			$screen = get_current_screen();
			if( is_object( $screen ) && isset( $screen->id ) && in_array( $screen->id,eos_scfm_post_types() ) ){
				global $post;
				$desktop_id = eos_scfm_related_desktop_id( $post->ID );
				$mobile_id = eos_scfm_related_mobile_id( $post->ID );
				$classes .= ' scfm';
				if( $desktop_id > 0 ){
					$classes .= ' scfm-mobile';
				}
				elseif( $mobile_id > 0 ){
					$classes .= ' scfm-desktop';
				}
			}
		}
	}
	if( isset( $_GET['page'] ) && 'eos_scfm' === $_GET['page'] ){
		$classes .= ' scfm-settings-page';
	}
	return $classes;
}

add_filter( 'preview_post_link','eos_scfm_mobile_preview_post_link',10,2 );
// Add nonce and preview ID to preview post link in case of mobile.
function eos_scfm_mobile_preview_post_link( $preview_link, $post ){
	$desktop_id = eos_scfm_related_desktop_id( $post->ID );
	if( $desktop_id > 0 ){
		$args = array();
		$nonce = wp_create_nonce( 'post_preview_' . $post->ID );
		$args['preview_id'] = $post->ID;
		$args['preview_nonce'] = $nonce;
		$preview_link = add_query_arg( $args,$preview_link );
	}
	return $preview_link;
}

register_activation_hook( EOS_SCFM_PLUGIN_BASE_NAME, function(){
	// It sends an ID to the FDP site to update the active number of installations. Thanks to the md5 function the FDP server will not be able to guess the home url, but it understands the plugin was deactivated on an anonymus site.
	$args = array( 'headers' => array( 'site_id' => md5( get_home_url() ) ) );
	wp_remote_get( 'https://stats.josemortellaro.com/scfm/activated/',$args );
} );

register_deactivation_hook( EOS_SCFM_PLUGIN_BASE_NAME, function(){
	// It sends an ID to the FDP site to update the active number of installations. Thanks to the md5 function the FDP server will not be able to guess the home url, but it understands tthe plugin was deactivated on an anonymus site.
	$args = array( 'headers' => array( 'site_id' => md5( get_home_url() ) ) );
	wp_remote_get( 'https://stats.josemortellaro.com/scfm/deactivated/',$args );
} );


add_filter( 'eos_dp_integration_action_plugins', 'eos_scfm_add_fdp_integration' );
// It adds custom ajax actions to the Actions Settings Pages of Freesoul Deactivate Plugins.
function eos_scfm_add_fdp_integration( $args ){
		$args['specific-content-for-mobile'] = array(
			'is_active' => defined( 'EOS_SCFM_PLUGIN_VERSION' ),
			'ajax_actions' => array(
				'eos_scfm_save_settings' => array( 'description' => __( 'Saving Settings','specific-content-for-mobile' ) )
      )
    );
		return $args;
}

// Warning about plugins on mobile.
function eos_scfm_plugins_on_mobile_warning(){
	if(
		! defined( 'EOS_DP_VERSION' )
		&& ! class_exists( 'PluginOrganizer' )
		&& ! class_exists( 'Plf_setting' )
	){
		$active_plugins = get_option( 'active_plugins' );
		if( $active_plugins ){
			$n = count( $active_plugins );
			if( $n > 15 ){
			?><p class="notice notice-warning">
			<span class="dashicons dashicons-plugins-checked"></span>
			<?php
				printf( esc_html__( "You have %s active plugins. If you want on mobile you can disable the unused ones with %s","specific-content-for-mobile" ), esc_html( $n ),'<a href="https://wordpress.org/plugins/freesoul-deactivate-plugins/" target="_blank" rel="noopener">Freesoul Deactivate Plugins</a>' );
			?></p><?php
			}
		}
	}
}

add_action( 'wp_ajax_eos_scfm_dismiss_warnings', 'eos_scfm_dismiss_warnings' );
// Clear transient that stores the warnings.
function eos_scfm_dismiss_warnings(){
	if( isset( $_POST['nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ),'scfm_dismiss_warning_nonce' ) ){
		delete_site_transient( 'scfm_debug' );
	}
	die();
	exit;
}

add_action( 'wp_ajax_eos_scfm_dismiss_offer', 'eos_scfm_dismiss_offer' );
// Delete the user meta to don't show any more the offer.
function eos_scfm_dismiss_offer(){
	if( isset( $_POST['nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ),'scfm_dismiss_offer_nonce' ) ){
		update_user_meta( get_current_user_id(), 'scfm_offer', true );
	}
	die();
	exit;
}
