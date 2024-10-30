<?php
/**
 * Plugin Name: SmartAsset Captivate
 * Plugin URI: https://smartasset.com/
 * Description: SmartAsset Captivate Wordpress Plugin
 * Author: SmartAsset.com
 * Version: 0.14.2
 * Author URI: https://smartasset.com/
 */

/**
 * Plugin version
 *
 * @var string SA_WP_PLUG_VER
 */
define( 'SA_WP_PLUG_VER', '0.14.2' );

/**
 * Text used as manual placement in short code
 * Usage: [SA_SHORTCODE]
 *
 * @var string SA_SHORTCODE
 */
define( 'SA_SHORTCODE', 'sa_captivate' );

/**
 * Text used to explicity define which captivate widget should be placed
 * Usage: [SA_SHORTCOE SA_TYPE_PRE"widget_name"]
 *
 * @var string SA_TYPE_PRE
 */
define( 'SA_TYPE_PRE', 'type=' );

/**
 * SmartAsset Widgets class.
 * Creates the shortcode, its handler, and initializes the admin panel.
 */
class SmartAssetWidgets {
	/**
	 * Constructor for smartasset widget.
	 */
	public function __construct() {
		// Load the admin settings.
		include_once $this->get_plugin_home() . '/inc/sa-widgets-admin.php';
		$admin = new SmartAssetWidgetsAdmin();

		// Set default config options.
		// Call set_config_defaults when the plugin is activated.
		register_activation_hook( __FILE__, array( $this, 'set_config_defaults' ) );

		$this->init();
	}

	/**
	 * Return the plugin's file location.
	 *
	 * @return string The absolute path of the directory that contains the file, with trailing slash
	 */
	public function get_plugin_home() {
		return plugin_dir_path( __FILE__ );
	}

	/**
	 * Returns the version of the plugin defined in constant SA_WP_PLUG_VER
	 *
	 * @return string The version of this plugin
	 */
	public function get_plugin_ver() {
		return SA_WP_PLUG_VER;
	}

	/**
	 * Initialize plugin.
	 */
	public function init() {
		// Get the saved API key from WordPress options table.
		$api_key = get_option( 'sa_key' );

		// Only show widgets for valid keys.
		if ( $this->validate_key( $api_key ) ) {
			// The key is valid.
			add_shortcode( SA_SHORTCODE, array( $this, 'handle_sa_captivate_shortcode' ) );

			// Add handlers for the exlusion checkbox in edit posts.
			$this->add_exclusion_checkbox();

			// Add content filtering.
			$this->set_content_filters();
		}
	}

	/**
	 * Hooks exclusion functions to add_meta_boxes and save_post actions for exclusions meta box.
	 *
	 * The meta box is a check box where the user can decide to prevent a post they are creating or editing
	 * from showing a captivate widget, adding it's ID to the exclusions setting.
	 */
	public function add_exclusion_checkbox() {
		add_action( 'add_meta_boxes', array( $this, 'sa_exclude_post_checkbox' ) );
		add_action( 'add_meta_boxes', array( $this, 'sa_exclude_page_checkbox' ) );
		add_action( 'save_post', array( $this, 'sa_exclude_post_save' ) );
		add_action( 'admin_print_footer_scripts', array( $this, 'sa_shortcode_button_script' ) );
	}

	/**
	 * Adds the post exclusion meta box to the side of the post creation page.
	 */
	public function sa_exclude_post_checkbox() {
		add_meta_box(
			'sa_exclude_post_check_id',
			'Exclude this Post from adding Captivate Widgets',
			array( $this, 'sa_exclude_post_check_content' ),
			'post',
			'side'
		);
	}

	/**
	 * Adds the page exclusion meta box to the side of the page creation page.
	 */
	public function sa_exclude_page_checkbox() {
		add_meta_box(
			'sa_exclude_page_check_id',
			'Exclude this Page from adding Captivate Widgets',
			array( $this, 'sa_exclude_page_check_content' ),
			'page',
			'side'
		);
	}

	/**
	 * Defines the HTML content for the page exclusion checkbox.
	 *
	 * @param array $post Info about the post.
	 */
	public function sa_exclude_post_check_content( $post ) {
		$checked = $this->excluded_from_post( $post->ID ) ? 'checked' : '';
		wp_nonce_field( 'sa_exclude_post_save', 'exclusion_nonce' );
		echo '<input type="checkbox" name="sa_exclude_post_id" value="' . esc_html( $post->ID ) . '"' . esc_html( $checked ) . '> Do not insert a SmartAsset Widget.';
	}

