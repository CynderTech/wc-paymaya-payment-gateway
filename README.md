# Maya Business Plugin

This is the repository of the official Maya Business payment gateway plugin

## Give your customers a better online checkout experience

Installation guide can be found [here](https://s3-us-west-2.amazonaws.com/developers.paymaya.com.pg/plugins/WooCommerce+Plugin+Installation+Guide+2.0.pdf)

With Maya Checkout, your website or app can directly accept credit and debit cards, e-wallet, and other emerging payment solutions.
* Mastercard
* Visa
* JCB
* WeChat Pay
* Pay With Maya

### Features
* Payments via Maya Checkout
* Full 3DS Support and PCI-DSS Compliant
* Checkout page customizations
* Voids and Refunds
* Straight purchase or Authorization & Capture

### Don't have an account yet? [Click here to get started](https://enterprise.paymaya.com/solutions/plugins/woocommerce)

### Version Compatibility
This version (1.3.4) is currently compatible with the following WordPress and WooCommerce version:
* WordPress 6.8.2
* WooCommerce 10.1.2

### Webhooks behind a proxy or CDN
The webhook endpoint only accepts requests from Maya's published IP addresses. The sender's IP is the connection address (`REMOTE_ADDR`); forwarding headers like `X-Forwarded-For` are not trusted because clients can set them. Cloudflare is detected automatically: `CF-Connecting-IP` is used when the request comes from a Cloudflare edge IP.

If your site sits behind any other reverse proxy or load balancer, `REMOTE_ADDR` will be the proxy's address and Maya's webhooks will be rejected. Resolve the real client IP with the `cynder_paymaya_webhook_source_ip` filter, for example in a small mu-plugin:

```php
add_filter('cynder_paymaya_webhook_source_ip', function ($ip) {
    // Only trust this header if your origin accepts traffic from the proxy alone.
    if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        return trim($_SERVER['HTTP_X_REAL_IP']);
    }
    return $ip;
});
```

### Running the tests
```
composer install
vendor/bin/phpunit
```
