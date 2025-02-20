<?php
/**
 * Wishlists list template
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/modules/wishlist/views
 *
 * @var array $wishlists User's wishlists
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

?>
<div class="mc-wishlists" x-data="{
    showNewForm: false,
    newListName: '',
    newListDesc: '',
    isCreating: false,
    isDeleting: false,
    isSharing: false,
    shareUrl: '',
    async createWishlist() {
        if (!this.newListName) return;
        this.isCreating = true;

        try {
            const response = await fetch(mcWishlistData.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'mc_create_wishlist',
                    nonce: mcWishlistData.nonce,
                    name: this.newListName,
                    description: this.newListDesc
                })
            });

            const data = await response.json();
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.data);
            }
        } catch (error) {
            console.error('Error:', error);
            alert(mcWishlistData.i18n.error);
        } finally {
            this.isCreating = false;
        }
    },
    async deleteWishlist(wishlistId) {
        if (!confirm(mcWishlistData.i18n.confirmDelete)) return;
        this.isDeleting = true;

        try {
            const response = await fetch(mcWishlistData.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'mc_delete_wishlist',
                    nonce: mcWishlistData.nonce,
                    wishlist_id: wishlistId
                })
            });

            const data = await response.json();
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.data);
            }
        } catch (error) {
            console.error('Error:', error);
            alert(mcWishlistData.i18n.error);
        } finally {
            this.isDeleting = false;
        }
    },
    async shareWishlist(wishlistId) {
        this.isSharing = true;

        try {
            const response = await fetch(mcWishlistData.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'mc_share_wishlist',
                    nonce: mcWishlistData.nonce,
                    wishlist_id: wishlistId
                })
            });

            const data = await response.json();
            if (data.success) {
                this.shareUrl = data.data.share_url;
            } else {
                alert(data.data);
            }
        } catch (error) {
            console.error('Error:', error);
            alert(mcWishlistData.i18n.error);
        } finally {
            this.isSharing = false;
        }
    }
}">
    <?php if (!is_user_logged_in()): ?>
        <div class="mc-wishlists__login-prompt">
            <p><?php esc_html_e('Please log in to view your wishlists', 'mesmeric-commerce'); ?></p>
            <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="mc-wishlists__login-link">
                <?php esc_html_e('Log In', 'mesmeric-commerce'); ?>
            </a>
        </div>
    <?php else: ?>
        <div class="mc-wishlists__header">
            <h2 class="mc-wishlists__title"><?php esc_html_e('My Wishlists', 'mesmeric-commerce'); ?></h2>
            <button type="button"
                    @click="showNewForm = true"
                    class="mc-wishlists__new-button"
                    x-show="!showNewForm">
                <?php esc_html_e('Create New Wishlist', 'mesmeric-commerce'); ?>
            </button>
        </div>

        <div class="mc-wishlists__new-form" x-show="showNewForm">
            <form @submit.prevent="createWishlist">
                <div class="mc-wishlists__form-group">
                    <label for="new-list-name" class="mc-wishlists__label">
                        <?php esc_html_e('Name', 'mesmeric-commerce'); ?>
                    </label>
                    <input type="text"
                           id="new-list-name"
                           x-model="newListName"
                           class="mc-wishlists__input"
                           required>
                </div>
                <div class="mc-wishlists__form-group">
                    <label for="new-list-desc" class="mc-wishlists__label">
                        <?php esc_html_e('Description (optional)', 'mesmeric-commerce'); ?>
                    </label>
                    <textarea id="new-list-desc"
                              x-model="newListDesc"
                              class="mc-wishlists__textarea"></textarea>
                </div>
                <div class="mc-wishlists__form-actions">
                    <button type="button"
                            @click="showNewForm = false"
                            class="mc-wishlists__cancel-button">
                        <?php esc_html_e('Cancel', 'mesmeric-commerce'); ?>
                    </button>
                    <button type="submit"
                            class="mc-wishlists__submit-button"
                            :disabled="!newListName || isCreating">
                        <span x-show="!isCreating">
                            <?php esc_html_e('Create Wishlist', 'mesmeric-commerce'); ?>
                        </span>
                        <span x-show="isCreating">
                            <?php esc_html_e('Creating...', 'mesmeric-commerce'); ?>
                        </span>
                    </button>
                </div>
            </form>
        </div>

        <?php if (empty($wishlists)): ?>
            <p class="mc-wishlists__empty">
                <?php esc_html_e('You haven\'t created any wishlists yet.', 'mesmeric-commerce'); ?>
            </p>
        <?php else: ?>
            <div class="mc-wishlists__grid">
                <?php foreach ($wishlists as $wishlist): ?>
                    <div class="mc-wishlists__item">
                        <div class="mc-wishlists__item-header">
                            <h3 class="mc-wishlists__item-title">
                                <a href="<?php echo esc_url(add_query_arg('id', $wishlist['id'], get_permalink())); ?>">
                                    <?php echo esc_html($wishlist['name']); ?>
                                </a>
                            </h3>
                            <div class="mc-wishlists__item-actions">
                                <button type="button"
                                        @click="shareWishlist(<?php echo esc_js($wishlist['id']); ?>)"
                                        class="mc-wishlists__share-button"
                                        :disabled="isSharing">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"></path>
                                        <polyline points="16 6 12 2 8 6"></polyline>
                                        <line x1="12" y1="2" x2="12" y2="15"></line>
                                    </svg>
                                </button>
                                <button type="button"
                                        @click="deleteWishlist(<?php echo esc_js($wishlist['id']); ?>)"
                                        class="mc-wishlists__delete-button"
                                        :disabled="isDeleting">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <?php if (!empty($wishlist['description'])): ?>
                            <p class="mc-wishlists__item-desc">
                                <?php echo esc_html($wishlist['description']); ?>
                            </p>
                        <?php endif; ?>
                        <div class="mc-wishlists__item-meta">
                            <span class="mc-wishlists__item-date">
                                <?php
                                printf(
                                    /* translators: %s: date */
                                    esc_html__('Created on %s', 'mesmeric-commerce'),
                                    date_i18n(get_option('date_format'), strtotime($wishlist['created_at']))
                                );
                                ?>
                            </span>
                            <span class="mc-wishlists__item-visibility">
                                <?php echo esc_html($wishlist['visibility']); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="mc-wishlists__share-modal"
                 x-show="shareUrl"
                 @click.away="shareUrl = ''">
                <div class="mc-wishlists__share-content">
                    <h4 class="mc-wishlists__share-title">
                        <?php esc_html_e('Share Wishlist', 'mesmeric-commerce'); ?>
                    </h4>
                    <div class="mc-wishlists__share-url">
                        <input type="text"
                               :value="shareUrl"
                               readonly
                               class="mc-wishlists__share-input"
                               @click="$event.target.select()">
                        <button type="button"
                                @click="navigator.clipboard.writeText(shareUrl)"
                                class="mc-wishlists__copy-button">
                            <?php esc_html_e('Copy', 'mesmeric-commerce'); ?>
                        </button>
                    </div>
                    <button type="button"
                            @click="shareUrl = ''"
                            class="mc-wishlists__close-button">
                        <?php esc_html_e('Close', 'mesmeric-commerce'); ?>
                    </button>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
