<?php
/*
 * Limelight CRM Order Processing API
 * Version 1.0
 * Copyright 2015
 * Austin Barnes
 */

error_reporting(-1);

/*
 * Limelight Gateway Configuration
 *
 * $username
 *  API account username created within Limelight
 * $password
 *  API account password created within Limelight
 * $url
 *  Full URL (beginning with https://) of the Limelight Gateway
 */
$username = '';
$password = '';
$url = '';

/*
 * Initial (Main) Order Configuration
 *
 * $campaign_id
 *  Campaign ID within Limelight for the initial order.
 * $product_id
 *  Product ID within Limelight for the initial order.
 * $shipping_id
 *  Shipping ID within Limelight for the initial order.
 * $allow_custom_products
 *  Setting this to true allows you to override the $product_id or $second_product_id from within the page or form, using javascript (and e.g. a hidden form input)
 *  Sending a field named "product_id" overrides the product ID on the initial order.
 *  Sending a field named "second_product_id" overrides the product ID on the second order.
 * $allow_custom_shipping
 *  Setting this to true allows you to override the $shipping_id or $second_shipping_id from within the page or form, using javascript (and e.g. a hidden form input)
 *  Sending a field named "shipping_id" overrides the product ID on the initial order.
 *  Sending a field named "second_shipping_id" overrides the product ID on the second order.
 */
$campaign_id = 16;
$product_id = 2;
$shipping_id = 6;
$allow_custom_products = true;
$allow_custom_shipping = true;

/*
 * Second Order Configuration
 *
 * $second_order
 *  If a second, separate order needs to be used (for example to have an upsell under a different campaign), set this to true
 *  If set to false, no secord order will be added in any case (even if $upsell_on_second is true)
 * $second_product_id
 *  Product ID within Limelight for the second order.
 *  If the main product on the second order is an optional upsell, set this variable to 0 and declare the upsell ID below in $second_upsell_product_id.
 * $second_campaign_id
 *  Campaign ID within Limelight for the second order.
 * $second_shipping_id
 *  Shipping ID within Limelight for the second order.
 */
$second_order = true;
$second_product_id = 0;
$second_campaign_id = 11;
$second_shipping_id = 8;

/* Upsell Configuration
 *
 * $upsell_optional
 *  Toggle whether the user can accept or decline the upsell product.
 *  Valid values:
 *  false - the upsell is non-optional. Any defined upsell product IDs will be added to every order, initial and second both.
 *  initial - passing "add-upsell" as 1 will add the upsell product to the initial order. 0 will not. The second order upsell will always be passed if the ID is >0.
 *  second - passing "add-upsell" as 1 will add the upsell product to the second order. 0 will not. The initial order upsell will always be passed if the ID is >0.
 *  Set to true to include the upsell product only if an input named "add-upsell" is passed and equal to 1.
 *
 * $initial_upsell_product_id
 *  Define the product ID(s) for additional product(s) on the initial order (comma delimited).
 *  Set to 0 to exclude an initial order upsell product.
 * $second_upsell_product_id
 *  Define the product ID(s) for additional product(s) on the second order (comma delimited).  If $second_product_id=0, $second_upsell_product_id will be used as the main product.
 *  Set to 0 to exclude a second order upsell product.
 */
$upsell_optional = 'second';
$initial_upsell_product_id = 22;
$second_upsell_product_id = 16;

/*
 * Prepaid Card Configuration
 *
 * Note: if prepaid cards are accepted in the initial order campaign (Limelight settings), these settings will not be used
 *
 * $accept_prepaid
 *  Set to true to accept prepaid cards under a different campaign ID. If false and the initial order campaign ($campaign_id) does not accept prepaid cards, prepaid transactions will be declined.
 * $prepaid_campaign_id
 *  The campaign ID for prepaid cards on the initial order
 * $prepaid_second_campaign_id
 *  The campaign ID for prepaid cards on the second order
 */
$accept_prepaid = true;
$prepaid_campaign_id = 28;
$prepaid_second_campaign_id = 30;