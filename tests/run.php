<?php

use MifestaClientBrowserData\ClientBrowserData;

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!is_file($autoload)) {
    fwrite(STDERR, "vendor/autoload.php is missing. Run composer update first.\n");
    exit(1);
}

require $autoload;

error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    $path = str_replace('\\', '/', $file);
    $fromLibrary = strpos($path, '/vendor/') !== false;
    if ($fromLibrary && ($severity === E_DEPRECATED || $severity === E_USER_DEPRECATED)) {
        return true;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

$checks = 0;

/**
 * @param mixed $actual
 * @param mixed $expected
 * @param string $message
 */
function expect_same($actual, $expected, $message)
{
    global $checks;
    $checks++;
    if ($actual !== $expected) {
        fwrite(STDERR, 'FAIL ' . $message . "\n");
        fwrite(STDERR, '  expected ' . var_export($expected, true) . "\n");
        fwrite(STDERR, '  actual   ' . var_export($actual, true) . "\n");
        exit(1);
    }
}

/**
 * @param array $headers
 */
function use_headers($headers)
{
    foreach (array_keys($_SERVER) as $key) {
        if (is_string($key) && strncmp($key, 'HTTP_', 5) === 0) {
            unset($_SERVER[$key]);
        }
    }
    foreach ($headers as $key => $value) {
        $_SERVER[$key] = $value;
    }
    ClientBrowserData::forgetRequest();
}

$iphone = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';
$ipad = 'Mozilla/5.0 (iPad; CPU OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';
$android = 'Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36';
$desktop = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
$cubot = 'Mozilla/5.0 (Linux; Android 10; CUBOT_X30) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.149 Mobile Safari/537.36';
$googlebot = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';
$googlebotMobile = 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.96 Mobile Safari/537.36 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';
$yeti = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko; compatible; Yeti/1.1; +https://naver.me/spd) Chrome/120.0.0.0 Safari/537.36';
$ichiro = 'ichiro/5.0 (http://help.goo.ne.jp/door/crawler.html)';
$facebook = 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)';
$semrush = 'Mozilla/5.0 (compatible; SemrushBot/7~bl; +http://www.semrush.com/bot.html)';

echo 'PHP ' . PHP_VERSION . PHP_EOL;
echo 'library ' . ClientBrowserData::getScriptVersion() . PHP_EOL;

use_headers(array('HTTP_USER_AGENT' => $iphone));
expect_same(ClientBrowserData::isMobile(), true, 'iphone mobile');
expect_same(ClientBrowserData::isTablet(), false, 'iphone tablet');
expect_same(ClientBrowserData::isBot(), false, 'iphone bot');
expect_same(ClientBrowserData::isAnyBot(), false, 'iphone any bot');
expect_same(ClientBrowserData::is('iPhone'), true, 'iphone rule');
expect_same(ClientBrowserData::is('MOBILE'), true, 'MOBILE');
expect_same(ClientBrowserData::is('mobile'), true, 'mobile');
expect_same(ClientBrowserData::is('Bot'), false, 'Bot on phone');
$ios = ClientBrowserData::version('iOS');
expect_same(strpos((string) $ios, '17') !== false, true, 'ios version ' . var_export($ios, true));

use_headers(array('HTTP_USER_AGENT' => $ipad));
expect_same(ClientBrowserData::isTablet(), true, 'ipad tablet');
expect_same(ClientBrowserData::isMobile(), true, 'ipad mobile');
expect_same(ClientBrowserData::is('iPad'), true, 'ipad rule');
expect_same(ClientBrowserData::is('TABLET'), true, 'TABLET');

use_headers(array('HTTP_USER_AGENT' => $android));
expect_same(ClientBrowserData::isMobile(), true, 'android mobile');
expect_same(ClientBrowserData::isTablet(), false, 'android tablet');
expect_same(ClientBrowserData::is('AndroidOS'), true, 'android os');
expect_same(ClientBrowserData::isBot(), false, 'android bot');

use_headers(array('HTTP_USER_AGENT' => $desktop));
expect_same(ClientBrowserData::isMobile(), false, 'desktop mobile');
expect_same(ClientBrowserData::isTablet(), false, 'desktop tablet');
expect_same(ClientBrowserData::isBot(), false, 'desktop bot');
expect_same(ClientBrowserData::is('iPhone'), false, 'desktop iphone');
expect_same(ClientBrowserData::version('Android'), false, 'desktop android version');

use_headers(array(
    'HTTP_USER_AGENT' => $desktop,
    'HTTP_X_DEVICE_USER_AGENT' => $iphone,
));
expect_same(ClientBrowserData::isMobile(), true, 'alternate header mobile');
expect_same(ClientBrowserData::is('iPhone'), true, 'alternate header iphone');

