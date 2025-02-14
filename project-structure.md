<!--
Project Structure of Mesmeric Commerce Plugin

This file outlines the directory and file structure of the Mesmeric Commerce plugin.
It provides a high-level overview of the plugin's organization and the purpose of each component.
-->

```

```
├── .eslintrc.js          # Configuration file for ESLint (JavaScript linting)
├── .gitignore            # Specifies intentionally untracked files that Git should ignore
├── .php-cs-fixer.php     # Configuration file for PHP CS Fixer (PHP code style fixer)
├── .prettierrc           # Configuration file for Prettier (code formatter)
├── .qodo                # Qodo - TODO list manager
│   └── history.sqlite    # Qodo history database
├── .windsurfignore       # Files and directories to be ignored by Windsurf
├── .windsurfrules        # Custom rules for Windsurf
├── CONVENTIONS.md        # Documentation of coding standards and practices
├── README.md             # General information about the plugin
├── admin                 # Directory containing admin-specific functionality
│   ├── class-mc-admin.php # PHP class for handling admin logic
│   ├── css               # CSS files for the admin area
│   ├── js                # JavaScript files for the admin area
│   └── vue-backend       # Vue.js application for the admin backend
│       └── src
│           └── components # Vue.js components
├── assets                # Directory containing static assets
│   ├── images            # Image files
│   └── other-assets      # Other miscellaneous assets
├── composer.json         # Composer dependency management file
├── composer.lock         # Composer lock file (tracks exact versions of dependencies)
├── includes              # Directory containing core plugin classes and functionality
│   ├── class-error-handler.php # PHP class for handling errors
│   ├── class-mc-activator.php   # PHP class for plugin activation logic
│   ├── class-mc-deactivator.php # PHP class for plugin deactivation logic
│   ├── class-mc-i18n.php        # PHP class for internationalization (i18n)
│   ├── class-mc-loader.php      # PHP class for managing actions and filters
│   ├── class-mc-logger.php      # PHP class for logging
│   └── class-mc-plugin.php     # Main plugin class
├── languages             # Directory containing language translation files
├── mesmeric-commerce.php # Main plugin file (plugin bootstrap)
├── modules               # Directory containing individual plugin modules
│   ├── inventory         # Inventory management module
│   │   ├── assets        # Assets specific to the inventory module
│   │   │   ├── css       # CSS files
│   │   │   └── js        # JavaScript files
│   │   └── views         # View templates (likely PHP files)
│   ├── quick-view        # Quick view functionality module
│   │   ├── assets        # Assets specific to the quick-view module
│   │   │   ├── css       # CSS files
│   │   │   └── js        # JavaScript files
│   │   └── views         # View templates (likely PHP files)
│   ├── shipping          # Shipping management module
│   │   ├── assets        # Assets specific to the shipping module
│   │   │   ├── css       # CSS files
│   │   │   └── js        # JavaScript files
│   │   └── views         # View templates (likely PHP files)
│   └── wishlist          # Wishlist functionality module
│       ├── assets        # Assets specific to the wishlist module
│       │   ├── css       # CSS files
│       │   └── js        # JavaScript files
│       └── views         # View templates (likely PHP files)
├── package.json          # npm package configuration file
├── phpcs.xml             # Configuration file for PHP_CodeSniffer
├── phpstan.neon          # Configuration file for PHPStan (static analysis)
├── public                # Directory containing public-facing functionality
│   ├── class-mc-public.php # PHP class for handling public-facing logic
│   ├── css               # CSS files for the public-facing side
│   └── js                # JavaScript files for the public-facing side
├── tailwind.config.js    # Configuration file for Tailwind CSS
└── woocommerce           # Directory containing WooCommerce-specific extensions and overrides
    ├── class-mc-woocommerce.php # PHP class for extending WooCommerce functionality
    └── templates             # Directory for overriding WooCommerce template files
