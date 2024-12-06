<?php

namespace MifestaClientBrowserData;

use Detection\MobileDetect;
use Exception;

/**
 * @method static array getHttpHeaders()
 * @method static array getHttpHeader(string $header)
 * @method static array getMobileHeaders()
 * @method static array getUaHttpHeaders()
 * @method static boolean setCfHeaders(array|null $cfHeaders = null)
 * @method static array getCfHeaders()
 * @method static string|null getUserAgent()
 * @method static string|null setDetectionType($type = null)
 * @method static string|null getMatchingRegex()
 * @method static array|null getMatchesArray()
 * @method static array getMobileDetectionRulesExtended()
 * @method static array getRules()
 * @method static array checkHttpHeadersForMobile()
 * @method static boolean isTablet()
 * @method static boolean match(string $regex)
 * @method static float prepareVersionNo(string $ver)
 * @method static string|float version(string $propertyName, string|null $type = null)
 * @method static string mobileGrade()
 * @method static string getScriptVersion()
 * @method static array getPhoneDevices()
 * @method static array getTabletDevices()
 * @method static array getUserAgents()
 * @method static array getBrowsers()
 * @method static array getUtilities()
 * @method static array getMobileDetectionRules()
 * @method static array getOperatingSystems()
 * @method static array getProperties()
 * @see \Detection\MobileDetect
 */
abstract class ClientBrowserData
{
    /**
     * Public methods from \Detection\MobileDetect
     * @var array
     */
    private static array $valid_functions = [
        'getHttpHeaders',
        'getHttpHeader',
        'getMobileHeaders',
        'getUaHttpHeaders',
        'setCfHeaders',
        'getCfHeaders',
        'getUserAgent',
        'setDetectionType',
        'getMatchingRegex',
        'getMatchesArray',
        'getMobileDetectionRulesExtended',
        'getRules',
        'checkHttpHeadersForMobile',
        'isTablet',
        'match',
        'prepareVersionNo',
        'version',
        'mobileGrade',
    ];

    /**
     * Public static methods from \Detection\MobileDetect
     * @var array
     */
    private static array $valid_static_functions = [
        'getScriptVersion',
        'getPhoneDevices',
        'getTabletDevices',
        'getUserAgents',
        'getBrowsers',
        'getUtilities',
        'getMobileDetectionRules',
        'getOperatingSystems',
        'getProperties',
    ];
    /**
     * @var \Detection\MobileDetect
     */
    private static MobileDetect $_mobile_detect_class;
    /**
     * Magic method __callStatic for ClientBrowserData
     * @param string $name
     * @param array $arguments
     * @return mixed
     * @throws \Exception
     */
    public static function __callStatic(string $name, array $arguments)
    {
        if (in_array($name, self::$valid_functions)) {
            return call_user_func_array([self::getMobileDetect(), $name], $arguments);
        } elseif (in_array($name, self::$valid_static_functions)) {
            return call_user_func_array([MobileDetect::class, $name], $arguments);
        } else {
            throw new Exception('Call to undefined methods '.__CLASS__.'::'.$name.'()');
        }
    }

    /**
     * Check if the device is mobile.
     * Returns true if any type of mobile device detected, including special ones
     * @return bool
     */
    public static function isMobile(): bool
    {
        return self::getMobileDetect()->is('MobileBot') || self::getMobileDetect()->isMobile();
    }

    /**
     * This method checks for a 'Bot' property in the userAgent.
     * @return bool
     */
    public static function isAnyBot(): bool
    {
        $classMobileDetect = self::getMobileDetect();
        return !($user_agent = $classMobileDetect->getUserAgent() ?? '') || preg_match('#(bot|spider|yeti|ichiro)#i', $user_agent) || $classMobileDetect->is('MobileBot') || $classMobileDetect->is('Bot');
    }

    /**
     * This method checks for a 'Bot' property in the userAgent.
     * Does not include mobile bots.
     * @return bool
     */
    public static function isBot(): bool
    {
        $classMobileDetect = self::getMobileDetect();
        return !($user_agent = $classMobileDetect->getUserAgent()) || $classMobileDetect->is('Bot') || preg_match('#(yeti|ichiro)#i', $user_agent) || (preg_match('#(bot|spider)#i', $user_agent) && ! $classMobileDetect->is('MobileBot'));
    }

    /**
     * This method checks for a certain property in the userAgent.
     * @param string $key
     * @return bool
     */
    public static function is(string $key): bool
    {
        return match ($key) {
            'Bot' => self::isBot(),
            'Mobile' => self::isMobile(),
            'AnyBot' => self::isAnyBot(),
            default => self::getMobileDetect()->is($key),
        };
    }

    /**
     * Get class \Detection\MobileDetect
     * @return \Detection\MobileDetect
     */
    private static function getMobileDetect(): MobileDetect
    {
        if (! self::$_mobile_detect_class) {
            self::$_mobile_detect_class = new MobileDetect($_SERVER, request()->header('User-Agent'));
        }
        return self::$_mobile_detect_class;
    }
}
