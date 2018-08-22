<?php

/**
 *
 * Light Gallery Native Gallery admin setting.
 *
 * @link       https://renrizzolo.github.com/
 * @since      1.0.0
 *
 * @package    lgng
 * @subpackage lgng/admin
 */
class lgng_Admin {
	/**
	 * Hook into admin_menu and admin_init
	 **/
	public function __construct() {
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'lgng_add_admin_menu' ) );
			add_action( 'admin_init', array( $this, 'lgng_settings_init' ) );
		}
	}

	/**
	 * Returns the array of default options.
	 **/
	public static function defaults() {
		return array(
			'display_in_lightslider'     	=> 1,
			'show_download'     					=> 1,
			'show_thumbnails'     				=> 1,
			'container_class'       			=> '',
			'selector_class'       				=> 'item',
			'item_class'									=> '',
			'img_class'										=> '',
			'ls_thumb_items'							=> 6,
			'ls_mode'											=> 'slide',
			'lg_mode'											=> 'lg-slide',
			'image_size'									=> 'full',

		);
	}

	/**
	 * Returns a default option by its key.
	 *
	 * @param string $key The key of the default option.
	 **/
	public function get_default( $key ) {
		$defaults = $this->defaults();
		return $defaults[ $key ];
	}

	/**
	 * Returns an option by its key.
	 * If empty or not set, returns the default.
	 *
	 * @param string $key The key of the option.
	 **/
	public function get_option( $key ) {
		$options = get_option( 'lgng_settings' );

		if ( isset( $options[ $key ] ) && '' !== $options[ $key ] ) {
			return $options[ $key ];
		} else {
			return $this->defaults()[ $key ];
		}
	}


	/**
	 * Adds the options.
	 **/
	public function init_options() {
		$options = $this->defaults();
		add_option( 'lgng_settings', $options );
	}

	/**
	 * Add Light Gallery Native Gallery to Settings menu.
	 **/
	public function lgng_add_admin_menu() {

		add_options_page( 'Light Gallery Native Gallery', 'Light Gallery Native Gallery', 'manage_options', 'lgng', array( $this, 'lgng_options_page' ) );

	}

	/**
	 * Validates text/number input options being saved
	 *
	 * @param array $options The options to be validated.
	 **/
	public function lgng_settings_validate( $options ) {

		// Create our array for storing the validated options.
		$output = array();

		// hard setting the checkboxes to 0 ( they will be overwritten to 1 if they are set (i.e checked) in the foreach loop ).
		$output['display_in_lightslider'] = 0;
		$output['display_in_lightslider'] = 0;
		$output['show_download']					= 0;
		$output['show_thumbnails']    		= 0;

		// Loop through each of the incoming options.
		foreach ( $options as $key => $value ) {

			// Check to see if the current option has a value. If so, process it.
			if ( isset( $options[ $key ] ) ) {

				// Strip all HTML and PHP tags and properly handle quoted strings.
				$output[ $key ] = sanitize_text_field( $options[ $key ] );
			}
		}

		// Return the sanitized array.
		return $output;
	}

	/**
	 * Validates checkbox options being saved
	 *
	 * @param array $options The options to be validated.
	 **/
	public function lgng_settings_validate_checkboxes( $options ) {

		// return 1 or 0 for checkboxes instead of unsetting when unchcecked.
		$output = array();
		foreach ( $options as $key => $value ) {
			$output[$key] = ( 1 === $option[$key] ) ? 1 : 0;
		}
		return $output;
	}

	/**
	 * Initialize the settings page.
	 *
	 * Registers settings and adds settings sections using the plugin settings API.
	 **/
	public function lgng_settings_init() {

		// Add the options if they haven't been added already.
		$this->init_options();

		register_setting(
			'pluginPage',
			'lgng_settings',
			array( $this, 'lgng_settings_validate' )
		);

		add_settings_section(
			'lgng_lg_section',
			__( 'Lightgallery', 'lgng' ),
			array( $this, 'lgng_lg_section_callback' ),
			'pluginPage'
		);

		add_settings_section(
			'lgng_ls_section',
			__( 'Lightslider', 'lgng' ),
			array( $this, 'lgng_ls_section_callback' ),
			'pluginPage'
		);

		add_settings_section(
			'lgng_styles_section',
			__( 'Styles', 'lgng' ),
			array( $this, 'lgng_styles_section_callback' ),
			'pluginPage'
		);

		add_settings_field(
			'display_in_lightslider',
			__( 'Display in slider*', 'lgng' ),
			array( $this, 'lgng_text_field_display_in_lightslider_render' ),
			'pluginPage',
			'lgng_ls_section'
		);

		add_settings_field(
			'ls_thumb_items',
			__( 'Lightslider # of thumbnails*', 'lgng' ),
			array( $this, 'lgng_text_ls_thumb_items_render' ),
			'pluginPage',
			'lgng_ls_section'
		);

		add_settings_field(
			'lg_mode',
			__( 'Lightgallery slide mode*', 'lgng' ),
			array( $this, 'lgng_select_lg_mode_render' ),
			'pluginPage',
			'lgng_lg_section'
		);

		add_settings_field(
			'image_size',
			__( 'image size', 'lgng' ),
			array( $this, 'lgng_select_image_size_render' ),
			'pluginPage',
			'lgng_lg_section'
		);

		add_settings_field(
			'ls_mode',
			__( 'Lightslider slide mode*', 'lgng' ),
			array( $this, 'lgng_select_ls_mode_render' ),
			'pluginPage',
			'lgng_ls_section'
		);

		add_settings_field(
			'show_thumbnails',
			__( 'show thumbnails in lightgallery', 'lgng' ),
			array( $this, 'lgng_text_field_show_thumbnails_render' ),
			'pluginPage',
			'lgng_lg_section'
		);

		add_settings_field(
			'show_download',
			__( 'enable download image button in lightgallery', 'lgng' ),
			array( $this, 'lgng_text_field_show_download_render' ),
			'pluginPage',
			'lgng_lg_section'
		);

		add_settings_field(
			'container_class',
			__( 'gallery container classes', 'lgng' ),
			array( $this, 'lgng_text_container_class_render' ),
			'pluginPage',
			'lgng_styles_section'
		);

		add_settings_field(
			'item_class',
			__( 'additional item (anchor) classes', 'lgng' ),
			array( $this, 'lgng_text_item_class_render' ),
			'pluginPage',
			'lgng_styles_section'
		);

		add_settings_field(
			'selector_class',
			__( 'selector class for lightgallery', 'lgng' ),
			array( $this, 'lgng_text_selector_class_render' ),
			'pluginPage',
			'lgng_styles_section'
		);

		add_settings_field(
			'img_class',
			__( 'Image element classes', 'lgng' ),
			array( $this, 'lgng_text_img_class_render' ),
			'pluginPage',
			'lgng_styles_section'
		);

	}

