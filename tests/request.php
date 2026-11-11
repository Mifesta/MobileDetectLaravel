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
    if (strpos($path, '/vendor/') !== false && ($severity === E_DEPRECATED || $severity === E_USER_DEPRECATED)) {
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

class RequestBag
{
    /**
     * @var array
     */
    private $items;

    /**
     * @param array $items
     */
    public function __construct(array $items)
    {
        $this->items = $items;
    }

    /**
     * @return array
     */
    public function all()
    {
        return $this->items;
    }
}

class FakeRequest
{
    /**
     * @var RequestBag
     */
    public $server;

    /**
     * @var RequestBag
     */
    public $headers;

    /**
     * @param array $server
     * @param array $headers
     */
    public function __construct(array $server, array $headers)
    {
        $this->server = new RequestBag($server);
        $this->headers = new RequestBag($headers);
    }
}

class FakeApp
{
    /**
     * @var FakeRequest|null
     */
    public $request;

    /**
     * @param string $key
     * @return bool
     */
    public function bound($key)
    {
        return $key === 'request' && $this->request !== null;
    }

    /**
     * @param string $key
     * @return FakeRequest|null
     */
    public function make($key)
    {
        return $this->request;
    }
}

/**
 * @return FakeApp
 */
function app()
{
    global $fakeApp;

    return $fakeApp;
}

$iphone = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';
$desktop = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
$cubot = 'Mozilla/5.0 (Linux; Android 10; CUBOT_X30) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.149 Mobile Safari/537.36';
$googlebot = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';
$googlebotMobile = 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.96 Mobile Safari/537.36 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';

$fakeApp = new FakeApp();

$_SERVER['HTTP_USER_AGENT'] = $desktop;
$fakeApp->request = new FakeRequest(
    array('HTTP_USER_AGENT' => $desktop),
    array('user-agent' => array($iphone))
);
expect_same(ClientBrowserData::isMobile(), true, 'header bag wins over server');
expect_same(ClientBrowserData::is('iPhone'), true, 'header bag iphone');
expect_same(isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone') !== false, true, 'server rewritten from request');

$fakeApp->request = new FakeRequest(
    array('HTTP_USER_AGENT' => $desktop),
    array('user-agent' => array($desktop))
);
expect_same(ClientBrowserData::isMobile(), false, 'second request desktop');
expect_same(ClientBrowserData::isBot(), false, 'second request bot');

$fakeApp->request = new FakeRequest(array(), array('user-agent' => array($cubot)));
expect_same(ClientBrowserData::isMobile(), true, 'cubot mobile');
expect_same(ClientBrowserData::isBot(), false, 'cubot bot');
expect_same(ClientBrowserData::isAnyBot(), false, 'cubot any');

$fakeApp->request = new FakeRequest(array(), array('user-agent' => array($googlebot)));
expect_same(ClientBrowserData::isBot(), true, 'googlebot');
expect_same(ClientBrowserData::isMobile(), false, 'googlebot mobile');

$fakeApp->request = new FakeRequest(array(), array('user-agent' => array($googlebotMobile)));
expect_same(ClientBrowserData::isBot(), false, 'googlebot-mobile bot');
expect_same(ClientBrowserData::isAnyBot(), true, 'googlebot-mobile any');
expect_same(ClientBrowserData::isMobile(), true, 'googlebot-mobile mobile');

$fakeApp->request = new FakeRequest(
    array('HTTP_USER_AGENT' => $desktop),
    array(
        'user-agent' => array($desktop),
        'x-device-user-agent' => array($iphone),
    )
);
expect_same(ClientBrowserData::isMobile(), true, 'x-device-user-agent');
expect_same(ClientBrowserData::is('iPhone'), true, 'x-device-user-agent iphone');

$fakeApp->request = new FakeRequest(array(), array('user-agent' => array($desktop)));
expect_same(ClientBrowserData::isMobile(), false, 'back to desktop');
ClientBrowserData::setUserAgent($iphone);
expect_same(ClientBrowserData::isMobile(), true, 'manual ua sticks');
expect_same(ClientBrowserData::is('iPhone'), true, 'manual ua iphone');
ClientBrowserData::forgetRequest();
expect_same(ClientBrowserData::isMobile(), false, 'forget returns to request');

$fakeApp->request = new FakeRequest(array(), array());
expect_same(ClientBrowserData::isMobile(), false, 'empty request mobile');
expect_same(ClientBrowserData::isBot(), false, 'empty request bot');

echo 'OK ' . $checks . ' request checks' . PHP_EOL;
