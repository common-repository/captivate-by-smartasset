<?php

/**
 * SmartAsset Widgets Admin class.
 * Defines the admin content in the SmartAsset Captivate Admin Settings page.
 */
class SmartAssetWidgetsAdmin {
	/**
	 * Hooks the function creating the SmartAsset admin page on the admin_menu action.
	 * Hooks the function creating the content of the admin page to the admin_init action.
	 */
	function __construct() {

		add_action( 'admin_menu', array( $this, 'sa_plugin_admin_add_page' ) );
		add_action( 'admin_init', array( $this, 'sa_admin_init' ) );
	}

	/**
	 * Function adding the SA Settings page as a sub page of the admin settings menu.
	 */
	function sa_plugin_admin_add_page() {

		add_options_page( 'SmartAsset Plugin Page', 'SmartAsset Captivate', 'manage_options', 'smartasset_plugin', array( $this, 'sa_plugin_options_page' ) );
	}

	/**
	 * Add header, form, and save button to admin settings page.
	 */
	function sa_plugin_options_page() {

		echo '<style>.form-table{border-bottom: 1px solid lightgray;}</style>';
		echo '<div><h2>SmartAsset Captivate -- Plugin Settings</h2><form action="options.php" method="post">';
		$this->sa_admin_fields();

		echo '<p></p><input class="button button-primary" name="Submit" type="submit" value="Save Changes" /></form></div>';
	}

	/**
	 * Set the setting group name and print out all setting sections.
	 */
	function sa_admin_fields() {

		// Set the settings group name. Must be done inside of a 'form' tag.
		settings_fields( 'sa_widget_options' );
		// Print out all setting sections added to this settings page.
		do_settings_sections( 'smartasset_plugin' );
	}

	/**
	 * Register settings and define HTML for each.
	 */
	function sa_admin_init() {

		// Register api key setting and define setting page HTML.
		register_setting( 'sa_widget_options', 'sa_key', array( $this, 'sa_key_validator' ) );
		add_settings_section( 'smartasset_plugin_main', 'Main Settings', array( $this, 'sa_key_text' ), 'smartasset_plugin' );
		add_settings_field( 'sa_captivate_key', 'API Key', array( $this, 'sa_key_input' ), 'smartasset_plugin', 'smartasset_plugin_main' );

		// Register placement and allow auto settings and define setting page HTML.
		register_setting( 'sa_widget_options', 'sa_placement' );
		register_setting( 'sa_widget_options', 'sa_allow_auto' );
		add_settings_section( 'smartasset_plugin_placement', 'Placement', array( $this, 'sa_placement_text' ), 'smartasset_plugin' );
		add_settings_field( 'sa_captivate_allow_auto_placement', 'Allow Automatic Placement', array( $this, 'sa_placement_allow_auto_input' ), 'smartasset_plugin', 'smartasset_plugin_placement' );
		add_settings_field( 'sa_captivate_placement', 'Automatic Placement Location (if applicable)', array( $this, 'sa_placement_input' ), 'smartasset_plugin', 'smartasset_plugin_placement' );
		add_settings_field( 'sa_captivate_manual', 'Manual Placement', array( $this, 'sa_manual_input' ), 'smartasset_plugin', 'smartasset_plugin_placement' );

		// Register section setting and define setting page HTML.
		register_setting( 'sa_widget_options', 'sa_section' );
		add_settings_section( 'smartasset_plugin_section', 'Sections', array( $this, 'sa_section_text' ), 'smartasset_plugin' );
		add_settings_field( 'sa_captivate_section', 'Sections', array( $this, 'sa_section_input' ), 'smartasset_plugin', 'smartasset_plugin_section' );

		// Register category exclusions setting and define setting page HTML.
		register_setting( 'sa_widget_options', 'sa_ex_categories', array( $this, 'sa_categories_validator' ) );
		add_settings_section( 'smartasset_plugin_categories', 'Categories', array( $this, 'sa_categories_text' ), 'smartasset_plugin' );
		add_settings_field( 'sa_captivate_categories', 'Categories', array( $this, 'sa_categories_input' ), 'smartasset_plugin', 'smartasset_plugin_categories' );

		// Register exclusions setting and define setting page HTML.
		register_setting( 'sa_widget_options', 'sa_exclusions', array( $this, 'sa_exclusions_validator' ) );
		add_settings_section( 'smartasset_plugin_exclusions', 'Exclusions', array( $this, 'sa_exclusions_text' ), 'smartasset_plugin' );
		add_settings_field( 'sa_captivate_exclusions', 'Post IDs', array( $this, 'sa_exclusions_input' ), 'smartasset_plugin', 'smartasset_plugin_exclusions' );

		// Register advanced setting and define setting page HTML.
		register_setting( 'sa_widget_options', 'sa_advanced' );
		add_settings_section( 'smartasset_plugin_advanced', 'Advanced', array( $this, 'sa_advanced_text' ), 'smartasset_plugin' );
		add_settings_field( 'sa_captivate_advanced_top', 'Top Widget Placement', array( $this, 'sa_advanced_input_top' ), 'smartasset_plugin', 'smartasset_plugin_advanced' );
		add_settings_field( 'sa_captivate_advanced_mid', 'Manual Widget Placement', array( $this, 'sa_advanced_input_mid' ), 'smartasset_plugin', 'smartasset_plugin_advanced' );
		add_settings_field( 'sa_captivate_advanced_bot', 'Bottom Widget Placement', array( $this, 'sa_advanced_input_bot' ), 'smartasset_plugin', 'smartasset_plugin_advanced' );
	}