// lightgallery section

	public function lgng_select_lg_mode_render() {
		$option = get_option( 'lgng_settings' );
		?>
			<select id="lgng_settings[lg_mode]" name="lgng_settings[lg_mode]">
				<option value="lg-slide" <?php selected( $option['lg_mode'], 'lg-slide' ); ?>>Slide</option>
				<option value="lg-fade" <?php selected( $option['lg_mode'], 'lg-fade' ); ?>>Fade</option>
				<option value="lg-zoom-in" <?php selected( $option['lg_mode'], 'lg-zoom-in' ); ?>>Zoom In</option>
				<option value="lg-zoom-in-big" <?php selected( $option['lg_mode'], 'lg-zoom-in-big' ); ?>>Zoom In Big</option>
				<option value="lg-zoom-out" <?php selected( $option['lg_mode'], 'lg-zoom-out' ); ?>>Zoom Out</option>
				<option value="lg-zoom-out-big" <?php selected( $option['lg_mode'], 'lg-zoom-out-big' ); ?>>Zoom Out Big</option>
				<option value="lg-zoom-out-in" <?php selected( $option['lg_mode'], 'lg-zoom-out-in' ); ?>>Zoom Out In</option>
				<option value="lg-zoom-in-out" <?php selected( $option['lg_mode'], 'lg-zoom-in-out' ); ?>>Zoom In Out</option>
				<option value="lg-soft-zoom" <?php selected( $option['lg_mode'], 'lg-soft-zoom' ); ?>>Soft Zoom</option>
				<option value="lg-scale-up" <?php selected( $option['lg_mode'], 'lg-scale-up' ); ?>>Scale Up</option>
				<option value="lg-slide-circular" <?php selected( $option['lg_mode'], 'lg-slide-circular' ); ?>>Slide Circular</option>
				<option value="lg-slide-circular-vertical" <?php selected( $option['lg_mode'], 'lg-slide-circular-vertical' ); ?>>Slide Circular Vertical</option>
				<option value="lg-slide-vertical" <?php selected( $option['lg_mode'], 'lg-slide-vertical' ); ?>>Slide Vertical</option>
				<option value="lg-slide-vertical-growth" <?php selected( $option['lg_mode'], 'lg-slide-vertical-growth' ); ?>>Slide Vertical Growth</option>
				<option value="lg-slide-skew-only" <?php selected( $option['lg_mode'], 'lg-slide-skew-only' ); ?>>Slide Skew Only</option>
				<option value="lg-slide-skew-only-rev" <?php selected( $option['lg_mode'], 'lg-slide-skew-only-rev' ); ?>>Slide Skew Only Reverse</option>
				<option value="lg-slide-skew-only-y" <?php selected( $option['lg_mode'], 'lg-slide-skew-only-y' ); ?>>Slide Skew Only Y</option>
				<option value="lg-slide-skew-only-y-rev" <?php selected( $option['lg_mode'], 'lg-slide-skew-only-y-rev' ); ?>>Slide Skew Only Y Reverse</option>
				<option value="lg-slide-skew" <?php selected( $option['lg_mode'], 'lg-slide-skew' ); ?>>Slide Skew</option>
				<option value="lg-slide-skew-rev" <?php selected( $option['lg_mode'], 'lg-slide-skew-rev' ); ?>>Slide Skew Reverse</option>
				<option value="lg-slide-skew-cross" <?php selected( $option['lg_mode'], 'lg-slide-skew-cross' ); ?>>Slide Skew Cross</option>
				<option value="lg-slide-skew-cross-rev" <?php selected( $option['lg_mode'], 'lg-slide-skew-cross-rev' ); ?>>Slide Skew Cross Reverse</option>
				<option value="lg-slide-skew-ver" <?php selected( $option['lg_mode'], 'lg-slide-skew-ver' ); ?>>Slide Skew Vertical</option>
				<option value="lg-slide-skew-ver-rev" <?php selected( $option['lg_mode'], 'lg-slide-skew-ver-rev' ); ?>>Slide Skew Vertical Reverse</option>
				<option value="lg-slide-skew-ver-cross" <?php selected( $option['lg_mode'], 'lg-slide-skew-ver-cross' ); ?>>Slide Skew Vertical Cross</option>
				<option value="lg-slide-skew-ver-cross-rev" <?php selected( $option['lg_mode'], 'lg-slide-skew-ver-cross-rev' ); ?>>Slide Skew Vertical Cross Reverse</option>
				<option value="lg-lollipop" <?php selected( $option['lg_mode'], 'lg-lollipop' ); ?>>Lollipop</option>
				<option value="lg-lollipop-rev" <?php selected( $option['lg_mode'], 'lg-lollipop-rev' ); ?>>Lollipop Reverse</option>
				<option value="lg-rotate" <?php selected( $option['lg_mode'], 'lg-rotate' ); ?>>Rotate</option>
				<option value="lg-rotate-rev" <?php selected( $option['lg_mode'], 'lg-rotate-rev' ); ?>>Rotate Reverse</option>
				<option value="lg-tube" <?php selected( $option['lg_mode'], 'lg-tube' ); ?>>Tube</option>
			</select>
	<?php 

	}
	public function lgng_select_image_size_render() {
		$option = get_option( 'lgng_settings' );
		$sizes = get_intermediate_image_sizes();
		?>
		<select id="lgng_settings[image_size]" name="lgng_settings[image_size]">
		<option value="full" <?php selected( $option['image_size'], 'full'); ?>>full</option>
		<?php foreach ( $sizes as $size ) { ?>
			<option value="<?php echo $size ?>" <?php selected( $option['image_size'], $size); ?>><?php echo $size ?></option>
		<?php	} ?>
		</select>
	<?php }
	public function lgng_text_field_show_thumbnails_render() {
		$option = get_option( 'lgng_settings' );
		?>
		<input type='checkbox' id="lgng_settings[show_thumbnails]" name='lgng_settings[show_thumbnails]' value="1" <?php checked( '1', $option['show_thumbnails'] ); ?> />
		<?php

	}

	public function lgng_text_field_show_download_render() {
		$option = get_option( 'lgng_settings' );
		?>
		<input type='checkbox' id="lgng_settings[show_download]" name='lgng_settings[show_download]' value="1" <?php checked( '1', $option['show_download'] ); ?> />
		<?php

	}


