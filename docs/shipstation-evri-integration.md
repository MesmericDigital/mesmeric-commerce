# ShipStation and Evri Integration Guide for WooCommerce

To enhance the integration of ShipStation with your WooCommerce store and streamline your shipping process with Evri in the UK, consider implementing the following code snippets and configurations. These will automate tasks, provide seamless tracking, and improve overall efficiency.

## 1. Automatically Add Tracking Information to WooCommerce Orders

When ShipStation processes a shipment, it sends tracking details back to WooCommerce. To display this information prominently in customer accounts and emails, you can use the following code snippet:

```php
// Add tracking information to WooCommerce order emails and customer account
function add_tracking_info_to_emails( $order, $sent_to_admin, $plain_text, $email ) {
    if ( $tracking_number = get_post_meta( $order->get_id(), '_tracking_number', true ) ) {
        if ( $email->id == 'customer_completed_order' ) {
            echo '<p>Your order has been shipped. Your tracking number is: ' . esc_html( $tracking_number ) . '.</p>';
        }
    }
}
add_action( 'woocommerce_email_after_order_table', 'add_tracking_info_to_emails', 20, 4 );

// Save tracking number to order meta when added by ShipStation
function save_tracking_number( $order_id, $tracking_number ) {
    update_post_meta( $order_id, '_tracking_number', sanitize_text_field( $tracking_number ) );
}
add_action( 'shipstation_tracking_number_added', 'save_tracking_number', 10, 2 );
```

This code ensures that tracking numbers are saved to order metadata and included in the 'Order Completed' email sent to customers. Replace the placeholder action `shipstation_tracking_number_added` with the actual action hook provided by ShipStation or your specific integration.

## 2. Automate Shipping Label Creation for Evri

To automate the creation of Evri shipping labels directly from your WooCommerce dashboard, consider integrating with a service like Shipmate. Shipmate offers a plugin that facilitates this process. Here's how you can set it up:

* Install the Shipmate Plugin: Download and install the Shipmate plugin from the Shipmate website.
* Configure the Plugin: Enter your API credentials and configure shipping services to include Evri.
* Automate Label Generation: With the plugin active, shipping labels for Evri can be generated automatically when an order is marked as 'Processing' or 'Completed'.

This setup reduces manual intervention, ensuring that labels are created promptly and accurately.

## 3. Customize Shipping Methods Displayed at Checkout

To offer Evri shipping options to customers at checkout, you can add custom shipping methods to WooCommerce. Here's a code snippet to register a custom Evri shipping method:

```php
// Register Evri Shipping Method
function register_evri_shipping_method( $methods ) {
    require_once 'class-wc-shipping-evri.php';
    $methods['evri'] = 'WC_Shipping_Evri';
    return $methods;
}
add_filter( 'woocommerce_shipping_methods', 'register_evri_shipping_method' );

// Define Evri Shipping Method Class
class WC_Shipping_Evri extends WC_Shipping_Method {
    public function __construct() {
        $this->id                 = 'evri';
        $this->method_title       = __( 'Evri Shipping', 'woocommerce' );
        $this->method_description = __( 'Custom Shipping Method for Evri', 'woocommerce' );
        $this->enabled            = 'yes';
        $this->title              = __( 'Evri Shipping', 'woocommerce' );

        $this->init();
    }

    function init() {
        // Load the settings API
        $this->init_form_fields();
        $this->init_settings();

        // Save settings in admin if you have any defined
        add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
    }

    public function calculate_shipping( $package = array() ) {
        $rate = array(
            'label'   => $this->title,
            'cost'    => '5.00', // Flat rate shipping cost
            'calc_tax' => 'per_item'
        );

        // Register the rate
        $this->add_rate( $rate );
    }
}
```

This code defines a custom shipping method for Evri with a flat rate of £5.00. Adjust the `calculate_shipping` function to implement dynamic pricing based on weight, dimensions, or destination as needed.

## 4. Automate Order Status Updates Upon Shipment

To keep customers informed, it's beneficial to update the order status automatically when a shipment is created. Implement the following code to change the order status to 'Completed' once the tracking number is added:

```php
// Update order status to Completed when tracking number is added
function update_order_status_on_tracking( $order_id, $tracking_number ) {
    $order = wc_get_order( $order_id );
    if ( $order && $order->get_status() !== 'completed' ) {
        $order->update_status( 'completed', __( 'Order shipped. Tracking number: ' . $tracking_number, 'woocommerce' ) );
    }
}
add_action( 'shipstation_tracking_number_added', 'update_order_status_on_tracking', 10, 2 );
```

This function listens for the `shipstation_tracking_number_added` action and updates the order status accordingly. Ensure that the action hook matches the one used by your ShipStation integration.

## 5. Implement Webhooks for Real-Time Updates

For real-time communication between ShipStation and WooCommerce, set up webhooks to handle events such as label creation or tracking updates. Here's an example of how to handle a webhook for shipment creation:

```php
// Handle ShipStation webhook for shipment creation
function handle_shipstation_shipment_webhook() {
    $payload = @file_get_contents( 'php://input' );
    $data = json_decode( $payload, true );

    if ( isset( $data['resource_type'] ) && $data['resource_type'] === 'SHIP_NOTIFY' ) {
        $order_id = $data['resource']['orderId'];
        $tracking_number = $data['resource']['trackingNumber'];

        // Update order with tracking number
        update_post_meta( $order_id, '_tracking_number', sanitize_text_field( $tracking_number ) );
    }
}
```

> Note: This documentation is part of the Mesmeric Commerce plugin and provides integration guidance for ShipStation and Evri shipping services with WooCommerce.
