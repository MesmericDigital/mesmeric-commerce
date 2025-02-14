# Mesmeric Commerce Coding Conventions

## Table of Contents
- [Core Principles](#core-principles)
- [Project Structure](#project-structure)
- [PHP Conventions](#php-conventions)
- [JavaScript Conventions](#javascript-conventions)
- [Testing Guidelines](#testing-guidelines)
- [Documentation Standards](#documentation-standards)
- [Tools and Enforcement](#tools-and-enforcement)

## Core Principles

1. **Separation of Concerns**
   - Extract responsibilities into separate functions, files, and tests
   - One file = one responsibility
   - No duplicate code—reuse functions and classes across modules

2. **Code Quality**
   - Write type-safe code using PHP 8+ features
   - Follow WordPress Coding Standards (WPCS) and WooCommerce best practices
   - Document complex or domain-specific flows only
   - Always verify package installations with the team

3. **Maintainability**
   - Use clear, requirement-based naming
   - Follow Single Responsibility Principle
   - Ensure compatibility with future WooCommerce and WordPress updates

## Project Structure

```
mesmeric-commerce/
├── admin/                    # Admin-specific functionality
│   └── vue-backend/         # Vue.js admin interface
├── modules/                  # Plugin features
│   ├── inventory/
│   ├── quick-view/
│   ├── shipping/
│   └── wishlist/
├── includes/                 # Core plugin classes
├── public/                   # Public-facing functionality
└── woocommerce/             # WooCommerce integrations
    └── templates/           # Template overrides
```

### Module Structure
Each module should follow this structure:
```
module-name/
├── assets/
│   ├── css/
│   └── js/
├── views/                   # Template files
├── class-mc-module.php      # Main module class
└── README.md               # Module documentation
```

## PHP Conventions

### Naming Conventions
1. **Classes**
   - Use PascalCase
   - Prefix with `MC_` (Mesmeric Commerce)
   - Example: `class MC_Wishlist_Manager`

2. **Functions**
   - Use snake_case
   - Requirement-based names
   - ✅ `get_cart_total()`
   - ❌ `calculatePrice()`

3. **Variables**
   - Use snake_case
   - Clear, descriptive names
   - No abbreviations
   - Example: `$product_quantity` instead of `$qty`

### Type Safety
```php
public function add_to_wishlist(int $product_id, ?int $user_id = null): bool
{
    // Type declarations required
}
```

### Error Handling
1. **Exception Hierarchy**
   ```php
   class MC_Exception extends Exception {}
   class MC_Validation_Exception extends MC_Exception {}
   class MC_Integration_Exception extends MC_Exception {}
   ```

2. **Error Logging**
   ```php
   try {
       // Operation
   } catch (MC_Exception $e) {
       MC_Logger::log($e->getMessage(), 'error');
       // Handle appropriately
   }
   ```

### WooCommerce Integration
1. **Filters and Actions**
   ```php
   add_filter('woocommerce_get_price_html', [$this, 'modify_price_display'], 10, 2);
   ```

2. **Template Overrides**
   - Place in `woocommerce/templates/`
   - Maintain original file structure
   - Document modifications

## JavaScript Conventions

### Vue.js Components
1. **Naming**
   - Use PascalCase for components
   - Example: `ProductQuickView.vue`

2. **File Structure**
   ```vue
   <template>
     <!-- Template -->
   </template>

   <script setup lang="ts">
   // Component logic
   </script>

   <style scoped>
   /* Component styles */
   </style>
   ```

### Frontend Interactions
1. **HTMX Usage**
   ```html
   <button 
     hx-post="/api/wishlist/add"
     hx-trigger="click"
     hx-target="#wishlist-count"
   >
     Add to Wishlist
   </button>
   ```

2. **Alpine.js**
   ```html
   <div x-data="{ open: false }">
     <button @click="open = !open">Toggle</button>
   </div>
   ```

## Testing Guidelines

### Unit Tests
1. **Test Structure**
   ```php
   class MC_Wishlist_Test extends WP_UnitTestCase {
       public function test_add_to_wishlist(): void {
           // Arrange
           // Act
           // Assert
       }
   }
   ```

2. **Integration Tests**
   - Test WooCommerce interactions
   - Use realistic scenarios
   - Test with various store sizes

### Test Coverage Requirements
- Minimum 80% code coverage
- 100% coverage for critical paths
- Integration tests for all WooCommerce hooks

## Documentation Standards

### PHPDoc Blocks
```php
/**
 * Adds a product to the user's wishlist.
 *
 * @since 1.0.0
 *
 * @param int      $product_id The product ID to add
 * @param int|null $user_id    Optional user ID
 *
 * @return bool True if added successfully
 *
 * @throws MC_Validation_Exception If product doesn't exist
 */
```

### README Requirements
- Module purpose
- Installation instructions
- Configuration options
- Examples
- Changelog

## Tools and Enforcement

### PHP Tools
1. **PHP_CodeSniffer**
   ```xml
   <!-- phpcs.xml -->
   <rule ref="WordPress-Core">
       <exclude name="WordPress.Files.FileName"/>
   </rule>
   ```

2. **PHP-CS-Fixer**
   ```php
   // .php-cs-fixer.php
   return PhpCsFixer\Config::create()
       ->setRules([
           '@PSR2' => true,
           'strict_types' => true,
       ]);
   ```

3. **PHPStan**
   ```neon
   # phpstan.neon
   parameters:
       level: 8
       paths:
           - src
   ```

### JavaScript Tools
1. **ESLint**
   ```js
   // .eslintrc.js
   module.exports = {
       extends: [
           'plugin:vue/vue3-recommended',
           '@vue/typescript/recommended'
       ]
   };
   ```

2. **Prettier**
   ```json
   // .prettierrc
   {
       "singleQuote": true,
       "trailingComma": "es5",
       "printWidth": 80
   }
   ```

### Git Hooks
1. **pre-commit**
   - Run PHP_CodeSniffer
   - Run ESLint
   - Run Prettier
   - Run PHPStan

2. **pre-push**
   - Run all tests
   - Check code coverage

### CI/CD Integration
- GitHub Actions for automated checks
- Deploy only when all checks pass
- Automated version bumping

## Version Control

### Branch Naming
- feature/feature-name
- bugfix/issue-description
- release/version-number

### Commit Messages
```
type(scope): description

[optional body]

[optional footer]
```

Types:
- feat: New feature
- fix: Bug fix
- docs: Documentation
- style: Formatting
- refactor: Code restructuring
- test: Adding tests
- chore: Maintenance

## Security Guidelines

1. **Input Validation**
   ```php
   $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
   if (false === $product_id) {
       throw new MC_Validation_Exception('Invalid product ID');
   }
   ```

2. **Output Escaping**
   ```php
   echo esc_html($product_name);
   ```

3. **Nonce Verification**
   ```php
   if (!wp_verify_nonce($_POST['nonce'], 'mc_action')) {
       wp_die('Invalid request');
   }