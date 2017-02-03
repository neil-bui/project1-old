=== FraudLabs Pro for WooCommerce ===
Contributors: FraudLabs Pro
Donate link: http://www.fraudlabspro.com
Tags: fraud, ecommerce, woocommerce
Requires at least: 2.0
Tested up to: 4.7
Stable tag: 2.8.14

Description: This plugin is an add-on for WooCommerce plugin that help you to screen your order transaction, such as credit card transaction, for online fraud.

== Description ==

FraudLabs Pro is a fraud prevention plugin to help merchants to protect their online stores from malicious fraudsters by screening all order transactions for fraud patterns. Its comprehensive and advanced algorithm engines validate all elements such as geolocation, proxy, email, blacklist, credit card, transaction velocity and others to unveil fraud orders accurately. This plugin operates behind the scene without interrupting the payment process and it provides detailed reports of all orders for the merchant’s reference.

Why FraudLabs Pro?

= Flexibility =
We offer you a flexible solution to identify a fraud order. You can use our FraudLabs Pro score to determine a fraud order or customize your own validation rules to target a specific case, or a combination of both.

= Free =
We are not offering you a trial version, but a free version to start protecting your online business. It’s absolutely free if your monthly orders are less than 500 transactions. There is no upfront credit card information needed, commitment, hidden cost and whatsoever.

= Easy to setup =
The setup is simple and only takes a few minutes. You just need to install the free FraudLabs Pro plugin, enter the API key and configure the settings.

= Trustworthy =
We have been in the fraud prevention industry for more than 10 years. Thousands of our clients are currently using our FraudLabs Pro solution. This WooCommerce plugin is one of 16 ready plugins for major shopping cart platforms. Please check out our website [http://www.fraudlabspro.com](http://www.fraudlabspro.com "http://www.fraudlabspro.com") for details.

= Key Features =
Below are the key features of FraudLabs Pro WooCommerce plugin:

* Fraud analysis and scoring
* IP address geolocation & proxy validation
* Email address validation
* Transaction velocity validation
* Device transaction validation
* Blacklist validation
* Export controlled country validation
* Malware exploit validation
* Custom rules trigger
* FraudLabs Pro Merchant Network
* FraudLabs Pro Merchant Administrative Interface
* Email notification of fraud orders
* Mobile app notification of fraud orders
* Social Profile query


= More Information =
Sign up for a Free license key at [http://www.fraudlabspro.com/sign-up](http://www.fraudlabspro.com/sign-up "http://www.fraudlabspro.com/sign-up") and start protecting your business from online fraud.


== Screenshots ==

1. **Fraud result** - Fraud result of the order validation.


== Changelog ==

* 1.0	First release.
* 1.1	Fixed to match WooCommerce standards.
* 2.0.0	Fully integrated into WooCommerce.
* 2.1.0	Fixed issue cannot read API key value.
* 2.1.1	Detect client IP correctly if WordPress installed behind load balancer or proxy.
* 2.2.0	Added email notification with fraud result to store owner.
* 2.2.1 Stop detecting client IP using X_FORWARDED_FOR header.
* 2.2.2 Added links to documentation and API key.
* 2.2.3 Added responsive fraud result and minor tuning.
* 2.2.4 Fixed missing billing address and other some minor changes.
* 2.3.0 Your order will now on-hold or cancelled based on FraudLabs Pro result.
* 2.4.0 Customer no longer see their order being rejected or review.
        Fixed issue when submitting order to payment gateway.
	Added detailed notes of fraud the progress.
* 2.4.1 Use customer IP address for fraud check instead of checkout IP.
* 2.4.2 Fixed the issue of undefined function for get_address in order object.
* 2.5.0 Fixed order status not changing after pressing "Approve" or "Reject" button.
        Added risk score column in orders list for easier reference.
* 2.5.1 Added custom actions for "Approve" and "Reject" button.
* 2.5.2 Added order note when user click on the "Approve" or "Reject" button.
* 2.5.3 Added order note when user click on the "Approve" or "Reject" button (for no-status-change case).
* 2.5.4 Minor fixes.
* 2.6.0 Added additional retries when API gateway is timed out.
* 2.6.1 Tested with WordPress 4.4.
* 2.7.0 Added SMS verification feature during checkout.
* 2.8.0 Added Javascript agent to detect device information.
* 2.8.1 Fixed SMS settings bugs causing SMS verification to appear when it is not enabled.
* 2.8.2 Custom approve and reject status based FraudLabs Pro result is now available.
* 2.8.3 Fixed errors with WordPress 4.5.
* 2.8.4 Minor changes.
* 2.8.5 Fixed warning messages when WP debug mode enabled.
* 2.8.6 Fixed warning message in debug log.
* 2.8.7 Fixed issue order status changed to blank when review action is not selected.
* 2.8.8 Added option to enable/disable fraud report email.
* 2.8.9 Fixed close admin notice issue.
* 2.8.10 Added fraud screening on failed order.
* 2.8.11 Fixed wrong order ID when using third party plugins.
* 2.8.12 Removed fraud screening on failed order which causing false positive result.
* 2.8.13 Fixed email formatting issues.
* 2.8.14 Fixed minor bugs.

== Installation ==

1. Upload `fraudlabs-pro` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Enter the License Key at the 'Settings' section.

= How to enable the FraudLabs Pro feature =
1. Click on the WooCommerce->Settings page.
2. Click on the Integration tab then choose "FraudLabs Pro".
3. Check the Enabled check box to enable it.
4. Enter your FraudLabs Pro API Key. You can sign up for a free API key at http://www.fraudlabspro.com/sign-up.
5. If you want to enabled SMS verification, just check the "SMS Verification Enabled" check box.
6. Click on the Save Settings button.

For more information, please visit [http://www.fraudlabspro.com](http://www.fraudlabspro.com/supported-platforms-woocommerce "http://www.fraudlabspro.com")
