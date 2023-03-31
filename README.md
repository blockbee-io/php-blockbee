[<img src="https://blockbee.io/static/assets/images/blockbee_logo_nospaces.png" width="300"/>](image.png)

# BlockBee's PHP Library
Official PHP library of BlockBee

## Requirements:

```
PHP >= 5.6
ext-curl
```

## Install

```
composer require blockbee/php-blockbee
```

[on GitHub](https://github.com/blockbee-io/php-blockbee) &emsp;
[on Composer](https://packagist.org/packages/blockbee/php-blockbee)

## Usage

### Generating a new address

```php
<?php
require 'vendor/autoload.php'; // Where your vendor directory is

$bb = new BlockBee\BlockBee($coin, $my_address, $callback_url, $parameters, $blockbee_params, $api_key);
$payment_address = $bb->get_address();
```

Where:

``$coin`` is the coin you wish to use, from BlockBee's supported currencies (e.g `'btc', 'eth', 'erc20_usdt', ...`)

``$my_address`` is your own crypto address, where your funds will be sent to  

``$callback_url`` is the URL that will be called upon payment

``$parameters`` is any parameter you wish to send to identify the payment, such as `['order_id' => 1234]`

``$blockbee_params`` parameters that will be passed to BlockBee _(check which extra parameters are available here: https://docs.blockbee.io/#operation/create)

``$payment_address`` is the newly generated address, that you will show your users

``$api_key`` is the API Key provided by our [Dashboard](https://dash.blockbee.io/).


### Getting notified when the user pays

The URL you provided earlier will be called when a user pays, for easier processing of the request we've added the ``process_callback`` helper

```php
<?php

require 'vendor/autoload.php'; // Where your vendor directory is

$payment_data = BlockBee\BlockBee::process_callback($_GET);
```

The `$payment_data` will be an array with the following keys:

`address_in` - the address generated by our service, where the funds were received

`address_out` - your address, where funds were sent

`txid_in` - the received TXID

`txid_out` - the sent TXID or null, in the case of a pending TX

`confirmations` - number of confirmations, or 0 in case of pending TX

`value` - the value that your customer paid

`value_coin` - the value that your customer paid, in the main coin denomination (e.g `BTC`)

`value_forwarded` - the value we forwarded to you, after our fee

`value_forwarded_coin` - the value we forwarded to you, after our fee, in the main coin denomination (e.g `BTC`)

`coin` - the coin the payment was made in (e.g: `'btc', 'eth', 'erc20_usdt', ...`)

`pending` - whether the transaction is pending, if `false` means it's confirmed

plus, any values set on `$params` when requesting the address, like the order ID.

&nbsp;

From here you just need to check if the value matches your order's value.


### Checking the logs of a request

```php
<?php

require 'vendor/autoload.php'; // Where your vendor directory is

$bb = new BlockBee\BlockBee($coin, $my_address, $callback_url, $parameters, $api_key);
$data = $bb->check_logs();
```

Same parameters as before, the `$data` returned can be checked here: https://docs.blockbee.io/#operation/logs


### Generating a QR code

```php
<?php
require 'vendor/autoload.php'; // Where your vendor directory is

$bb = new BlockBee\BlockBee($coin, $my_address, $callback_url, $parameters, $blockbee_params, $api_key);
$payment_address = $bb->get_address();

$qrcode = $bb->get_qrcode($value, $size);
```

For object creation, same parameters as before.  You must first call `get_address` as this method requires the payment address to have been created.

For QR code generation:

``$value`` Value to request the user, in the main coin (BTC, ETH, etc).  Optional, pass `false` to not add a value to the QR.

``$size`` Size of the QR Code image in pixels. Optional, pass `false` to use the default size of 512.

Response is an object with `qr_code` (base64 encoded image data) and `payment_uri` (the value encoded in the QR), see https://docs.blockbee.io/#operation/qrcode for more information.


### Estimating transaction fees

```php
<?php
require 'vendor/autoload.php'; // Where your vendor directory is

$fees = BlockBee\BlockBee::get_estimate($coin, $addresses, $priority, $api_key);
```

Where:

``$coin`` is the coin you wish to check, from BlockBee's supported currencies (e.g `'btc', 'eth', 'erc20_usdt', ...`)

``$addresses`` The number of addresses to forward the funds to.  Optional, defaults to 1.

``$priority`` Confirmation priority, needs to be one of `['fast', 'default', 'economic']`.  Optional, defaults to `default`.

Response is an object with `estimated_cost` and `estimated_cost_usd`, see https://docs.blockbee.io/#operation/estimate for more information.


### Converting between coins and fiat

```php
<?php
require 'vendor/autoload.php'; // Where your vendor directory is

$conversion = BlockBee\BlockBee::get_convert($coin, $value, $from, $api_key);
```

Where:

``$coin`` the target currency to convert to, from BlockBee's supported currencies (e.g `'btc', 'eth', 'erc20_usdt', ...`)

``$value`` Value to convert in `from`.

``$from`` Currency to convert from, FIAT or crypto.

Response is an object with `value_coin` and `exchange_rate`, see https://docs.blockbee.io/#operation/convert for more information.


### Getting supported coins

```php
<?php
require 'vendor/autoload.php'; // Where your vendor directory is

$coins = BlockBee\BlockBee::get_supported_coins($api_key);
```

Response is an array with all support coins.

### Request Payout

```php
<?php
require 'vendor/autoload.php'; // Where your vendor directory is

$create_payout = BlockBee\BlockBee::create_payout($coin, $address, $value, $api_key);
```

This function can be used by you to request payouts (withdrawals in your platform).

Where:
* ``$coin`` The cryptocurrency you want to request the Payout in (e.g `btc`, `eth`, `erc20_usdt`, ...).

* ``$address`` Address where the Payout must be sent to.

* ``$value`` Amount to send to the ``address``.

The response will be only a ``success`` to confirm the Payout Request was successfully created. To fulfill it you will need to go to BlockBee Dashboard.

## Help

Need help?  
Contact us @ https://blockbee.io/contacts/

### Changelog

#### 1.0.0
* Initial release.

#### 1.0.1
* Minor bugfixes.

#### 1.1.0
* Added Payouts
* Minor bugfixes