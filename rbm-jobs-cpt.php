<?php
/*
Plugin Name: RBM Jobs CPT
Plugin URL: https://github.com/realbig/RBM-Jobs-CPT
Description: Jobs CPT moved from CPT-onomies
Version: 1.0.1
Text Domain: rbm-jobs-cpt
Author: Eric Defore
Author URL: http://realbigmarketing.com
Contributors: d4mation
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'RBM_Jobs_CPT' ) ) {

	/**
	 * Main RBM_Jobs_CPT class
	 *
	 * @since	  1.0.0
	 */
	class RBM_Jobs_CPT {
		
		/**
		 * @var			RBM_Jobs_CPT $plugin_data Holds Plugin Header Info
		 * @since		1.0.0
		 */
		public $plugin_data;
		
		/**
		 * @var			RBM_Jobs_CPT $admin_errors Stores all our Admin Errors to fire at once
		 * @since		1.0.0
		 */
		private $admin_errors;
		
		/**
		 * @var			RBM_Jobs_CPT $cpt Holds the CPT
		 * @since		1.0.0
		 */
		public $cpt;

		/**
		 * Get active instance
		 *
		 * @access	  public
		 * @since	  1.0.0
		 * @return	  object self::$instance The one true RBM_Jobs_CPT
		 */
		public static function instance() {
			
			static $instance = null;
			
			if ( null === $instance ) {
				$instance = new static();
			}
			
			return $instance;

		}
		
		protected function __construct() {
			
			$this->setup_constants();
			$this->load_textdomain();
			
			if ( ! class_exists( 'RBM_CPTS' ) ||
			   ! class_exists( 'RBM_FieldHelpers' ) ) {
				
				$this->admin_errors[] = sprintf( _x( 'To use the %s Plugin, both %s and %s must be active as either a Plugin or a Must Use Plugin!', 'Missing Dependency Error', 'rbm-jobs-cpt' ), '<strong>' . $this->plugin_data['Name'] . '</strong>', '<a href="//github.com/realbig/rbm-field-helpers/" target="_blank">' . __( 'RBM Field Helpers', 'rbm-jobs-cpt' ) . '</a>', '<a href="//github.com/realbig/rbm-cpts/" target="_blank">' . __( 'RBM Custom Post Types', 'rbm-jobs-cpt' ) . '</a>' );
				
				if ( ! has_action( 'admin_notices', array( $this, 'admin_errors' ) ) ) {
					add_action( 'admin_notices', array( $this, 'admin_errors' ) );
				}
				
				return false;
				
			}

			if ( ! class_exists( 'WP_Statuses' ) ) {

				$this->admin_errors[] = sprintf( __( 'To use the %s Plugin, %s needs to be active!', 'rbm-jobs-cpt' ), '<strong>' . $this->plugin_data['Name'] . '</strong>', '<a href="//github.com/imath/wp-statuses" target="_blank">' . __( 'WP Statuses', 'rbm-jobs-cpt' ) . '</a>' );
				
				if ( ! has_action( 'admin_notices', array( $this, 'admin_errors' ) ) ) {
					add_action( 'admin_notices', array( $this, 'admin_errors' ) );
				}
				
				return false;

			}
			
			$this->require_necessities();
			
			// Register our CSS/JS for the whole plugin
			add_action( 'init', array( $this, 'register_scripts' ) );
			
		}

		/**
		 * Setup plugin constants
		 *
		 * @access	  private
		 * @since	  1.0.0
		 * @return	  void
		 */
		private function setup_constants() {
			
			// WP Loads things so weird. I really want this function.
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . '/wp-admin/includes/plugin.php';
			}
			
			// Only call this once, accessible always
			$this->plugin_data = get_plugin_data( __FILE__ );

			if ( ! defined( 'RBM_Jobs_CPT_VER' ) ) {
				// Plugin version
				define( 'RBM_Jobs_CPT_VER', $this->plugin_data['Version'] );
			}

			if ( ! defined( 'RBM_Jobs_CPT_DIR' ) ) {
				// Plugin path
				define( 'RBM_Jobs_CPT_DIR', plugin_dir_path( __FILE__ ) );
			}

			if ( ! defined( 'RBM_Jobs_CPT_URL' ) ) {
				// Plugin URL
				define( 'RBM_Jobs_CPT_URL', plugin_dir_url( __FILE__ ) );
			}
			
			if ( ! defined( 'RBM_Jobs_CPT_FILE' ) ) {
				// Plugin File
				define( 'RBM_Jobs_CPT_FILE', __FILE__ );
			}

		}

		/**
		 * Internationalization
		 *
		 * @access	  private 
		 * @since	  1.0.0
		 * @return	  void
		 */
		private function load_textdomain() {

			// Set filter for language directory
			$lang_dir = RBM_Jobs_CPT_DIR . '/languages/';
			$lang_dir = apply_filters( 'rbm_jobs_cpt_languages_directory', $lang_dir );

			// Traditional WordPress plugin locale filter
			$locale = apply_filters( 'plugin_locale', get_locale(), 'rbm-jobs-cpt' );
			$mofile = sprintf( '%1$s-%2$s.mo', 'rbm-jobs-cpt', $locale );

			// Setup paths to current locale file
			$mofile_local   = $lang_dir . $mofile;
			$mofile_global  = WP_LANG_DIR . '/rbm-jobs-cpt/' . $mofile;

			if ( file_exists( $mofile_global ) ) {
				// Look in global /wp-content/languages/rbm-jobs-cpt/ folder
				// This way translations can be overridden via the Theme/Child Theme
				load_textdomain( 'rbm-jobs-cpt', $mofile_global );
			}
			else if ( file_exists( $mofile_local ) ) {
				// Look in local /wp-content/plugins/rbm-jobs-cpt/languages/ folder
				load_textdomain( 'rbm-jobs-cpt', $mofile_local );
			}
			else {
				// Load the default language files
				load_plugin_textdomain( 'rbm-jobs-cpt', false, $lang_dir );
			}

		}
		
		/**
		 * Include different aspects of the Plugin
		 * 
		 * @access	  private
		 * @since	  1.0.0
		 * @return	  void
		 */
		private function require_necessities() {
			
			require_once RBM_Jobs_CPT_DIR . 'core/cpt/class-rbm-cpt-jobs.php';
			$this->cpt = new RBM_CPT_Jobs();
			
		}
		
		/**
		 * Show admin errors.
		 * 
		 * @access	  public
		 * @since	  1.0.0
		 * @return	  HTML
		 */
		public function admin_errors() {
			?>
			<div class="error">
				<?php foreach ( $this->admin_errors as $notice ) : ?>
					<p>
						<?php echo $notice; ?>
					</p>
				<?php endforeach; ?>
			</div>
			<?php
		}
		
		/**
		 * Register our CSS/JS to use later
		 * 
		 * @access	  public
		 * @since	  1.0.0
		 * @return	  void
		 */
		public function register_scripts() {
			
			wp_register_style(
				'rbm-jobs-cpt-admin',
				RBM_Jobs_CPT_URL . 'assets/css/admin.css',
				null,
				defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : RBM_Jobs_CPT_VER
			);
			
			wp_register_script(
				'rbm-jobs-cpt-admin',
				RBM_Jobs_CPT_URL . 'assets/js/admin.js',
				array( 'jquery' ),
				defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : RBM_Jobs_CPT_VER,
				true
			);
			
			wp_localize_script( 
				'rbm-jobs-cpt-admin',
				'rBMJobsCPT',
				apply_filters( 'rbm_jobs_cpt_localize_admin_script', array() )
			);
			
		}
		
	}
	
} // End Class Exists Check

/**
 * The main function responsible for returning the one true RBM_Jobs_CPT
 * instance to functions everywhere
 *
 * @since	  1.0.0
 * @return	  \RBM_Jobs_CPT The one true RBM_Jobs_CPT
 */
add_action( 'plugins_loaded', 'rbm_jobs_cpt_load', 999 );
function rbm_jobs_cpt_load() {

	require_once __DIR__ . '/core/rbm-jobs-cpt-functions.php';
	RBMJOBSCPT();

}