	/**
	 * Defines the HTML content for the page exclusion checkbox.
	 *
	 * @param array $page Info about the page.
	 */
	public function sa_exclude_page_check_content( $page ) {
		$checked = $this->excluded_from_post( $page->ID ) ? 'checked' : '';
		wp_nonce_field( 'sa_exclude_post_save', 'exclusion_nonce' );
		echo '<input type="checkbox" name="sa_exclude_post_id" value="' . esc_html( $page->ID ) . '"' . esc_html( $checked ) . '> Do not insert a SmartAsset Widget.';
	}

	/**
	 * Saves the exclusion from the meta box options on a post creation page.
	 * Called when the post is saved.
	 *
	 * @param string $post_id Post id being saved.
	 */
	public function sa_exclude_post_save( $post_id ) {
		/*
		 * WordPress can auto save posts before a user has submitted the form.
		 * In this case, we don't want to save any exclusions options.
		 */
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if (
			isset( $_POST['exclusion_nonce'], $_POST['sa_exclude_post_id'] )
			&& wp_verify_nonce( sanitize_key( $_POST['exclusion_nonce'] ), 'sa_exclude_post_save' )
			) {
			// Add this post to the list of exclusions.
			if ( ! $this->excluded_from_post( $post_id ) ) {
				$exclusions = trim( get_option( 'sa_exclusions' ) . ' ' . $post_id );
				update_option( 'sa_exclusions', $exclusions );
			}
		} else {

			// Remove this post from the list of exclusions.
			if ( $this->excluded_from_post( $post_id ) ) {
				$pattern = '/[, ]/';
				$exclude = array_map( 'intval', preg_split( $pattern, get_option( 'sa_exclusions' ) ) );
				$exclusions = array_diff( $exclude, array( $post_id ) );
				update_option( 'sa_exclusions', implode( ' ', $exclusions ) );
			}
		}
	}

	/**
	 * Adds a quicktags button to the text editor for the Captivate shortcode.
	 *
	 * Only adds buttons for edit pages in an enabled section. I.E. if the "single post" section
	 * is checked and the "single post" sections is not, the button will appear when editing a post
	 * and will not appear when editing a page.
	 *
	 * Called when loading the edit page.
	 */
	public function sa_shortcode_button_script() {
		if ( wp_script_is( 'quicktags' ) ) {

			// get enabled sections
			$enabled_sections = get_option( 'sa_section' );
			if ( '' === $enabled_sections || ! is_array( $enabled_sections ) ) {
				$enabled_sections = array();
			}

			// get the post that we are editing
			global $post;
			$post_type = $post->post_type;

			// The section names for the post type 'post' and 'page' are 'single_post' and 'single_page'
			// Prepend 'single_' to these post types so that it will match the saved enabled section.
			if ( 'post' === $post_type || 'page' === $post_type ) {
				$post_type = 'single_' . $post_type;
			}

			// Check if the post type is in the enabled sections.
			$show_button = in_array( $post_type, $enabled_sections, true );

			/**
			 * The front page and blog page have the post type 'page'.
			 * If the post being edited is of type 'page' we should additionally check if it is the front page or blog page.
			 * We do this by comparing the id with the saved id of the front page and blog page.
			 *
			 * If the page being edited is found to be one of the front page or blog page
			 * (as determined by post id) we should check if those sections are enabled.
			 */
			if ( 'single_page' === $post_type ) {
				$front_page_id = get_option( 'page_on_front' );
				if ( $post->ID == $front_page_id ) {
					// the page being edited is the front page
					$show_button = in_array( 'front_page', $enabled_sections, true );
				}
				$blog_page_id = get_option( 'page_for_posts' );
				if ( $post->ID == $blog_page_id ) {
					// the page being edited is the blog page
					$show_button = in_array( 'blog_page', $enabled_sections, true );
				}
			}

			if ( $show_button ) {
				echo "
					<script type=\"text/javascript\">
						QTags.addButton( 'code_shortcode', 'SA Captivate', '[sa_captivate]' );
					</script>
				";
			}
		}// End if().
	}

	/**
	 * Checks if the given post id is in the exclusions
	 *
	 * @param string $id The Post ID.
	 * @return boolean Presence of the post id in exclusions.
	 */
	public function excluded_from_post( $id ) {
		$pattern = '/[, ]/';
		$exclude = array_map( 'intval', preg_split( $pattern, get_option( 'sa_exclusions' ) ) );

		return in_array( $id, $exclude );
	}

