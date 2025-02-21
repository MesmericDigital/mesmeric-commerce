/**
 * Mobile Menu JavaScript
 *
 * Handles mobile menu functionality and cart count updates.
 */

document.addEventListener('DOMContentLoaded', () => {
    // Update cart count when fragments are refreshed
    document.addEventListener('wc_fragments_refreshed', () => {
        const cartCount = window?.wc?.cart?.cart_contents_count || 0;
        window.mcMobileMenu = {
            ...window.mcMobileMenu,
            cart_count: cartCount
        };
    });

    // Handle viewport changes
    const handleViewportChange = () => {
        const menu = document.querySelector('.mc-mobile-menu');
        if (!menu) return;

        const breakpoint = window.mcMobileMenu?.settings?.breakpoint || 'md';
        const breakpointValue = parseInt(
            getComputedStyle(document.documentElement)
                .getPropertyValue(`--breakpoint-${breakpoint}`)
        );

        menu.classList.toggle('hidden', window.innerWidth >= breakpointValue);
    };

    // Listen for resize events
    let resizeTimeout;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(handleViewportChange, 250);
    });

    // Initial viewport check
    handleViewportChange();
});
