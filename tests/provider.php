<?php

use Illuminate\Http\Request;
use MifestaClientBrowserData\ClientBrowserData;
use MifestaClientBrowserData\Facades\MobileDetect;
use MifestaClientBrowserData\MobileDetectServiceProvider;

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!is_file($autoload)) {
    fwrite(STDERR, "vendor/autoload.php is missing. Run composer update first.\n");
    exit(1);
}

require $autoload;

if (!class_exists('Illuminate\\Http\\Request') || !class_exists('Illuminate\\Support\\ServiceProvider')) {
    echo "SKIP provider, illuminate/http is not installed\n";
    exit(0);
}

error_reporting(E_ALL);
set_error_handler(function ($severity) {
    return $severity === E_DEPRECATED || $severity === E_USER_DEPRECATED;
});

class ProviderApplication implements ArrayAccess
{
    /**
     * @var array
     */
    public $bindings = array();

    /**
     * @var array
     */
    public $instances = array();

    /**
     * @var array
     */
    public $listeners = array();

    /**
     * @param string $key
     * @return bool
     */
    public function bound($key)
    {
        return isset($this->instances[$key]) || isset($this->bindings[$key]);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function instance($key, $value)
    {
        $this->instances[$key] = $value;

        return $value;
    }

    /**
     * @param string $key
     * @param callable $concrete
     * @return void
     */
    public function singleton($key, $concrete)
    {
        $this->bindings[$key] = $concrete;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function make($key)
    {
        if (isset($this->instances[$key])) {
            return $this->instances[$key];
        }
        if (!isset($this->bindings[$key])) {
            throw new RuntimeException('Not bound: ' . $key);
        }
        $concrete = $this->bindings[$key];
        $this->instances[$key] = call_user_func($concrete, $this);

        return $this->instances[$key];
    }

    /**
     * @param string $event
     * @param callable $listener
     * @return void
     */
    public function listen($event, $listener)
    {
        $this->listeners[$event] = $listener;
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->bound($offset);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->make($offset);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->instance($offset, $value);
    }

    /**
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->instances[$offset], $this->bindings[$offset]);
    }
}

restore_error_handler();
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

$iphone = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';
$desktop = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

$_SERVER['HTTP_USER_AGENT'] = $desktop;
ClientBrowserData::forgetRequest();

$app = new ProviderApplication();
$app->instance('request', Request::create('/', 'GET', array(), array(), array(), array(
    'HTTP_USER_AGENT' => $iphone,
)));
$app->instance('events', $app);

$provider = new MobileDetectServiceProvider($app);
$provider->register();
$provider->boot();

if (!isset($app->listeners['Illuminate\\Foundation\\Http\\Events\\RequestHandled'])) {
    fwrite(STDERR, "FAIL request listener was not registered\n");
    exit(1);
}
if (!isset($app->listeners['Illuminate\\Queue\\Events\\JobProcessing'])) {
    fwrite(STDERR, "FAIL queue listener was not registered\n");
    exit(1);
}

$detector = $app->make('mifesta.mobiledetect');
if ($detector->isMobile() !== true || $detector->is('iPhone') !== true) {
    fwrite(STDERR, "FAIL provider read the Laravel request\n");
    exit(1);
}

MobileDetect::setFacadeApplication($app);
if (MobileDetect::isTablet() !== false || MobileDetect::is('mobile') !== true) {
    fwrite(STDERR, "FAIL facade\n");
    exit(1);
}

$app->instance('request', Request::create('/', 'GET', array(), array(), array(), array(
    'HTTP_USER_AGENT' => $desktop,
)));
call_user_func($app->listeners['Illuminate\\Foundation\\Http\\Events\\RequestHandled']);
if (MobileDetect::isMobile() !== false) {
    fwrite(STDERR, "FAIL facade kept the previous request\n");
    exit(1);
}

echo "OK provider\n";
