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
This version (1.4.1) is currently compatible with the following WordPress and WooCommerce version:
* WordPress 7.1
* WooCommerce 11.2.1
* PHP 7.2.5 or later. Tested on PHP 8.2.35, 8.3.36, 8.4.27 and 8.5.12 (the releases that fix CVE-2026-109618 and CVE-2026-109619). Keep PHP on a supported, patched release.

### Keeping your API keys out of the database
The secret key is never shown again after it is saved; leave the field blank to keep it. To keep the keys out of the database entirely, define them in `wp-config.php`. The settings fields are then locked:
```php
define('CYNDER_PAYMAYA_PUBLIC_KEY', 'pk-...');
define('CYNDER_PAYMAYA_SECRET_KEY', 'sk-...');
```

### Testing with Sandbox Mode
* Use **your own sandbox keys** from Maya Business Manager (Developer Sandbox Application Management). The shared test keys published in Maya's docs are used by many developers at once: other people's payments show up under your order numbers, and their settings changes overwrite your webhooks, so orders stay "Pending payment".
* In Sandbox Mode the webhook source-IP check is skipped, so tunnels such as ngrok work. The webhook signature and timestamp are still verified. Production always checks the IP.
* Webhook URLs are saved with the site address at the time you save. If your tunnel URL changes, update the three webhook URL fields and click **Save** to register the new URLs with Maya.

### Debug Mode
Debug logs mask buyer names, emails, phone numbers and addresses, but still contain order and payment details. Turn Debug Mode off when you are done.

### Developer hooks
* `cynder_paymaya_webhook_source_ip` (filter) - override the resolved webhook sender IP.
* `cynder_paymaya_webhook_public_keys` (filter) - add or replace Maya's webhook verification public keys (e.g. after Maya rotates them). Receives the key list and whether Sandbox Mode is on.

### Webhooks behind a proxy or CDN
Maya only sends webhooks from its own IP addresses, so the plugin checks the sender's IP. The IP used is the connection address; client-settable headers like `X-Forwarded-For` are not trusted on their own.

**Symptom:** customers pay but orders stay "Pending payment", and the WooCommerce log (WooCommerce > Status > Logs) shows "Webhook has a valid Maya signature but came from IP ...". An error notice also appears in the WordPress admin.

**Cloudflare connecting directly to your server:** works automatically, nothing to do. If a load balancer sits between Cloudflare and your server, follow the steps below using the load balancer's IPs (the visitor's IP is then taken from Cloudflare's `CF-Connecting-IP` header).

**Any other proxy, CDN or load balancer:** go to WooCommerce > Settings > Payments > Maya and fill in the **Proxy / CDN** section:
1. **Trusted Proxy Header** - the header your proxy uses to pass on the visitor's IP (`X-Forwarded-For` for most load balancers, such as AWS ALB/ELB and nginx).
2. **Trusted Proxy IPs** - the IP addresses or ranges (CIDR) of your proxy or load balancer, one per line. The header is only trusted for requests coming from these addresses. If you leave this blank the header is ignored.

Saving the settings clears the admin notice. Developers can still override the resolved IP with the `cynder_paymaya_webhook_source_ip` filter.

### Running the tests
```
composer install
vendor/bin/phpunit
```
