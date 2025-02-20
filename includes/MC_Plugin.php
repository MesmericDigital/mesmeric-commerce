<?php
declare(strict_types=1);

namespace MesmericCommerce\Includes;

use MesmericCommerce\Admin\MC_Admin;
use MesmericCommerce\Frontend\MC_Public;
use MesmericCommerce\Includes\Interfaces\MC_ModuleInterface;
use MesmericCommerce\Modules\BreakdanceAdminMenu\Mc_BreakdanceAdminMenuModule;
use MesmericCommerce\Modules\Inventory\Mc_InventoryModule;
use MesmericCommerce\Modules\QuickView\Mc_QuickViewModule;
use MesmericCommerce\Modules\Wishlist\Mc_WishlistModule;
use MesmericCommerce\WooCommerce\MC_WooCommerce;
use MesmericCommerce\Includes\MC_WooCommerceLogger;
use MesmericCommerce\Includes\MC_LogsRestController;
use Throwable;

/**
 * Class MC_Plugin
 *
 * The core plugin class
 */
class MC_Plugin {
	protected MC_Loader $loader;
	protected MC_Logger $logger;
	protected MC_I18n $i18n;
	protected MC_Database $db;
	protected MC_TwigService $twig;
	protected MC_Media $media;
	protected array $modules = array();
	protected MC_WooCommerceLogger $wc_logger;
	protected MC_LogsRestController $logs_rest_controller;

	private const MODULES = array(
		'quickview' => array(
			'option' => 'mc_enable_quickview',
			'path' => 'modules/QuickView/MC_QuickViewModule.php',
			'class' => 'MesmericCommerce\\Modules\\QuickView\\MC_QuickViewModule',
			'dependencies' => [],
		),
		'breakdance_admin_menu' => array(
			'option' => 'mc_enable_breakdance_admin_menu',
			'path' => 'modules/BreakdanceAdminMenu/MC_BreakdanceAdminMenuModule.php',
			'class' => MC_BreakdanceAdminMenuModule::class,
			'dependencies' => [],
		),
		'wishlist' => array(
			'option' => 'mc_enable_wishlist',
			'path' => 'modules/Wishlist/MC_WishlistModule.php',
			'class' => MC_WishlistModule::class,
			'dependencies' => [ 'quickview' ],
		),
		'inventory' => array(
			'option' => 'mc_enable_inventory',
			'path' => 'modules/Inventory/MC_InventoryModule.php',
			'class' => MC_InventoryModule::class,
			'dependencies' => [],
		),
	);

	/**
	 * Define the core functionality of the plugin.
	 */
	public function __construct() {
		try {
			$this->load_dependencies();
			$this->setup_logger();
			$this->set_locale();
			$this->init_twig();
			$this->init_woocommerce_logger();
			$this->init_rest_api();
			$this->define_admin_hooks();
			$this->define_public_hooks();
			$this->init_modules();
		} catch (Throwable $e) {
			// Log fatal initialization errors
			error_log(
				sprintf(
					'[Mesmeric Commerce] Fatal Error: %s in %s on line %d',
					$e->getMessage(),
					$e->getFile(),
					$e->getLine()
				)
			);
			throw $e;
		}
	}

	/**
	 * Load the required dependencies.
	 *
	 * @throws \RuntimeException If required files cannot be loaded
	 */
	private function load_dependencies(): void {
		$required_files = array(
			'includes/class-mc-loader.php',
			'includes/class-mc-i18n.php',
			'includes/class-mc-logger.php',
			'includes/MC_TwigService.php',
			'includes/MC_Media.php',
			'includes/MC_WooCommerceLogger.php',
			'includes/MC_LogsRestController.php',
			'includes/Interfaces/MC_ModuleInterface.php',
			'includes/Abstract/MC_AbstractModule.php',
			'admin/class-mc-admin.php',
			'public/class-mc-public.php',
			'woocommerce/class-mc-woocommerce.php',
		);

		foreach ( $required_files as $file ) {
			$path = MC_PLUGIN_DIR . $file;
			if ( ! file_exists( $path ) ) {
				throw new \RuntimeException( "Required file not found: {$file}" );
			}
			require_once $path;
		}

		$this->loader = new MC_Loader();
		$this->logger = new MC_Logger();
		$this->i18n = new MC_I18n();
		$this->db = new MC_Database();
		$this->media = new MC_Media();
	}

	/**
	 * Initialize Twig service
	 */
	private function init_twig(): void {
		$this->twig = new MC_TwigService( MC_PLUGIN_DIR . 'templates' );
	}

	/**
	 * Get the Twig service instance
	 */
	public function get_twig(): MC_TwigService {
		return $this->twig;
	}

