<?php

namespace MifestaClientBrowserData;

/**
 * Laravel entry point for mifesta/mobiledetect.
 *
 * The current Illuminate request supplies the HTTP headers. Outside a booted
 * request the call falls through to $_SERVER, which is what
 * MifestaBrowserData\BrowserData reads on its own.
 *
 * A manual setUserAgent() or setHttpHeaders() stays in effect until the next
 * HTTP request or queued job. MobileDetectServiceProvider drops that cache
 * after the response and before each job.
 */
class ClientBrowserData
{
    /**
     * md5 of the headers taken from the request that last synced $_SERVER.
     *
     * @var string|null
     */
    private static $requestFingerprint;

    /**
     * Application set by the service provider. Null until Laravel boots the provider.
     *
     * @var object|null
     */
    private static $container;

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        return self::forward($name, $arguments);
    }

    /**
     * Facade and container calls arrive here.
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return self::forward($name, $arguments);
    }

    /**
     * @return bool
     */
    public static function isMobile()
    {
        return self::forward('isMobile', array());
    }

    /**
     * @return bool
     */
    public static function isTablet()
    {
        return self::forward('isTablet', array());
    }

    /**
     * @return bool
     */
    public static function isBot()
    {
        return self::forward('isBot', array());
    }

    /**
     * @return bool
     */
    public static function isAnyBot()
    {
        return self::forward('isAnyBot', array());
    }

    /**
     * @param string $key
     * @return bool
     */
    public static function is($key)
    {
        return self::forward('is', array($key));
    }

    /**
     * @param string $regex
     * @param string|null $userAgent
     * @return bool
     */
    public static function match($regex, $userAgent = null)
    {
        return self::forward('match', array($regex, $userAgent));
    }

    /**
     * @param string $propertyName
     * @param string|null $type
     * @return string|float|false
     */
    public static function version($propertyName, $type = null)
    {
        if (func_num_args() < 2) {
            return self::forward('version', array($propertyName));
        }

        return self::forward('version', array($propertyName, $type));
    }

    /**
     * @param string|null $userAgent
     * @return string
     */
    public static function setUserAgent($userAgent)
    {
        return self::forward('setUserAgent', array($userAgent));
    }

    /**
     * @param array|null $httpHeaders
     * @return void
     */
    public static function setHttpHeaders($httpHeaders = null)
    {
        self::forward('setHttpHeaders', array($httpHeaders));
    }

    /**
     * @param object|null $container
     * @return void
     */
    public static function useContainer($container)
    {
        self::$container = is_object($container) ? $container : null;
        self::$requestFingerprint = null;
    }

    /**
     * The next call reads the current request again.
     * Used after the HTTP response and before a queued job.
     *
     * @return void
     */
    public static function forgetRequest()
    {
        self::$requestFingerprint = null;
        if (class_exists('MifestaBrowserData\\BrowserData', false) || class_exists('MifestaBrowserData\\BrowserData', true)) {
            \MifestaBrowserData\BrowserData::setHttpHeaders(null);
        }
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    private static function forward($name, array $arguments)
    {
        self::applyRequest();

        return call_user_func_array(array('\\MifestaBrowserData\\BrowserData', $name), $arguments);
    }

    /**
     * @return void
     */
    private static function applyRequest()
    {
        $request = self::currentRequest();
        if ($request === null) {
            return;
        }

        $headers = self::headersFromRequest($request);
        $fingerprint = md5(serialize($headers));
        if (self::$requestFingerprint === $fingerprint) {
            return;
        }

        if (self::headersFromServer() !== $headers) {
            \MifestaBrowserData\BrowserData::setHttpHeaders($headers);
        }

        self::$requestFingerprint = $fingerprint;
    }

    /**
     * @return object|null
     */
    private static function currentRequest()
    {
        $request = self::requestFrom(self::$container);
        if ($request !== null) {
            return $request;
        }

        if (!function_exists('app')) {
            return null;
        }

        try {
            return self::requestFrom(app());
        } catch (\Exception $exception) {
            return null;
        }
    }

    /**
     * @param object|null $app
     * @return object|null
     */
    private static function requestFrom($app)
    {
        if (!is_object($app) || !method_exists($app, 'bound') || !$app->bound('request')) {
            return null;
        }

        try {
            $request = $app->make('request');
        } catch (\Exception $exception) {
            return null;
        }

        return is_object($request) ? $request : null;
    }

    /**
     * @param object $request
     * @return array<string, string>
     */
    private static function headersFromRequest($request)
    {
        $headers = array();

        if (isset($request->server) && is_object($request->server) && method_exists($request->server, 'all')) {
            foreach ($request->server->all() as $key => $value) {
                if (is_string($key) && strncasecmp($key, 'HTTP_', 5) === 0) {
                    $key = strtoupper($key);
                }
                self::keepHeader($headers, $key, $value);
            }
        }

        if (isset($request->headers) && is_object($request->headers) && method_exists($request->headers, 'all')) {
            foreach ($request->headers->all() as $name => $values) {
                if (!is_string($name) || $name === '') {
                    continue;
                }
                $lower = strtolower($name);
                if ($lower === 'content-type' || $lower === 'content-length' || $lower === 'content-md5') {
                    continue;
                }
                $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
                $value = is_array($values) ? reset($values) : $values;
                self::keepHeader($headers, $key, $value);
            }
        }

        ksort($headers);

        return $headers;
    }

    /**
     * @return array<string, string>
     */
    private static function headersFromServer()
    {
        $headers = array();
        foreach ($_SERVER as $key => $value) {
            if (is_string($key) && strncasecmp($key, 'HTTP_', 5) === 0) {
                self::keepHeader($headers, strtoupper($key), $value);
            }
        }
        ksort($headers);

        return $headers;
    }

    /**
     * @param array<string, string> $headers
     * @param mixed $key
     * @param mixed $value
     * @return void
     */
    private static function keepHeader(array &$headers, $key, $value)
    {
        if (!is_string($key) || !is_scalar($value)) {
            return;
        }
        if (!preg_match('/^HTTP_[A-Z0-9_]+$/', $key)) {
            return;
        }
        $headers[$key] = (string) $value;
    }
}