	/**
	 * Sets the content filter hook.
	 */
	public function set_content_filters() {
		// Add filter hook for inserting widget automatically, with priority 1 ( highest ).
		// Called when the content is retrieved from the database.
		add_filter( 'the_content', array( $this, 'insert_sa_captivate_widget' ), 1 );
	}

	/**
	 * Validates that the api key is 32 digits and alphanumeric
	 *
	 * @param string $api_key The api key to validate.
	 * @return boolean Is the api key valid.
	 */
	public function validate_key( $api_key ) {
		return preg_match( '/^[a-z0-9]{32}$/i', trim( $api_key ) );
	}

	/**
	 * Handler for the shortcode.
	 *
	 * @param array $atts Shortcode attribute array.
	 * @return string The html code to insert.
	 */
	public function handle_sa_captivate_shortcode( $atts ) {
		return $this->get_sa_captivate_snippet( $atts, true );
	}

	/**
	 * Get the SACaptivateSnippet with pre/post html.  Will return blank if widgets are not allowed in this section.
	 *
	 * @param array   $atts Shortcode attribute array.
	 * @param boolean $manual_placement Optional. Defaults to false.
	 * @return string Captivate markup with pre/post html or empty string.
	 */
	public function get_sa_captivate_snippet( $atts, $manual_placement = false ) {
		$pre_html = '';
		$post_html = '';
		$advanced = get_option( 'sa_advanced' );

		if ( $this->load_widget_in_section() ) {
			if ( $manual_placement ) {
				// Use manual pre/post HTML.
				$pre_html = $advanced['mid']['pre'];
				$post_html = $advanced['mid']['post'];
			} else {
				// Use position based pre/post HTML.
				$position = get_option( 'sa_placement' );
				$position = $position['position'];
				if ( 'top' === $position ) {
					$pre_html = $advanced['top']['pre'];
					$post_html = $advanced['top']['post'];
				}
				if ( 'bottom' === $position ) {
					$pre_html = $advanced['bot']['pre'];
					$post_html = $advanced['bot']['post'];
				}
			}

			return
				$pre_html
				. $this->construct_sa_captivate_snippet( $atts )
				. $post_html;
		} else {
			return '';
		}
	}

	/**
	 * Set the configuration defaults if they are not found in the database.
	 *
	 * Values to set:
	 * Position- bottom by default
	 * Categories- all checked by default
	 * Sections- Disable Front Page, Blog, Single Page. Enable Single Post and custom post types if applicable
	 * Allow Auto- true by default (for backward compatibility)
	 */
	public function set_config_defaults() {
		$opts = array();

		// If the placement is not set in the database, set it to 'bottom' by default.
		$placement = get_option( 'sa_placement' );
		if ( empty( $placement ) ) {
			$opts['sa_placement']['position'] = 'bottom';
		}

		// If allow_auto is not set it should default to true.
		$allow_auto = get_option( 'sa_allow_auto' );
		if ( empty( $allow_auto ) ) {
			$opts['sa_allow_auto'] = 'true';
		}

		$section = get_option( 'sa_section' );

		if ( empty( $section ) ) {
			// No current section settings, default to single_post and custom post types.
			$sections = array( 'single_post' );

			$custom_post_types = get_post_types( array(
				'_builtin' => false,
			) );
			foreach ( $custom_post_types as $custom_section ) {
				array_push( $sections, $custom_section );
			}

			$opts['sa_section'] = $sections;
		} else {
			/*
			 * Previous versions of the plugin used different names for sections.
			 * In order to preserve settings when a user upgrades from an old
			 * plugin version, we need to map these to the new plugin section names.
			 */
			$new_sections = array();

			if ( in_array( 'home', $section ) ) {
				array_push( $new_sections, 'blog_page' );
			}
			if ( in_array( 'single', $section ) ) {
				array_push( $new_sections, 'single_page' );
				array_push( $new_sections, 'single_post' );

				// Also add any custom post types.
				$custom_post_types = get_post_types( array(
					'_builtin' => false,
				) );
				foreach ( $custom_post_types as $custom_section ) {
					array_push( $new_sections, $custom_section );
				}
			}
			if ( in_array( 'static', $section ) ) {
				array_push( $new_sections, 'front_page' );
			}
			if ( in_array( 'blog', $section ) ) {
				// 'blog_page' may have already been mapped.
				if ( ! in_array( 'blog_page', $new_sections ) ) {
					array_push( $new_sections, 'blog_page' );
				}
			}

			// If older versions have been mapped, update the setting to the new values.
			if ( ! empty( $new_sections ) ) {
				$opts['sa_section'] = $new_sections;
			}
		}// End if().

		$ex_categories = get_option( 'sa_ex_categories' );
		if ( empty( $ex_categories ) ) {
			$opts['sa_ex_categories'] = array();
		}

		$advanced = get_option( 'sa_advanced' );
		if ( empty( $advanced ) ) {
			$opts['sa_advanced']['top'] = array(
				'pre' => '',
				'post' => '',
			);
			$opts['sa_advanced']['mid'] = array(
				'pre' => '',
				'post' => '',
			);
			$opts['sa_advanced']['bot'] = array(
				'pre' => '',
				'post' => '',
			);
		}

		// Update the database with each of the options that have been changed.
		foreach ( $opts as $k => $v ) {
			update_option( $k, $v );
		}
	}