	/**
	 * Setup the logger instance with improved error handling
	 */
	private function setup_logger(): void {
		$this->logger = new MC_Logger();

		set_error_handler(
			function (int $errno, string $errstr, string $errfile, int $errline): bool {
				$severity = match ( $errno ) {
					E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR => 'error',
					E_WARNING | E_CORE_WARNING | E_COMPILE_WARNING | E_USER_WARNING => 'warning',
					E_NOTICE | E_USER_NOTICE => 'notice',
					default => 'info'
				};

				$this->logger->log_error(
					sprintf( '[%s] %s in %s on line %d', $severity, $errstr, $errfile, $errline ),
					$severity,
					true
				);

				return false;
			}
		);

		set_exception_handler(
			function (Throwable $e): void {
				$this->logger->log_error(
					sprintf(
						'Uncaught %s: %s in %s on line %d',
						get_class( $e ),
						$e->getMessage(),
						$e->getFile(),
						$e->getLine()
					),
					'error',
					true
				);
			}
		);
	}

	public function get_logger(): MC_Logger {
		return $this->logger;
	}

	private function set_locale(): void {
		$this->i18n->load_plugin_textdomain();
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   void
	 */
	private function define_admin_hooks(): void {
		$plugin_admin = new MC_Admin( $this->get_plugin_name(), $this->get_version(), $this );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'register_settings' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   void
	 */
	private function define_public_hooks(): void {
		$plugin_public = new MC_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

		$woocommerce = new MC_WooCommerce();
		$this->loader->add_action( 'init', $woocommerce, 'init' );
	}

	private function init_modules(): void {
		$initialized = [];
		$failed = [];

		foreach ( self::MODULES as $module_id => $config ) {
			if ( get_option( $config['option'], 'yes' ) === 'yes' ) {
				$this->init_module( $module_id, $config, $initialized, $failed );
			}
		}

		if ( ! empty( $failed ) ) {
			$this->logger->log_error(
				sprintf( 'Failed to initialize modules: %s', implode( ', ', $failed ) ),
				'error',
				true
			);
		}
	}

	/**
	 * Initialize a single module with dependency checking.
	 *
	 * @param string $module_id    Module identifier.
	 * @param array  $config       Module configuration.
	 * @param array  $initialized  Reference to array of initialized modules.
	 * @param array  $failed       Reference to array of failed modules.
	 */
	private function init_module( string $module_id, array $config, array &$initialized, array &$failed ): void {
		// Skip if already initialized or failed
		if ( isset( $initialized[ $module_id ] ) || in_array( $module_id, $failed ) ) {
			return;
		}

		// Check dependencies
		foreach ( $config['dependencies'] as $dependency ) {
			if ( ! isset( self::MODULES[ $dependency ] ) ) {
				$failed[] = $module_id;
				$this->logger->log_error(
					sprintf( 'Module %s has invalid dependency: %s', $module_id, $dependency ),
					'error',
					true
				);
				return;
			}

			// Initialize dependency first
			$this->init_module( $dependency, self::MODULES[ $dependency ], $initialized, $failed );

			// Check if dependency failed
			if ( in_array( $dependency, $failed ) ) {
				$failed[] = $module_id;
				$this->logger->log_error(
					sprintf( 'Module %s failed due to failed dependency: %s', $module_id, $dependency ),
					'error',
					true
				);
				return;
			}
		}

		try {
			require_once MC_PLUGIN_DIR . $config['path'];
			$instance = new $config['class']( $module_id );

			if ( ! ( $instance instanceof MC_ModuleInterface ) ) {
				throw new \RuntimeException(
					sprintf( 'Module %s must implement MC_ModuleInterface', $module_id )
				);
			}

			$instance->init();
			$this->modules[ $module_id ] = $instance;
			$initialized[ $module_id ] = true;

			$this->logger->log_error(
				sprintf( 'Module %s initialized successfully', $module_id ),
				'info'
			);
		} catch (Throwable $e) {
			$failed[] = $module_id;
			$this->logger->log_error(
				sprintf( 'Failed to initialize module %s: %s', $module_id, $e->getMessage() ),
				'error',
				true
			);
		}
	}

	/**
	 * Get an initialized module instance.
	 *
	 * @param string $module_id Module identifier.
	 * @return MC_ModuleInterface|null Module instance or null if not found.
	 */
	public function get_module( string $module_id ): ?MC_ModuleInterface {
		return $this->modules[ $module_id ] ?? null;
	}

	public function run(): void {
		$this->loader->run();
	}

	/**
	 * Get the plugin name.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name(): string {
		return MC_PLUGIN_BASENAME;
	}

	/**
	 * Get the plugin version.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version(): string {
		return MC_VERSION;
	}

    /**
     * Get all initialized modules
     *
     * @return array Array of initialized module instances
     */
    public function get_modules(): array {
        return $this->modules;
    }

    /**
     * Get the plugin file path
     *
     * @return string Plugin file path
     */
    public function get_plugin_file(): string {
        return MC_PLUGIN_FILE;
    }

    /**
     * Get the media service instance
     *
     * @return MC_Media The media service instance
     */
    public function get_media(): MC_Media {
        return $this->media;
    }

    /**
     * Initialize WooCommerce logger
     */
    private function init_woocommerce_logger(): void {
        $this->wc_logger = new MC_WooCommerceLogger();
    }

    /**
     * Initialize REST API endpoints
     */
    private function init_rest_api(): void {
        $this->logs_rest_controller = new MC_LogsRestController();
        add_action('rest_api_init', [$this->logs_rest_controller, 'register_routes']);
    }
}
