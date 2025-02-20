<?php
declare(strict_types=1);

namespace MesmericCommerce\WooCommerce\Traits;

/**
 * WooCommerce Caching Trait
 *
 * Provides caching functionality for WooCommerce data.
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/woocommerce/includes/traits
 * @since      1.0.0
 */
trait MC_WC_Caching {
    /**
     * Cache group prefix
     *
     * @since 1.0.0
     * @var string
     */
    private string $cache_group = 'mc_wc_cache';

    /**
     * Cache product data
     *
     * @since 1.0.0
     * @param \WC_Product|false $product The product object or false
     * @return \WC_Product|false
     */
    public function cache_product_data($product) {
        if (!$product instanceof \WC_Product) {
            return $product;
        }

        $cache_key = 'product_' . $product->get_id();
        wp_cache_set($cache_key, $product, $this->cache_group);

        return $product;
    }

    /**
     * Clear product cache
     *
     * @since 1.0.0
     * @param int $product_id The product ID
     * @return void
     */
    public function clear_product_cache(int $product_id): void {
        $cache_key = 'product_' . $product_id;
        wp_cache_delete($cache_key, $this->cache_group);
    }

    /**
     * Cache cart fragments
     *
     * @since 1.0.0
     * @param array<string, string> $fragments Cart fragments
     * @return array<string, string>
     */
    public function cache_cart_fragments(array $fragments): array {
        $cache_key = 'cart_fragments_' . get_current_user_id();
        wp_cache_set($cache_key, $fragments, $this->cache_group, 5 * MINUTE_IN_SECONDS);

        return $fragments;
    }

    /**
     * Get cached cart fragments
     *
     * @since 1.0.0
     * @return array<string, string>|false
     */
    public function get_cached_cart_fragments() {
        $cache_key = 'cart_fragments_' . get_current_user_id();
        return wp_cache_get($cache_key, $this->cache_group);
    }

    /**
     * Cache query results
     *
     * @since 1.0.0
     * @param string $query_key Unique query identifier
     * @param mixed  $results   Query results
     * @param int    $expire    Cache expiration in seconds
     * @return void
     */
    public function cache_query_results(string $query_key, $results, int $expire = 3600): void {
        $cache_key = 'query_' . md5($query_key);
        wp_cache_set($cache_key, $results, $this->cache_group, $expire);
    }

    /**
     * Get cached query results
     *
     * @since 1.0.0
     * @param string $query_key Unique query identifier
     * @return mixed|false
     */
    public function get_cached_query_results(string $query_key) {
        $cache_key = 'query_' . md5($query_key);
        return wp_cache_get($cache_key, $this->cache_group);
    }

    /**
     * Clear all caches
     *
     * @since 1.0.0
     * @return void
     */
    public function clear_all_caches(): void {
        wp_cache_flush();
    }

    /**
     * Clear specific cache group
     *
     * @since 1.0.0
     * @param string $group Cache group to clear
     * @return void
     */
    public function clear_cache_group(string $group): void {
        wp_cache_delete_group($group);
    }
}
