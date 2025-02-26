# Login Popup Module

A flexible and customizable login popup module for Mesmeric Commerce that enhances the user authentication experience.

## Features

- Modal-based login, registration, and password reset forms
- Multiple trigger options (button, link, or automatic)
- Customizable positioning and styling
- Password strength meter for registration
- Social login integration (if available)
- Google reCAPTCHA support for enhanced security
- Fully responsive design
- AJAX-powered form submissions
- Customizable redirect options after login/registration

## Usage

### Basic Usage

The login popup module can be activated from the Mesmeric Commerce settings page. Once activated, it will automatically add a login/register button or link based on your configuration.

### Shortcode

You can also use the shortcode to display the login trigger anywhere on your site:

```
[mc_login_popup]
```

With custom attributes:

```
[mc_login_popup type="button" text="Sign In" icon="user"]
```

### PHP Function

To programmatically display the login trigger:

```php
<?php
if (function_exists('mc_login_popup_trigger')) {
    mc_login_popup_trigger([
        'type' => 'button',
        'text' => 'Sign In',
        'icon' => 'user'
    ]);
}
?>
```

### JavaScript API

You can also trigger the login popup programmatically using JavaScript:

```javascript
// Open the login popup
mcLoginPopup.open();

// Close the login popup
mcLoginPopup.close();

// Switch to a specific tab (login, register, reset)
mcLoginPopup.switchTab('register');
```

## Configuration

The module can be configured from the Mesmeric Commerce settings page under the "Login Popup" tab. Available settings include:

### Trigger Settings

- **Trigger Type**: Choose between button, text link, or automatic popup
- **Trigger Text**: The text to display on the button/link
- **Trigger Icon**: Icon to display alongside the text
- **Trigger Position**: Where to place the trigger (menu, header, footer, or custom)
- **Auto-Trigger Delay**: Seconds to wait before showing the popup automatically
- **Auto-Trigger Pages**: Specific pages to show the popup on
- **Auto-Trigger Once**: Only show the popup once per session

### Content Settings

- **Show Registration Form**: Allow new users to register
- **Show Social Login**: Display social login options if available
- **Show Password Reset**: Allow users to reset their password
- **Modal Width**: Size of the popup modal
- **Show When Logged In**: Whether to show the popup for logged-in users

### Redirect Settings

- **Redirect After Login**: Where to redirect users after successful login
- **Custom Redirect URL**: Custom URL to redirect to

### Security Settings

- **Enable reCAPTCHA**: Add Google reCAPTCHA to forms
- **reCAPTCHA Site Key**: Your reCAPTCHA site key
- **reCAPTCHA Secret Key**: Your reCAPTCHA secret key

## Hooks and Filters

### Actions

- `mc_login_popup_before_login`: Fires before processing a login request
- `mc_login_popup_after_login`: Fires after successful login
- `mc_login_popup_before_register`: Fires before processing a registration request
- `mc_login_popup_after_register`: Fires after successful registration
- `mc_login_popup_before_reset`: Fires before processing a password reset request
- `mc_login_popup_after_reset`: Fires after successful password reset request

### Filters

- `mc_login_popup_form_fields`: Modify form fields
- `mc_login_popup_redirect_url`: Modify the redirect URL after login/registration
- `mc_login_popup_error_messages`: Customize error messages
- `mc_login_popup_social_providers`: Modify social login providers
- `mc_login_popup_recaptcha_verify`: Custom reCAPTCHA verification

## Styling

The module comes with default styling that integrates with your theme. You can customize the appearance using CSS or by overriding the templates.

### CSS Classes

The module uses BEM-style CSS classes for easy targeting and customization:

- `.mc-login-popup-modal`: The main modal container
- `.mc-login-form`: The login form container
- `.mc-register-form`: The registration form container
- `.mc-reset-form`: The password reset form container
- `.mc-login-popup-trigger`: The trigger button/link

### Template Overrides

You can override the templates by copying them from the module's `templates` directory to your theme's `mesmeric-commerce/login-popup` directory.

## Requirements

- WordPress 5.8 or higher
- WooCommerce 6.0 or higher
- PHP 8.0 or higher
- Mesmeric Commerce 1.0.0 or higher

## Changelog

### 1.0.0
- Initial release
