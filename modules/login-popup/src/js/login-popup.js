/**
 * Mesmeric Commerce - Login Popup Module
 *
 * Main JavaScript for the login popup functionality
 */

(function () {
    'use strict';

    // Store DOM elements
    const elements = {};

    // Store module settings
    let settings = {};

    /**
     * Initialize the login popup module
     *
     * @param {Object} options Module options
     */
    function init(options = {}) {
        // Merge default settings with options
        settings = Object.assign({
            ajaxUrl: '',
            ajaxNonce: '',
            redirectAfterLogin: false,
            redirectUrl: '',
            recaptchaEnabled: false,
            recaptchaSiteKey: '',
            messages: {
                loginError: 'Login failed. Please check your credentials and try again.',
                registerError: 'Registration failed. Please try again.',
                resetError: 'Password reset request failed. Please try again.',
                genericError: 'An error occurred. Please try again.',
                passwordMismatch: 'Passwords do not match.',
                emailInvalid: 'Please enter a valid email address.',
                requiredField: 'This field is required.',
                passwordWeak: 'Password is too weak.',
                recaptchaError: 'Please complete the reCAPTCHA verification.',
                loginSuccess: 'Login successful. Redirecting...',
                registerSuccess: 'Registration successful. Please check your email for verification.',
                resetSuccess: 'Password reset email sent. Please check your inbox.'
            }
        }, options);

        // Cache DOM elements
        cacheElements();

        // Bind events
        bindEvents();

        // Initialize reCAPTCHA if enabled
        if (settings.recaptchaEnabled && settings.recaptchaSiteKey) {
            initRecaptcha();
        }
    }

    /**
     * Cache DOM elements
     */
    function cacheElements() {
        elements.triggers = document.querySelectorAll('.mc-login-popup-trigger');
        elements.modal = document.querySelector('.mc-login-popup-modal');
        elements.modalClose = document.querySelector('.mc-login-popup-modal-close');
        elements.modalContent = document.querySelector('.mc-login-popup-modal-content');
        elements.forms = {
            login: document.querySelector('.mc-login-popup-form-login'),
            register: document.querySelector('.mc-login-popup-form-register'),
            reset: document.querySelector('.mc-login-popup-form-reset')
        };
        elements.formLinks = document.querySelectorAll('.mc-login-popup-form-links a');
        elements.formSubmits = document.querySelectorAll('.mc-login-popup-form');
        elements.loading = document.querySelector('.mc-login-popup-loading');
        elements.messages = document.querySelectorAll('.mc-login-popup-message');
    }

    /**
     * Bind event listeners
     */
    function bindEvents() {
        // Trigger click events
        if (elements.triggers) {
            elements.triggers.forEach(trigger => {
                trigger.addEventListener('click', openModal);
            });
        }

        // Close modal events
        if (elements.modalClose) {
            elements.modalClose.addEventListener('click', closeModal);
        }

        // Close modal when clicking outside
        if (elements.modal) {
            elements.modal.addEventListener('click', function (e) {
                if (e.target === elements.modal) {
                    closeModal();
                }
            });
        }

        // Form switch links
        if (elements.formLinks) {
            elements.formLinks.forEach(link => {
                link.addEventListener('click', switchForm);
            });
        }

        // Form submissions
        if (elements.formSubmits) {
            elements.formSubmits.forEach(form => {
                form.addEventListener('submit', handleFormSubmit);
            });
        }

        // Password strength meter
        const passwordField = document.querySelector('.mc-login-popup-form-register input[name="password"]');
        if (passwordField) {
            passwordField.addEventListener('input', checkPasswordStrength);
        }

        // Password confirmation validation
        const confirmPasswordField = document.querySelector('.mc-login-popup-form-register input[name="password_confirm"]');
        if (confirmPasswordField) {
            confirmPasswordField.addEventListener('input', validatePasswordConfirmation);
        }

        // Close modal on escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && elements.modal && elements.modal.classList.contains('is-active')) {
                closeModal();
            }
        });
    }

    /**
     * Open the login modal
     *
     * @param {Event} e Click event
     */
    function openModal(e) {
        e.preventDefault();

        if (elements.modal) {
            elements.modal.classList.add('is-active');
            document.body.style.overflow = 'hidden';

            // Focus the first input field
            setTimeout(() => {
                const firstInput = elements.modal.querySelector('input:not([type="hidden"])');
                if (firstInput) {
                    firstInput.focus();
                }
            }, 100);
        }
    }

    /**
     * Close the login modal
     */
    function closeModal() {
        if (elements.modal) {
            elements.modal.classList.remove('is-active');
            document.body.style.overflow = '';

            // Reset forms
            resetForms();
        }
    }

    /**
     * Switch between login, register, and reset forms
     *
     * @param {Event} e Click event
     */
    function switchForm(e) {
        e.preventDefault();

        const formType = e.target.getAttribute('data-form');
        if (!formType || !elements.forms[formType]) {
            return;
        }

        // Hide all forms
        Object.values(elements.forms).forEach(form => {
            if (form) {
                form.classList.remove('is-active');
            }
        });

        // Show the selected form
        elements.forms[formType].classList.add('is-active');

        // Update modal title
        const modalTitle = document.querySelector('.mc-login-popup-modal-title');
        if (modalTitle) {
            switch (formType) {
                case 'login':
                    modalTitle.textContent = 'Log In';
                    break;
                case 'register':
                    modalTitle.textContent = 'Create Account';
                    break;
                case 'reset':
                    modalTitle.textContent = 'Reset Password';
                    break;
            }
        }

        // Focus the first input field
        setTimeout(() => {
            const firstInput = elements.forms[formType].querySelector('input:not([type="hidden"])');
            if (firstInput) {
                firstInput.focus();
            }
        }, 100);

        // Hide any existing messages
        hideMessages();
    }

    /**
     * Handle form submissions
     *
     * @param {Event} e Submit event
     */
    function handleFormSubmit(e) {
        e.preventDefault();

        // Get form type
        const form = e.target;
        const formType = form.classList.contains('mc-login-popup-form-login') ? 'login' :
            form.classList.contains('mc-login-popup-form-register') ? 'register' :
                form.classList.contains('mc-login-popup-form-reset') ? 'reset' : null;

        if (!formType) {
            return;
        }

        // Validate form
        if (!validateForm(form, formType)) {
            return;
        }

        // Show loading state
        showLoading();

        // Collect form data
        const formData = new FormData(form);
        formData.append('action', `mc_login_popup_${formType}`);
        formData.append('nonce', settings.ajaxNonce);

        // Add reCAPTCHA token if enabled
        if (settings.recaptchaEnabled && window.grecaptcha) {
            const recaptchaToken = grecaptcha.getResponse();
            if (!recaptchaToken) {
                hideLoading();
                showMessage('error', settings.messages.recaptchaError);
                return;
            }
            formData.append('recaptcha_token', recaptchaToken);
        }

        // Send AJAX request
        fetch(settings.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                hideLoading();

                if (data.success) {
                    showMessage('success', data.data.message || settings.messages[`${formType}Success`]);

                    // Handle successful login
                    if (formType === 'login' && settings.redirectAfterLogin) {
                        setTimeout(() => {
                            window.location.href = data.data.redirect_url || settings.redirectUrl || window.location.href;
                        }, 1000);
                    }

                    // Reset form
                    form.reset();

                    // Reset reCAPTCHA if enabled
                    if (settings.recaptchaEnabled && window.grecaptcha) {
                        grecaptcha.reset();
                    }
                } else {
                    showMessage('error', data.data.message || settings.messages[`${formType}Error`]);

                    // Reset reCAPTCHA if enabled
                    if (settings.recaptchaEnabled && window.grecaptcha) {
                        grecaptcha.reset();
                    }
                }
            })
            .catch(error => {
                hideLoading();
                showMessage('error', settings.messages.genericError);
                console.error('Login Popup Error:', error);

                // Reset reCAPTCHA if enabled
                if (settings.recaptchaEnabled && window.grecaptcha) {
                    grecaptcha.reset();
                }
            });
    }

    /**
     * Validate form fields
     *
     * @param {HTMLFormElement} form The form element
     * @param {string} formType The form type (login, register, reset)
     * @return {boolean} Whether the form is valid
     */
    function validateForm(form, formType) {
        let isValid = true;

        // Reset previous errors
        const errorElements = form.querySelectorAll('.mc-login-popup-form-error');
        errorElements.forEach(error => {
            error.classList.remove('is-visible');
        });

        const inputs = form.querySelectorAll('input:not([type="hidden"])');
        inputs.forEach(input => {
            input.classList.remove('has-error');

            // Check required fields
            if (input.required && !input.value.trim()) {
                showInputError(input, settings.messages.requiredField);
                isValid = false;
            }

            // Validate email
            if (input.type === 'email' && input.value.trim()) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(input.value.trim())) {
                    showInputError(input, settings.messages.emailInvalid);
                    isValid = false;
                }
            }

            // Validate password confirmation
            if (formType === 'register' && input.name === 'password_confirm') {
                const passwordField = form.querySelector('input[name="password"]');
                if (passwordField && input.value !== passwordField.value) {
                    showInputError(input, settings.messages.passwordMismatch);
                    isValid = false;
                }
            }
        });

        return isValid;
    }

    /**
     * Show error message for an input field
     *
     * @param {HTMLInputElement} input The input element
     * @param {string} message The error message
     */
    function showInputError(input, message) {
        input.classList.add('has-error');

        const errorElement = input.parentNode.querySelector('.mc-login-popup-form-error');
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.classList.add('is-visible');
        }
    }

    /**
     * Check password strength
     *
     * @param {Event} e Input event
     */
    function checkPasswordStrength(e) {
        const password = e.target.value;
        const strengthMeter = document.querySelector('.mc-login-popup-password-strength');

        if (!strengthMeter) {
            return;
        }

        // Remove previous classes
        strengthMeter.className = 'mc-login-popup-password-strength';

        // Check password strength
        let strength = 0;

        // Length check
        if (password.length >= 8) {
            strength += 1;
        }

        // Complexity checks
        if (password.match(/[a-z]/) && password.match(/[A-Z]/)) {
            strength += 1;
        }

        if (password.match(/\d/)) {
            strength += 1;
        }

        if (password.match(/[^a-zA-Z\d]/)) {
            strength += 1;
        }

        // Update strength meter
        switch (strength) {
            case 0:
            case 1:
                strengthMeter.textContent = 'Very Weak';
                strengthMeter.classList.add('very-weak');
                break;
            case 2:
                strengthMeter.textContent = 'Weak';
                strengthMeter.classList.add('weak');
                break;
            case 3:
                strengthMeter.textContent = 'Medium';
                strengthMeter.classList.add('medium');
                break;
            case 4:
                strengthMeter.textContent = 'Strong';
                strengthMeter.classList.add('strong');
                break;
        }
    }

    /**
     * Validate password confirmation
     *
     * @param {Event} e Input event
     */
    function validatePasswordConfirmation(e) {
        const confirmPassword = e.target;
        const password = document.querySelector('.mc-login-popup-form-register input[name="password"]');

        if (!password) {
            return;
        }

        if (confirmPassword.value !== password.value) {
            showInputError(confirmPassword, settings.messages.passwordMismatch);
        } else {
            confirmPassword.classList.remove('has-error');

            const errorElement = confirmPassword.parentNode.querySelector('.mc-login-popup-form-error');
            if (errorElement) {
                errorElement.classList.remove('is-visible');
            }
        }
    }

    /**
     * Initialize reCAPTCHA
     */
    function initRecaptcha() {
        if (!window.grecaptcha || !settings.recaptchaSiteKey) {
            return;
        }

        const recaptchaContainers = document.querySelectorAll('.mc-login-popup-recaptcha');
        recaptchaContainers.forEach(container => {
            grecaptcha.render(container, {
                sitekey: settings.recaptchaSiteKey
            });
        });
    }

    /**
     * Show loading state
     */
    function showLoading() {
        if (elements.loading) {
            elements.loading.classList.add('is-active');
        }

        // Hide forms
        Object.values(elements.forms).forEach(form => {
            if (form) {
                form.style.display = 'none';
            }
        });
    }

    /**
     * Hide loading state
     */
    function hideLoading() {
        if (elements.loading) {
            elements.loading.classList.remove('is-active');
        }

        // Show active form
        Object.values(elements.forms).forEach(form => {
            if (form && form.classList.contains('is-active')) {
                form.style.display = '';
            }
        });
    }

    /**
     * Show message
     *
     * @param {string} type Message type (success, error, info)
     * @param {string} text Message text
     */
    function showMessage(type, text) {
        hideMessages();

        const messageElement = document.createElement('div');
        messageElement.className = `mc-login-popup-message mc-login-popup-message-${type}`;
        messageElement.textContent = text;

        const activeForm = document.querySelector('.mc-login-popup-form.is-active');
        if (activeForm) {
            activeForm.insertBefore(messageElement, activeForm.firstChild);
        }
    }

    /**
     * Hide all messages
     */
    function hideMessages() {
        const messages = document.querySelectorAll('.mc-login-popup-message');
        messages.forEach(message => {
            message.remove();
        });
    }

    /**
     * Reset all forms
     */
    function resetForms() {
        // Reset form fields
        elements.formSubmits.forEach(form => {
            form.reset();
        });

        // Hide error messages
        const errorElements = document.querySelectorAll('.mc-login-popup-form-error');
        errorElements.forEach(error => {
            error.classList.remove('is-visible');
        });

        // Remove error classes
        const inputs = document.querySelectorAll('.mc-login-popup-form-control');
        inputs.forEach(input => {
            input.classList.remove('has-error');
        });

        // Hide messages
        hideMessages();

        // Reset reCAPTCHA if enabled
        if (settings.recaptchaEnabled && window.grecaptcha) {
            grecaptcha.reset();
        }

        // Show login form by default
        Object.values(elements.forms).forEach(form => {
            if (form) {
                form.classList.remove('is-active');
            }
        });

        if (elements.forms.login) {
            elements.forms.login.classList.add('is-active');
        }

        // Update modal title
        const modalTitle = document.querySelector('.mc-login-popup-modal-title');
        if (modalTitle) {
            modalTitle.textContent = 'Log In';
        }
    }

    // Expose public API
    window.MesmericCommerceLoginPopup = {
        init: init,
        openModal: openModal,
        closeModal: closeModal
    };
})();
