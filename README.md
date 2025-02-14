# Mesmeric Commerce

A modern, feature-rich enhancement suite for WooCommerce with HTMX, Alpine.js, and Vue.js integration.

## Features

- **Quick View Module**: Allow customers to preview products without leaving the current page
- **Wishlist Module**: Enable customers to save products for later
- **Shipping Module**: Enhanced shipping calculations and display
- **Inventory Module**: Advanced inventory management features
- **Modern Tech Stack**:
  - HTMX for dynamic content updates
  - Alpine.js for lightweight interactivity
  - Vue.js for complex admin interfaces
  - Tailwind CSS with DaisyUI for beautiful styling

## Requirements

- WordPress 6.0+
- PHP 8.3+
- WooCommerce 8.0.0+
- Node.js 18+ (for development)
- Composer (for development)

## Installation

1. Download the latest release
2. Upload to your WordPress site's `/wp-content/plugins/` directory
3. Activate the plugin through the WordPress admin interface

### Development Setup

1. Clone the repository:
```bash
git clone https://github.com/beanbagplanet/mesmeric-commerce.git
cd mesmeric-commerce
```

2. Install PHP dependencies:
```bash
composer install
```

3. Install Node.js dependencies:
```bash
npm install
```

4. Build assets:
```bash
# Development
npm run dev

# Production
npm run prod

# Watch for changes
npm run watch
```

## Configuration

1. Navigate to WP Admin > Mesmeric Commerce > Settings
2. Enable/disable modules as needed
3. Configure individual module settings

## Modules

### Quick View

- Adds a quick view button to product listings
- AJAX-powered modal with product details
- Add to cart functionality without page reload

### Wishlist

- Multiple wishlists per user
- Share wishlist functionality
- Add/remove products via AJAX
- Persistent storage in database

### Shipping

- Enhanced shipping calculations
- Real-time shipping rates
- Custom shipping rules
- Shipping zones management

### Inventory

- Advanced stock management
- Low stock notifications
- Stock level history
- Automated reorder points

## Development

### Directory Structure

```
mesmeric-commerce/
├── mesmeric-commerce.php             # Main plugin file
├── admin/                            # Admin area files
├── public/                           # Public-facing files
├── includes/                         # Core plugin classes
├── modules/                          # Feature modules
├── woocommerce/                      # WC integration
├── languages/                        # Translations
└── assets/                          # Global assets
```

### Build Tools

- Laravel Mix for asset compilation
- Tailwind CSS for styling
- DaisyUI for UI components
- Vue.js for admin SPA
- HTMX for dynamic content
- Alpine.js for interactivity

### Coding Standards

- PSR-12 coding standard
- WordPress Coding Standards
- TypeScript for Vue components
- ESLint + Prettier for JS/TS

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## License

GPL-2.0-or-later

## Support

- [Documentation](https://beanbagplanet.co.uk/docs/mesmeric-commerce)
- [Issue Tracker](https://github.com/beanbagplanet/mesmeric-commerce/issues)
- [Support Forum](https://beanbagplanet.co.uk/support)

## Credits

Developed by [Bean Bag Planet](https://beanbagplanet.co.uk)
