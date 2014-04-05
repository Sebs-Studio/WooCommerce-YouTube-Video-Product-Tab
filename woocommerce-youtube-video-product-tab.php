<?php
/*
 * Plugin Name: WooCommerce YouTube Video Product Tab
 * Plugin URI: http://www.sebs-studio.com/wp-plugins/woocommerce-youtube-video-product-tab/
 * Description: Extends WooCommerce to allow you to add a YouTube Video to the Product page. Customise the player the way you want. An additional tab is added on the single products page to allow your customers to view the video you added. 
 * Version: 1.0
 * Author: Sebs Studio
 * Author URI: http://www.sebs-studio.com
 *
 * Text Domain: wc_youtube_video_product_tab
 * Domain Path: /lang/
 * 
 * Copyright: © 2013 Sebs Studio.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if(!defined('ABSPATH')) exit; // Exit if accessed directly

// Required minimum version of WordPress.
if(!function_exists('woo_youtube_video_tab_min_required')){
	function woo_youtube_video_tab_min_required(){
		global $wp_version;
		$plugin = plugin_basename(__FILE__);
		$plugin_data = get_plugin_data(__FILE__, false);

		if(version_compare($wp_version, "3.3", "<")){
			if(is_plugin_active($plugin)){
				deactivate_plugins($plugin);
				wp_die("'".$plugin_data['Name']."' requires WordPress 3.3 or higher, and has been deactivated! Please upgrade WordPress and try again.<br /><br />Back to <a href='".admin_url()."'>WordPress Admin</a>.");
			}
		}
	}
	add_action('admin_init', 'woo_youtube_video_tab_min_required');
}

/* Load Sebs Studio Updater */
if(!function_exists('sebs_studio_queue_update')){
	require_once('includes/sebs-functions.php');
}
/* If Sebs Studio Updater is loaded, integrate for plugin updates. */
if(function_exists('sebs_studio_queue_update')){
	sebs_studio_queue_update(plugin_basename(__FILE__), '909c063d204d3fa13436389da7dcb3ce', '3402');
}

