=== WP Easy Paypal Payment Accept ===
Contributors: Tips and Tricks HQ, Ruhul Amin
Donate link: https://www.tipsandtricks-hq.com
Tags: Paypal payment, Accept payment for services or product, PayPal donation, wordpress paypal, paypal for wordpress, paypal plugin for wordpress, paypal integration, paypal, buy now, payment, currency,
Requires at least: 3.0
Tested up to: 4.0
Stable tag: 4.1
License: GPLv2 or later

Easy to use Wordpress plugin to accept paypal payment for a service or product or donation in one click

== Description ==

Easy to use Wordpress plugin to accept paypal payment for a service or product or donation in one click. Can be used in the sidebar, posts and pages of your site.

For information, detailed documentation, video tutorial and updates, please visit the [WordPress PayPal Payment](https://www.tipsandtricks-hq.com/wordpress-easy-paypal-payment-or-donation-accept-plugin-120) Plugin Page

* Quick installation and setup.
* Easily take payment for a service from your site via PayPal.
* The ultimate plugin to create PayPal buy now buttons.
* Create the payment buttons on the fly and embed them anywhere on your site using a shortcode.
* Add multiple payment widget for different services or products.
* Ability to configure which currency you want to use to accept the payment.
* You will need to have your own PayPal account (creating a PayPal account is free).
* Integrate PayPal with your WordPress powered site.
* Accept donation on your WordPress site for a cause.
* Allow your users to specify an amount that they wish to pay. Useful when you need to accept variable payment amount.
* Ability to specify a reference value for the payment.
* Ability to specify a payment subject for the payment widget.
* Add PayPal Buy Now buttons anywhere on a WordPress post or a page.
* Ability to set the country code to use a particular language for the PayPal checkout page.
* Create a payment button widget to accept payment in any currency accepted by PayPal. 
* Ability to specify a payment subject for each paypal payment widget.
* Ability to specify a custom button image for the payment button.
* Ability to specify a cancel URL for the payment widget.
* Ability to collect tax for the payment (if you need to).
* Ability to open the payment window in a new browser tab or window.
* Create a payment widget to accept any amount from your customer. Users will specify the amount to pay (useful for donations).
* Create text based or image based payment buttons.

== Usage ==

There are few ways you can use this plugin:

1. Use the sortcode [wp_paypal_payment] to place the payment accept form.
2. Add the paypal payment widget to your sidebar widgets.
3. Call the function from a template file: <?php echo Paypal_payment_accept(); ?>
4. Use the shortcode with custom parameter option to add multiple different payment widget in different areas of the site.

== Installation ==

1. Unzip and Upload the folder 'WP-accept-paypal-payment' to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings and configure the options eg. your email, Subject text etc.
4. See the usage section for details on how to place the paypal payment widget

== Frequently Asked Questions ==

== Screenshots ==

Visit the plugin site at https://www.tipsandtricks-hq.com/wordpress-easy-paypal-payment-or-donation-accept-plugin-120 for screenshots.

== Changelog ==

= 4.1 =
- The currency code will now be shown after the "Other Amount" field.
- Added the option to create text based payment button. Use parameter "button_text" in the shortcode to use it.
- WordPress 4.0 compatibility.

= 4.0 = 
- Added two new filters to allow modification of the payer email parameter programmatically. The filters are 'wppp_widget_any_amt_email' and 'wppp_widget_email'.
- Added a new parameter in the shortcode to override tax value. The name of the new shortcode parameter is "tax".

= 3.9 =
- Added an option to exclude the "reference" field from the payment widget. Using the parameter reference="" in the shortcode will disable that field.

= 3.8 =
- Added a new feature to open the payment window in a new browser tab/window. Use the new_window parameter in the shortcode to use it.
- Fixed a minor bug in the [wp_paypal_payment_box_for_any_amount] shortcode.

= 3.7 =
- Added more parameters in the "wp_paypal_payment_box_for_any_amount" shortcode. New parameters are "reference" (for adding a reference field) and "currency" (for adding a currency code).
- Moved some inline CSS to a CSS file.

= 3.6 = 
- Added the ability to specify a cancel URL using the "cancel_url" parameter in the shortcode
- Added a new shortcode that allows you to create a payment widget for any amount.

= 3.5 =
- WordPress 3.8 compatibility

= 3.4 =
- Added an option to specify a custom button image for the payment button. You can use the "button_image" parameter in the shortcode to use a customized image for the buy button.

= 3.3 =
- Added an option in the shortcode to specify a payment subject. This can be handy if you have multiple payment widgets on your site.
- WordPress 3.7 compatibility
- Fixed some deprecated calls

= 3.2 =
- Added an option in the shortcode to set the country code to be used for the PayPal checkout page language.

= 3.1 =
- Added an option to specify a different amount (any amount your user whish to pay) via the shortcode.

Changelog for old versions can be found at the following URL
https://www.tipsandtricks-hq.com/wordpress-easy-paypal-payment-or-donation-accept-plugin-120

