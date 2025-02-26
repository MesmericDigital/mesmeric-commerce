# HTMX Module for Mesmeric Commerce

This module integrates [HTMX 2.0](https://htmx.org/) into the Mesmeric Commerce plugin, providing a modern, lightweight approach to dynamic content without heavy JavaScript frameworks.

## Overview

HTMX allows you to access modern browser features directly from HTML, rather than using JavaScript. This module provides:

- HTMX 2.0 core library integration
- Official HTMX extensions support
- Admin settings for configuring HTMX behavior
- Demo examples and templates
- REST API endpoints for HTMX interactions
- Shortcodes for easy implementation

## Installation

The HTMX module is included with Mesmeric Commerce. To enable it:

1. Go to Mesmeric Commerce settings in the WordPress admin
2. Navigate to the Modules tab
3. Enable the HTMX module
4. Configure settings as needed

Alternatively, you can enable it programmatically:

```php
update_option('mc_enable_htmx', 'yes');
```

## Usage

### Basic HTMX Attributes

HTMX works by adding attributes to your HTML elements. Here are some examples:

```html
<!-- Load content from an endpoint when clicked -->
<button hx-get="/api/mesmeric-commerce/v1/htmx/demo/click"
        hx-target="#result">
  Click Me
</button>

<!-- Submit a form via HTMX -->
<form hx-post="/api/mesmeric-commerce/v1/htmx/demo/form"
      hx-target="#form-result">
  <input type="text" name="name" placeholder="Your name">
  <button type="submit">Submit</button>
</form>

<!-- Search as you type -->
<input type="search" name="search" placeholder="Search products"
       hx-get="/api/mesmeric-commerce/v1/htmx/demo/search"
       hx-trigger="keyup changed delay:500ms"
       hx-target="#search-results">
```

### Using the HTMX Service

The `MC_HtmxService` class provides methods for working with HTMX in your PHP code:

```php
// Get the HTMX service
$htmx_service = $plugin->get_htmx_service();

// Enqueue HTMX scripts
$htmx_service->enqueue_htmx();

// Enqueue specific extensions
$htmx_service->enqueue_extension('json-enc');
$htmx_service->enqueue_extension('loading-states');

// Check if a request is from HTMX
if ($htmx_service->is_htmx_request()) {
    // Handle HTMX-specific logic
}
```

### Shortcodes

The module provides a demo shortcode:

```
[mc_htmx_demo]
```

This shortcode displays a comprehensive demo of HTMX features including:
- Basic click interactions
- Form submissions
- Search functionality
- Infinite scrolling
- Tab switching

### Creating Custom HTMX Endpoints

To create custom endpoints for HTMX interactions:

1. Create a REST controller class:

```php
class My_HTMX_Controller {
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes() {
        register_rest_route('mesmeric-commerce/v1', '/my-endpoint', [
            'methods' => 'GET',
            'callback' => [$this, 'handle_request'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function handle_request($request) {
        // Process the request
        $data = ['message' => 'Hello from HTMX!'];

        // Return a response
        return rest_ensure_response($data);
    }
}
```

2. Instantiate your controller:

```php
new My_HTMX_Controller();
```

### HTMX with Twig Templates

The module integrates with the Twig templating system:

```php
// Render a Twig template for HTMX response
$twig = $plugin->get_twig_service();
$html = $twig->render('my-template.twig', [
    'data' => $my_data
]);

// Return the HTML response
return rest_ensure_response($html);
```

## Available Extensions

The following HTMX extensions are included:

- `json-enc`: JSON encoding for requests
- `loading-states`: Adds loading states to elements
- `class-tools`: Utilities for class manipulation
- `ajax-header`: Adds custom headers to HTMX requests
- `response-targets`: Target elements based on response headers
- `path-deps`: Path-based dependencies
- `morphdom-swap`: Uses morphdom for DOM swapping
- `alpine-morph`: Integration with Alpine.js
- `debug`: Debugging tools for HTMX

## Updating HTMX

The HTMX library is included with the plugin and will be updated with plugin updates. If you need to update manually:

1. Download the latest version from [https://unpkg.com/htmx.org/dist/](https://unpkg.com/htmx.org/dist/)
2. Replace the file at `assets/js/htmx/htmx.min.js`
3. Update extensions as needed from [https://unpkg.com/htmx.org/dist/ext/](https://unpkg.com/htmx.org/dist/ext/)

## Best Practices

1. **Progressive Enhancement**: Design your interfaces to work without JavaScript first, then enhance with HTMX.

2. **Minimize DOM Updates**: Target specific elements rather than replacing large sections of the page.

3. **Use Indicators**: Add loading indicators for better user experience:
   ```html
   <button hx-get="/api/endpoint"
           hx-indicator="#spinner">
     Click Me
   </button>
   <span id="spinner" class="htmx-indicator">Loading...</span>
   ```

4. **Leverage Extensions**: Use HTMX extensions for advanced functionality.

5. **Combine with Alpine.js**: For complex UI interactions, combine HTMX with Alpine.js:
   ```html
   <div x-data="{ open: false }"
        hx-get="/api/data"
        hx-trigger="load">
     <button @click="open = !open">Toggle</button>
     <div x-show="open" class="content">
       <!-- HTMX loaded content here -->
     </div>
   </div>
   ```

## Troubleshooting

- **HTMX Not Loading**: Ensure the module is enabled and scripts are enqueued.
- **AJAX Requests Failing**: Check browser console for errors and verify endpoints.
- **Content Not Updating**: Verify target selectors and response content.
- **Extensions Not Working**: Make sure extensions are properly enqueued.

## Resources

- [HTMX Documentation](https://htmx.org/docs/)
- [HTMX Examples](https://htmx.org/examples/)
- [HTMX Extensions](https://htmx.org/extensions/)
- [HTMX Discord](https://htmx.org/discord)
