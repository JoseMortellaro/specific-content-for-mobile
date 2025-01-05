<?php
/*
Template for blog posts preview simulating a mobile device
*/
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
global $post;
$args = $_GET;
$args['scfm_preview'] = true;
$args['t'] = time();
$url = get_permalink( $post->ID );
$desktop_id = eos_scfm_related_desktop_id( $post->ID );
$desktop_post = get_post( $desktop_id );
$src = esc_url( add_query_arg( $args,$url ) );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>" />
		<meta name="viewport" content="width=device-width, minimum-scale=1.0, maximum-scale=1.0" />
		<meta name="robots" content="noindex,nofollow,noarchive">
		<title><?php echo is_object( $desktop_post ) ? esc_html( $desktop_post->post_title ).' | '.esc_html__( 'Mobile Preview','specific-content-for-mobile' ) : esc_html__( 'Mobile Preview','specific-content-for-mobile' ); ?></title>
		<style>
		.eos-hidden,
		.scfm-mobile-phone #scfm-tablet-wrp,
		.scfm-mobile-tablet #scfm-phone-wrp{
			display:none !important
		}
		.scfm-rotated #scfm-to-landscape,
		#scfm-to-portrait,
		.scfm-mobile-phone #scfm-show-phone-wrp,
		.scfm-mobile-tablet #scfm-show-tablet-wrp{
			opacity:0.6;
			pointer-events:none;
			cursor:default
		}
		.scfm-rotated #scfm-to-portrait{
			opacity:1;
			pointer-events:initial
		}
		.scfm-device-wrp iframe,
		#scfm-phone-iframe{
			position:absolute;
			border:none
		}
		#scfm-preview-title,
		#scfm-preview-subtitle{
			font-family: Arial;
			text-align: center;
			margin-top: 16px;
		}
		#scfm-preview-description{
			font-family:Arial;
			text-align:center;
			margin-bottom: 48px;
			font-size:18px
		}
		#scfm-preview-title{
			font-size:24px
		}
		#scfm-preview-subtitle{
			font-size:20px
		}
		@media screen and (max-width:800px){
			#scfm-phone-iframe {
				width: 100%;
				height: 100vh;
			}
			#scfm-preview-title,
			#scfm-preview-subtitle,
			.scfm-buttons-wrp,
			.scfm-device-wrp{
				display:none
			}
			body{
				margin:0;
				padding:0;
				-ms-overflow-style:none;
				scrollbar-width:none				
			}
			body::-webkit-scrollbar{ 
				display:none; 
			}			
		}
		@media screen and (min-width:802px){
			.scfm-buttons-wrp{
				margin-bottom:48px;
				text-align:center
			}
			.scfm-btn{
				border:1px solid #000;
				display:inline-block;
				cursor:pointer;
				background-color:#fff
			}
			.scfm-btn:hover{
				opacity:0.7
			}
			.scfm-button{
				margin:4px;
				font-size:30px;
				color:#000;
				width:35px;
				height:35px;
				display:inline-block
			}			
			#scfm-phone-wrp,
			#scfm-show-phone{
				background-image:url(<?php echo esc_url( EOS_SCFM_PLUGIN_URL . '/assets/img/scfm-phone.png' ); ?>);
				background-repeat:no-repeat;
			}
			#scfm-to-landscape{
				background-image:url(<?php echo esc_url( EOS_SCFM_PLUGIN_URL . '/assets/img/scfm-to-landscape-icon.jpg' ); ?>);
			}
			#scfm-to-portrait{
				background-image:url(<?php echo esc_url( EOS_SCFM_PLUGIN_URL . '/assets/img/scfm-to-portrait-icon.jpg'); ?>);
			}
			#scfm-to-landscape,
			#scfm-to-portrait{
				background-repeat:no-repeat;
				background-size:contain
			}
			#scfm-phone-wrp{	
				background-size:cover;
				position:relative;
				width:365px;
				height:742px;
				margin:0 auto
			}
			.scfm-rotated #scfm-phone-wrp{
				background-image:url(<?php echo esc_url( EOS_SCFM_PLUGIN_URL . '/assets/img/scfm-phone-rotated.png'); ?>);
				background-repeat:no-repeat;
				background-size:cover;
				position:relative;
				width:742px;
				height:365px;
				margin:48px auto
			}
			#scfm-tablet-wrp,
			#scfm-show-tablet{
				background-image:url(<?php echo esc_url( EOS_SCFM_PLUGIN_URL . '/assets/img/scfm-tablet.png'); ?>);
				background-repeat:no-repeat
			}
			#scfm-show-tablet,
			#scfm-show-phone{
				background-size:contain;
				background-position:center center
			}
			#scfm-tablet-wrp{
				background-size:cover;
				position:relative;
				width:780px;
				height:1137px;
				margin:48px auto				
			}
			.scfm-rotated #scfm-tablet-wrp{
				background-image:url(<?php echo esc_url( EOS_SCFM_PLUGIN_URL . '/assets/img/scfm-tablet-rotated.png'); ?>);
				background-repeat:no-repeat;
				background-size:cover;
				position:relative;
				width:1137px;
				height:780px;
				margin:48px auto
			}
			#scfm-phone-iframe{
				<?php echo is_rtl() ? 'right' : 'left'; ?>:22px;
				width:320px;
				height:568px;
				top:89px
			}
			#scfm-tablet-iframe{
				<?php echo is_rtl() ? 'right' : 'left'; ?>:43px;
				width:695px;
				height:922px;
				top:105px
			}
			.scfm-rotated #scfm-phone-iframe{
				<?php echo is_rtl() ? 'right' : 'left'; ?>:89px;
				width:568px;
				height:319px;
				top:23px
			}
			.scfm-rotated #scfm-tablet-iframe{
				<?php echo is_rtl() ? 'right' : 'left'; ?>:105px;
				width:927px;
				height:695px;
				top:45px
			}
		}
		</style>
	</head>
	<body class="scfm-preview scfm-mobile-phone">
		<?php if( is_object( $desktop_post ) ){ ?>
		<h1 id="scfm-preview-title"><?php echo esc_html( $desktop_post->post_title ); ?></h1>
		<h2 id="scfm-preview-subtitle"><?php printf( esc_html__( 'Mobile preview of %s','specific-content-for-mobile' ), esc_url( get_permalink( $desktop_id ) ) ); ?></h2>
		<p id="scfm-preview-description"><?php printf( esc_html__( 'Content (from ID %s) replaced with the content of the mobile version (from ID %s)','specific-content-for-mobile' ), esc_html( $desktop_id ), esc_html( $post->ID ) ); ?></p>
		<?php } ?>		
		<div class="scfm-buttons-wrp">
			<span id="scfm-show-phone-wrp" class="scfm-btn"><span id="scfm-show-phone" class="scfm-button" title="<?php esc_attr_e( 'Preview on phone','specific-content-for-mobile' ); ?>"></span></span>
			<?php if( eos_scfm_is_tablet_mobile() ){ ?>
			<span id="scfm-show-tablet-wrp" class="scfm-btn"><span id="scfm-show-tablet" class="scfm-button" title="<?php esc_attr_e( 'Preview on tablet','specific-content-for-mobile' ); ?>"></span></span>
			<?php } ?>
			<span id="scfm-to-landscape-wrp" class="scfm-btn"><span id="scfm-to-landscape" class="scfm-button" title="<?php esc_attr_e( 'Rotate device','specific-content-for-mobile' ); ?>"></span></span>
			<span id="scfm-to-portrait-wrp" class="scfm-btn"><span id="scfm-to-portrait" class="scfm-button" title="<?php esc_attr_e( 'Rotate device','specific-content-for-mobile' ); ?>"></span></span>
		</div>
		<div id="scfm-phone-wrp">
			<iframe id="scfm-phone-iframe" src="<?php echo esc_url( $src ); ?>"></iframe>
		</div>
		<?php if( eos_scfm_is_tablet_mobile() ){ ?>
		<div id="scfm-tablet-wrp" class="scfm-device-wrp">
			<iframe id="scfm-tablet-iframe" src="<?php echo esc_url( $src ); ?>"></iframe>
		</div>
		<?php } ?>
	<script>
	var scfm_body = document.getElementsByTagName('body')[0];
	document.getElementById('scfm-to-landscape').addEventListener('click',function(){
		scfm_addClass(scfm_body,'scfm-rotated');
	});
	document.getElementById('scfm-to-portrait').addEventListener('click',function(){
		scfm_removeClass(scfm_body,'scfm-rotated');
	});
	document.getElementById('scfm-show-phone').addEventListener('click',function(){
		scfm_removeClass(scfm_body,'scfm-mobile-tablet');
		scfm_addClass(scfm_body,'scfm-mobile-phone');
	});
	document.getElementById('scfm-show-tablet').addEventListener('click',function(){
		scfm_removeClass(scfm_body,'scfm-mobile-phone');
		scfm_addClass(scfm_body,'scfm-mobile-tablet');
	});
	function scfm_removeClass(el,class_name){
		el.className = el.className.replace(' ' + class_name,'').replace(class_name,'');
	}
	function scfm_addClass(el,class_name){
		el.className = el.className.replace(' ' + class_name,'').replace(class_name,'') + ' ' + class_name;
		el.className.className = el.className.replace('  ',' ').trim();
	}
	</script>
	</body>
</html>