<?php
/**
 * Plugin Name: Elementor ACF Repeater
 * Description: Allows ACF repeater field values to be used in Elementor via Dynamic Tags.
 * Version:     1.0.0
 * Author:      Justin Kucerak
 * Text Domain: elementor-acf-repeater
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once __DIR__ . '/classes/acf-repeater-metabox.php';

/**
 * Responsible for
 */
class Elementor_ACF_Repeater {
	/**
	 * Plugin Version
	 *
	 * @since 1.0.0
	 *
	 * @var string The plugin version.
	 */
	const VERSION = '1.0.0';

	/**
	 * Minimum Elementor Version
	 *
	 * @since 1.0.0
	 *
	 * @var string Minimum Elementor version required to run the plugin.
	 */
	const MINIMUM_ELEMENTOR_VERSION = '2.0.0';

	/**
	 * Minimum PHP Version
	 *
	 * @since 1.0.0
	 *
	 * @var string Minimum PHP version required to run the plugin.
	 */
	const MINIMUM_PHP_VERSION = '5.6';

	/**
	 * Instance
	 *
	 * @since 1.0.0
	 *
	 * @access private
	 * @static
	 *
	 * @var Elementor_ACF_Repeater The single instance of the class.
	 */
	private static $_instance = null;

	/**
	 * Instance
	 *
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 * @static
	 *
	 * @return Elementor_ACF_Repeater An instance of the class.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;

	}

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'i18n' ] );
		add_action( 'plugins_loaded', [ $this, 'init' ] );
	}

	/**
	 * Load Textdomain
	 *
	 * Load plugin localization files.
	 *
	 * Fired by `init` action hook.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function i18n() {
		load_plugin_textdomain( 'elementor-test-extension' );
	}

	/**
	 * Initialize the plugin
	 *
	 * Load the plugin only after Elementor (and other plugins) are loaded.
	 * Checks for basic plugin requirements, if one check fail don't continue,
	 * if all check have passed load the files required to run the plugin.
	 * Define additional WP hooks.
	 *
	 * Fired by `plugins_loaded` action hook.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function init() {
		// Check if Elementor installed and activated.
		if ( ! did_action( 'elementor/loaded' ) ) {
			add_action( 'admin_notices', [ $this, 'admin_notice_missing_main_plugin' ] );
			return;
		}

		// Check for required Elementor version.
		if ( ! version_compare( ELEMENTOR_VERSION, self::MINIMUM_ELEMENTOR_VERSION, '>=' ) ) {
			add_action( 'admin_notices', [ $this, 'admin_notice_minimum_elementor_version' ] );
			return;
		}

		// Check for required PHP version.
		if ( version_compare( PHP_VERSION, self::MINIMUM_PHP_VERSION, '<' ) ) {
			add_action( 'admin_notices', [ $this, 'admin_notice_minimum_php_version' ] );
			return;
		}

		// Initialize the plugin metabox.
		Elementor_ACF_Repeater_Metabox::init();

		// Register widget(s).
		add_action( 'elementor/widgets/widgets_registered', [ $this, 'init_widgets' ] );

		// Modify Elementor Section behavrior.
		add_action( 'elementor/element/before_section_end', [ $this, 'modify_section_controls' ], 10, 3 );
		add_action( 'elementor/frontend/before_render', [ $this, 'modify_section_render' ], 10, 1 );

		// Add in new Dynamic Tags.
		add_action(
			'elementor/dynamic_tags/register_tags',
			function( $dynamic_tags ) {
				// Register each tag class.
				foreach ( ACF_Repeater_Module::get_tag_classes_names() as $class ) {
					// Modify class name to match file name structure.
					$class_file = strtolower( str_replace( '_', '-', $class ) );
					// Include tag class file and register.
					include_once 'tags/' . $class_file . '.php';
					$dynamic_tags->register_tag( $class );
				}
			}
		);

		// Setup method to populate widget dropdown control.
		add_filter( 'elementor_pro/query_control/get_autocomplete/library_widget_section_templates', [ $this, 'get_autocomplete_for_acf_repeater_widget' ], 10, 2 );
		add_filter( 'elementor_pro/query_control/get_value_titles/library_widget_section_templates', [ $this, 'get_value_title_for_acf_repeater_widget' ], 10, 2 );
	}

	/**
	 * Init Widgets
	 *
	 * Include widgets files and register them
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function init_widgets() {
		// include Widget files.
		require_once __DIR__ . '/widgets/elementor-acf-repeater-widget.php';

		// Register widget.
		\Elementor\Plugin::instance()->widgets_manager->register_widget_type( new \Elementor_ACF_Repeater_Widget() );
	}
}
