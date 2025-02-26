<?php
declare(strict_types=1);

namespace MesmericCommerce\Includes;

use MesmericCommerce\Admin\MC_Admin;
use MesmericCommerce\Frontend\MC_Public;
use MesmericCommerce\Includes\Interfaces\MC_ModuleInterface;
use MesmericCommerce\Includes\MC_LogsRestController;
use MesmericCommerce\Includes\MC_WooCommerceLogger;
use MesmericCommerce\Modules\BreakdanceAdminMenu\Mc_BreakdanceAdminMenuModule;
use MesmericCommerce\Modules\Inventory\Mc_InventoryModule;
use MesmericCommerce\Modules\MobileMenu\MC_MobileMenuModule;
use MesmericCommerce\Modules\QuickView\Mc_QuickViewModule;
use MesmericCommerce\Modules\Wishlist\Mc_WishlistModule;
use MesmericCommerce\Modules\ProductNavigationLinks\MC_ProductNavigationLinksModule;
use MesmericCommerce\Modules\Htmx\MC_HtmxModule;
use MesmericCommerce\WooCommerce\MC_WooCommerce;
use Throwable;

/**
 * Class MC_Plugin
 *
 * The core plugin class
 */
class MC_Plugin {
	protected MC_Loader $loader;
	protected ?MC_Logger $logger = null;
	protected ?MC_I18n $i18n = null;
	protected ?MC_Database $db = null;
	protected ?MC_TwigService $twig = null;
	protected ?MC_Media $media = null;
	protected array $modules = array();
	protected ?MC_WooCommerceLogger $wc_logger = null;
	protected ?MC_LogsRestController $logs_rest_controller = null;

	/**
	 * Discovered modules cache
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array
	 */
	private $discovered_modules = null;

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