	/**
	 * HTML to be displayed for a discription of the "Advanced" section.
	 */
	function sa_advanced_text() {

		echo '<p>These advanced configurations are meant to allow developers to modify the post contents surrounding the widget, using plain HTML.<br><b>Please use caution when utilizing this feature, it is not required for most users.</b></p>';
		echo "<p>The HTML code added in the boxes below will be inserted before and after the widget code.<br>Please note that the HTML code may vary depending on the widget's placement.</p>";
	}

	/**
	 * HTML to be displayed for "Top Widget Placement"
	 */
	function sa_advanced_input_top() {

		$options = get_option( 'sa_advanced' );
		echo "<p>HTML code to insert before the widget:<br><textarea id='sa_advanced_top_pre' rows='5' cols='50' type='text' name='sa_advanced[top][pre]' value='" . esc_attr( $options['top']['pre'] ) . "'>" . esc_attr( $options['top']['pre'] ) . '</textarea></p>'
			. "<p>HTML code to insert after the widget:<br><textarea id='sa_advanced_top_post' rows='5' cols='50' type='text' name='sa_advanced[top][post]' value='" . esc_attr( $options['top']['post'] ) . "'>" . esc_attr( $options['top']['post'] ) . '</textarea></p>';
	}

	/**
	 * HTML to be displayed for "Manual Widget Placement"
	 */
	function sa_advanced_input_mid() {

		$options = get_option( 'sa_advanced' );
		echo "<p>HTML code to insert before the widget:<br><textarea id='sa_advanced_mid_pre' rows='5' cols='50' type='text' name='sa_advanced[mid][pre]' value='" . esc_attr( $options['mid']['pre'] ) . "'>" . esc_attr( $options['mid']['pre'] ) . '</textarea></p>'
			. "<p>HTML code to insert after the widget:<br><textarea id='sa_advanced_mid_post' rows='5' cols='50' type='text' name='sa_advanced[mid][post]' value='" . esc_attr( $options['mid']['post'] ) . "'>" . esc_attr( $options['mid']['post'] ) . '</textarea></p>';
	}

	/**
	 * HTML to be displayed for "Bottom Widget Placement"
	 */
	function sa_advanced_input_bot() {

		$options = get_option( 'sa_advanced' );
		echo "<p>HTML code to insert before the widget:<br><textarea id='sa_advanced_bot_pre' rows='5' cols='50' type='text' name='sa_advanced[bot][pre]' value='" . esc_attr( $options['bot']['pre'] ) . "'>" . esc_attr( $options['bot']['pre'] ) . '</textarea></p>'
			. "<p>HTML code to insert after the widget:<br><textarea id='sa_advanced_bot_post' rows='5' cols='50' type='text' name='sa_advanced[bot][post]' value='" . esc_attr( $options['bot']['post'] ) . "'>" . esc_attr( $options['bot']['post'] ) . '</textarea></p>';
	}

	/**
	 * Description for the Main Settings.
	 */
	function sa_key_text() {

		echo '<p>The API key is required for the plugin to work.<br>If you need any assistance please contact us at <a href="mailto:captivate@smartasset.com">captivate@smartasset.com</a>.</p>';
	}

	/**
	 * Input HTML for API key
	 */
	function sa_key_input() {

		$options = get_option( 'sa_key' );
		echo "<input id='sa_captivate_key' name='sa_key' size='40' type='text' value='" . esc_attr( $options ) . "' />";
	}