use_headers(array('HTTP_USER_AGENT' => $cubot));
expect_same(ClientBrowserData::isMobile(), true, 'cubot mobile');
expect_same(ClientBrowserData::isBot(), false, 'cubot bot');
expect_same(ClientBrowserData::isAnyBot(), false, 'cubot any bot');
expect_same(ClientBrowserData::is('bot'), false, 'cubot is bot');
expect_same(ClientBrowserData::is('AnyBot'), false, 'cubot anybot');

use_headers(array('HTTP_USER_AGENT' => $googlebot));
expect_same(ClientBrowserData::isBot(), true, 'googlebot');
expect_same(ClientBrowserData::isAnyBot(), true, 'googlebot any');
expect_same(ClientBrowserData::is('bot'), true, 'googlebot case');
expect_same(ClientBrowserData::is('ANYBOT'), true, 'googlebot anybot case');
expect_same(ClientBrowserData::isMobile(), false, 'googlebot mobile');
expect_same(ClientBrowserData::isTablet(), false, 'googlebot tablet');
expect_same(ClientBrowserData::is('mobilebot'), false, 'googlebot mobilebot');

use_headers(array('HTTP_USER_AGENT' => $googlebotMobile));
expect_same(ClientBrowserData::is('mobilebot'), true, 'googlebot-mobile flag');
expect_same(ClientBrowserData::isBot(), false, 'googlebot-mobile isBot');
expect_same(ClientBrowserData::isAnyBot(), true, 'googlebot-mobile any');
expect_same(ClientBrowserData::isMobile(), true, 'googlebot-mobile mobile');

use_headers(array('HTTP_USER_AGENT' => $yeti));
expect_same(ClientBrowserData::isBot(), true, 'yeti');
expect_same(ClientBrowserData::isMobile(), false, 'yeti mobile');

use_headers(array('HTTP_USER_AGENT' => $ichiro));
expect_same(ClientBrowserData::isBot(), true, 'ichiro');

use_headers(array('HTTP_USER_AGENT' => $facebook));
expect_same(ClientBrowserData::isBot(), true, 'facebookexternalhit');
expect_same(ClientBrowserData::isMobile(), false, 'facebook mobile');

use_headers(array('HTTP_USER_AGENT' => $semrush));
expect_same(ClientBrowserData::isBot(), true, 'semrush');

use_headers(array(
    'HTTP_USER_AGENT' => 'Amazon CloudFront',
    'HTTP_CLOUDFRONT_IS_MOBILE_VIEWER' => 'true',
));
expect_same(ClientBrowserData::isMobile(), true, 'cloudfront mobile');
expect_same(isset(ClientBrowserData::getCfHeaders()['HTTP_CLOUDFRONT_IS_MOBILE_VIEWER']), true, 'cloudfront header');

use_headers(array(
    'HTTP_USER_AGENT' => 'Amazon CloudFront',
    'HTTP_CLOUDFRONT_IS_TABLET_VIEWER' => 'true',
));
expect_same(ClientBrowserData::isTablet(), true, 'cloudfront tablet');

use_headers(array('HTTP_USER_AGENT' => $iphone));
expect_same(ClientBrowserData::isMobile(), true, 'request iphone');
use_headers(array('HTTP_USER_AGENT' => $desktop));
expect_same(ClientBrowserData::isMobile(), false, 'same process desktop');
use_headers(array('HTTP_USER_AGENT' => $iphone));
expect_same(ClientBrowserData::isMobile(), true, 'same process iphone again');

use_headers(array('HTTP_USER_AGENT' => $desktop));
ClientBrowserData::setUserAgent($android);
expect_same(ClientBrowserData::isMobile(), true, 'setUserAgent');
expect_same(ClientBrowserData::getUserAgent() !== null && ClientBrowserData::getUserAgent() !== '', true, 'getUserAgent');

use_headers(array());
expect_same(ClientBrowserData::isMobile(), false, 'empty mobile');
expect_same(ClientBrowserData::isTablet(), false, 'empty tablet');
expect_same(ClientBrowserData::isBot(), false, 'empty bot');
expect_same(ClientBrowserData::is('NotARealDeviceXYZ'), false, 'unknown rule');

expect_same(is_array(ClientBrowserData::getPhoneDevices()) && count(ClientBrowserData::getPhoneDevices()) > 10, true, 'phone devices');
expect_same(is_string(ClientBrowserData::getScriptVersion()) && ClientBrowserData::getScriptVersion() !== '', true, 'script version');
expect_same((float) ClientBrowserData::prepareVersionNo('2_0') == 2.0, true, 'prepareVersionNo');

$failed = false;
try {
    ClientBrowserData::thisMethodDoesNotExist();
    $failed = true;
} catch (BadMethodCallException $exception) {
    expect_same($failed, false, 'unknown method');
}
if ($failed) {
    fwrite(STDERR, "FAIL unknown method did not throw\n");
    exit(1);
}

echo 'OK ' . $checks . ' checks' . PHP_EOL;
