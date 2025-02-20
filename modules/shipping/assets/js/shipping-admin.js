/* global mcShippingData */
'use strict';

const ShippingAdmin = {
    init() {
        this.bindEvents();
        this.initTabs();
        this.initSortable();
    },

    bindEvents() {
        // Tab navigation
        document.querySelectorAll('.mc-shipping-tabs .nav-tab').forEach(tab => {
            tab.addEventListener('click', this.handleTabClick.bind(this));
        });

        // Zone management
        document.querySelector('.add-zone')?.addEventListener('click', this.showZoneModal.bind(this));
        document.querySelectorAll('.edit-zone').forEach(btn => {
            btn.addEventListener('click', this.handleEditZone.bind(this));
        });
        document.querySelectorAll('.delete-zone').forEach(btn => {
            btn.addEventListener('click', this.handleDeleteZone.bind(this));
        });

        // Rule management
        document.querySelector('.add-rule')?.addEventListener('click', this.showRuleModal.bind(this));
        document.querySelectorAll('.edit-rule').forEach(btn => {
            btn.addEventListener('click', this.handleEditRule.bind(this));
        });
        document.querySelectorAll('.delete-rule').forEach(btn => {
            btn.addEventListener('click', this.handleDeleteRule.bind(this));
        });

        // Modal events
        document.addEventListener('click', e => {
            if (e.target.matches('.mc-modal .cancel')) {
                this.closeModal();
            }
        });

        document.addEventListener('submit', e => {
            if (e.target.matches('.zone-form')) {
                this.handleSaveZone(e);
            } else if (e.target.matches('.rule-form')) {
                this.handleSaveRule(e);
            }
        });

        document.addEventListener('click', e => {
            if (e.target.matches('.add-condition')) {
                this.addCondition(e);
            } else if (e.target.matches('.remove-condition')) {
                this.removeCondition(e);
            }
        });
    },

    initTabs() {
        document.querySelectorAll('.mc-shipping-tabs .tab-content:not(.active)').forEach(tab => {
            tab.style.display = 'none';
        });
    },

    initSortable() {
        // Using Sortable.js instead of jQuery UI sortable
        document.querySelectorAll('.mc-shipping-zones-table tbody, .mc-shipping-rules-table tbody').forEach(el => {
            new Sortable(el, {
                handle: 'tr',
                animation: 150,
                onEnd: this.handleSortUpdate.bind(this)
            });
        });
    },

    handleTabClick(e) {
        e.preventDefault();
        const tab = e.currentTarget;
        const target = tab.getAttribute('href').substring(1);

        document.querySelectorAll('.mc-shipping-tabs .nav-tab').forEach(t => {
            t.classList.remove('nav-tab-active');
        });
        tab.classList.add('nav-tab-active');

        document.querySelectorAll('.mc-shipping-tabs .tab-content').forEach(content => {
            content.style.display = 'none';
        });
        document.getElementById(target).style.display = 'block';
    },

    showZoneModal(e, data = {}) {
        const template = document.getElementById('tmpl-zone-modal').innerHTML;
        const html = this.renderTemplate(template, data);
        const modal = document.createElement('div');
        modal.innerHTML = html;
        document.body.appendChild(modal.firstElementChild);
        modal.firstElementChild.style.display = 'block';
    },

    handleEditZone(e) {
        const row = e.currentTarget.closest('tr');
        const zoneId = row.dataset.zoneId;
        const data = this.getZoneData(zoneId);
        this.showZoneModal(e, data);
    },

    handleDeleteZone(e) {
        const row = e.currentTarget.closest('tr');
        const zoneId = row.dataset.zoneId;

        if (!confirm(mcShippingData.i18n.confirmDeleteZone)) {
            return;
        }

        this.deleteZone(zoneId);
    },

    showRuleModal(e, data = {}) {
        const template = document.getElementById('tmpl-rule-modal').innerHTML;
        const html = this.renderTemplate(template, data);
        const modal = document.createElement('div');
        modal.innerHTML = html;
        document.body.appendChild(modal.firstElementChild);
        modal.firstElementChild.style.display = 'block';
    },

    handleEditRule(e) {
        const row = e.currentTarget.closest('tr');
        const ruleId = row.dataset.ruleId;
        const data = this.getRuleData(ruleId);
        this.showRuleModal(e, data);
    },

    handleDeleteRule(e) {
        const row = e.currentTarget.closest('tr');
        const ruleId = row.dataset.ruleId;

        if (!confirm(mcShippingData.i18n.confirmDeleteRule)) {
            return;
        }

        this.deleteRule(ruleId);
    },

    handleSaveZone(e) {
        e.preventDefault();
        const form = e.currentTarget;
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        this.saveZone(data);
    },

    handleSaveRule(e) {
        e.preventDefault();
        const form = e.currentTarget;
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        this.saveRule(data);
    },

    addCondition(e) {
        const button = e.currentTarget;
        const list = button.parentElement.querySelector('.condition-list');
        const index = list.children.length;

        const template = document.getElementById('tmpl-condition').innerHTML;
        const html = this.renderTemplate(template, { index });
        const temp = document.createElement('div');
        temp.innerHTML = html;
        list.appendChild(temp.firstElementChild);
    },

    removeCondition(e) {
        const condition = e.currentTarget.closest('.condition');
        condition.remove();
        this.reindexConditions();
    },

    reindexConditions() {
        document.querySelectorAll('.condition-list .condition').forEach((condition, index) => {
            condition.querySelectorAll('[name^="conditions["]').forEach(input => {
                const name = input.getAttribute('name');
                input.setAttribute('name', name.replace(/\[\d+\]/, `[${index}]`));
            });
        });
    },

    closeModal() {
        document.querySelectorAll('.mc-modal').forEach(modal => modal.remove());
    },

    handleSortUpdate(e) {
        const table = e.target.closest('table');
        const isZones = table.classList.contains('mc-shipping-zones-table');
        const items = Array.from(table.querySelectorAll('tbody tr')).map(tr =>
            tr.dataset[isZones ? 'zoneId' : 'ruleId']
        );

        if (isZones) {
            this.updateZoneOrder(items);
        } else {
            this.updateRuleOrder(items);
        }
    },

    getZoneData(zoneId) {
        // Implement zone data retrieval
        return {};
    },

    getRuleData(ruleId) {
        return mcShippingData.rules.find(rule => rule.id === ruleId) || {};
    },

    async saveZone(data) {
        try {
            const response = await fetch(mcShippingData.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'mc_save_shipping_zone',
                    nonce: mcShippingData.nonce,
                    zone: JSON.stringify(data)
                })
            });

            const result = await response.json();
            if (result.success) {
                this.closeModal();
                window.location.reload();
            } else {
                alert(result.data);
            }
        } catch (error) {
            alert(mcShippingData.i18n.errorSaving);
        }
    },

    async deleteZone(zoneId) {
        try {
            const response = await fetch(mcShippingData.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'mc_delete_shipping_zone',
                    nonce: mcShippingData.nonce,
                    zone_id: zoneId
                })
            });

            const result = await response.json();
            if (result.success) {
                window.location.reload();
            } else {
                alert(result.data);
            }
        } catch (error) {
            alert(mcShippingData.i18n.errorDeleting);
        }
    },

    async saveRule(data) {
        try {
            const response = await fetch(mcShippingData.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'mc_save_shipping_rule',
                    nonce: mcShippingData.nonce,
                    rule: JSON.stringify(data)
                })
            });

            const result = await response.json();
            if (result.success) {
                this.closeModal();
                window.location.reload();
            } else {
                alert(result.data);
            }
        } catch (error) {
            alert(mcShippingData.i18n.errorSaving);
        }
    },

    async deleteRule(ruleId) {
        try {
            const response = await fetch(mcShippingData.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'mc_delete_shipping_rule',
                    nonce: mcShippingData.nonce,
                    rule_id: ruleId
                })
            });

            const result = await response.json();
            if (result.success) {
                window.location.reload();
            } else {
                alert(result.data);
            }
        } catch (error) {
            alert(mcShippingData.i18n.errorDeleting);
        }
    },

    async updateZoneOrder(items) {
        try {
            await fetch(mcShippingData.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'mc_update_zone_order',
                    nonce: mcShippingData.nonce,
                    order: JSON.stringify(items)
                })
            });
        } catch (error) {
            alert(mcShippingData.i18n.errorSaving);
        }
    },

    async updateRuleOrder(items) {
        try {
            await fetch(mcShippingData.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'mc_update_rule_order',
                    nonce: mcShippingData.nonce,
                    order: JSON.stringify(items)
                })
            });
        } catch (error) {
            alert(mcShippingData.i18n.errorSaving);
        }
    },

    renderTemplate(template, data) {
        return template.replace(/\{\{(\w+)\}\}/g, (match, key) => data[key] || '');
    }
};

document.addEventListener('DOMContentLoaded', () => {
    ShippingAdmin.init();
});