			$this->log_debug('Mesmeric Commerce plugin initialized successfully');
		} catch (Throwable $e) {
			// Log fatal initialization errors
			$this->log_error(
				sprintf(
					'Fatal Error during plugin initialization: %s in %s on line %d',
					$e->getMessage(),
					$e->getFile(),
					$e->getLine()
				)
			);

			// Re-throw the exception for WordPress to handle
			throw $e;
		}
	}

	/**
	 * Load the required dependencies.
	 *
	 * @throws \RuntimeException If required files cannot be loaded
	 */
	private function load_dependencies(): void {
		try {
			// Initialize core components
			$this->loader = new MC_Loader();

			// Initialize logger early for error tracking
			if (class_exists('MesmericCommerce\\Includes\\MC_Logger')) {
				$this->logger = MC_Logger::get_instance();
				$this->log_debug('Logger initialized successfully');
			} else {
				// Fall back to error_log if logger isn't available
				error_log('[Mesmeric Commerce] Warning: MC_Logger class not found');
			}

			// Initialize other components with error handling
			try {
				$this->i18n = new MC_I18n();
				$this->log_debug('Internationalization initialized');
			} catch (Throwable $e) {
				$this->log_error('Failed to initialize i18n: ' . $e->getMessage());
			}

			try {
				$this->db = new MC_Database();
				$this->log_debug('Database initialized');
			} catch (Throwable $e) {
				$this->log_error('Failed to initialize database: ' . $e->getMessage());
			}

			try {
				$this->media = new MC_Media();
				$this->log_debug('Media initialized');
			} catch (Throwable $e) {
				$this->log_error('Failed to initialize media: ' . $e->getMessage());
			}
		} catch (Throwable $e) {
			$this->log_error('Error loading dependencies: ' . $e->getMessage());
			throw new \RuntimeException('Failed to load core dependencies: ' . $e->getMessage(), 0, $e);
		}
	}

	/**
	 * Initialize Twig service
	 */
	private function init_twig(): void {
		try {
			$this->twig = new MC_TwigService(MC_PLUGIN_DIR . 'templates');
			$this->log_debug('Twig service initialized');
		} catch (Throwable $e) {
			$this->log_error('Failed to initialize Twig: ' . $e->getMessage());
			// Continue without Twig - plugin can still function with limited template capabilities
		}
	}

	/**
	 * Initialize WooCommerce logger
	 */
	private function init_woocommerce_logger(): void {
		try {
			$this->wc_logger = new MC_WooCommerceLogger();
			$this->log_debug('WooCommerce logger initialized');
		} catch (Throwable $e) {
			$this->log_error('Failed to initialize WooCommerce logger: ' . $e->getMessage());
			// Continue without WC logger - plugin can still function
		}
	}

	/**
	 * Initialize REST API controllers
	 */
	private function init_rest_api(): void {
		try {
			$this->logs_rest_controller = new MC_LogsRestController();
			$this->loader->add_action('rest_api_init', $this->logs_rest_controller, 'register_routes');
			$this->log_debug('REST API initialized');
		} catch (Throwable $e) {
			$this->log_error('Failed to initialize REST API: ' . $e->getMessage());
			// Continue without REST API - plugin can still function
		}
	}

	/**
	 * Get the Twig service instance
	 */
	public function get_twig(): ?MC_TwigService {
		return $this->twig;
	}

	/**
	 * Setup the logger instance with improved error handling
	 */
	private function setup_logger(): void {
		if (!$this->logger) {
			try {
				$this->logger = MC_Logger::get_instance();
				$this->log_debug('Logger setup completed');
			} catch (Throwable $e) {
				error_log('[Mesmeric Commerce] Error setting up logger: ' . $e->getMessage());
				return;
			}
		}

		// Set custom error handler
		set_error_handler(
			function (int $errno, string $errstr, string $errfile, int $errline): bool {
				$severity = match ($errno) {
					E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR => 'error',
					E_WARNING | E_CORE_WARNING | E_COMPILE_WARNING | E_USER_WARNING => 'warning',
					E_NOTICE | E_USER_NOTICE => 'notice',
					default => 'info'
				};

				$this->log_error(
					sprintf('[%s] %s in %s on line %d', $severity, $errstr, $errfile, $errline),
					$severity
				);

				// Let PHP handle the error as well
				return false;
			}
		);

		// Set custom exception handler
		set_exception_handler(
			function (Throwable $e): void {
				$this->log_error(
					sprintf(
						'Uncaught %s: %s in %s on line %d',
						get_class($e),
						$e->getMessage(),
						$e->getFile(),
						$e->getLine()
					),
					'error'
				);
			}
		);
	}

	/**
	 * Get the logger instance
	 */
	public function get_logger(): ?MC_Logger {
		return $this->logger;
	}

	/**
	 * Set up internationalization
	 */
	private function set_locale(): void {
		if ($this->i18n) {
			try {
				$this->i18n->load_plugin_textdomain();
				$this->log_debug('Plugin textdomain loaded');
			} catch (Throwable $e) {
				$this->log_error('Failed to load textdomain: ' . $e->getMessage());
			}
		}
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
		try {
			$plugin_admin = new MC_Admin($this->get_plugin_name(), $this->get_version(), $this);

			$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
			$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
			$this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
			$this->loader->add_action('admin_init', $plugin_admin, 'register_settings');

			$this->log_debug('Admin hooks defined');
		} catch (Throwable $e) {
			$this->log_error('Failed to define admin hooks: ' . $e->getMessage());
		}
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
		try {
			$plugin_public = new MC_Public($this->get_plugin_name(), $this->get_version());

			$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
			$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');

			$woocommerce = new MC_WooCommerce($this);
			$this->loader->add_action('init', $woocommerce, 'init');

			$this->log_debug('Public hooks defined');
		} catch (Throwable $e) {
			$this->log_error('Failed to define public hooks: ' . $e->getMessage());
		}
	}

	/**
	 * Initialize modules based on configuration
	 */
	private function init_modules(): void {
		try {
			$modules = $this->discover_modules();

			foreach ($modules as $module_id => $module_config) {
				try {
					$this->load_module($module_id, $module_config);
				} catch (Throwable $e) {
					$this->log_error(
						sprintf(
							'Failed to load module %s: %s',
							$module_id,
							$e->getMessage()
						)
					);
				}
			}
			$this->log_debug('Modules initialization completed');
		} catch (Throwable $e) {
			$this->log_error('Error initializing modules: ' . $e->getMessage());
		}
	}

	/**
	 * Discover available modules by scanning the modules directory
	 *
	 * @return array Array of module configurations
	 */
	private function discover_modules(): array {
		// Return cached modules if already discovered
		if ($this->discovered_modules !== null) {
			return $this->discovered_modules;
		}

		$modules = [];
		$modules_dir = MC_PLUGIN_DIR . 'modules';

		try {
			// Legacy hardcoded modules for backward compatibility
			$legacy_modules = [
				'quickview' => [
					'option' => 'mc_enable_quickview',
					'path' => 'modules/QuickView/MC_QuickViewModule.php',
					'class' => 'MesmericCommerce\\Modules\\QuickView\\MC_QuickViewModule',
					'dependencies' => [],
				],
				'breakdance_admin_menu' => [
					'option' => 'mc_enable_breakdance_admin_menu',
					'path' => 'modules/BreakdanceAdminMenu/MC_BreakdanceAdminMenuModule.php',
					'class' => MC_BreakdanceAdminMenuModule::class,
					'dependencies' => [],
				],
				'wishlist' => [
					'option' => 'mc_enable_wishlist',
					'path' => 'modules/Wishlist/MC_WishlistModule.php',
					'class' => MC_WishlistModule::class,
					'dependencies' => ['quickview'],
				],
				'inventory' => [
					'option' => 'mc_enable_inventory',
					'path' => 'modules/Inventory/MC_InventoryModule.php',
					'class' => MC_InventoryModule::class,
					'dependencies' => [],
				],
				'mobile_menu' => [
					'option' => 'mc_enable_mobile_menu',
					'path' => 'modules/MobileMenu/MC_MobileMenuModule.php',
					'class' => MC_MobileMenuModule::class,
					'dependencies' => [],
				],
				'product_navigation_links' => [
					'option' => 'mc_enable_product_navigation_links',
					'path' => 'modules/ProductNavigationLinks/MC_ProductNavigationLinksModule.php',
					'class' => 'MesmericCommerce\\Modules\\ProductNavigationLinks\\MC_ProductNavigationLinksModule',
					'dependencies' => [],
				],
				'htmx' => [
					'option' => 'mc_enable_htmx',
					'path' => 'modules/Htmx/MC_HtmxModule.php',
					'class' => 'MesmericCommerce\\Modules\\Htmx\\MC_HtmxModule',
					'dependencies' => [],
				],
			];

			// Start with legacy modules for backward compatibility
			$modules = $legacy_modules;

			// Scan modules directory
			if (is_dir($modules_dir)) {
				$module_folders = scandir($modules_dir);

				foreach ($module_folders as $folder) {
					// Skip . and .. directories and hidden folders
					if ($folder === '.' || $folder === '..' || $folder[0] === '.') {
						continue;
					}

					$module_path = $modules_dir . '/' . $folder;

					// Skip if not a directory
					if (!is_dir($module_path)) {
						continue;
					}

					// Convert folder name to module ID (snake_case)
					$module_id = $this->folder_to_module_id($folder);

					// Skip if already defined in legacy modules
					if (isset($modules[$module_id])) {
						continue;
					}

					// Look for module class file
					$module_class_file = $this->find_module_class_file($module_path, $folder);

					if ($module_class_file) {
						$relative_path = 'modules/' . $folder . '/' . basename($module_class_file);
						$class_name = $this->get_module_class_name($folder);

						$modules[$module_id] = [
							'option' => 'mc_enable_' . $module_id,
							'path' => $relative_path,
							'class' => $class_name,
							'dependencies' => [],
						];

						$this->log_debug("Discovered module: {$module_id} at {$relative_path}");
					}
				}
			}

			// Allow modules to be filtered by plugins/themes
			$modules = apply_filters('mesmeric_commerce_modules', $modules);

			// Cache discovered modules
			$this->discovered_modules = $modules;

			return $modules;
		} catch (Throwable $e) {
			$this->log_error('Error discovering modules: ' . $e->getMessage());

			// Fallback to legacy modules in case of error
			return $legacy_modules;
		}
	}

	/**
	 * Convert folder name to module ID (snake_case)
	 *
	 * @param string $folder Folder name
	 * @return string Module ID in snake_case
	 */
	private function folder_to_module_id(string $folder): string {
		// Convert PascalCase or camelCase to snake_case
		$module_id = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $folder));
		// Convert kebab-case to snake_case
		$module_id = str_replace('-', '_', $module_id);

		return strtolower($module_id);
	}

	/**
	 * Find the module class file in the module directory
	 *
	 * @param string $module_path Full path to module directory
	 * @param string $folder Module folder name
	 * @return string|null Path to module class file or null if not found
	 */
	private function find_module_class_file(string $module_path, string $folder): ?string {
		// Check for common module class file patterns
		$patterns = [
			// Standard pattern: ModuleNameModule.php
			$module_path . '/MC_' . $folder . 'Module.php',
			// Src directory pattern: src/ModuleNameModule.php
			$module_path . '/src/' . $folder . 'Module.php',
			// Src directory with MC prefix: src/MC_ModuleNameModule.php
			$module_path . '/src/MC_' . $folder . 'Module.php',
			// Legacy pattern: class-module-name.php
			$module_path . '/class-' . strtolower($folder) . '.php',
		];

		foreach ($patterns as $pattern) {
			if (file_exists($pattern)) {
				return $pattern;
			}
		}

		// If no standard patterns match, look for any PHP file that might be the module class
		$php_files = glob($module_path . '/*.php');
		if (!empty($php_files)) {
			foreach ($php_files as $file) {
				$content = file_get_contents($file);
				// Check if file contains class definition and implements ModuleInterface
				if ($content && (
					strpos($content, 'class MC_' . $folder . 'Module') !== false ||
					strpos($content, 'class ' . $folder . 'Module') !== false
				) && strpos($content, 'MC_ModuleInterface') !== false) {
					return $file;
				}
			}
		}

		// Check src directory if it exists
		$src_dir = $module_path . '/src';
		if (is_dir($src_dir)) {
			$php_files = glob($src_dir . '/*.php');
			if (!empty($php_files)) {
				foreach ($php_files as $file) {
					$content = file_get_contents($file);
					// Check if file contains class definition and implements ModuleInterface
					if ($content && (
						strpos($content, 'class MC_' . $folder . 'Module') !== false ||
						strpos($content, 'class ' . $folder . 'Module') !== false
					) && strpos($content, 'MC_ModuleInterface') !== false) {
						return $file;
					}
				}
			}
		}

		return null;
	}

	/**
	 * Get the expected module class name based on folder name
	 *
	 * @param string $folder Module folder name
	 * @return string Fully qualified class name
	 */
	private function get_module_class_name(string $folder): string {
		return 'MesmericCommerce\\Modules\\' . $folder . '\\MC_' . $folder . 'Module';
	}

	/**
	 * Load a specific module with dependency resolution
	 *
	 * @param string $module_id The module identifier
	 * @param array $module_config The module configuration
	 * @return bool True if module was loaded successfully
	 * @throws \RuntimeException If module dependencies cannot be resolved
	 */
	private function load_module(string $module_id, array $module_config): bool {
		// Check if module is enabled
		if (!$this->is_module_enabled($module_config['option'])) {
			$this->log_debug("Module {$module_id} is disabled, skipping");
			return false;
		}

		try {
			// Check if class exists or try to load it
			if (!class_exists($module_config['class']) && !empty($module_config['path'])) {
				$file_path = MC_PLUGIN_DIR . $module_config['path'];
				if (file_exists($file_path)) {
					require_once $file_path;
				} else {
					$this->log_error(
						"Module {$module_id} file not found: {$file_path}",
						'error'
					);
					return false;
				}
			}

			// Check if class exists after loading
			if (!class_exists($module_config['class'])) {
				$this->log_error(
					"Module {$module_id} class not found: {$module_config['class']}",
					'error'
				);
				return false;
			}

			// Create module instance
			$module = new $module_config['class']($this);

			// Register the module
			$this->modules[$module_id] = $module;

			// Initialize the module
			if (method_exists($module, 'init')) {
				$module->init();
			}

			$this->log_debug("Module {$module_id} loaded successfully");
			return true;
		} catch (\Throwable $e) {
			$this->log_error(
				"Failed to load module {$module_id}: " . $e->getMessage(),
				'error'
			);
			return false;
		}
	}

	/**
	 * Check if a module is enabled
	 *
	 * @param string $option_name The option name to check
	 * @return bool Whether the module is enabled
	 */
	private function is_module_enabled(string $option_name): bool {
		// Default to enabled if option doesn't exist
		if (empty($option_name)) {
			return true;
		}

		// Check the option value
		$option_value = get_option($option_name, 'yes');
		return $option_value === 'yes';
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run(): void {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name(): string {
		return 'mesmeric-commerce';
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    MC_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader(): MC_Loader {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version(): string {
		return defined('MC_VERSION') ? MC_VERSION : '1.0.0';
	}

	/**
	 * Log an error message
	 *
	 * @param string $message Error message
	 * @param string $level Log level (default: error)
	 */
	public function log_error(string $message, string $level = 'error'): void {
		if ($this->logger) {
			try {
				$this->logger->log_error($message, $level, true);
			} catch (Throwable $e) {
				error_log('[Mesmeric Commerce] ' . $message);
				error_log('[Mesmeric Commerce] Logger error: ' . $e->getMessage());
			}
		} else {
			error_log('[Mesmeric Commerce] ' . $message);
		}
	}

	/**
	 * Log a debug message
	 *
	 * @param string $message Debug message
	 */
	public function log_debug(string $message): void {
		if ($this->logger) {
			try {
				$this->logger->log_debug($message);
			} catch (Throwable $e) {
				// Silently fail for debug messages
				if (defined('WP_DEBUG') && WP_DEBUG) {
					error_log('[Mesmeric Commerce Debug] ' . $message);
				}
			}
		} else if (defined('WP_DEBUG') && WP_DEBUG) {
			error_log('[Mesmeric Commerce Debug] ' . $message);
		}
	}

	/**
	 * Get a specific module by ID
	 *
	 * @since  1.0.0
	 * @param  string $module_id The module ID to retrieve
	 * @return MC_AbstractModule|null The module instance or null if not found
	 */
	public function get_module(string $module_id): ?MC_AbstractModule {
		if (empty($this->modules) || empty($module_id)) {
			return null;
		}

		foreach ($this->modules as $module) {
			if ($module->get_module_id() === $module_id) {
				return $module;
			}
		}

		return null;
	}

	/**
	 * Get all loaded modules
	 *
	 * @since  1.0.0
	 * @return array Array of loaded module instances
	 */
	public function get_modules(): array {
		return $this->modules ?? [];
	}
}

