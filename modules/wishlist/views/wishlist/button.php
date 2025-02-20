<?php
/**
 * Wishlist button template
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/modules/wishlist/views
 *
 * @var WC_Product $product   The product object
 * @var array      $wishlists User's wishlists
 */

if (  ! defined('ABSPATH' )) {
	exit; // Exit if accessed directly
}

?>
<div class="mc-wishlist-button-wrapper"
	x-data="{
		showDropdown: false,
		showNewForm: false,
		newListName: '',
		newListDesc: '',
		isAdding: false,
		async addToWishlist(wishlistId) {
			if (this.isAdding) return;
			this.isAdding = true;

			try {
				const response = await fetch(mcWishlistData.ajaxUrl, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
					},
					body: new URLSearchParams({
						action: 'mc_add_to_wishlist',
						nonce: mcWishlistData.nonce,
						product_id: <?php echo esc_js( $product->get_id() ); ?>,
                        wishlist_id: wishlistId
                    })
                });

                const data = await response.json();
                if (data.success) {
                    this.showDropdown = false;
                    this.showNewForm = false;
                } else {
                    alert(data.data);
                }
            } catch (error) {
                console.error('Error:', error);
                alert(mcWishlistData.i18n.error);
            } finally {
                this.isAdding = false;
            }
        },
        async createWishlist() {
            if (!this.newListName) {
                alert(mcWishlistData.i18n.nameRequired);
                return;
            }

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
            }
        }
    }"
    @click.away="showDropdown = false; showNewForm = false">

    <button type="button"
            class="mc-wishlist-button"
            @click="showDropdown = !showDropdown"
            :class="{ 'mc-wishlist-button--active': showDropdown }">
        <span class="mc-wishlist-button__icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
            </svg>
        </span>
        <span class="mc-wishlist-button__text">
            <?php esc_html_e( 'Add to Wishlist', 'mesmeric-commerce' ); ?>
        </span>
    </button>

    <div class="mc-wishlist-dropdown"
        x-show="showDropdown"
        x-transition:enter="mc-wishlist-dropdown--enter"
        x-transition:enter-start="mc-wishlist-dropdown--enter-start"
        x-transition:enter-end="mc-wishlist-dropdown--enter-end"
        x-transition:leave="mc-wishlist-dropdown--leave"
        x-transition:leave-start="mc-wishlist-dropdown--leave-start"
        x-transition:leave-end="mc-wishlist-dropdown--leave-end">

        <?php if (! is_user_logged_in() ) : ?>
            <div class="mc-wishlist-dropdown__login-prompt">
                <p><?php esc_html_e( 'Please log in to add items to your wishlist', 'mesmeric-commerce' ); ?></p>
                <a href="<?php echo esc_url( wp_login_url(get_permalink()) ); ?>" class="mc-wishlist-dropdown__login-link">
                    <?php esc_html_e('Log In', 'mesmeric-commerce'); ?>
                </a>
            </div>
        <?php else : ?>
            <?php if ( ! empty( $wishlists ) ) : ?>
                <ul class="mc-wishlist-dropdown__lists">
                    <?php foreach ( $wishlists as $wishlist ) : ?>
                        <li>
                            <button type="button"
                                    @click="addToWishlist(<?php echo esc_js( $wishlist['id'] ); ?>)"
                                    :disabled="isAdding"
                                    class="mc-wishlist-dropdown__list-button">
                                <?php echo esc_html( $wishlist['name'] ); ?>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <div class="mc-wishlist-dropdown__new">
                <?php if (  ! $showNewForm ) : ?>
                    <button type="button"
                            @click="showNewForm = true"
                            class="mc-wishlist-dropdown__new-button">
                        <?php esc_html_e( 'Create New Wishlist', 'mesmeric-commerce' ); ?>
                    </button>
                <?php else : ?>
                    <form @submit.prevent="createWishlist" class="mc-wishlist-dropdown__new-form">
                        <div class="mc-wishlist-dropdown__form-group">
                            <label for="new-list-name" class="mc-wishlist-dropdown__label">
                                <?php esc_html_e( 'Name', 'mesmeric-commerce' ); ?>
                            </label>
                            <input type="text"
                                    id="new-list-name"
                                    x-model="newListName"
                                    class="mc-wishlist-dropdown__input"
                                    required>
                        </div>
                        <div class="mc-wishlist-dropdown__form-group">
                            <label for="new-list-desc" class="mc-wishlist-dropdown__label">
                                <?php esc_html_e( 'Description (optional)', 'mesmeric-commerce' ); ?>
                            </label>
                            <textarea id="new-list-desc"
                                        x-model="newListDesc"
                                        class="mc-wishlist-dropdown__textarea"></textarea>
                        </div>
                        <div class="mc-wishlist-dropdown__form-actions">
                            <button type="button"
                                    @click="showNewForm = false"
                                    class="mc-wishlist-dropdown__cancel-button">
                                <?php esc_html_e( 'Cancel', 'mesmeric-commerce' ); ?>
                            </button>
                            <button type="submit"
                                    class="mc-wishlist-dropdown__submit-button"
                                    :disabled="!newListName || isAdding">
                                <?php esc_html_e( 'Create', 'mesmeric-commerce' ); ?>
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
