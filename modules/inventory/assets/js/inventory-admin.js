/* global mcInventoryData */
'use strict';

const InventoryAdmin = {
    init() {
        this.bindEvents();
        this.initTabs();
    },

    bindEvents() {
        // Tab navigation
        document.querySelectorAll('.mc-inventory-tabs .nav-tab').forEach(tab => {
            tab.addEventListener('click', this.handleTabClick.bind(this));
        });

        // Stock updates
        document.querySelectorAll('.update-stock').forEach(btn => {
            btn.addEventListener('click', this.handleStockUpdate.bind(this));
        });

        // Modal events
        document.addEventListener('click', e => {
            if (e.target.matches('.mc-modal .cancel')) {
                this.closeModal();
            }
        });

        document.addEventListener('submit', e => {
            if (e.target.matches('.stock-form')) {
                this.handleStockSubmit(e);
            }
        });
    },

    initTabs() {
        document.querySelectorAll('.mc-inventory-tabs .tab-content:not(.active)').forEach(tab => {
            tab.style.display = 'none';
        });
    },

    handleTabClick(e) {
        e.preventDefault();
        const tab = e.currentTarget;
        const target = tab.getAttribute('href').substring(1);

        document.querySelectorAll('.mc-inventory-tabs .nav-tab').forEach(t => {
            t.classList.remove('nav-tab-active');
        });
        tab.classList.add('nav-tab-active');

        document.querySelectorAll('.mc-inventory-tabs .tab-content').forEach(content => {
            content.style.display = 'none';
        });
        document.getElementById(target).style.display = 'block';
    },

    handleStockUpdate(e) {
        const button = e.currentTarget;
        const productId = button.dataset.productId;
        const row = button.closest('tr');
        const currentStock = row.querySelector('td:nth-child(3)').textContent;

        const template = document.getElementById('tmpl-stock-modal').innerHTML;
        const html = this.renderTemplate(template, { stock_quantity: currentStock });
        const modal = document.createElement('div');
        modal.innerHTML = html;
        document.body.appendChild(modal.firstElementChild);
        modal.firstElementChild.style.display = 'block';

        // Store product ID on form
        const form = modal.querySelector('.stock-form');
        form.dataset.productId = productId;
    },

    async handleStockSubmit(e) {
        e.preventDefault();
        const form = e.currentTarget;
        const productId = form.dataset.productId;
        const quantity = form.querySelector('[name="quantity"]').value;
        const note = form.querySelector('[name="note"]').value;

        try {
            const response = await fetch(mcInventoryData.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'woocommerce_update_product_stock',
                    product_id: productId,
                    stock_quantity: quantity,
                    _ajax_nonce: mcInventoryData.nonce
                })
            });

            const data = await response.json();
            if (data.success) {
                // Update stock in table
                const row = document.querySelector(`[data-product-id="${productId}"]`).closest('tr');
                row.querySelector('td:nth-child(3)').textContent = quantity;

                // Add to recent changes
                this.addStockChange(productId, data.old_stock, quantity, note);

                this.closeModal();
                window.location.reload();
            } else {
                alert(data.data.error);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error updating stock');
        }
    },

    async addStockChange(productId, oldStock, newStock, note) {
        try {
            await fetch(mcInventoryData.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'mc_add_stock_change',
                    product_id: productId,
                    old_stock: oldStock,
                    new_stock: newStock,
                    note: note,
                    nonce: mcInventoryData.nonce
                })
            });
        } catch (error) {
            console.error('Error:', error);
        }
    },

    closeModal() {
        document.querySelectorAll('.mc-modal').forEach(modal => modal.remove());
    },

    renderTemplate(template, data) {
        return template.replace(/\{\{(\w+)\}\}/g, (match, key) => data[key] || '');
    }
};

document.addEventListener('DOMContentLoaded', () => {
    InventoryAdmin.init();
});
