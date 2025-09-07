# WordPress Plugin Boilerplate for WooCommerce

A fork of the [WordPress Plugin Boilerplate](https://github.com/DevinVinson/WordPress-Plugin-Boilerplate), which was written by [Tom McFarlin](http://twitter.com/tommcfarlin/), passed on to [Devin Vinson](http://devinvinson.com/contact/), and enhanced by [Josh Eaton](https://twitter.com/jjeaton), [Ulrich Pogson](https://twitter.com/grapplerulrich), and [Brad Vincent](https://twitter.com/themergency).

This fork adds a new tab with two sections to the WooCommerce Settings page. For further information, visit [the tutorial](https://medium.com/@paulmiller3000/how-to-extend-woocommerce-with-the-wordpress-plugin-boilerplate-adac178b5a9b).

## Contents

* `.gitignore`. Used to exclude certain files from the repository.
* `.distignore`. Excludes development and test files from distribution packages.
* `README.md`. The file that you’re currently reading.
* A `auspost-shipping` directory that contains the source code - a fully executable WordPress plugin.

## Building

When preparing the plugin for distribution, use a build process such as `wp dist-archive`. The `.distignore` file ensures that the `tests/` directory and any test configuration files are omitted from the resulting archive.

## Testing

Run the test suite with [PHPUnit](https://phpunit.de/):

```
vendor/bin/phpunit
```

## API Configuration

The shipping method supports both public PAC and contracted account APIs.
1. Enter your PAC or contracted credentials in **WooCommerce → Settings → Shipping → AusPost**.
2. Choose the appropriate account type and save your API key, account number, and secrets as required.

## Box Definitions

Boxes used for packing can be configured under **WooCommerce → Settings → Shipping → AusPost → Boxes**. Each box requires length, width, height, maximum weight and padding values. The smallest fitting box is used and dimensional weight is applied automatically.

## Developer Hooks

Developers can extend packing and rate selection using filters and actions:

```php
// Add or modify available boxes before packing.
add_filter( 'auspost_shipping_boxes', function( $boxes ) {
    $boxes[] = [
        'length' => 20,
        'width'  => 20,
        'height' => 20,
        'max_weight' => 10,
        'padding' => 0,
    ];
    return $boxes;
} );

// Adjust rates returned from the API.
add_filter( 'auspost_shipping_available_rates', function( $rates, $shipment ) {
    return array_filter( $rates, fn( $rate ) => $rate['code'] !== 'EXP' );
}, 10, 2 );

// React when a rate is selected.
add_action( 'auspost_shipping_rate_selected', function( $rate, $package ) {
    error_log( 'Selected rate: ' . $rate['code'] );
}, 10, 2 );
```

## Credits

90% of this code was developed by the following people:

* [Tom McFarlin](http://twitter.com/tommcfarlin/)
* [Devin Vinson](http://devinvinson.com/contact/)
* [Josh Eaton](https://twitter.com/jjeaton)
* [Ulrich Pogson](https://twitter.com/grapplerulrich)
* [Brad Vincent](https://twitter.com/themergency)

The general approach to adding a WooCommerce tab was inspired by [Beka Rice](http://bekarice.com/). StackOverflow answers and/or code samples from the following individuals helped with some stumbling blocks and best practices:

* [Slicktrick](https://stackexchange.com/users/3835188/slicktrick?tab=accounts)
* [Goran Jakovljevic](https://gist.github.com/goranefbl)

Any poor practices and mistakes are strictly of my own making.
