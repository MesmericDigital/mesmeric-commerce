# Mesmeric Commerce Template Module

This directory contains a template for creating new modules for the Mesmeric Commerce plugin. Use this as a starting point for developing your own custom modules.

## How to Use This Template

1. **Copy the Template Directory**:
   ```bash
   cp -r modules/template modules/your-module-name
   ```

2. **Rename the Module Class File**:
   ```bash
   mv modules/your-module-name/MC_TemplateModule.php modules/your-module-name/MC_YourModuleNameModule.php
   ```

3. **Update the Namespace and Class Name**:
   - Open `MC_YourModuleNameModule.php`
   - Change the namespace from `MesmericCommerce\Modules\Template` to `MesmericCommerce\Modules\YourModuleName`
   - Rename the class from `MC_TemplateModule` to `MC_YourModuleNameModule`
   - Update the `get_module_id()` method to return your module's ID (snake_case)

4. **Customize the Module**:
   - Update the module's description in the file header
   - Implement your custom functionality in the `init()` method
   - Define your module's default settings in the `get_default_settings()` method
   - Customize the CSS and JS files in the `css` and `js` directories
   - Update the view template in the `views` directory

## Directory Structure

```
modules/your-module-name/
├── css/
│   ├── admin.css       # Admin styles
│   └── template.css    # Frontend styles
├── js/
│   ├── admin.js        # Admin scripts
│   └── template.js     # Frontend scripts
├── views/
│   └── template.php    # View template
├── MC_YourModuleNameModule.php  # Main module class
└── README.md           # This file
```

## Module Class Structure

The module class extends `MC_AbstractModule` and should implement the following methods:

- `init()`: Initialize the module, register hooks, filters, and actions
- `get_module_id()`: Return the module's ID (snake_case)
- `get_default_settings()`: Return the module's default settings

Additional methods can be added as needed for your module's functionality.

## Auto-Discovery

Your module will be automatically discovered by the Mesmeric Commerce plugin as long as:

1. It's placed in the `modules` directory
2. The module class extends `MC_AbstractModule`
3. The module class is named according to the pattern `MC_YourModuleNameModule`
4. The module class is in the namespace `MesmericCommerce\Modules\YourModuleName`

## Best Practices

- Follow the WordPress coding standards
- Use proper error handling with try/catch blocks
- Log errors and debug information using the logger
- Use the loader to register hooks, filters, and actions
- Implement proper security measures (nonce verification, capability checks, etc.)
- Sanitize inputs and escape outputs
- Use dependency injection where appropriate
- Write clean, maintainable code with proper documentation

## Example Usage

```php
// In your module's init() method
public function init(): void {
    try {
        // Register hooks, filters, and actions
        $loader = $this->get_plugin()->get_loader();

        // Register admin scripts
        $loader->add_action('admin_enqueue_scripts', $this, 'admin_enqueue_scripts');

        // Register public scripts
        $loader->add_action('wp_enqueue_scripts', $this, 'enqueue_scripts');

        // Register shortcode
        add_shortcode('mc_your_module', [$this, 'render_shortcode']);

        $this->get_logger()->info('Your module initialized successfully');
    } catch (\Throwable $e) {
        $this->get_logger()->error('Failed to initialize your module: ' . $e->getMessage());
    }
}
```