// lightslider section

	public function lgng_text_field_display_in_lightslider_render() {
		$option = get_option( 'lgng_settings' );
		?>
		<input type='checkbox' id="lgng_settings[display_in_lightslider]" name='lgng_settings[display_in_lightslider]' value="1" <?php checked( '1', $option['display_in_lightslider'] ); ?> /> 
		Whether to display the thumbnails in a grid or show a slider with optional thumbnails under it.
		<?php

	}


	public function lgng_text_ls_thumb_items_render() {

		$option = $this->get_option( 'ls_thumb_items' );
		?>
		<input class="widefat" type='number' name='lgng_settings[ls_thumb_items]' value='<?php echo esc_html( $option ); ?>'>
			Amount of thumbs to show when using lightslider ( set to 0 to disable thumbs ).
		<?php

	}
	
	public function lgng_select_ls_mode_render() {
			$option = get_option( 'lgng_settings' );
		?>

		<select id="lgng_settings[ls_mode]" name='lgng_settings[ls_mode]'/>
			<option value="slide" <?php selected( $option['ls_mode'], 'slide' ); ?>>Slide</option>
			<option disabled value="fade" <?php selected( $option['ls_mode'], 'fade' ); ?>>Fade</option>
		</select>
		<?php

	}

// styles section

	public function lgng_text_container_class_render() {

		$option = $this->get_option( 'container_class' );
		?>
		<input class="widefat" type='text' name='lgng_settings[container_class]' value='<?php echo esc_html( $option ); ?>'>
		<?php

	}

	public function lgng_text_item_class_render() {

		$option = $this->get_option( 'item_class' );
		?>
		<input class="widefat" type='text' name='lgng_settings[item_class]' value='<?php echo esc_html( $option ); ?>'>
		<?php

	}

	public function lgng_text_selector_class_render() {

		$option = $this->get_option( 'selector_class' );
		?>
		<input class="widefat" type='text' name='lgng_settings[selector_class]' value='<?php echo esc_html( $option ); ?>'>
		<?php

	}

	public function lgng_text_img_class_render() {

		$option = $this->get_option( 'img_class' );
		?>
		<input class="widefat" type='text' name='lgng_settings[img_class]' value='<?php echo esc_html( $option ); ?>'>
		<?php

	}






	public function lgng_lg_section_callback() {
		echo esc_html_e( '* overridden by the gutenberg block settings, i.e gutenberg takes preference, but these settings can be used to shape how the block will be set by default when adding a new gallery', 'lgng' );

	}

	public function lgng_ls_section_callback() {

		echo esc_html_e( 'Settings for lightslider', 'lgng' );

	}
	public function lgng_styles_section_callback() {

		echo esc_html_e( 'You can add your own classes here', 'lgng' );

	}

	public function lgng_options_page() {
		if ( isset( $_GET['tab'] ) ) {
			$active_tab = sanitize_text_field( wp_unslash( $_GET['tab'] ) );
		} else {
			// Set settings_tab tab as a default tab.
			$active_tab = 'settings_tab';
		}

		?>
		<form action='options.php' method='post' style="max-width: 800px;">

			<h2>Light Gallery Native Gallery</h2>
			<h2 class="nav-tab-wrapper">
				<a href="<?php get_admin_url(); ?>options-general.php?page=lgng&tab=settings_tab" class="nav-tab <?php echo 'settings_tab' === $active_tab ? 'nav-tab-active' : ''; ?>">Settings	</a>
				<a href="<?php get_admin_url(); ?>options-general.php?page=lgng&tab=usage_tab" class="nav-tab <?php echo 'usage_tab' === $active_tab ? 'nav-tab-active' : ''; ?>">Usage</a>
			</h2>
			<?php

			if ( 'settings_tab' === $active_tab ) {
				settings_fields( 'pluginPage' );
				do_settings_sections( 'pluginPage' );
				submit_button();
			} else {
			?>

			<h2>Usage:</h2>
			<h3>Gutenberg</h3>

			<p>Add Lightgallery block in the gutenberg editor. Requires the gutenberg plugin to be active.</p>
	<br/>
	<br/>
			<h3>Legacy implementation (shortcode)</h3>
			<p>Add a gallery via the WordPress add media button, change shortcode to <code>lg_gallery</code>
			</br>
			or write shortcode directly: <code>[lg_gallery ids=xxx,xxx,xxx]</code>
					</br>
		</p>
				
			<h3>Legacy implementation (replaces native gallery)</h3>
			<p>Add a gallery via the WordPress add media button or via shortcode: <code>[gallery ids=xxx,xxx,xxx]</code></p>
		
			<?php
			}
			?>

	</form>
		<?php

	}
}
new lgng_Admin();