// Checks if the WooCommerce plugin is installed and active.
if(in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))){

	/* Localisation */
	$locale = apply_filters('plugin_locale', get_locale(), 'woocommerce-youtube-video-product-tab');
	load_textdomain('wc_youtube_video_product_tab', WP_PLUGIN_DIR."/".plugin_basename(dirname(__FILE__)).'/lang/wc_youtube_video_product_tab-'.$locale.'.mo');
	load_plugin_textdomain('wc_youtube_video_product_tab', false, dirname(plugin_basename(__FILE__)).'/lang/');

	if(!class_exists('WooCommerce_YouTube_Video_Product_Tab')){
		class WooCommerce_YouTube_Video_Product_Tab{

			public static $plugin_prefix;
			public static $plugin_url;
			public static $plugin_path;
			public static $plugin_basefile;

			private $tab_data = false;

			/**
			 * Gets things started by adding an action to initialize this plugin once
			 * WooCommerce is known to be active and initialized
			 */
			public function __construct(){
				WooCommerce_YouTube_Video_Product_Tab::$plugin_prefix = 'wc_youtube_video_tab_';
				WooCommerce_YouTube_Video_Product_Tab::$plugin_basefile = plugin_basename(__FILE__);
				WooCommerce_YouTube_Video_Product_Tab::$plugin_url = plugin_dir_url(WooCommerce_YouTube_Video_Product_Tab::$plugin_basefile);
				WooCommerce_YouTube_Video_Product_Tab::$plugin_path = trailingslashit(dirname(__FILE__));
				add_action('woocommerce_init', array(&$this, 'init'));

				// Checks if the JWPlayer plugin is installed and active.
				if(in_array('jw-player-plugin-for-wordpress/jwplayermodule.php', apply_filters('active_plugins', get_option('active_plugins')))){
					$this->settings = array(
										array(
											'name' => __('YouTube Video Product Tab', 'wc_youtube_video_product_tab'),
											'type' => 'title',
											'desc' => __('If you have a license for JWPlayer, activate the official <a href="'.admin_url('plugin-install.php?tab=search&type=term&s=JW Player for WordPress').'" target="_blank">JWPlayer</a> plugin and enter the <a href="'.admin_url('admin.php?page=jwp6_menu_licensing').'" target="_blank">license key.</a>', 'wc_youtube_video_product_tab'),
											'id'   => 'youtube_video_product_tab'
										),
										array(
											'name' => __('Enable JWPlayer', 'wc_youtube_video_product_tab'),
											'desc' => __('Enable the use of JWPlayer as the video player for your videos in the product tab.', 'wc_youtube_video_product_tab'),
											'id'   => 'wc_youtube_video_tab_custom_player',
											'type' => 'checkbox',
											'std'  => '',
										),
										array(  
											'name' => __('Player Skin', 'wc_youtube_video_product_tab'),
											'desc' 		=> __('Select the player skin you want to use. <small>Licensed version of JWPlayer Premium/Ads is required for additional premium skins to work!</small>', 'wc_youtube_video_product_tab'),
											'id' 		=> 'wc_youtube_video_tab_player_skin',
											'type' 		=> 'select',
											'options'	=> array(
										    		            '' => 'Six '.__('(default)', 'wc_youtube_video_product_tab'),
																'custom-skin' => __('Custom Skin', 'wc_youtube_video_product_tab'),
										            		    'beelden' => 'Beelden',
												                'bekle' => 'Bekle', 
												                'five' => 'Five', 
												                'glow' => 'Glow',
												                'modieus' => 'Modieus',
												                'roundster' => 'Roundster',
												                'stormtrooper' => 'Stormtrooper',
										    		            'vapor' => 'Vapor',
															),
											'std'		=> '',
										),
										array(
											'name' => __('Custom Skin Location', 'wc_youtube_video_product_tab'),
											'desc' => __('Enter the url location of the custom skin. Upload your skin using the <a href="'.admin_url('media-new.php').'">media uploader</a>.', 'wc_youtube_video_product_tab'),
											'desc_tip' => false,
											'id' => 'wc_youtube_video_tab_player_skin_custom',
											'type' => 'text',
										),
										array(
											'type' => 'sectionend',
											'id'   => 'youtube_video_product_tab'
										),
					);
				}
				else{
					$this->settings = array(
										array(
											'name' => __('YouTube Video Product Tab', 'wc_youtube_video_product_tab'),
											'type' => 'title',
											'desc' => '',
											'id'   => 'youtube_video_product_tab'
										),
										array(
											'name' => __('Enable JWPlayer', 'wc_youtube_video_product_tab'),
											'desc' => __('Enable the use of JWPlayer as the video player for your videos in the product tab.', 'wc_youtube_video_product_tab'),
											'id'   => 'wc_youtube_video_tab_custom_player',
											'type' => 'checkbox',
											'std'  => '',
										),
										array(  
											'name' => __('Player Skin', 'wc_youtube_video_product_tab'),
											'desc' 		=> __('Select the player skin you want to use. <small>If you have a licensed version of JWPlayer Premium/Ads edition, it will unlock more skins to choose!</small>', 'wc_youtube_video_product_tab'),
											'id' 		=> 'wc_youtube_video_tab_player_skin',
											'type' 		=> 'select',
											'options'	=> array(
										    		            '' => 'Six '.__('(default)', 'wc_youtube_video_product_tab'),
																'custom-skin' => __('Custom Skin', 'wc_youtube_video_product_tab'),
															),
											'std'		=> '',
										),
										array(
											'name' => __('Custom Skin Location', 'wc_youtube_video_product_tab'),
											'desc' => __('Enter the url location of the custom skin. Upload your skin using the <a href="'.admin_url('media-new.php').'">media uploader</a>.', 'wc_youtube_video_product_tab'),
											'desc_tip' => false,
											'id' => 'wc_youtube_video_tab_player_skin_custom',
											'type' => 'text',
										),
										array(
											'type' => 'sectionend',
											'id'   => 'youtube_video_product_tab'
										),
					);
				}
			}

			/**
			 * Init WooCommerce YouTube Video Product Tab extension once we know WooCommerce is active
			 */
			public function init(){
				// backend stuff
				add_filter('plugin_row_meta', array(&$this, 'add_support_link'), 10, 2);
				add_action('admin_print_scripts', array(&$this, 'admin_script'));
				add_action('admin_enqueue_scripts', array(&$this, 'register_media_uploader'), 10,1);
				add_action('woocommerce_product_write_panel_tabs', array(&$this, 'product_write_panel_tab'));
				add_action('woocommerce_product_write_panels', array(&$this, 'product_write_panel'));
				add_action('woocommerce_process_product_meta', array(&$this, 'product_save_data'), 10, 2);
				// Settings
				add_action('woocommerce_settings_catalog_options_after', array(&$this, 'youtube_video_tab_admin_settings'));
				add_action('woocommerce_update_options_catalog', array(&$this, 'save_youtube_video_tab_admin_settings'));
				// frontend stuff
				if(version_compare(WOOCOMMERCE_VERSION, "2.0", '>=')){
					// WC >= 2.0
					add_filter('woocommerce_product_tabs', array(&$this, 'youtube_video_product_tabs_two'));
				}
				else{
					add_action('woocommerce_product_tabs', array(&$this, 'youtube_video_product_tabs'), 28);
					// in between the attributes and reviews panels.
					add_action('woocommerce_product_tab_panels', array(&$this, 'youtube_video_product_tabs_panel'), 28);
				}
				// If the official JWPlayer plugin is installed and active then don't load the script in the header.
				if(!in_array('jw-player-plugin-for-wordpress/jwplayermodule.php', apply_filters('active_plugins', get_option('active_plugins')))){
					add_action('wp_enqueue_scripts', array(&$this, 'wc_youtube_video_product_tab_scripts'));
				}
			}

			/**
			 * Add support links to plugin page.
			 */
			public function add_support_link($links, $file){
				if(!current_user_can('install_plugins')){
					return $links;
				}
				if($file == WooCommerce_YouTube_Video_Product_Tab::$plugin_basefile){
					$links[] = '<a href="http://www.sebs-studio.com/forum/woocommerce-youtube-video-product-tab/" target="_blank">'.__('Support', 'wc_youtube_video_product_tab').'</a>';
					$links[] = '<a href="http://www.sebs-studio.com/wp-plugins/woocommerce-extensions/" target="_blank">'.__('More WooCommerce Extensions', 'wc_youtube_video_product_tab').'</a>';
				}
				return $links;
			}

			/* 
			 * Add javascript to the admin control panel.
			 */
			function admin_script(){
				/* Localize the javascript. */
				$wc_youtube_video_product_tab_translations = array(
																'vwidth' => __('Video Width', 'wc_youtube_video_product_tab'),
																'vheight' => __('Video Height', 'wc_youtube_video_product_tab'),
																'vskinloc' => __('Custom Skin Location', 'wc_youtube_video_product_tab'),
																'vupload' => __('Upload', 'wc_youtube_video_product_tab'),
																'insertURL' => __('Insert file URL', 'wc_youtube_video_product_tab'),
																'skin_url_input_placeholder' => __('Enter the url location of the custom skin.', 'wc_youtube_video_product_tab'),
				);
				wp_enqueue_script('woocommerce_youtube_video_product_tab', plugins_url('/assets/js/admin-product-edit.js', __FILE__), array('jquery'), '1.0');
				wp_localize_script('woocommerce_youtube_video_product_tab', 'youtube_video_product_tab', apply_filters('wc_youtube_video_product_tab_translations', $wc_youtube_video_product_tab_translations));
			}

			/* Register WordPress Media Manager/Uploader */
			function register_media_uploader($hook){
				if($hook == 'admin.php?page=woocommerce_settings&tab=catalog'){
					if(function_exists('wp_enqueue_media')){
						wp_enqueue_media();
					}
					else{
						wp_enqueue_style('thickbox');
						wp_enqueue_script('media-upload');
						wp_enqueue_script('thickbox');
					}
				}
			}

			/**
			 * Add javascript to the front.
			 */
			function wc_youtube_video_product_tab_scripts(){
				if(get_post_type() == 'product'){
					wp_enqueue_script('woocommerce-youtube-video-product-tab', plugins_url('/assets/js/jwplayer.js', __FILE__), '', '1.0', false);
				}
			}

			/**
			 * Write the video tab on the product view page for WC 2.0+.
			 * In WooCommerce these are handled by templates.
			 */
			public function youtube_video_product_tabs_two($tabs){
				global $product;

				if($this->product_has_youtube_tab($product)){
					foreach($this->tab_data as $tab){
						$tabs[$tab['id']] = array(
												'title'    => $tab['title'],
												'priority' => 28,
												'callback' => array(&$this, 'youtube_video_product_tabs_panel_content'),
												'content'  => $tab['video']
						);
					}
				}
				return $tabs;
			}

			/**
			 * Write the video tab on the product view page for WC 1.6.6 and below.
			 * In WooCommerce these are handled by templates.
			 */
			public function youtube_video_product_tabs(){
				global $product;

				if($this->product_has_youtube_tab($product)){
					foreach($this->tab_data as $tab){
						echo "<li><a href=\"#{$tab['id']}\">".__('YouTube Video', 'wc_youtube_video_product_tab')."</a></li>";
					}
				}
			}

			/**
			 * Write the video tab panel on the product view page WC 2.0+.
			 * In WooCommerce these are handled by templates.
			 */
			public function youtube_video_product_tabs_panel_content(){
				global $product, $post;

				$embed = new WP_Embed();

				if($this->product_has_youtube_tab($product)){
					foreach($this->tab_data as $tab){
						echo '<h2 style="margin-bottom:10px;">'.$tab['title'].'</h2>';
						if(empty($tab['video_suggest'])){ $suggest = '?rel=0'; }else{ $suggest = ''; }
						if($tab['video_size'] == 'custom'){
							$width = $tab['video_width'];
							$height = $tab['video_height'];
						}
						if($tab['video_size'] == '560315'){
							$width = '560';
							$height = '315';
						}
						if($tab['video_size'] == '640360'){
							$width = '640';
							$height = '360';
						}
						if($tab['video_size'] == '583480'){
							$width = '583';
							$height = '480';
						}
						if($tab['video_size'] == '1280720'){
							$width = '1280';
							$height = '720';
						}
						$video_url = str_replace('watch?v=', 'embed/', $tab['video']);
						$embed_code = '<iframe width="'.$width.'" height="'.$height.'" src="'.$video_url.''.$suggest.'" frameborder="0" allowfullscreen></iframe>';
						if(!empty($tab['video_secure'])){ $embed_code = str_replace('http://', 'https://', $embed_code); }
						if(!empty($tab['video_enhanced'])){ $embed_code = str_replace('youtube.com', 'youtube-nocookie.com', $embed_code); }
						if(!empty($tab['video_rich_snippets'])){ // If rich snippet has been enabled.
							$find_thumb_url = array('http://', 'https://', 'www.', 'youtube.com', 'youtube-nocookie.com', 'watch?v=', '/');
							$video_thumb_default = str_replace($find_thumb_url, '', $tab['video']);
							$video_thumb_default = 'http://i4.ytimg.com/vi/'.$video_thumb_default.'/hqdefault.jpg';
						?>
						<div itemscope="video" itemscope itemtype="http://schema.org/VideoObject">
						<link itemprop="url" href="<?php echo get_permalink($post->ID); ?>">
						<meta itemprop="name" content="<?php if(get_post_meta($post->ID, '_yoast_wpseo_title', true)){ echo get_post_meta($post->ID, '_yoast_wpseo_title', true); }else{ echo get_the_title($post->ID); } ?>">
						<meta itemprop="description" content="<?php if(get_post_meta($post->ID, '_yoast_wpseo_metadesc', true)){ echo get_post_meta($post->ID, '_yoast_wpseo_metadesc', true); }else{ echo get_the_excerpt($post->ID); } ?>">
						<link itemprop="thumbnailUrl" href="<?php echo $video_thumb_default; ?>">
						<span itemprop="thumbnail" itemscope itemtype="http://schema.org/ImageObject">
							<link itemprop="url" href="<?php echo $video_thumb_default; ?>">
							<meta itemprop="width" content="480">
							<meta itemprop="height" content="360">
						</span>
						<?php $embed_url = str_replace('watch?v=', 'v/', $tab['video']); ?>
						<link itemprop="embedURL" href="<?php echo $embed_url; ?>?autohide=1&amp;version=3">
						<meta itemprop="playerType" content="Flash">
						<meta itemprop="width" content="<?php echo $width; ?>">
						<meta itemprop="height" content="<?php echo $height; ?>">
						<meta itemprop="isFamilyFriendly" content="<?php if(empty($tab['video_friendly']) || $tab['video_friendly'] == 'yes'){ echo 'True'; }else{ echo 'False'; } ?>">
						<?php
						} // end of video seo rich snippet.
						if(get_option('wc_youtube_video_tab_custom_player') != 'no'){
							if(!empty($tab['video_secure'])){ $video_url = str_replace('http://', 'https://', $tab['video']); }
							$youtube_video_url = $video_url;
						?>
						<div id="wc_youtube_video"><?php _e('Loading Video ...', 'wc_youtube_video_product_tab'); ?></div>
						<script type="text/javascript">
						jwplayer('wc_youtube_video').setup({
							flashplayer: "<?php echo plugins_url('/assets/swf/jwplayer.flash.swf', __FILE__); ?>",
							<?php
							$load_skin = '0'; // No player skin is loaded.
							if(get_option('wc_youtube_video_tab_player_skin') != '' || !empty($tab['video_skin'])){
								if(!empty($tab['video_skin'])){ $video_skin = $tab['video_skin']; }
								else{ $video_skin = get_option('wc_youtube_video_tab_player_skin'); }
								// Checks if custom skin was selected, is so load custom skin.
								if($video_skin == 'custom-skin'){ if(!empty($tab['video_skin_custom'])){ $video_skin = $tab['video_skin_custom']; }
								else{ $video_skin = get_option('wc_youtube_video_tab_player_skin_custom'); } }
								// Load skin if any selected.
								if($video_skin != ''){ if($tab['video_skin_custom'] != ''){ $load_skin = '1'; } }
								if($load_skin == '1'){ // Player skin is loaded.
							?>
							skin: "<?php if($tab['video_skin'] == 'custom-skin'){ echo $tab['video_skin_custom']; }else{ echo $video_skin; } ?>",
							<?php
								} // end if $load_skin equals one.
							} // end if skin is not empty.
							?>
							file: "<?php echo $youtube_video_url; ?>",
							width: <?php echo $width; ?>,
							height: <?php echo $height; ?>
						});
						</script>
						<?php
						}
						else{
							echo $embed->autoembed(apply_filters('woocommerce_youtube_video_product_tab', $embed_code, $tab['id']));
						}
						if(!empty($tab['video_rich_snippets'])){ echo '</div>'; }
					}
				}
			}

			/**
			 * Write the video tab panel on the product view page for WC 1.6.6 and below.
			 * In WooCommerce these are handled by templates.
			 */
			public function youtube_video_product_tabs_panel(){
				global $product, $post;

				$embed = new WP_Embed();

				if($this->product_has_youtube_tab($product)){
					foreach($this->tab_data as $tab){
						echo '<div class="panel" id="'.$tab['id'].'">';
						echo '<h2 style="margin-bottom:10px;">'.$tab['title'].'</h2>';
						if(empty($tab['video_suggest'])){ $suggest = '?rel=0'; }else{ $suggest = ''; }
						if($tab['video_size'] == 'custom'){
							$width = $tab['video_width'];
							$height = $tab['video_height'];
						}
						if($tab['video_size'] == '560315'){
							$width = '560';
							$height = '315';
						}
						if($tab['video_size'] == '640360'){
							$width = '640';
							$height = '360';
						}
						if($tab['video_size'] == '583480'){
							$width = '583';
							$height = '480';
						}
						if($tab['video_size'] == '1280720'){
							$width = '1280';
							$height = '720';
						}
						$video_url = str_replace('watch?v=', 'embed/', $tab['video']);
						$embed_code = '<iframe width="'.$width.'" height="'.$height.'" src="'.$video_url.''.$suggest.'" frameborder="0" allowfullscreen></iframe>';
						if(!empty($tab['video_secure'])){ $embed_code = str_replace('http://', 'https://', $embed_code); }
						if(!empty($tab['video_enhanced'])){ $embed_code = str_replace('youtube.com', 'youtube-nocookie.com', $embed_code); }
						if(!empty($tab['video_rich_snippets'])){ // If rich snippet has been enabled.
							$find_thumb_url = array('http://', 'https://', 'www.', 'youtube.com', 'youtube-nocookie.com', 'watch?v=', '/');
							$video_thumb_default = str_replace($find_thumb_url, '', $tab['video']);
							$video_thumb_default = 'http://i4.ytimg.com/vi/'.$video_thumb_default.'/hqdefault.jpg';
						?>
						<div itemscope="video" itemscope itemtype="http://schema.org/VideoObject">
						<link itemprop="url" href="<?php echo get_permalink($post->ID); ?>">
						<meta itemprop="name" content="<?php if(get_post_meta($post->ID, '_yoast_wpseo_title', true)){ echo get_post_meta($post->ID, '_yoast_wpseo_title', true); }else{ echo get_the_title($post->ID); } ?>">
						<meta itemprop="description" content="<?php if(get_post_meta($post->ID, '_yoast_wpseo_metadesc', true)){ echo get_post_meta($post->ID, '_yoast_wpseo_metadesc', true); }else{ echo get_the_excerpt($post->ID); } ?>">
						<link itemprop="thumbnailUrl" href="<?php echo $video_thumb_default; ?>">
						<span itemprop="thumbnail" itemscope itemtype="http://schema.org/ImageObject">
							<link itemprop="url" href="<?php echo $video_thumb_default; ?>">
							<meta itemprop="width" content="480">
							<meta itemprop="height" content="360">
						</span>
						<?php $embed_url = str_replace('watch?v=', 'v/', $tab['video']); ?>
						<link itemprop="embedURL" href="<?php echo $embed_url; ?>?autohide=1&amp;version=3">
						<meta itemprop="playerType" content="Flash">
						<meta itemprop="width" content="<?php echo $width; ?>">
						<meta itemprop="height" content="<?php echo $height; ?>">
						<meta itemprop="isFamilyFriendly" content="<?php if(empty($tab['video_friendly']) || $tab['video_friendly'] == 'yes'){ echo 'True'; }else{ echo 'False'; } ?>">
						<?php
						} // end of video seo rich snippet.
						if(get_option('wc_youtube_video_tab_custom_player') != 'no'){
							if(!empty($tab['video_secure'])){ $video_url = str_replace('http://', 'https://', $tab['video']); }
							$youtube_video_url = $video_url;
						?>
						<div id="wc_youtube_video"><?php _e('Loading Video ...', 'wc_youtube_video_product_tab'); ?></div>
						<script type="text/javascript">
						jwplayer('wc_youtube_video').setup({
							flashplayer: "<?php echo plugins_url('/assets/swf/jwplayer.flash.swf', __FILE__); ?>",
							<?php
							$load_skin = '0'; // No player skin is loaded.
							if(get_option('wc_youtube_video_tab_player_skin') != '' || !empty($tab['video_skin'])){
								if(!empty($tab['video_skin'])){ $video_skin = $tab['video_skin']; }
								else{ $video_skin = get_option('wc_youtube_video_tab_player_skin'); }
								// Checks if custom skin was selected, is so load custom skin.
								if($video_skin == 'custom-skin'){ if(!empty($tab['video_skin_custom'])){ $video_skin = $tab['video_skin_custom']; }
								else{ $video_skin = get_option('wc_youtube_video_tab_player_skin_custom'); } }
								// Load skin if any selected.
								if($video_skin != ''){ if($tab['video_skin_custom'] != ''){ $load_skin = '1'; } }
								if($load_skin == '1'){ // Player skin is loaded.
							?>
							skin: "<?php if($tab['video_skin'] == 'custom-skin'){ echo $tab['video_skin_custom']; }else{ echo $video_skin; } ?>",
							<?php
								} // end if $load_skin equals one.
							} // end if skin is not empty.
							?>
							file: "<?php echo $youtube_video_url; ?>",
							width: <?php echo $width; ?>,
							height: <?php echo $height; ?>
						});
						</script>
						<?php
						}
						else{
							echo $embed->autoembed(apply_filters('woocommerce_youtube_video_product_tab', $embed_code, $tab['id']));
						}
						if(!empty($tab['video_rich_snippets'])){ echo '</div>'; }
						echo '</div>';
					}
				}
			}

			/**
			 * Lazy-load the product_tabs meta data, and return true if it exists,
			 * false otherwise.
			 * 
			 * @return true if there is video tab data, false otherwise.
			 */
			private function product_has_youtube_tab($product){
				if($this->tab_data === false){
					$this->tab_data = maybe_unserialize(get_post_meta($product->id, 'woo_youtube_video_product_tab', true));
				}
				// tab must at least have a embed code inserted.
				return !empty($this->tab_data) && !empty($this->tab_data[0]) && !empty($this->tab_data[0]['video']);
			}

			/**
			 * Adds a new tab to the Product Data postbox in the admin product interface
			 */
			public function product_write_panel_tab(){
				$tab_icon = WooCommerce_YouTube_Video_Product_Tab::$plugin_url.'assets/img/play.png';

				if(version_compare(WOOCOMMERCE_VERSION, "2.0.0") >= 0 ){
					$style = 'padding:5px 5px 5px 28px; background-image:url('.$tab_icon.'); background-repeat:no-repeat; background-position:5px 7px;';
					$active_style = '';
				}
				else{
					$style = 'padding:9px 9px 9px 34px; line-height:16px; border-bottom:1px solid #d5d5d5; text-shadow:0 1px 1px #fff; color:#555555; background-image:url('.$tab_icon.'); background-repeat:no-repeat; background-position:9px 9px;';
					$active_style = '#woocommerce-product-data ul.product_data_tabs li.my_plugin_tab.active a { border-bottom: 1px solid #F8F8F8; }';
				}
				?>
				<style type="text/css">
				#woocommerce-product-data ul.product_data_tabs li.youtube_video_tab a { <?php echo $style; ?> }
				<?php echo $active_style; ?>
				p.form-field._tab_youtube_video_width_field, 
				p.form-field._tab_youtube_video_height_field { float: left; margin-top: 2px; }
				p.form-field._tab_youtube_video_height_field { clear: right; }
				p.form-field._tab_youtube_video_width_field label, 
				p.form-field._tab_youtube_video_height_field label { width: 80px; }
				p.form-field._tab_youtube_video_skin_field, 
				p.form-field._tab_youtube_video_suggestions_field { clear: left; }
				</style>
				<?php
				echo "<li class=\"youtube_video_tab\"><a href=\"#youtube_video_tab\">".__('YouTube Video', 'wc_youtube_video_product_tab')."</a></li>";
			}

			/**
			 * Adds the panel to the Product Data postbox in the product interface
			 */
			public function product_write_panel(){
				global $post;

				// Pull the video tab data out of the database
				$tab_data = maybe_unserialize(get_post_meta($post->ID, 'woo_youtube_video_product_tab', true));

				if(empty($tab_data)){
					$tab_data[] = array('title' => '', 'video' => '', 'video_size' => '', 'video_width' => '', 'video_height' => '', 'video_skin' => '', 'video_suggest' => '', 'video_secure' => '', 'video_enhanced' => '', 'video_rich_snippets' => '');
				}

				// Display the video tab panel
				foreach($tab_data as $tab){
					echo '<div id="youtube_video_tab" class="panel woocommerce_options_panel">';
					$this->wc_youtube_video_product_tab_text_input(
															array(
																'id' => '_tab_youtube_video_title', 
																'label' => __('Video Title', 'wc_youtube_video_product_tab'), 
																'placeholder' => __('Enter your title here.', 'wc_youtube_video_product_tab'), 
																'value' => $tab['title'], 
																'style' => 'width:70%;',
															)
					);
					$this->wc_youtube_video_product_tab_text_input(
															array(
																'id' => '_tab_youtube_video_url', 
																'label' => __('Video URL', 'wc_youtube_video_product_tab'), 
																'placeholder' => 'http://www.youtube.com/watch?v=yhz4A5BCMAA', 
																'value' => $tab['video'], 
																'style' => 'width:70%;',
															)
					);
					$this->wc_youtube_video_product_tab_select(
										array(
											'id' => '_tab_youtube_video_size', 
											'label' => __('Video Size', 'wc_youtube_video_product_tab'), 
											'options' => array(
															'560315' => __('560 x 315', 'wc_youtube_video_product_tab'),
															'640360' => __('640 x 360', 'wc_youtube_video_product_tab'),
															'853480' => __('853 x 480', 'wc_youtube_video_product_tab'),
															'1280720' => __('1280 x 720', 'wc_youtube_video_product_tab'),
															'custom' => __('Custom Size', 'wc_youtube_video_product_tab')
											),
											'value' => $tab['video_size'],
											'class' => 'select'
										)
					);

					if($tab_data[0]['video_size'] == 'custom'){
						$this->wc_youtube_video_product_tab_text_input(
																	array(
																		'id' => '_tab_youtube_video_width', 
																		'label' => __('Video Width', 'wc_youtube_video_product_tab'),  
																		'value' => $tab['video_width'], 
																		'style' => 'width:60px;'
																	)
						);
						$this->wc_youtube_video_product_tab_text_input(
																	array(
																		'id' => '_tab_youtube_video_height', 
																		'label' => __('Video Height', 'wc_youtube_video_product_tab'),  
																		'value' => $tab['video_height'], 
																		'style' => 'width:60px;'
																	)
						);
					}

					// Checks if the JWPlayer plugin is installed and active.
					if(in_array('jw-player-plugin-for-wordpress/jwplayermodule.php', apply_filters('active_plugins', get_option('active_plugins')))){

						$this->wc_youtube_video_product_tab_select(
											array(
												'id' => '_tab_youtube_video_skin', 
												'label' => __('Player Skin', 'wc_youtube_video_product_tab'), 
												'options' => array(
										    		            '' => 'Six '.__('(default)', 'wc_youtube_video_product_tab'),
																'custom-skin' => __('Custom Skin', 'wc_youtube_video_product_tab'),
										            		    'beelden' => 'Beelden',
												                'bekle' => 'Bekle', 
												                'five' => 'Five', 
												                'glow' => 'Glow',
												                'modieus' => 'Modieus',
											    	            'roundster' => 'Roundster',
											        	        'stormtrooper' => 'Stormtrooper',
								    			        	    'vapor' => 'Vapor',
												),
												'description' => __('This overides the player skin selected in the <a href="'.admin_url('admin.php?page=woocommerce_settings&tab=catalog').'" target="_blank">settings</a>.', 'wc_youtube_video_product_tab'),
												'desc_tip' => __('Player skin only applies if you have enabled the JWPlayer in the <a href="'.admin_url('admin.php?page=woocommerce_settings&tab=catalog').'" target="_blank">settings</a>.', 'wc_youtube_video_product_tab'),
												'value' => $tab['video_skin'],
												'class' => 'select'
											)
						);

					}
					else{

						$this->wc_youtube_video_product_tab_select(
											array(
												'id' => '_tab_youtube_video_skin', 
												'label' => __('Player Skin', 'wc_youtube_video_product_tab'), 
												'options' => array(
										    		            '' => 'Six '.__('(default)', 'wc_youtube_video_product_tab'),
																'custom-skin' => __('Custom Skin', 'wc_youtube_video_product_tab'),
												),
												'description' => __('Player skin only applies if you have enabled the JWPlayer in the <a href="'.admin_url('admin.php?page=woocommerce_settings&tab=catalog').'" target="_blank">settings</a>.', 'wc_youtube_video_product_tab'),
												'desc_tip' => __('This overides the player skin selected in the settings.', 'wc_youtube_video_product_tab'),
												'value' => $tab['video_skin'],
												'class' => 'select'
											)
						);

					}

					if(!empty($tab['video_skin']) && $tab['video_skin'] == 'custom-skin'){
						$this->wc_youtube_video_product_tab_input_upload(
																array(
																	'id' => '_tab_youtube_video_skin_custom', 
																	'label' => __('Custom Skin Location', 'wc_youtube_video_product_tab'), 
																	'placeholder' => __('Enter the url location of the custom skin.', 'wc_youtube_video_product_tab'), 
																	'value' => $tab['video_skin_custom'], 
																	'style' => 'width:50%;',
																	'upload' => __('Upload', 'wc_youtube_video_product_tab'),
																)
						);
					}

					woocommerce_wp_checkbox(
										array(
											'id' => '_tab_youtube_video_suggestions', 
											'label' => __('Suggested videos', 'wc_youtube_video_product_tab'), 
											'description' => __('Show suggested videos when the video is finished playing. <strong>YouTube Player Only</strong>', 'wc_youtube_video_product_tab'),
											'value' => esc_attr($tab['video_suggest'])
										)
					);
					woocommerce_wp_checkbox(
										array(
											'id' => '_tab_youtube_video_https', 
											'label' => __('Secure connection', 'wc_youtube_video_product_tab'), 
											'description' => __('Use HTTPS', 'wc_youtube_video_product_tab'),
											'value' => esc_attr($tab['video_secure'])
										)
					);
					woocommerce_wp_checkbox(
										array(
											'id' => '_tab_youtube_video_privacy_enhanced', 
											'label' => __('No tracking', 'wc_youtube_video_product_tab'), 
											'description' => __('Enable privacy-enhanced mode. <strong>YouTube Player Only</strong>', 'wc_youtube_video_product_tab'),
											'value' => esc_attr($tab['video_enhanced'])
										)
					);

					woocommerce_wp_checkbox(
										array(
											'id' => '_tab_youtube_video_rich_snippets', 
											'label' => __('Rich Snippets', 'wc_youtube_video_product_tab'), 
											'description' => __('Enable video rich snippets for better product search results in search engines.', 'wc_youtube_video_product_tab'), 
											'value' => esc_attr($tab['video_rich_snippets']) 
										)
					);

					if(!empty($tab_data[0]['video_rich_snippets'])){

						$this->wc_youtube_video_product_tab_select(
											array(
												'id' => '_tab_youtube_video_friendly', 
												'label' => __('Family Friendly', 'wc_youtube_video_product_tab'), 
												'description' => __('If the video is not family friendly, select "No"', 'wc_youtube_video_product_tab'), 
												'options' => array(
																'yes' => __('Yes', 'wc_youtube_video_product_tab'), 
																'no' => __('No', 'wc_youtube_video_product_tab'), 
												),
												'value' => $tab['video_friendly'],
												'class' => 'select'
											)
						);

					}

					echo '</div>';
				}
			}

			/**
			 * Output a text input box with a upload button to load the media manager.
			 */
			public function wc_youtube_video_product_tab_input_upload($field){
				global $thepostid, $post, $woocommerce;

				$thepostid              = empty( $thepostid ) ? $post->ID : $thepostid;
				$field['upload']   = isset( $field['upload'] ) ? $field['upload'] : '';
				$field['placeholder']   = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
				$field['class']         = isset( $field['class'] ) ? $field['class'] : 'short file_paths';
				$field['wrapper_class'] = isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : '';
				$field['value']         = isset( $field['value'] ) ? $field['value'] : get_post_meta( $thepostid, $field['id'], true );
				$field['name']          = isset( $field['name'] ) ? $field['name'] : $field['id'];
				$field['type']          = isset( $field['type'] ) ? $field['type'] : 'text';

				echo '<p class="form-field '.esc_attr($field['id']).'_field '.esc_attr($field['wrapper_class']).'"><label for="'.esc_attr($field['id']).'">'.wp_kses_post($field['label']).'</label><input type="'.esc_attr($field['type']).'" class="'.esc_attr($field['class']).'" name="'.esc_attr($field['name']).'" id="'.esc_attr($field['id']).'" value="'.esc_attr($field['value']).'" placeholder="'.esc_attr($field['placeholder']).'"'.(isset($field['style']) ? ' style="'.$field['style'].'"' : '').' /> <input type="button" class="upload_file_button button" data-choose="'.esc_attr($field['upload']).'" data-update="'.__('Insert file URL', 'wc_youtube_video_product_tab').'" value="'.esc_attr($field['upload']).'" />';

				if(!empty($field['desc_tip'])){
					if(!empty($field['description'])){ echo '<span class="description">'.wp_kses_post($field['description']).'</span>'; }
					echo '<img class="help_tip" data-tip="'.esc_attr($field['desc_tip']).'" src="'.$woocommerce->plugin_url().'/assets/images/help.png" height="16" width="16" />';
				}
				else{
					if(!empty($field['description'])){ echo '<span class="description">'.wp_kses_post($field['description']).'</span>'; }
				}
				echo '</p>';
			}

			/**
			 * Output a text input box.
			 */
			public function wc_youtube_video_product_tab_text_input($field){
				global $thepostid, $post, $woocommerce;

				$thepostid              = empty( $thepostid ) ? $post->ID : $thepostid;
				$field['placeholder']   = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
				$field['class']         = isset( $field['class'] ) ? $field['class'] : 'short';
				$field['wrapper_class'] = isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : '';
				$field['value']         = isset( $field['value'] ) ? $field['value'] : get_post_meta( $thepostid, $field['id'], true );
				$field['name']          = isset( $field['name'] ) ? $field['name'] : $field['id'];
				$field['type']          = isset( $field['type'] ) ? $field['type'] : 'text';

				echo '<p class="form-field '.esc_attr($field['id']).'_field '.esc_attr($field['wrapper_class']).'"><label for="'.esc_attr($field['id']).'">'.wp_kses_post($field['label']).'</label><input type="'.esc_attr($field['type']).'" class="'.esc_attr($field['class']).'" name="'.esc_attr($field['name']).'" id="'.esc_attr($field['id']).'" value="'.esc_attr($field['value']).'" placeholder="'.esc_attr($field['placeholder']).'"'.(isset($field['style']) ? ' style="'.$field['style'].'"' : '').' /> ';

				if(!empty($field['desc_tip'])){
					if(!empty($field['description'])){ echo '<span class="description">'.wp_kses_post($field['description']).'</span>'; }
					echo '<img class="help_tip" data-tip="'.esc_attr($field['desc_tip']).'" src="'.$woocommerce->plugin_url().'/assets/images/help.png" height="16" width="16" />';
				}
				else{
					if(!empty($field['description'])){ echo '<span class="description">'.wp_kses_post($field['description']).'</span>'; }
				}
				echo '</p>';
			}

			/**
			 * Output a select input box.
			 */
			function wc_youtube_video_product_tab_select($field){
				global $thepostid, $post, $woocommerce;

				$thepostid 				= empty( $thepostid ) ? $post->ID : $thepostid;
				$field['class'] 		= isset( $field['class'] ) ? $field['class'] : 'select short';
				$field['wrapper_class'] = isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : '';
				$field['value'] 		= isset( $field['value'] ) ? $field['value'] : get_post_meta( $thepostid, $field['id'], true );

				echo '<p class="form-field ' . esc_attr( $field['id'] ) . '_field ' . esc_attr( $field['wrapper_class'] ) . '"><label for="' . esc_attr( $field['id'] ) . '">' . wp_kses_post( $field['label'] ) . '</label><select id="' . esc_attr( $field['id'] ) . '" name="' . esc_attr( $field['id'] ) . '" class="' . esc_attr( $field['class'] ) . '">';

				foreach($field['options'] as $key => $value){
					echo '<option value="'.esc_attr($key).'" '.selected(esc_attr($field['value']), esc_attr($key), false).'>'.esc_html($value).'</option>';
				}
				echo '</select> ';

				if(!empty($field['desc_tip'])){
					if(!empty($field['description'])){ echo '<span class="description">'.wp_kses_post($field['description']).'</span>'; }
					echo '<img class="help_tip" data-tip="'.esc_attr($field['desc_tip']).'" src="'.$woocommerce->plugin_url().'/assets/images/help.png" height="16" width="16" />';
				}
				else{
					if(!empty($field['description'])){ echo '<span class="description">'.wp_kses_post($field['description']).'</span>'; }
				}
				echo '</p>';
			}

			/**
			 * Saves the data inputed into the product boxes, as post meta data
			 * identified by the name 'woo_youtube_video_product_tab'
			 * 
			 * @param int $post_id the post (product) identifier
			 * @param stdClass $post the post (product)
			 */
			public function product_save_data($post_id, $post){

				$tab_title = stripslashes($_POST['_tab_youtube_video_title']);
				if($tab_title == ''){
					$tab_title = __('Video', 'wc_youtube_video_product_tab');
				}
				$tab_video = stripslashes($_POST['_tab_youtube_video_url']);
				$tab_video_size = $_POST['_tab_youtube_video_size'];
				$tab_video_width = $_POST['_tab_youtube_video_width'];
				$tab_video_height = $_POST['_tab_youtube_video_height'];
				$tab_video_skin = $_POST['_tab_youtube_video_skin'];
				$tab_video_skin_custom = $_POST['_tab_youtube_video_skin_custom'];
				$tab_video_suggest = $_POST['_tab_youtube_video_suggestions'];
				$tab_video_secure = $_POST['_tab_youtube_video_https'];
				$tab_video_enhanced = $_POST['_tab_youtube_video_privacy_enhanced'];
				$tab_video_rich_snippets = $_POST['_tab_youtube_video_rich_snippets'];
				$tab_video_friendly = $_POST['_tab_youtube_video_friendly'];

				if(empty($tab_video) && get_post_meta($post_id, 'woo_youtube_video_product_tab', true)){
					// clean up if the video tabs are removed
					delete_post_meta($post_id, 'woo_youtube_video_product_tab');
				}
				elseif(!empty($tab_video)){
					$tab_data = array();

					$tab_id = '';
					// convert the tab title into an id string
					$tab_id = strtolower($tab_title);
					$tab_id = preg_replace("/[^\w\s]/",'',$tab_id); // remove non-alphas, numbers, underscores or whitespace 
					$tab_id = preg_replace("/_+/", ' ', $tab_id); // replace all underscores with single spaces
					$tab_id = preg_replace("/\s+/", '-', $tab_id); // replace all multiple spaces with single dashes
					$tab_id = 'tab-'.$tab_id; // prepend with 'tab-' string

					// save the data to the database
					$tab_data[] = array(
									'title' => $tab_title, 
									'id' => $tab_id, 
									'video' => $tab_video,
									'video_size' => $tab_video_size,
									'video_width' => $tab_video_width,
									'video_height' => $tab_video_height,
									'video_skin' => $tab_video_skin,
									'video_skin_custom' => $tab_video_skin_custom,
									'video_suggest' => $tab_video_suggest,
									'video_secure' => $tab_video_secure,
									'video_enhanced' => $tab_video_enhanced,
									'video_rich_snippets' => $tab_video_rich_snippets,
									'video_friendly' => $tab_video_friendly,
					);
					update_post_meta($post_id, 'woo_youtube_video_product_tab', $tab_data);
				}
			}

			// Adds a settings to control the youtube video in the tab.
			function youtube_video_tab_admin_settings(){
				global $settings;

				woocommerce_admin_fields($this->settings);
			}

			function save_youtube_video_tab_admin_settings(){
				woocommerce_update_options($this->settings);
			}

		}
	}

	/* 
	 * Instantiate plugin class and add it to the set of globals.
	 */
	$woocommerce_youtube_video_tab = new WooCommerce_YouTube_Video_Product_Tab();
}
else{
	add_action('admin_notices', 'wc_youtube_video_tab_error_notice');
	function wc_youtube_video_tab_error_notice(){
		global $current_screen;
		if($current_screen->parent_base == 'plugins'){
			echo '<div class="error"><p>WooCommerce YouTube Video Product Tab '.__('requires <a href="http://www.woothemes.com/woocommerce/" target="_blank">WooCommerce</a> to be activated in order to work. Please install and activate <a href="'.admin_url('plugin-install.php?tab=search&type=term&s=WooCommerce').'" target="_blank">WooCommerce</a> first.', 'wc_youtube_video_product_tab').'</p></div>';
		}
	}
}
?>