	/**
	 * Method to insert the captivate shortcode only if there are no other shortcodes present in the post
	 * Checks the categories of the current post and compares it against the selected categories
	 * If there is a match, places the widget using place_sa_captivate_widget function.
	 *
	 * @param string $content The HTML content of the post/page.
	 * @return string The HTML content of the page/post with captivate widget if applicable.
	 */
	public function insert_sa_captivate_widget( $content ) {
		global $post;

		// Make sure this post is not in exclusions.
		if ( ! $this->excluded_from_post( $post->ID ) ) {
			// Make sure this content has no shortcode present.
			// Shortcodes are either [SA_SHORTCODE] or [SA_SHORTCODE SA_TYPE_PRE"sometype"].
			if ( ! preg_match( '/(\[' . SA_SHORTCODE . '( ' . SA_TYPE_PRE . '"[a-z]*")?\])/', $content ) ) {

				// Only insert widget if auto placement is allowed.
				// If allow_auto is not set it should default to true.
				$allow_auto = get_option( 'sa_allow_auto', 'true' );

				if ( 'true' === $allow_auto ) {
					$ex_cats = get_option( 'sa_ex_categories' );
					$ex_categories = $ex_cats ? $ex_cats : array();
					$post_categories = get_the_category( $post->ID );

					// Add all parent categories.
					foreach ( $ex_categories as $cat ) {
						$arg = array(
							'child_of' => $cat,
						);
						foreach ( get_categories( $arg ) as $c ) {
							$ex_categories[] = $c->cat_ID;
						}
					}

					// Simplify the post categories data so it matches what is stored in the db.
					$simple_post_categories = array();
					foreach ( $post_categories as $cat ) {
						$simple_post_categories[ $cat->slug ] = $cat->cat_ID;
					}

					// Matches contains the excluded categories.
					$matches = array_intersect( $ex_categories, $simple_post_categories );

					// Place the widget only if there are no matches to excluded categories.
					// Empty() only supports variables prior to PHP 5.5, anything else will error!
					if ( empty( $matches ) ) {
						// Categories match! Insert the widget.
						$opts = get_option( 'sa_placement' );

						return $this->place_sa_captivate_widget( $content, $opts['position'] );
					} else {
						return $content;
					}
				}
			}// End if().
		}// End if().

		return $content;
	}

	/**
	 * Place the widget on the page according to config settings ( top or bottom ).
	 *
	 * @param string $content The HTML content of the post/page.
	 * @param string $placement The position where the widget should be placed in the content. Value is 'top' or 'bottom'.
	 * @return string The HTML content of the post/page with added captivate widget if applicable.
	 */
	public function place_sa_captivate_widget( $content, $placement ) {
		// Use the placement to put the widget accordingly.
		$the_code = $this->get_sa_captivate_snippet( array() );

		if ( 'top' === $placement ) {
			return $the_code . $content;
		} elseif ( 'bottom' === $placement ) {
			return $content . $the_code;
		}

		return $content;
	}

	/**
	 * Handler for the shortcode, constructs the code snippet.
	 *
	 * @param array $atts Shortcode attribute array.
	 * @return string The HTML content of the post/page.
	 */
	public function construct_sa_captivate_snippet( $atts ) {
		// Creates variables named $key and $type containing the value from shortcode_atts.
		$atts = shortcode_atts( array(
			'key' => get_option( 'sa_key' ),
			'type' => false,
		), $atts );

		$key = $atts['key'];
		$type = $atts['type'];

		return $this->get_sa_widget_snippet( $key, $type );
	}

