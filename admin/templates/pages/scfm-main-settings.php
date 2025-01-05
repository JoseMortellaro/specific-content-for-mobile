<?php
defined( 'ABSPATH' ) || exit; // Exit if accessed directly

$options = eos_scfm_get_main_options_array();
$meta_integrations = eos_scfm_meta_integration_array( $options );
$other_meta = isset( $options['other_meta'] ) ? $options['other_meta'] : array();
$scfm_post_types = array_unique( eos_scfm_post_types() );
$post_types = get_post_types();
?>
<h2 style="margin-top:32px">
	<i class="dashicons-smartphone dashicons"></i>
	<?php printf( esc_html__( 'Specific Content For Mobile v%s','specific-content-for-mobile' ), esc_html( EOS_SCFM_PLUGIN_VERSION ) ); ?>
</h2>
<section style="margin-top:64px">
	<h3><span class="dashicons dashicons-update-alt"></span><?php esc_html_e( 'Main Settings','specific-content-for-mobile' ); ?></h3>
	<?php
	wp_nonce_field( 'eos_scfm_nonce_main_setts','eos_scfm_nonce_main_setts' );
	$args = array(
		'metaboxes' => array(
			'title' => esc_html__( 'General metadata synchronization','specific-content-for-mobile' ),
			'type' => 'select',
			'value' => isset( $other_meta['metaboxes'] ) ? esc_attr( $other_meta['metaboxes'] ) : false,
			'options' => array(
				'separated' => esc_attr__( 'Allow mobile versions having their own metadata','specific-content-for-mobile' ),
				'synchronized' => esc_attr__( 'Synchronize desktop and mobile metadata','specific-content-for-mobile' )
			)
		)
	);
	foreach( $meta_integrations as $key => $arr ){
		if( $arr['is_active'] ){
			$args[$key] = $arr['args'];
		}
	}
	eos_scfm_options_table( apply_filters( 'eos_scfm_options_args',$args ) );
	?>
</section>
<section>
	<div class="scfm-subsection">
		<h3><span class="dashicons dashicons-plugins-checked"></span><?php esc_html_e( 'Plugins','specific-content-for-mobile' ); ?></h3>
		<?php
		$active_plugins = get_option( 'active_plugins' );
		if( $active_plugins ){
			$n = count( $active_plugins );
			if( $n > 1 ){ ?>
				<p><?php esc_html_e( 'If you want to disable specific plugins on mobile, you need Freesoul Deactivate Plugins','specific-content-for-mobile' ); ?></p>
				<?php
				if( defined( 'EOS_DP_PLUGIN_BASE_NAME' ) ){
				?>
				<div>
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=eos_dp_mobile' ) ); ?>"><?php esc_html_e( 'Select active plugins on mobile with FDP','specific-content-for-mobile' ); ?></a>
				</div>
		<?php } } } ?>
		<div style="margin-top:64px">
			<a style="color:#B07700;font-weight:bold" class="button" href="https://specific-content-for-mobile.com/" rel="noopener" target="_scfm_pro"><?php esc_html_e( 'Upgrade','specific-content-for-mobile' ); ?> <span style="position:relative;top:-8px;<?php echo ( is_rtl() ? 'right' : 'left' ); ?>:-6px;display:inline-block">👑</span></a>
		</div>
	</div>
</section>
<?php eos_scfm_save_button(); ?>
