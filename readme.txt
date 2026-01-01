=== SellApp ===
Contributors: sellapp
Tags: sellapp, woocommerce, paypal, payment gateway, crypto payments
Stable tag: 1.0.0
Requires at least: 4.9
Tested up to: 6.8
WC requires at least: 3.5
WC tested up to: 9.5
Requires PHP: 7.4
Author URI: https://sell.app
Author:   Sell.app
License: MIT

Accept various payment methods including crypto, paypal, and more.

== Description ==

Accept various payment methods including crypto, paypal, and more.

### What is SellApp? 

SellApp is an eCommerce platform that helps you sell digital goods online. We handle all the difficult parts of selling online, such as fraud prevention, payment processing, product delivery, and much more.

We're releasing this WooCommerce payment plugin so users of our platform can integrate our payment processing capabilities seamlessly with WooCommerce. Simply install the plugin, set your API key, and you're good to go!

We support a wide variety of payment methods, including a range of cryptocurrencies as well as local payment options. You can configure each payment method via the SellApp dashboard, which instantly reflects on the WooCommerce side too.

Our goal is to help you streamline your sales as much as possible, regardless of which platform you're using. All of the SellApp checkout functionality you have come to know and love is integrated in this plugin.

This means that you're also protected from malicious customers and fraudulent purchases. Our advanced detection techniques will help you keep bad customers at bay.


### How do I start?

We've made the installation process as easy as we can. Simply install this plugin, then proceed to the plugin's settings and enter your store's API key and save.

Once the plugin has been enabled and the API key has been set, the SellApp checkout option is then visible to your WooCommerce customers.

When a customer selects SellApp, they are redirected to our familiar checkout flow which lets them select a payment method of their choosing. As always, these payment methods depend on which ones you've configured in your store settings.

Upon completion of the payment, your customer is redirected back to your WooCommerce store and the status of the order is updated accordingly.


### Why should I choose SellApp?

SellApp helps you streamline your sales by reducing friction and automating as much of the sales process as possible. If you are looking for a solution that does just this, then you're at the right place.

== Installation ==

1. Upload the `sellapp` folder you downloaded to your WordPress `/wp-content/plugins/` directory
2. Ensure the plugin is activated. Then, go to WooCommerce -> Settings -> Payments and enable SellApp
3. Finally, Click on "Manage" and fill the details in the payment settings. Most importantly, make sure to add your API key.
4. You're good to go!

== External services ==

This plugin connects to an external API that generates checkout sessions, it's needed so the customer can be redirected and can make their payment for the product they are purchasing.

It sends the customer's checkout data every time the customer selects the payment method and clicks to check out. This includes the customer name, email, phone, country, state, and customer ID.

This service is provided by "Toffee, Inc. DBA SellApp": [terms of use](https://sell.app/terms-of-service), [privacy policy](https://sell.app/privacy-policy).


== Frequently Asked Questions ==

= What is SellApp? =
SellApp is an eCommerce platform that helps you sell digital goods online. We handle all the difficult parts of selling online, such as fraud prevention, payment processing, product delivery, and much more.

= Which payment methods does SellApp offer? =
We support a wide variety of payment methods, including more than a dozen crypto options, as well as the majority of regular payment methods such as PayPal and Stripe. For an extensive list of supported payment methods, feel free **[to take a look here](https://docs.sell.app/docs/payment-methods-introduction)**.

= How does SellApp prevent fraud? =
We utilize a number of techniques to prevent customers from making fraudulent purchases. In addition to our in-house fraud prevention logic, we also offer the ability to blacklist customers based on email, IP, and more.


== Screenshots ==
1. SellApp WooCommerce Settings
2. SellApp in WooCommerce Checkout
3. SellApp Payment Method Selection
4. SellApp Dashboard
5. SellApp Payment Method Settings

== Changelog ==

= 1.0.0 =
* Mar 22, 2025
* Initial release.