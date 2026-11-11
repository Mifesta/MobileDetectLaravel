<?php

namespace MifestaClientBrowserData\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool isMobile()
 * @method static bool isTablet()
 * @method static bool isBot()
 * @method static bool isAnyBot()
 * @method static bool is(string $key)
 * @method static bool match(string $regex, string|null $userAgent = null)
 * @method static string|float|false version(string $propertyName, string|null $type = null)
 * @method static string setUserAgent(string|null $userAgent)
 * @method static void setHttpHeaders(array|null $httpHeaders = null)
 * @method static string|null getUserAgent()
 * @method static string getScriptVersion()
 *
 * @see \MifestaClientBrowserData\ClientBrowserData
 */
class MobileDetect extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'mifesta.mobiledetect';
    }
}
