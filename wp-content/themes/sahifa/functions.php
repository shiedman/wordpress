<?php

$themename = "Sahifa";
$themefolder = "sahifa";

define ('theme_name', $themename );
define ('theme_ver' , 4 );

// Notifier Info
$notifier_name = $themename;
$notifier_url = "http://themes.tielabs.com/xml/".$themefolder.".xml";

//Docs Url
$docs_url = "http://themes.tielabs.com/docs/".$themefolder;

// Constants for the theme name, folder and remote XML url
define( 'MTHEME_NOTIFIER_THEME_NAME', $themename );
define( 'MTHEME_NOTIFIER_THEME_FOLDER_NAME', $themefolder );
define( 'MTHEME_NOTIFIER_XML_FILE', $notifier_url );
define( 'MTHEME_NOTIFIER_CACHE_INTERVAL', 43200 );

// WooCommerce
define('WOOCOMMERCE_USE_CSS', false);
add_action('woocommerce_before_main_content', 'tie_woocommerce_wrapper_start', 22);
function tie_woocommerce_wrapper_start() {
	echo '<div class="post-listing"><div class="post-inner">';
}
add_action('woocommerce_after_main_content', 'tie_woocommerce_wrapper_start2', 11);
function tie_woocommerce_wrapper_start2() {
  echo '</div></div>';
}
add_action('woocommerce_before_shop_loop', 'tie_woocommerce_wrapper_start3', 33);
function tie_woocommerce_wrapper_start3() {
  echo '<div class="clear"></div>';
}


global $pagenow;
if ( is_admin() && isset( $_GET['activated'] ) && $pagenow == 'themes.php' )
	add_action( 'init', 'tie_woocommerce_image_dimensions', 1 );

function tie_woocommerce_image_dimensions() {
  	$catalog = array(
		'width' 	=> '400',	// px
		'height'	=> '400',	// px
		'crop'		=> 1 		// true
	);
 
	$single = array(
		'width' 	=> '600',	// px
		'height'	=> '600',	// px
		'crop'		=> 1 		// true
	);
 
	$thumbnail = array(
		'width' 	=> '200',	// px
		'height'	=> '200',	// px
		'crop'		=> 1 		// false
	);
 
	// Image sizes
	update_option( 'shop_catalog_image_size', $catalog ); 		// Product category thumbs
	update_option( 'shop_single_image_size', $single ); 		// Single product image
	update_option( 'shop_thumbnail_image_size', $thumbnail ); 	// Image gallery thumbs
}


// Custom Functions 
include (TEMPLATEPATH . '/custom-functions.php');

// Get Functions
include (TEMPLATEPATH . '/functions/home-cats.php');
include (TEMPLATEPATH . '/functions/home-cat-tabs.php');
include (TEMPLATEPATH . '/functions/home-cat-scroll.php');
include (TEMPLATEPATH . '/functions/home-cat-pic.php');
include (TEMPLATEPATH . '/functions/home-cat-videos.php');
include (TEMPLATEPATH . '/functions/home-recent-box.php');
include (TEMPLATEPATH . '/functions/theme-functions.php');
include (TEMPLATEPATH . '/functions/common-scripts.php');
include (TEMPLATEPATH . '/functions/banners.php');
include (TEMPLATEPATH . '/functions/widgetize-theme.php');
include (TEMPLATEPATH . '/functions/default-options.php');
include (TEMPLATEPATH . '/functions/updates.php');

include (TEMPLATEPATH . '/includes/pagenavi.php');
include (TEMPLATEPATH . '/includes/breadcrumbs.php');
include (TEMPLATEPATH . '/includes/wp_list_comments.php');
include (TEMPLATEPATH . '/includes/widgets.php');

// tie-Panel
include (TEMPLATEPATH . '/panel/shortcodes/shortcode.php');
if (is_admin()) {
	include (TEMPLATEPATH . '/panel/mpanel-ui.php');
	include (TEMPLATEPATH . '/panel/mpanel-functions.php');
	include (TEMPLATEPATH . '/panel/category-options.php');
	include (TEMPLATEPATH . '/panel/post-options.php');
	include (TEMPLATEPATH . '/panel/custom-slider.php');
	include (TEMPLATEPATH . '/panel/notifier/update-notifier.php');
	include (TEMPLATEPATH . '/panel/importer/tie-importer.php');
}


/*-----------------------------------------------------------------------------------*/
# Custom Admin Bar Menus
/*-----------------------------------------------------------------------------------*/
function tie_admin_bar() {
	global $wp_admin_bar;
	
	if ( current_user_can( 'switch_themes' ) ){
		$wp_admin_bar->add_menu( array(
			'parent' => 0,
			'id' => 'mpanel_page',
			'title' => theme_name ,
			'href' => admin_url( 'admin.php?page=panel')
		) );
	}
	
}
add_action( 'wp_before_admin_bar_render', 'tie_admin_bar' );

if ( ! isset( $content_width ) ) $content_width = 618;


// with activate istall option
if ( is_admin() && isset($_GET['activated'] ) && $pagenow == 'themes.php' ) {

	if( !get_option('tie_active') ){
		tie_save_settings( $default_data );
		update_option( 'tie_active' , theme_ver );
	}
   //header("Location: admin.php?page=panel");
}

add_action( 'import_done', 'wordpress_importer_init' );

//do not display shortcode buttons on post editor.
remove_action('admin_head','html_quicktags');
//do not show "Sahifa-Post Options" panel below post editor.
remove_action("admin_init", "posts_init");


?>
