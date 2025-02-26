# Auto Module Discovery

Mesmeric Commerce now features automatic module discovery, allowing developers to create new modules without manually registering them in the plugin's core code.

## How It Works

The auto module discovery feature scans the `modules` directory for module folders and automatically loads any valid modules it finds. This eliminates the need to manually update the `MODULES` constant in the `MC_Plugin` class when adding new modules.

### Discovery Process

1. The plugin scans the `modules` directory for subdirectories
2. For each subdirectory, it looks for a module class file following these patterns:
   - `MC_[ModuleName]Module.php`
   - `[ModuleName]/MC_[ModuleName]Module.php`
3. If a valid module class is found, it's automatically registered and loaded

### Module Requirements

For a module to be discovered automatically, it must:

1. Be placed in a subdirectory of the `modules` directory
2. Have a class that extends `MC_AbstractModule`
3. Follow the naming convention `MC_[ModuleName]Module`
4. Be in the namespace `MesmericCommerce\Modules\[ModuleName]`
5. Implement the required methods (`init()`, `get_module_id()`, `get_default_settings()`)

## Creating a New Module

### Using the Template Module

The easiest way to create a new module is to use the provided template module:

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

### Module Activation

Modules are activated through WordPress options. By default, a module is enabled if:

1. It has no option name specified (always enabled)
2. Its option value is set to 'yes' in the WordPress options table

To make your module configurable in the admin interface, you should:

1. Define an option name in the `get_module_id()` method
2. Add your module's settings to the admin interface
3. Use the `get_settings()` method to retrieve your module's settings

## Backward Compatibility

The auto module discovery feature maintains backward compatibility with existing modules. The discovery process:

1. First checks for modules defined in the legacy `MODULES` constant (if it exists)
2. Then discovers additional modules in the `modules` directory
3. Merges the results, with discovered modules taking precedence in case of conflicts

## Best Practices

### Module Organization

- Keep each module in its own directory
- Use a consistent directory structure (css, js, views, etc.)
- Follow the naming conventions for files and classes
- Document your module's functionality and usage

### Performance Considerations

- The module discovery process runs only once during plugin initialization
- Discovered modules are cached to avoid repeated directory scans
- Keep your module's initialization code efficient to minimize impact on page load time

### Security

- Validate and sanitize all inputs
- Implement nonce verification for all form submissions
- Perform capability checks before any privileged operations
- Escape all output data appropriately for its context

## Troubleshooting

If your module is not being discovered:

1. Check that it's in the correct directory (`modules/your-module-name`)
2. Verify that the class name follows the convention (`MC_YourModuleNameModule`)
3. Ensure the namespace is correct (`MesmericCommerce\Modules\YourModuleName`)
4. Confirm that the class extends `MC_AbstractModule`
5. Check the WordPress debug log for any errors during module loading

## Example Module

See the `modules/template` directory for a complete example of a module that works with the auto discovery feature.