	/**
	 * Validates an api key
	 *
	 * @param string $input Value from API key text input element.
	 * @return string The entered key or "Invalid Key" if the key is not valid.
	 */
	function sa_key_validator( $input ) {

		$key = trim( $input );

		// Key should be 32 characters long and alphanumeric.
		if ( ! preg_match( '/^[a-z0-9]{32}$/i', $key ) ) {
			$key = 'Invalid Key';
		}

		return $key;
	}

	/**
	 * Description HTML for the Placement setting section.
	 */
	function sa_placement_text() {

		echo '<p>If automatic placement is allowed, this plugin will automatically insert the SmartAsset Captivate widgets into blog posts that match the selected sections and categories.</p>';
		echo '<p>Please choose to allow automatic placement or not. If automatic placement is not chosen, the widgets can be placed via manual shortcodes (see Manual Placement section for details). ';
		echo '<p>If using automatic placement, please choose the widget placement location: at the top of the post (before the contents) or at the bottom of the post (after the contents). </p>';
	}

	/**
	 * Input HTML for automatic placement radio buttons.
	 */
	function sa_placement_allow_auto_input() {
		$options = get_option( 'sa_allow_auto' );
		echo
		'<input type="radio" name="sa_allow_auto" value="true"' . esc_html( 'true' === $options ?'checked':'' ) . '> Yes &nbsp&nbsp' .
			'<input type="radio" name="sa_allow_auto" value="false"' . esc_html( 'false' === $options ?'checked':'' ) . '> No';
	}

	/**
	 * Dropdown HTML for Automatic Placement Location setting.
	 */
	function sa_placement_input() {

		$options = get_option( 'sa_placement' );
		// PHP 5.3 compat.
		$options = $options['position'];
		$placement = array(
			'top' => 'Top of Post',
			'bottom' => 'Bottom of Post',
		);
		echo "<select id='sa_captivate_placement' name='sa_placement[position]'>";
		foreach ( $placement as $option => $value ) {
			echo "<option value='" . esc_html( $option ) . "'" . esc_html( $options === $option?' selected':'' ) . '>' . esc_html( $value ) . '</option>';
		}
		echo '</select>';
	}

	/**
	 * Description text for manual placement.
	 */
	function sa_manual_input() {

		echo '<p>To insert a widget anywhere in your posts include the following <b>shortcode</b> in the contents: <br><b>[' . esc_html( SA_SHORTCODE ) . ']</b></p>';
	}

	/**
	 * Description text for Sections.
	 */
	function sa_section_text() {

		echo '<p>Please choose the sections in which you want the widgets to appear.  Unchecking the box will cause the automatically and manually placed widgets to be removed from the entire section.</p>';
	}

	/**
	 * HTML for Sections options.
	 */
	function sa_section_input() {

		$options = get_option( 'sa_section' );

		$sections = array(
			'front_page' => array( 'Front Page', 'The Front Page is set under Reading Settings -> Front page displays. Please note not all blogs have a Front Page set.' ),
			'blog_page' => array( 'Blog Page', 'A page listing the most recent blog posts with a summary or the full text of each. This refers to either the Main page when “Your latest posts” is selected under Reading Settings -> Front page displays or the designated “Posts page” on the same settings page.' ),
			'single_post' => array( 'Single Post', 'An individual post.' ),
			'single_page' => array( 'Single Page', 'An individual page (excluding the Front Page and Blog Page as those are controlled via separate settings above).' ),
		);

		// Add any custom post types.
		$custom_post_types = get_post_types( array(
			'_builtin' => false,
		) );
		foreach ( $custom_post_types as $custom_post_type ) {
			// Custom post types should be snake_case. Replace any underscores with spaces and capitalize words before displaying on settings page.
			$custom_section_name = ucwords( str_replace( '_', ' ', $custom_post_type ) );
			$sections[ $custom_post_type ] = array( $custom_section_name, 'A custom post type.' );
		}

		echo '<span id="sa-all-cats" style="color:#0074a2; padding: 5px 5px 0px 10px; cursor:pointer;font-size:10px" onclick="jQuery(\'input[name^=sa_section]\').prop(\'checked\', true);">Check All</span>
		<span style="font-size:10px">|</span>
		<span id="sa-no-cats" style="color:#0074a2; padding: 5px 0px 0px 5px;cursor:pointer;font-size:10px" onclick="jQuery(\'input[name^=sa_section]\').prop(\'checked\', false);">Uncheck All</span>
		<table>';

		foreach ( $sections as $section_key => $section_name ) {
			$checked = ( is_array( $options ) && in_array( $section_key, $options )?'checked':'');
			echo
				'<tr>' .
					'<td style="padding: 15px 10px 5px 10px"><input type="checkbox" name="sa_section[' . esc_html( $section_key ) . ']" value="' . esc_html( $section_key ) . '" ' . esc_html( $checked ) . '></td>' .
					'<td style="padding: 15px 10px 5px 10px">' . esc_html( $section_name[0] ) . '</td>' .
				'</tr>' .
				'<tr><td></td><td style="padding:0 0 15px 10px"><i style="font-size:12px">' . esc_html( $section_name[1] ) . '</i></td></tr>';
		}
		echo '</table>';
	}

