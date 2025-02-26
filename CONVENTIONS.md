# Mesmeric Commerce Coding Conventions

## General
- Use PHP 8.3+ features and syntax
- Follow PSR-12 coding standards
- Use strict typing: `declare(strict_types=1);` at the top of every PHP file
- Use namespaces for all classes
- Use type hints for method parameters and return types
- Use PHPDoc comments for classes, methods, and complex properties

## Naming Conventions
- Classes: PascalCase (e.g., `MC_ClassName`)
- Interfaces: PascalCase with "Interface" suffix (e.g., `MC_ClassNameInterface`)
- Traits: PascalCase with "Trait" suffix (e.g., `MC_ClassNameTrait`)
- Methods and Functions: camelCase (e.g., `doSomething()`)
- Variables: camelCase (e.g., `$myVariable`)
- Constants: UPPER_CASE with underscores (e.g., `MC_CONSTANT_NAME`)
- File names: Match the class name they contain (e.g., `MC_ClassName.php`)

## Structure
- One class per file
- Group related classes into subdirectories within the `includes/` directory
- Use `modules/` directory for feature-specific code
- Keep third-party dependencies in `vendor/` (managed by Composer)

## Documentation
- Use inline comments for complex logic
- Write clear, concise PHPDoc blocks for all classes and methods
- Include `@since` tags in PHPDoc blocks to indicate when a feature was added

## WordPress Integration
- Use WordPress coding standards for hooks, filters, and naming conventions
- Prefix all function names, hooks, and database entries with `mc_`
- Use WordPress' built-in functions and APIs when available

## JavaScript
- Use ES6+ syntax
- Follow Airbnb JavaScript Style Guide
- Use JSDoc comments for functions and complex objects

## CSS
- Use Tailwind CSS for styling
- Follow BEM naming convention for custom CSS classes

## Version Control
- Use descriptive commit messages
- Create feature branches for new developments
- Use pull requests for code reviews

## Testing
- Write unit tests for all new features
- Maintain at least 80% code coverage
- Use PHPUnit for PHP tests and Jest for JavaScript tests

## Security
- Sanitize and validate all user inputs
- Use prepared statements for database queries
- Follow WordPress security best practices

## Performance
- Use caching where appropriate
- Optimize database queries
- Minify and concatenate assets for production

## Accessibility
- Follow WCAG 2.1 AA standards
- Use semantic HTML
- Ensure keyboard navigation support

## Internationalization
- Use WordPress i18n functions for all user-facing strings
- Maintain up-to-date .pot files

## Static Analysis Tools
- Use PHPStan (level 6 or higher) for static analysis
- Implement PHP_CodeSniffer with our custom ruleset
- Use ESLint for JavaScript linting
- Configure Psalm for advanced type checking
- Run all static analysis tools as part of the CI pipeline
- Address all errors and warnings before merging code

## Dependency Management
- Pin dependency versions to avoid unexpected updates
- Conduct monthly security audits of all dependencies
- Document reasons for major dependency version changes
- Maintain a `dependencies.md` file listing key dependencies and their purposes
- Limit the number of production dependencies; favor small, focused packages

## Code Review Process
- Use the standardized code review checklist for all PRs
- Require at least one approval before merging
- Authors should respond to all review comments
- Focus reviews on architecture, security, and performance
- Review for compliance with these coding conventions
- Use automated tools to address styling issues before human review

## Error Handling Standards
- Always catch and log exceptions with appropriate context
- Use custom exception classes for domain-specific errors
- Return meaningful error messages to users without exposing system details
- Log with appropriate severity levels (info, warning, error)
- Use consistent error codes for front-end/back-end communication
- Include robust error handling in all API interactions

## CI/CD Pipeline Standards
- All PRs must pass automated tests and linting
- Feature branches must be rebased on main before merging
- Deployment must only occur from the main branch
- Include a staging environment that mirrors production
- Implement feature flags for gradual rollouts of major changes
- Run performance benchmarks as part of the CI pipeline

*Remember to update this document as new conventions are adopted or existing ones are modified.*