	/**
	 * Generates the js script function for the widget based on the random id, the api key and the section ( optional ).
	 *
	 * @param int            $rand Random int.
	 * @param string         $key Api key.
	 * @param string|boolean $section Optional. Specifies which captivate widget should appear.
	 * @param string         $src Optional. Source url.
	 * @return string The anonymous js function ( plain js )
	 */
	public function get_sa_widget_script( $rand, $key, $section = false, $src = false ) {
		global $wp_version;

		$src = ( ( $src && ! $section ) ? 'src: "' . $src . '",' : '' );
		$section = ( $section ? 'endpoint: "/embed/' . $section . '"' : '' );

		return
		'( function () {' .
				'var SA = window["SA"] || [];' .
				'SA.push( {' .
					'version: 1.1,' .
					// 'embedUrl: "https://staging2.smartasset.com",'.
					'container: "#sa-captivate-' . wp_json_encode( $rand ) . '",' .
					esc_js( $src ) .
					'data:' .
					'{' .
						'key: ' . wp_json_encode( $key ) . ',' .
						'pluginVer:' . wp_json_encode( $this->get_plugin_ver() ) . ',' .
						'wpVer:' . wp_json_encode( $wp_version ) . ',' .
						esc_js( $section ) .
					'},' .
					'events:' .
					'{' .
						'on_nowidget: function()' .
						'{' .
							'document.getElementById( "sa-captivate-' . wp_json_encode( $rand ) . '" ).style.display="none";' .
							'document.getElementById( "sa-captivate-logo-' . wp_json_encode( $rand ) . '" ).style.display="none";' .
						'}' .
					'}' .
				'} );' .
				'window["SA"] = SA;' .
				'var smscript = document.createElement( "script" );' .
				'smscript.type = "text/javascript"; smscript.async = true; smscript.src = "//smartasset.com/snippet.js";' .
				'var s = document.getElementsByTagName( "script" )[0]; s.parentNode.insertBefore( smscript, s );' .
		'} )()';
	}

	/**
	 * Generate the widget html snippet from the a key and a section ( optional ).
	 *
	 * @param string         $key The Api key.
	 * @param string|boolean $section Optional. Specifies which captivate widget should appear.
	 * @return string The widget html snippet.
	 */
	public function get_sa_widget_snippet( $key, $section = false ) {
		global $post;
		$rand = mt_rand();
		$this_url = get_permalink( $post->ID );

		return
			'<div id="sa-captivate-' . wp_json_encode( $rand ) . '" class="sa-captivate-box"></div>
			<div id="sa-captivate-script-' . wp_json_encode( $rand ) . '" style="display:none">
				<img src="https://s3.amazonaws.com/sa-pub/clear.png" title="sa-captivate-placeholder" onload=\'' . esc_js( $this->get_sa_widget_script( $rand, $key, $section, $this_url ) ) . '\' alt="sa-captivate-placeholder"/>
			</div>';
	}

	/**
	 * Returns the location of the current page.
	 * For definitions of each condition see: https://codex.wordpress.org/Conditional_Tags
	 * under section"The Conditions For ...".
	 *
	 * @return the location of the page
	 */
	public function get_page_desc() {
		if ( is_home() ) {
			// Page showing a list of posts.
			return 'blog_page';
		} elseif ( is_front_page() ) {
			// Static front page as configured by reading settings.
			return 'front_page';
		}

		// Check for custom post type.
		// is_single() returns true for custom posts, so check for custom posts first.
		$custom_post_types = get_post_types( array(
			'_builtin' => false,
		) );
		foreach ( $custom_post_types as $post_type ) {
			if ( is_singular( $post_type ) ) {
				return $post_type;
			}
		}

		if ( is_single() ) {
			// Single post.
			return 'single_post';
		} elseif ( is_page() ) {
			// Single page.
			return 'single_page';
		} else {
			// Not in any classified sections, do not place widget.
			return 'other';
		}
	}

	/**
	 * Used to check if the widgets are allowed in this section.
	 *
	 * @return bool If the widget is allowed in the section.
	 */
	public function load_widget_in_section() {
		$section = get_option( 'sa_section' );
		if ( '' === $section || ! is_array( $section ) ) {
			$section = array();
		}

		return is_array( $section ) && in_array( $this->get_page_desc(), $section, true );
	}
}

// Start the plugin...
new SmartAssetWidgets();
