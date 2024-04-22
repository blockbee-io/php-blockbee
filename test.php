<?php
/**
 * Testing BlockBee Library...
 */

require __DIR__ . '/vendor/autoload.php';

$coin = "bep20_usdt";
$callback_url = "https://example.com";
$parameters = [
    'payment_id' => 123,
];

$blockbee_params = [
    'pending' => 1,
];

$api_key = "<your-api-key>";

try {
    $bb = new BlockBee\BlockBee($coin, '', $callback_url, $parameters, $blockbee_params, $api_key);

    # var_dump($bb->get_address()) . PHP_EOL;

    # var_dump($bb->check_logs()) . PHP_EOL;

    # var_dump($bb->get_qrcode()) . PHP_EOL;

    # var_dump($bb->get_qrcode(2, 500)) . PHP_EOL;

    # var_dump(\BlockBee\BlockBee::get_info('btc', true)) . PHP_EOL;

    # var_dump(\BlockBee\BlockBee::get_info($coin, false)) . PHP_EOL;

    # var_dump(\BlockBee\BlockBee::get_supported_coins()) . PHP_EOL;

    # var_dump(\BlockBee\BlockBee::get_estimate($coin, 1, '')) . PHP_EOL;

    # var_dump(\BlockBee\BlockBee::get_convert($coin, 3, 'usd')) . PHP_EOL;

    # var_dump(\BlockBee\BlockBee::list_payouts('bep20_usdt', 'all',  1, $api_key)) . PHP_EOL;

    //    var_dump(\BlockBee\BlockBee::create_payout('bep20_usdt', [
    //        '0xA6B78B56ee062185E405a1DDDD18cE8fcBC4395d' => 0.2,
    //        '0x18B211A1Ba5880C7d62C250B6441C2400d588589' => 0.3,
    //    ], $api_key, false)) . PHP_EOL;

    # var_dump(\BlockBee\BlockBee::get_payout_wallet($coin, $api_key, true)) . PHP_EOL;

    # var_dump(\BlockBee\BlockBee::create_payout_by_ids($api_key, [52408, 52407])) . PHP_EOL;

    # var_dump(\BlockBee\BlockBee::check_payout_status($api_key, 2598)) . PHP_EOL;

    # var_dump(\BlockBee\BlockBee::process_payout($api_key, 2598)) . PHP_EOL;

    /* Checkout */

    $bb_checkout = new BlockBee\Checkout($api_key, $parameters, $blockbee_params);

    # var_dump($bb_checkout->payment_request('https://example.com/', 'https://example.com/', 5)) . PHP_EOL;

    # ar_dump($bb_checkout->deposit_request('https://example.com/')) . PHP_EOL;

    # var_dump(\BlockBee\Checkout::payment_logs('YhyExpGDA7FIBszS4bMV9FfYWOCJsYoO', $api_key)) . PHP_EOL;

    # var_dump(\BlockBee\Checkout::deposit_logs('8yHRn7dKn3WrtwcJVpgBUCreMXSRbCki', $api_key)) . PHP_EOL;

} catch (Exception $e) {
    var_dump($e);
}
