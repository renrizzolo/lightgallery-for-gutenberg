<?php

/**
 * A simple Light Gallery plugin for the WordPress native gallery.
 *
 * @link              https://renrizzolo.github.com/
 * @since             1.0.0
 * @package           lgng
 *
 * @wordpress-plugin
 * Plugin Name: Light Gallery for Gutenberg
 * Plugin URI:
 * Description: A simple Light Gallery WordPress plugin for use in the gutenberg editor
 * Version: 2.0.2
 * Author: Ren Rizzolo
 * Author URI: https://renrizzolo.github.com/
 * License: GPLv3 or later
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt

	WordPress plugin that hacks the gallery[id="1,22,... etc "] shortcode to display a
	clean basic Light Gallery instead of the default static thumbnails. No custom classes
	or extra posts necessary, just use the normal add media button and the nice gallery
	editor already available.
 */

?>
<?php

// Exit if accessed directly.
if (!defined('WPINC')) {
	die;
}
define('PLUGIN_DIR', trailingslashit(plugin_dir_path(__FILE__)));

// Load admin class.
include PLUGIN_DIR . 'admin/class-lgng-admin.php';

if (!class_exists('Lgng_Init')) {

	class Lgng_Init
	{

		static $blockId;

		public function __construct()
		{

			self::$blockId = 0;

			add_action('init',  array($this, 'register_block_action'));

			// legacy shortcode implementation

			if (!function_exists('register_block_type')) {
				// Gutenberg is not active.
				add_action('wp_enqueue_scripts', array($this, 'enqueue_lg_scripts'));
			}
			// this shortcode can (maybe) be converted to a block in gutenberg
			add_shortcode('lg_gallery', array($this, 'lgng_get_gallery'));

			// even more legacy native gallery replacement implementation
			add_filter('the_content', array($this, 'lgng_replace_gallery'));

			//plugin settings link
			add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'plugin_settings_link'));

			// add_action('rest_api_init', function() {
			// 	// Surface all Gutenberg blocks in the WordPress REST API
			// 	$post_types = get_post_types_by_support( [ 'editor' ] );
			// 	foreach ( $post_types as $post_type ) {
			// 		if ( gutenberg_can_edit_post_type( $post_type ) ) {
			// 			register_rest_field( $post_type, 'blocks', [
			// 				'get_callback' => function ( array $post ) {
			// 					return gutenberg_parse_blocks( $post['content']['raw'] );
			// 				}
			// 			] );
			// 		}
			// 	}
			// });

		}

		public function register_block_action()
		{
			if (!function_exists('register_block_type')) {
				// Gutenberg is not active.
				return;
			}
			$this->enqueue_lg_scripts();

			$script_slug = 'lg_gallery_block-cgb-block-js';
			$style_slug = 'lg_gallery_block-cgb-block-style-css';
			$editor_style_slug = 'lg_gallery_block-cgb-block-editor-css';

			wp_register_script(
				$script_slug, // Handle.
				plugin_dir_url(__FILE__) . 'lg-gallery-block/dist/blocks.build.js', // Block.build.js: We register the block here. Built with Webpack.
				array('wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor') // Dependencies, defined above.
				// filemtime( plugin_dir_path( __DIR__ ) . 'dist/blocks.build.js' ), // Version: filemtime — Gets file modification time.
				// true // Enqueue the script in the footer.
			);


			// Styles.
			wp_register_style(
				$style_slug, // Handle.
				plugin_dir_url(__FILE__) .  'lg-gallery-block/dist/blocks.style.build.css', // Block style CSS.
				array('wp-blocks') // Dependency to include the CSS after it.
				// filemtime( plugin_dir_path( __DIR__ ) . 'dist/blocks.style.build.css' ) // Version: filemtime — Gets file modification time.
			);

			wp_register_style(
				$editor_style_slug, // Handle.
				plugin_dir_url(__FILE__) . 'lg-gallery-block/dist/blocks.editor.build.css', // Block editor CSS.
				array('wp-edit-blocks') // Dependency to include the CSS after it.
				// filemtime( plugin_dir_path( __DIR__ ) . 'dist/blocks.editor.build.css' ) // Version: filemtime — Gets file modification time.
			);

			register_meta('post', 'gallery_image_ids', array(
				'show_in_rest' => true,
				'type' => 'string',
			));

			register_block_type(
				'lgng/gallery',  // Block name with namespace
				array(
					'style' => $style_slug, // General block style slug
					'editor_style' => $editor_style_slug, // Editor block style slug
					'editor_script' => $script_slug,  // The block script slug
					'render_callback' => array($this, 'lg_gallery_block_render_cb'), // The render callback
					'attributes' => array(
						'image_size' => array(
							'type' => 'string',
							'default' => $this->get_option('image_size')
						),
						'ls_mode' => array(
							'type' => 'string',
							'default' => $this->get_option('ls_mode')
						),
						'lg_mode' => array(
							'type' => 'string',
							'default' => $this->get_option('lg_mode')
						),
						'images' => array(
							'type' => 'string',
							'default' => '[]',
							// 'source' => 'query',
							// 'selector' => 'div.wp-block-lgng-gallery .ls-item',
						),
						'columns' => array(
							'type' => 'number',
							'default' => $this->get_option('ls_thumb_items'),
						),
						'align' => array(
							'type' => 'string',
							'default' => 'center',
						),
						'lightslider' => array(
							'type' => 'boolean',
							'default' =>  $this->get_option('display_in_lightslider'),
						),
						'lightgallery' => array(
							'type' => 'boolean',
							'default' =>  true,
						),
						'lightSliderAddClass' => array(
							'type' => 'string',
							'default' =>  $this->get_option('lightslider_add_class'),
						),
						'lightSliderOptions' => array(
							'type' => 'string',
							'default' =>  $this->get_option('lightslider_extra_options'),
						),
						'lightGalleryOptions' => array(
							'type' => 'string',
							'default' =>  $this->get_option('lightgallery_extra_options'),

						),
					)
				)
			);
		}

		/**
		 * Get options.
		 **/
		public function get_option($key)
		{
			$options = get_option('lgng_settings');

			if (isset($options[$key]) && '' !== $options[$key]) {
				return $options[$key];
			} else {
				return lgng_Admin::defaults()[$key];
			}
		}

		/**
		 * Register scripts.
		 **/
		public function register_lg_scripts()
		{
			// if ( ! is_admin() ) {
			wp_register_script('lightgallery', plugin_dir_url(__FILE__) . 'public/js/lightgallery.min.js', array('jquery'), '', true);
			wp_register_script('lightgallery-thumbnail', plugin_dir_url(__FILE__) . 'public/js/lg-thumbnail.min.js', array('jquery'), '', true);
			wp_register_script('lightslider', plugin_dir_url(__FILE__) . 'public/js/lightslider.min.js', array('jquery'), '', true);

			// }
		}


		/**
		 * Register styles.
		 **/
		public function register_lg_styles()
		{
			// if ( ! is_admin() ) {
			wp_register_style('lightgallery-css', plugin_dir_url(__FILE__) . 'public/css/lightgallery.css');
			wp_register_style('lightslider-css', plugin_dir_url(__FILE__) . 'public/css/lightslider.css');
			wp_register_style('lgng-css', plugin_dir_url(__FILE__) . 'public/css/lgng.css');
			wp_register_style('lightgallery-transitions-css', plugin_dir_url(__FILE__) . 'public/css/lg-transitions.min.css');

			// }
		}

		/**
		 * Enqueue lightslider.
		 **/
		public function add_ls_scripts()
		{
			wp_enqueue_script('lightslider');
			wp_enqueue_style('lightslider-css');
		}

		/**
		 * 
		 * Enqueue Gutenberg block assets for both frontend + backend.
		 *
		 * `wp-blocks`: includes block type registration and related functions.
		 *
		 * @since 2.0.0
		 */
		public function enqueue_lg_scripts()
		{

			$this->register_lg_scripts();
			$this->register_lg_styles();

			wp_enqueue_style('lgng-css');
			wp_enqueue_style('lightgallery-transitions-css');
			wp_enqueue_style('lightgallery-css');
			wp_enqueue_script('lightgallery');
			wp_enqueue_script('lightgallery-thumbnail');

			// Enqueue the lightslider js if enabled. 
			// can't detect anymore as it's in the gutenberg render callback $atts
			// if ( $this->get_option( 'display_in_lightslider' ) ) {
			$this->add_ls_scripts();
			// }
			// add_action( 'wp_head', array( $this, 'lg_settings_script' ) );
		}

		/**
		 * Enqueue Gutenberg block assets for backend editor.
		 *
		 * `wp-blocks`: includes block type registration and related functions.
		 * `wp-element`: includes the WordPress Element abstraction for describing the structure of your blocks.
		 * `wp-i18n`: To internationalize the block's text.
		 *
		 * @since 2.0.0
		 */

		public function lg_gallery_block_cgb_editor_assets()
		{
			// Scripts.
			wp_register_script(
				'lg_gallery_block-cgb-block-js', // Handle.
				plugin_dir_url(__FILE__) . 'lg-gallery-block/dist/blocks.build.js', // Block.build.js: We register the block here. Built with Webpack.
				array('lodash', 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor'), // Dependencies, defined above.
				// filemtime( plugin_dir_path( __DIR__ ) . 'dist/blocks.build.js' ), // Version: filemtime — Gets file modification time.
				true // Enqueue the script in the footer.
			);

			// Styles.
			wp_register_style(
				'lg_gallery_block-cgb-block-editor-css', // Handle.
				plugin_dir_url(__FILE__) . 'lg-gallery-block/dist/blocks.editor.build.css', // Block editor CSS.
				array('wp-edit-blocks') // Dependency to include the CSS after it.
				// filemtime( plugin_dir_path( __DIR__ ) . 'dist/blocks.editor.build.css' ) // Version: filemtime — Gets file modification time.
			);
		}


		public function lg_settings_script()
		{
			if (is_single()) {

?>
				<script>
					jQuery(document).ready(function($) {

						<?php if ($this->get_option('display_in_lightslider')) { ?>
							$('.lightgallery#lgng-block-<?php echo self::$blockId ?>').lightGallery({
								thumbnail: <?php echo $this->get_option('show_thumbnails') ? 'true' : 'false'; ?>,
								selector: '.lg-open',
								download: <?php echo $this->get_option('show_download') ? 'true' : 'false'; ?>,
								showAfterLoad: true,
								subHtmlSelectorRelative: true,
								hideBarsDelay: 2000,
								exThumbImage: 'data-exthumbimage',
								mode: '<?php echo esc_js($this->get_option('lg_mode')); ?>',
							});
							$('.lightgallery#lgng-block-<?php echo self::$blockId ?>').lightSlider({
								gallery: true,
								item: 1,
								loop: true,
								slideMargin: 0,
								thumbItem: <?php echo esc_js($this->get_option('ls_thumb_items')) ?>,
								gallery: <?php echo ($this->get_option('ls_thumb_items') !== '0') ? 'true' : 'false'; ?>,
								mode: '<?php echo esc_js($this->get_option('ls_mode')); ?>',
							});
						<?php } else { ?>
							$('.lightgallery#lgng-block-<?php echo self::$blockId ?>').lightGallery({
								thumbnail: <?php echo $this->get_option('show_thumbnails') ? 'true' : 'false'; ?>,
								selector: '.<?php echo esc_js($this->get_option('selector_class')); ?>',
								download: <?php echo $this->get_option('show_download') ? 'true' : 'false'; ?>,
								showAfterLoad: true,
								subHtmlSelectorRelative: true,
								hideBarsDelay: 2000,
								exThumbImage: 'data-exthumbimage',
								mode: '<?php echo esc_js($this->get_option('lg_mode')); ?>',
							});
						<?php } ?>
					});
				</script>

<?php
			}
		}


		/**
		 * Block render callback
		 * 
		 * Render callback for the dynamic block.
		 * 
		 * Instead of rendering from the block's save(), this callback will render the front-end
		 *
		 * @since 2.0.0
		 * @param $att Attributes from the JS block
		 * @return string Rendered HTML
		 */
		public function lg_gallery_block_render_cb($atts)
		{

			if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'], 'graphql')) {
				// maybe probably this is a wpgraphql query
				return;
			}

			self::$blockId++;

			$selector 		= $this->get_option('selector_class');
			$container 		= $this->get_option('container_class');
			$img_class 		= $this->get_option('img_class');
			$item 				= $this->get_option('item_class');
			$lghtml = '';


			$lghtml .= '<script>';
			$lghtml .= 'jQuery(document).ready(function($){';

			if ($atts['lightslider']) {
				// lol this was annoying to write
				$lghtml .= '$(".lightgallery#lgng-block-' . self::$blockId . '").lightSlider({';
				$lghtml .= '"item": 1,';
				$lghtml .= '"loop": true,';
				$lghtml .= '"slideMargin": 0,';
				$lghtml .= '"mode": "' . $atts['ls_mode'] . '",';
				$lghtml .= '"thumbItem":' . $atts['columns'] . ',';
				if ($atts['columns'] == 0) {
					$lghtml .= '"gallery": false,';
					$lghtml .= '"pager": false,';
				} else {
					$lghtml .= '"gallery": true,';
				}
				$lghtml .= '"addClass": "align' . $atts['align'] . ' ' . $atts['lightSliderAddClass'] . '",';
				$lghtml .= $atts['lightSliderOptions'];
				$lghtml .= '});';
				if ($atts['lightgallery']) {
					$lghtml .= '$(".lightgallery#lgng-block-' . self::$blockId . '").lightGallery({';
					if ($this->get_option('show_thumbnails') !== 0) {
						$lghtml .= '"thumbnail": true,';
					} else {
						$lghtml .= '"thumbnail": false,';
					}
					$lghtml .= '"selector": ".ls-item:not(.clone) .lg-open",';
					if ($this->get_option('show_download')) {
						$lghtml .= '"download": true,';
					} else {
						$lghtml .= '"download": false,';
					}
					$lghtml .= '"showAfterLoad": true,';
					$lghtml .= '"hideBarsDelay": 2000,';
					$lghtml .= '"subHtmlSelectorRelative": true,';
					$lghtml .= '"exThumbImage": "data-exthumbimage",';
					$lghtml .= '"mode": "' . $atts['lg_mode'] . '",';
					$lghtml .= $atts['lightGalleryOptions'];
					$lghtml .= '});';
				}
			} else {
				$lghtml .= '$(".lightgallery#lgng-block-' . self::$blockId . '").lightGallery({';
				if ($this->get_option('show_thumbnails') !== 0) {
					$lghtml .= '"thumbnail": true,';
				} else {
					$lghtml .= '"thumbnail": false,';
				}
				$lghtml .= '"selector": ".' . esc_js($this->get_option('selector_class')) . '",';
				if ($this->get_option('show_download')) {
					$lghtml .= '"download": true,';
				} else {
					$lghtml .= '"download": false,';
				}
				$lghtml .= '"showAfterLoad": true,';
				$lghtml .= '"hideBarsDelay": 2000,';
				$lghtml .= '"subHtmlSelectorRelative": true,';
				$lghtml .= '"exThumbImage": "data-exthumbimage",';
				$lghtml .= '"mode": "' . $atts['lg_mode'] . '",';
				$lghtml .=  $atts['lightGalleryOptions'];
				$lghtml .= '});';
			}
			$lghtml .= '});';
			$lghtml .= '</script>';



			switch ($atts['columns']) {
				case '6':
					$classes = 'lgng-6-cols';
					break;
				case '5':
					$classes = 'lgng-5-cols';
					break;
				case '4':
					$classes = 'lgng-4-cols';
					break;
				case '3':
					$classes = 'lgng-3-cols';
					break;
				case '2':
					$classes = 'lgng-2-cols';
					break;
				default:
					$classes = 'lgng-4-cols';
					break;
			}

			$images = json_decode($atts['images'], true);
			$lghtml .=  '<div class="lightgallery lgng-row ' . $container . '" id="lgng-block-' . self::$blockId . '">';
			foreach ($images as $img) {

				if (isset($img['id']) && '' !== $img['id']) {

					$thumb 	= wp_get_attachment_image_src($img['id'], 'thumbnail')[0];

					$full	= wp_get_attachment_image_src($img['id'], $atts['image_size'])[0];

					// deprecated: get caption from attachment
					// $caption = get_post( $img['id'] );

					// if( !empty( $caption->post_excerpt ) ) {
					// 	$caption_text = $caption->post_excerpt;
					// }

					// how to parse this when it's from <RichText/>? 
					// oh, use format="string" prop
					$caption_html =  isset($img['caption']) ? $img['caption'] : '';
					// echo $caption_html[0];

					if ($atts['lightslider']) {
						$lghtml .= '<figure class="ls-item ' . $item . '" data-thumb="' . $thumb . '">';
						$lghtml .= '<img alt="' . htmlspecialchars($caption_html) . '" class="' . $img_class . '" src="' . $full . '" />';
						// 	$lghtml .= 	'<div class="lg-caption">Caption here</div>';
						if ($atts['lightgallery']) {
							$lghtml .= '<span class="' . $selector . ' lg-open lg-fullscreen lg-icon" data-exthumbimage="' . $thumb . '" data-sub-html="' . htmlspecialchars($caption_html) . '"  data-src="' . $full . '">';
							$lghtml .= '</span>';
						}
						$lghtml .= '</figure>';
					} else {
						$lghtml .= '<a data-sub-html="' . htmlspecialchars($caption_html) . '" data-exthumbimage="' . $thumb . '" class="' . $selector . ' ' . $classes . '" href="' . $full . '">';
						$lghtml .= '<img class="' . $img_class . ' lg-thumb" src="' . $thumb . '">';
						$lghtml .= "</a>";
					}
				}
			}

			$lghtml .=  '</div>';

			return $lghtml;
		}

		/**
		 * Return the gallery for the lg_gallery shortcode.
		 *
		 * @param string $ids CSV string of image ids taken from gallery shortcode.
		 **/
		public function lgng_get_gallery($atts)
		{
			if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'], 'graphql')) {
				// maybe probably this is a wpgraphql query
				return;
			}
			$lghtml = '';
			self::$blockId++;

			// if ( get_post_gallery() ) {
			$lghtml .= $this->lg_settings_script();


			// $match_ids = '/ids\s*=\s*"(.*?)"/';
			// $match_cols = '/columns\s*=\s*"(.*?)"/';
			$match_ids = $atts['ids'];
			$match_cols = isset($atts['columns']) ? $atts['columns'] : 4;

			// preg_match($match_ids, $match, $ids);
			// preg_match($match_cols, $match, $cols);

			$array_id = explode(',', $match_ids);
			// if (!$array_id) return null;

			$selector 		= $this->get_option('selector_class');
			$container 		= $this->get_option('container_class');
			$img 					= $this->get_option('img_class');
			$item 				= $this->get_option('item_class');

			//	$classes = 'lgng-3-cols';

			// if ( count($array_id) % 3 === 0 ) {
			// 	$classes = 'lgng-4-cols';
			// }
			$classes = 'lgng-4-cols';

			if ($match_cols) {
				switch ($match_cols) {
					case '6':
						$classes = 'lgng-6-cols';
						break;
					case '5':
						$classes = 'lgng-5-cols';
						break;
					case '4':
						$classes = 'lgng-4-cols';
						break;
					case '3':
						$classes = 'lgng-3-cols';
						break;
					case '2':
						$classes = 'lgng-2-cols';
						break;
					default:
						$classes = 'lgng-4-cols';
						break;
				}
			}

			$lghtml .=  '<div class="lightgallery lgng-row ' . $container . '" id="lgng-block-' . self::$blockId . '">';
			foreach ($array_id as $id) {
				// in case of empty id (from trailing comma in ids string)
				if (isset($id) && '' !== $id) {
					$thumb 	= wp_get_attachment_image_src($id, 'thumbnail')[0];
					$full	= wp_get_attachment_image_src($img['id'], $this->get_option('image_size'))[0];

					$caption = get_post($id);
					$caption_text = '';

					if (!empty($caption->post_excerpt)) {
						$caption_text = $caption->post_excerpt;
					}

					if ($this->get_option('display_in_lightslider')) {
						$lghtml .= '<figure class="ls-item ' . $item . '" data-thumb="' . $thumb . '">';
						$lghtml .= '<img alt="' . $caption_text . '" class="' . $img . '" src="' . $full . '" />';
						// 	$lghtml .= 	'<div class="lg-caption">Caption here</div>';
						$lghtml .= '<span class="' . $selector . ' lg-open lg-fullscreen lg-icon" data-exthumbimage="' . $thumb . '" data-sub-html="' . $caption_text . '"  data-src="' . $full . '">';
						$lghtml .= '</span>';
						$lghtml .= '</figure>';
					} else {
						$lghtml .= '<a data-sub-html="' . $caption_text . '" data-exthumbimage="' . $thumb . '" class="' . $selector . ' ' . $classes . '" href="' . $full . '">';
						$lghtml .= '<img class="' . $img . '" src="' . $thumb . '">';
						$lghtml .= "</a>";
					}
				}
			}

			$lghtml .=  '</div>';

			// }	else {
			// 	$lghtml = false;
			// }
			return $lghtml;
		}


		/**
		 * Return the gallery html for the native gallery shortcode replacement.
		 *
		 * @param string $ids CSV string of image ids taken from gallery shortcode.
		 **/
		public function lgng_get_gallery_legacy($match)
		{
			if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'], 'graphql')) {
				// maybe probably this is a wpgraphql query
				return;
			}

			// if (strpos($_SERVER['REQUEST_URI'], 'wp-admin')) return;
			// if ($_SERVER['REQUEST_METHOD'] == 'POST' && strpos($_SERVER['REQUEST_URI'], 'graphql')) return '';
			// if (strpos($_SERVER['REQUEST_URI'], 'wp-json')) return '';

			$lghtml = '';
			self::$blockId++;

			$lghtml .= $this->lg_settings_script();

			$match_ids = '/ids\s*=\s*"(.*?)"/';
			$match_cols = '/columns\s*=\s*"(.*?)"/';


			preg_match($match_ids, $match, $ids);
			preg_match($match_cols, $match, $cols);

			$array_id = explode(',', $ids[1]);
			// if (!$array_id) return null;
			$selector 		= $this->get_option('selector_class');
			$container 		= $this->get_option('container_class');
			$img 					= $this->get_option('img_class');
			$item 				= $this->get_option('item_class');

			//	$classes = 'lgng-3-cols';

			// if ( count($array_id) % 3 === 0 ) {
			// 	$classes = 'lgng-4-cols';
			// }
			$classes = 'lgng-4-cols';

			if ($cols) {
				switch ($cols) {
					case '6':
						$classes = 'lgng-6-cols';
						break;
					case '5':
						$classes = 'lgng-5-cols';
						break;
					case '4':
						$classes = 'lgng-4-cols';
						break;
					case '3':
						$classes = 'lgng-3-cols';
						break;
					case '2':
						$classes = 'lgng-2-cols';
						break;
					default:
						$classes = 'lgng-4-cols';
						break;
				}
			}

			$lghtml .=  '<div class="lightgallery lgng-row ' . $container . '" id="lgng-block-' . self::$blockId . '">';
			foreach ($array_id as $id) {
				// in case of empty id (from trailing comma in ids string)
				if (isset($id) && '' !== $id) {
					$thumb 	= wp_get_attachment_image_src($id, 'thumbnail')[0];
					$full 	= wp_get_attachment_image_src($id, $this->get_option('image_size'))[0];

					$caption = get_post($id);
					$caption_text = '';

					if (!empty($caption->post_excerpt)) {
						$caption_text = $caption->post_excerpt;
					}

					if ($this->get_option('display_in_lightslider')) {
						$lghtml .= '<figure class="ls-item ' . $item . '" data-thumb="' . $thumb . '">';
						$lghtml .= '<img alt="' . $caption_text . '" class="' . $img . '" src="' . $full . '" />';
						// 	$lghtml .= 	'<div class="lg-caption">Caption here</div>';
						$lghtml .= '<span class="' . $selector . ' lg-open lg-fullscreen lg-icon" data-exthumbimage="' . $thumb . '" data-sub-html="' . $caption_text . '"  data-src="' . $full . '">';
						$lghtml .= '</span>';
						$lghtml .= '</figure>';
					} else {
						$lghtml .= '<a data-sub-html="' . $caption_text . '" data-exthumbimage="' . $thumb . '" class="' . $selector . ' ' . $classes . '" href="' . $full . '">';
						$lghtml .= '<img class="' . $img . '" src="' . $thumb . '">';
						$lghtml .= "</a>";
					}
				}
			}

			$lghtml .=  '</div>';

			return $lghtml;
		}
		/**
		 * Filter that replaces Gallery shortcode with the newly generated Light Gallery html.
		 *
		 * @param content $content the post content.
		 **/
		public function lgng_replace_gallery($content)
		{

			global $post;
			$new_content = $content;
			// make sure that the gallery is on a page/single post.
			if (is_singular()) {
				// return content if there isn't a gallery present.
				if (!has_shortcode($post->post_content, 'gallery')) {
					return $content;
				}

				$gallery_pattern = '/\[gallery(.*?)\]/';

				// get all gallery shortcodes
				preg_match_all($gallery_pattern, $new_content, $gallery_matches);
				// print_r($gallery_matches);

				//for each gallery atts
				foreach ($gallery_matches[0] as $matches) {
					// print_r($matches);

					$new_content = preg_replace_callback(
						$gallery_pattern,
						function (&$matches) {
							return $this->lgng_get_gallery_legacy($matches[1]);
						},
						$new_content
					);
				}
				// echo '</pre>';
			}
			return wpautop($new_content);
		}

		public function collect($out, $pair, $atts)
		{
			$this->atts[] = $atts;
			print_r($out);
			return $out;
		}

		/**
		 * Add settings link to plugins page.
		 *
		 * @param array $links plugin settings links.
		 */
		public function plugin_settings_link($links)
		{
			$url           = get_admin_url() . 'options-general.php?page=lgng';
			$settings_link = '<a href="' . $url . '">' . __('Settings', 'lgng') . '</a>';
			array_unshift($links, $settings_link);
			return $links;
		}
	}

	new Lgng_Init();
}
