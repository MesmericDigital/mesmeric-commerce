/* global mcFaqData */
'use strict';

const FAQAdmin = {
    init() {
        this.bindEvents();
        this.initTabs();
        this.initSelect2();
    },

    bindEvents() {
        // Tab navigation
        document.querySelectorAll('.mc-faq-tabs .nav-tab').forEach(tab => {
            tab.addEventListener('click', this.handleTabClick.bind(this));
        });

        // FAQ actions
        document.querySelector('.add-faq').addEventListener('click', () => this.showFaqModal());
        document.querySelectorAll('.edit-faq').forEach(btn => {
            btn.addEventListener('click', e => this.handleFaqEdit(e));
        });
        document.querySelectorAll('.delete-faq').forEach(btn => {
            btn.addEventListener('click', e => this.handleFaqDelete(e));
        });

        // Category actions
        document.querySelector('.add-category').addEventListener('click', () => this.showCategoryModal());
        document.querySelectorAll('.edit-category').forEach(btn => {
            btn.addEventListener('click', e => this.handleCategoryEdit(e));
        });
        document.querySelectorAll('.delete-category').forEach(btn => {
            btn.addEventListener('click', e => this.handleCategoryDelete(e));
        });

        // Modal events
        document.addEventListener('click', e => {
            if (e.target.matches('.mc-modal .cancel')) {
                this.closeModal();
            }
        });

        document.addEventListener('submit', e => {
            if (e.target.matches('.faq-form')) {
                this.handleFaqSubmit(e);
            } else if (e.target.matches('.category-form')) {
                this.handleCategorySubmit(e);
            }
        });
    },

    initTabs() {
        document.querySelectorAll('.tab-content:not(.active)').forEach(tab => {
            tab.style.display = 'none';
        });
    },

    initSelect2() {
        if (window.jQuery && window.jQuery.fn.select2) {
            jQuery('#faq-category, #faq-products').select2({
                width: '100%',
                placeholder: 'Select items'
            });
        }
    },

    handleTabClick(e) {
        e.preventDefault();
        const tab = e.currentTarget;
        const target = tab.getAttribute('href').substring(1);

        document.querySelectorAll('.mc-faq-tabs .nav-tab').forEach(t => {
            t.classList.remove('nav-tab-active');
        });
        tab.classList.add('nav-tab-active');

        document.querySelectorAll('.tab-content').forEach(content => {
            content.style.display = 'none';
        });
        document.getElementById(target).style.display = 'block';
    },

    showFaqModal(data = {}) {
        const template = document.getElementById('tmpl-faq-modal').innerHTML;
        const html = this.renderTemplate(template, data);
        const modal = document.createElement('div');
        modal.innerHTML = html;
        document.body.appendChild(modal.firstElementChild);
        modal.firstElementChild.style.display = 'block';

        // Initialize select2 for the modal
        this.initSelect2();

        // Set selected values if editing
        if (data.id) {
            if (data.categories) {
                jQuery('#faq-category').val(data.categories).trigger('change');
            }
            if (data.products) {
                jQuery('#faq-products').val(data.products).trigger('change');
            }
        }
    },

    showCategoryModal(data = {}) {
        const template = document.getElementById('tmpl-category-modal').innerHTML;
        const html = this.renderTemplate(template, data);
        const modal = document.createElement('div');
        modal.innerHTML = html;
        document.body.appendChild(modal.firstElementChild);
        modal.firstElementChild.style.display = 'block';
    },

    async handleFaqEdit(e) {
        e.preventDefault();
        const row = e.target.closest('tr');
        const faqId = row.dataset.faqId;

        try {
            const response = await fetch(mcFaqData.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'mc_get_faq',
                    faq_id: faqId,
                    nonce: mcFaqData.nonce
                })
            });

            const data = await response.json();
            if (data.success) {
                this.showFaqModal(data.data);
            } else {
                alert(data.data.error);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error loading FAQ data');
        }
    },

    async handleFaqDelete(e) {
        e.preventDefault();
        if (!confirm('Are you sure you want to delete this FAQ?')) {
            return;
        }

        const row = e.target.closest('tr');
        const faqId = row.dataset.faqId;

        try {
            const response = await fetch(mcFaqData.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'mc_delete_faq',
                    faq_id: faqId,
                    nonce: mcFaqData.nonce
                })
            });

            const data = await response.json();
            if (data.success) {
                row.remove();
            } else {
                alert(data.data.error);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error deleting FAQ');
        }
    },

    async handleFaqSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const faqId = form.closest('.mc-modal').dataset.faqId;

        try {
            const response = await fetch(mcFaqData.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'mc_save_faq',
                    faq_id: faqId || '',
                    question: formData.get('question'),
                    answer: formData.get('answer'),
                    categories: formData.getAll('category[]'),
                    products: formData.getAll('products[]'),
                    nonce: mcFaqData.nonce
                })
            });

            const data = await response.json();
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.data.error);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error saving FAQ');
        }
    },

    async handleCategoryEdit(e) {
        e.preventDefault();
        const row = e.target.closest('tr');
        const categoryId = row.dataset.categoryId;

        try {
            const response = await fetch(mcFaqData.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'mc_get_category',
                    category_id: categoryId,
                    nonce: mcFaqData.nonce
                })
            });

            const data = await response.json();
            if (data.success) {
                this.showCategoryModal(data.data);
            } else {
                alert(data.data.error);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error loading category data');
        }
    },

    async handleCategoryDelete(e) {
        e.preventDefault();
        if (!confirm('Are you sure you want to delete this category?')) {
            return;
        }

        const row = e.target.closest('tr');
        const categoryId = row.dataset.categoryId;

        try {
            const response = await fetch(mcFaqData.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'mc_delete_category',
                    category_id: categoryId,
                    nonce: mcFaqData.nonce
                })
            });

            const data = await response.json();
            if (data.success) {
                row.remove();
            } else {
                alert(data.data.error);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error deleting category');
        }
    },

    async handleCategorySubmit(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const categoryId = form.closest('.mc-modal').dataset.categoryId;

        try {
            const response = await fetch(mcFaqData.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'mc_save_category',
                    category_id: categoryId || '',
                    name: formData.get('name'),
                    description: formData.get('description'),
                    nonce: mcFaqData.nonce
                })
            });

            const data = await response.json();
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.data.error);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error saving category');
        }
    },

    closeModal() {
        document.querySelectorAll('.mc-modal').forEach(modal => modal.remove());
    },

    renderTemplate(template, data) {
        return template.replace(/\{\{(\w+)\}\}/g, (match, key) => {
            return data[key] !== undefined ? data[key] : '';
        });
    }
};

document.addEventListener('DOMContentLoaded', () => {
    FAQAdmin.init();
});
