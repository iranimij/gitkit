<?php
/**
 *
 * @link              http://www.iranimij.com
 * @since             1.0.0
 * @package           Gitkit
 *
 * @wordpress-plugin
 * Plugin Name:       GitKit
 * Plugin URI:        http://www.iranimij.com
 * Description:       Upload or update your plugins with different version controls provider like Github.
 * Version:           1.0.0
 * Author:            Iman Heydari
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Author URI:        http://www.iranimij.com
 * Text Domain:       automatic-image-uploader
 * Domain Path:       /languages
 */

use Gitkit\Downloader;

defined( 'ABSPATH' ) || die();

require_once 'vendor/autoload.php';

/**
 * Check If Gitkit Class exists.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
if ( ! class_exists( 'Gitkit' ) ) {

	/**
	 * Gitkit class.
	 *
	 * @since NEXT
	 */
	class Gitkit {

		/**
		 * Class instance.
		 *
		 * @since NEXT
		 * @var Gitkit
		 */
		private static $instance = null;

		/**
		 * The plugin version number.
		 *
		 * @since NEXT
		 *
		 * @access private
		 * @var string
		 */
		private static $version;

		/**
		 * The plugin basename.
		 *
		 * @since NEXT
		 *
		 * @access private
		 * @var string
		 */
		private static $plugin_basename;

		/**
		 * The plugin name.
		 *
		 * @since NEXT
		 *
		 * @access private
		 * @var string
		 */
		private static $plugin_name;

		/**
		 * The plugin directory.
		 *
		 * @since NEXT
		 *
		 * @access private
		 * @var string
		 */
		public static $plugin_dir;

		/**
		 * The plugin URL.
		 *
		 * @since NEXT
		 *
		 * @access private
		 * @var string
		 */
		private static $plugin_url;

		/**
		 * The plugin assets URL.
		 *
		 * @since NEXT
		 * @access public
		 *
		 * @var string
		 */
		public static $plugin_assets_url;

		public $downloader;

		/**
		 * Get a class instance.
		 *
		 * @since NEXT
		 *
		 * @return Gitkit Class
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Class constructor.
		 *
		 * @since NEXT
		 */
		public function __construct() {
//            var_dump(get_option('iman'));die();
			$this->define_constants();

			$this->load_files( [
				'lib/wp-async-request',
				'lib/wp-background-process',
				'downloader',
			] );

			$this->downloader = new Downloader();

			add_action( 'init', [ $this, 'init' ] );
			add_action( 'admin_init', [ $this, 'admin_init' ] );
			add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );

			// Register activation and deactivation hook.
			register_activation_hook( __FILE__, array( $this, 'activation' ) );
			register_deactivation_hook( __FILE__, array( $this, 'deactivation' ) );

			if ( ! $this->is_gitkit_screen() ) {
				return;
			}

			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
			add_filter( 'admin_body_class', [ $this, 'gitkit_admin_body_class' ] );
		}

		/**
		 * Defines constants used by the plugin.
		 *
		 * @since NEXT
		 */
		protected function define_constants() {
			$plugin_data = get_file_data( __FILE__, array( 'Plugin Name', 'Version' ), 'aiu' );

			self::$plugin_basename   = plugin_basename( __FILE__ );
			self::$plugin_name       = array_shift( $plugin_data );
			self::$version           = array_shift( $plugin_data );
			self::$plugin_dir        = trailingslashit( plugin_dir_path( __FILE__ ) );
			self::$plugin_url        = trailingslashit( plugin_dir_url( __FILE__ ) );
			self::$plugin_assets_url = trailingslashit( self::$plugin_url . 'assets' );
		}

		/**
		 * Do some stuff on plugin activation.
		 *
		 * @since  NEXT
		 * @return void
		 */
		public function activation() {

		}

		/**
		 * Do some stuff on plugin activation
		 *
		 * @since  NEXT
		 * @return void
		 */
		public function deactivation() {

		}

		/**
		 * Adding Gitkit class to body.
		 *
		 * @param string $classes Adding gitkit class.
		 * @return string
		 */
		public function gitkit_admin_body_class( $classes ) {
			return "{$classes} gitkit";
		}

		/**
		 * Initialize admin.
		 *
		 * @since NEXT
		 */
		public function admin_init() {
			$this->load_files(
                [
                    'settings',
                    'plugin',
                    'git-providers/git-provider-factory',
                    'git-providers/git-providers-interface',
                    'git-providers/github',
                ]
            );
		}

		/**
		 * Initialize.
		 *
		 * @since NEXT
		 */
		public function init() {
			$this->load_files();

			load_plugin_textdomain( 'automatic-image-uploader', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}

		/**
		 * Enqueue admin scripts.
		 *
		 * @since NEXT
		 */
		public function enqueue_admin_scripts() {
			wp_enqueue_editor();

			wp_enqueue_script(
				'gitkit',
				gitkit()->plugin_url() . 'assets/dist/admin/admin.js',
				[ 'lodash', 'wp-element', 'wp-i18n', 'wp-util' ],
				gitkit()->version(),
				true
			);

			wp_localize_script( 'gitkit', 'gitkit', [
				'nonce' => wp_create_nonce( 'gitkit' ),
				'gitkitOptions' => wp_options_manager()->get(),
			] );

			wp_enqueue_style( 'gitkit', gitkit()->plugin_url() . 'assets/dist/admin/admin.css', [], self::version() );

			wp_set_script_translations( 'gitkit', 'gitkit', gitkit()->plugin_dir() . 'languages' );
		}

		/**
		 * Register admin menu.
		 *
		 * @since NEXT
		 * @SuppressWarnings(PHPMD.NPathComplexity)
		 */
		public function register_admin_menu() {
			add_submenu_page(
				'options-general.php',
				__( 'GitKit', 'automatic-image-uploader' ),
				__( 'GitKit', 'automatic-image-uploader' ),
				'edit_theme_options',
				'gitkit',
				[ $this, 'register_admin_menu_callback' ]
			);
		}

		/**
		 * Register admin menu callback.
		 *
		 * @since NEXT
		 */
		public function register_admin_menu_callback() {
			?>
			<div id="wrap" class="wrap">
				<!-- It's required for notices, otherwise WP adds the notices wherever it finds the first heading element. -->
				<h1></h1>
				<div id="gitkit-root"></div>
			</div>
			<?php
		}

		/**
		 * Check if in gitkit pages.
		 *
		 * @since NEXT
		 *
		 * @return boolean aiu screen.
		 */
		private function is_gitkit_screen() {
			global $pagenow;
            if ( ! is_admin() ) {
                return false;
            }

			$page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

            if ( isset( $page ) && str_contains( $page, 'gitkit' ) || 'plugin-install.php' === $pagenow ) {
                return true;
            }

			return false;
		}

		/**
		 * Loads specified PHP files from the plugin includes directory.
		 *
		 * @since NEXT
		 *
		 * @param array $file_names The names of the files to be loaded in the includes directory.
		 */
		public function load_files( $file_names = array() ) {
			foreach ( $file_names as $file_name ) {
				$path = self::plugin_dir() . 'includes/' . $file_name . '.php';

				if ( file_exists( $path ) ) {
					require_once realpath( $path );
				}
			}
		}

		/**
		 * Returns the version number of the plugin.
		 *
		 * @since NEXT
		 *
		 * @return string
		 */
		public function version() {
			return self::$version;
		}

		/**
		 * Returns the plugin basename.
		 *
		 * @since NEXT
		 *
		 * @return string
		 */
		public function plugin_basename() {
			return self::$plugin_basename;
		}

		/**
		 * Returns the plugin name.
		 *
		 * @since NEXT
		 *
		 * @return string
		 */
		public function plugin_name() {
			return self::$plugin_name;
		}

		/**
		 * Returns the plugin directory.
		 *
		 * @since NEXT
		 *
		 * @return string
		 */
		public function plugin_dir() {
			return self::$plugin_dir;
		}

		/**
		 * Returns the plugin URL.
		 *
		 * @since NEXT
		 *
		 * @return string
		 */
		public function plugin_url() {
			return self::$plugin_url;
		}

		/**
		 * Returns the plugin assets URL.
		 *
		 * @since NEXT
		 *
		 * @return string
		 */
		public function plugin_assets_url() {
			return self::$plugin_assets_url;
		}
	}
}

if ( ! function_exists( 'gitkit' ) ) {
	/**
	 * Initialize the aiu.
	 *
	 * @since NEXT
	 */
	function gitkit() {
		return Gitkit::get_instance();
	}
}

/**
 * Initialize the aiu application.
 *
 * @since NEXT
 */
gitkit();