	/**
	 * Description text for Categories section.
	 */
	function sa_categories_text() {

		echo '<p>This list contains all the categories that have at least 1 post associated with them, as shown in the number next to each category. Please choose the categories in which the widget will be automatically inserted.<br> '
		. 'The widget will only be placed if the post belongs in one of the selected categories, including any of its children categories.<br>'
		. '<b>This setting does not apply to shortcodes (i.e. manual placement)</b>.</p>';
	}

	/**
	 * Input HTML for Categories section.
	 */
	function sa_categories_input() {

		$options = get_option( 'sa_ex_categories' );

		$args = array(
			'orderby' => 'name',
			'order' => 'ASC',
			'parent' => 0,
		);
		$categories = get_categories( $args );

		echo '<span id="sa-all-cats" style="color:#0074a2; padding: 5px 5px 0px 10px; cursor:pointer;font-size:10px" onclick="jQuery(\'input[name^=sa_ex_categories]\').prop(\'checked\', true);">Check All</span>
		<span style="font-size:10px">|</span>
		<span id="sa-no-cats" style="color:#0074a2; padding: 5px 0px 0px 5px;cursor:pointer;font-size:10px" onclick="jQuery(\'input[name^=sa_ex_categories]\').prop(\'checked\', false);">Uncheck All</span>
		<table>';

		foreach ( $categories as $category ) {
			$checked = ( ! isset( $options[ $category->slug ] )?'checked':'');
			echo
				'<tr>' .
					'<td><input type="checkbox" name="sa_ex_categories[' . esc_html( $category->slug ) . ']" value="' . esc_html( $category->cat_ID ) . '" ' . esc_html( $checked ) . '></td>' .
					'<td>' . esc_html( $category->name ) . ' (' . esc_html( $category->count ) . ')</td>' .
				'</tr>';
		}
		echo '</table>';
	}

	/**
	 * Gets category checkbox values from input and uses inverse of values to create an array of excluded categories.
	 *
	 * The sa_ex_categories options is essentially a "blacklist".
	 * Instead of saving the categories in which the user would like to see captivate widgets,
	 * we save the categories that the user has not selected in the database.
	 * This way, when a new category is created, it will be eligible for captivate widgets by default.
	 *
	 * @param array $input The form data.
	 * @return array Associative array mapping category slugs to category ids.
	 */
	function sa_categories_validator( $input ) {

		$args = array(
			'orderby' => 'name',
			'order' => 'ASC',
			'parent' => 0,
		);
		$categories = get_categories( $args );

		$excluded_categories = array();

		foreach ( $categories as $category ) {
			if ( ! in_array( $category->slug, array_keys( $input ) ) ) {
				// If the category is not in the input values, add it to the excluded categories array.
				$excluded_categories[ $category->slug ] = $category->cat_ID;
			}
		}

		return $excluded_categories;
	}

	/**
	 * HTML for exclusions section input
	 */
	function sa_exclusions_input() {

		$exclusions = get_option( 'sa_exclusions' );
		echo '<textarea id="sa_captivate_exclusions" name="sa_exclusions" rows="5" cols="50" type="text" value="$exclusions">' . esc_html( $exclusions ) . '</textarea>';
	}

	/**
	 * Description for Exclusions section.
	 */
	function sa_exclusions_text() {

		echo '<p>Exclude SmartAsset Captivate widgets on specific posts.  Please enter the post IDs separated by commas or spaces.<br>'
		. '<b>This setting does not apply to shortcodes (i.e. manual placement)</b>.</p>';
	}

	/**
	 * Strips any non-numeric data from exclusions.
	 *
	 * @param string $input Value from the exclusions text input.
	 * @return string $input with non-numeric characters removed
	 */
	function sa_exclusions_validator( $input ) {

		return preg_replace( '/[^0-9, ]/', '', $input );
	}
}
