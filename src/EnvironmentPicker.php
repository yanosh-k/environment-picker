<?php

namespace YanoshK\EnvironmentPicker;

/**
 * Choose a labeled environment based on environment variables or
 * URL.
 *
 * This class is based on the work of dfreerksen. The original gist can be
 * found here: https://gist.github.com/dfreerksen/3366172.
 *
 *
 * @author David (dfreerksen - https://github.com/dfreerksen)
 * @author Yanosh Kunsh (https://github.com/yanosh-k) <me@yanosh.net>
 */
class EnvironmentPicker
{
    /**
     * Current environment
     *
     * @var string
     */
    protected static $env;
    /**
     * Known environments
     *
     * @var array
     */
    protected static $environments = [];
    /**
     * Strict environments (only allow known environments to be used)
     *
     * @var bool
     */
    protected static $strict       = true;
    /**
     * Environment variable name set in .htaccess (or equivalent)
     *
     * @var string
     */
    protected static $htaccess_env = 'ENGINE_ENV';
    /**
     * Default environment name
     *
     * @var string
     */
    protected static $default_env  = 'production';
    /**
     * Magic method for testing environment. Does not work in PHP < 5.3. Causes fatal error
     * ex: Environment::isLocal() === Environment::is('local')
     *
     * @param   $name
     * @param   $arguments
     * @return  bool
     */
    public static function __callStatic($name, $arguments)
    {
        if (substr($name, 0, 2) == 'is') {
            $env = strtolower(substr($name, 2));
            $url = isset($arguments[0]) ? $arguments[0] : null;

            return self::is($env, $url);
        }
    }
    /**
     * Set environment(s) (an alias for self::init())
     *
     * @param   array|string    $env
     * @param   string          $regex
     * @return  bool
     */
    public static function init($env = [], $regex = '')
    {
        return self::add($env, $regex);
    }
    /**
     * Add environment(s)
     *
     * @param   array|string    $env
     * @param   string          $regex
     * @return  array
     */
    public static function add($env = [], $regex = '')
    {
        // Array of environments
        if (is_array($env)) {
            array_change_key_case($env, CASE_LOWER);
        } else { // Single environment
            $env = [strtolower($env) => $regex];
        }

        // Combine the existing environments with the newly defined ones
        return self::$environments = array_merge(self::$environments, $env);
    }
    /**
     * Strict environment
     *
     * @param   null|bool   $strict
     * @return  bool
     */
    public static function strict($strict = null)
    {
        // Value not set. Return the strict value
        if (is_null($strict)) {
            return self::$strict;
        }

        // Value set. If it is a boolean, set it. Otherwise, skip it
        if (is_bool($strict)) {
            self::$strict = $strict;
        }

        return self::$strict;
    }
    /**
     * Set or get environment var key from .htaccess file
     *
     * @param   string  $var
     * @return  string
     */
    public static function envVarKey($var = null)
    {
        // Value not set. Return the .htaccess environment var
        if (is_null($var)) {
            return self::$htaccess_env;
        }

        // Value set. If it is not empty, set it. Otherwise, skip it
        if (!empty($var)) {
            self::$htaccess_env = $var;
        }

        return self::$htaccess_env;
    }
    /**
     * Environment var value from .htaccess file
     *
     * @param   mixed   $default
     * @return  bool
     */
    public static function envVar($default = null)
    {
        return (getenv(self::$htaccess_env)) ? strtolower(getenv(self::$htaccess_env)) : $default;
    }
    /**
     * Get current environment
     *
     * @param   string  $url
     * @return  string
     */
    public static function get($url = null)
    {
        // To be tried if no URL is passed
        $envFromVar = self::envVar();


        // extract the host from the passed URL
        if ($url) {
            $prepedUrl = self::prepURL($url);
            $host      = parse_url($prepedUrl, PHP_URL_HOST);
        } elseif ($envFromVar) {// .htaccess environment variable is set. Make that the current environment
            return self::setEnv($envFromVar);
        } elseif (!$url) {// try to load the host from the request uri
            $host = parse_url(self::getCurrentURL(), PHP_URL_HOST);
        }


        // Determine the environment based on the host
        if (isset($host)) {
            foreach (self::$environments as $env => $regex) {
                if (preg_match($regex, $host)) {
                    return self::setEnv($env);
                }
            }
        }


        // No enivronment cloud be determined, so just return the default one
        return self::setEnv(self::$default_env);
    }
    /**
     * Check is specific environment
     *
     * @param   string  $env
     * @param   string  $url
     * @return  bool
     */
    public static function is($env = null, $url = null)
    {
        // Current environment
        $current = self::get($url);

        // Environment to check for
        $lowerCaseEnv = strtolower($env);

        // Are they the same?
        return ($current === $lowerCaseEnv) ? true : false;
    }
    /**
     * Is running through command line
     *
     * @return  bool
     */
    public static function isCLI()
    {
        // To work as expcted unit testes sometimes should mask that they are run trough CLI
        $shouldHideCli      = getenv('PHPUNIT_SIMULATE_AS_WEB_REQUEST');
        // Check if the SAPI constant is set and if so look at its content
        $isServerApiCli     = defined('PHP_SAPI') && PHP_SAPI == 'cli';
        // This is a CLI if the argc key is set and greater than one
        $hasPassedArguments = isset($_SERVER['argc']) && $_SERVER['argc'] >= 1;

        $isCli = $isServerApiCli || $hasPassedArguments;
        if (!$shouldHideCli && $isCli) {
            return true;
        }

        return false;
    }
    /**
     * Is running on web (not command line )
     *
     * @return  bool
     */
    public static function isWeb()
    {
        return !self::isCLI();
    }
    /**
     * Determine the full URL that was requested by the client.
     *
     * @return string|boolean
     */
    public static function getCurrentURL()
    {
        $currentURL = false;

        // Try to determine the current URL only when this is a web request
        if (self::isWeb()) {
            $s        = &$_SERVER;
            $ssl      = (isset($s['HTTPS']) && strtolower($s['HTTPS']) === 'on');
            $sp       = strtolower($s['SERVER_PROTOCOL']);
            $protocol = substr($sp, 0, strpos($sp, '/')) . (($ssl) ? 's' : '');
            $port     = $s['SERVER_PORT'];
            $port     = ((!$ssl && $port == '80') || ($ssl && $port == '443')) ? '' : ':' . $port;
            $host     = isset($s['HTTP_HOST']) ? $s['HTTP_HOST'] : $s['SERVER_NAME'];

            $currentURL = $protocol . '://' . $host . $port . $s['REQUEST_URI'];
        }


        return $currentURL;
    }
    /**
     * Prepare the URI in case it's not an actual URL
     *
     * @param   string  $url
     * @return  string
     */
    public static function prepURL($url = '')
    {
        $trimmedUrl = trim($url);
        $prepedUrl  = '';

        // Check if any URL was passed at all
        if ($trimmedUrl) {
            // If the URL contains a protocol, it is ok
            if (preg_match('@^(?:[a-z]+:)?//@i', $trimmedUrl)) {
                $prepedUrl = $trimmedUrl;
            } else {// If no protocol was detected add it to the URI
                $serverProtocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : '';
                $urlProtocol    = strtolower(substr($serverProtocol, 0, strpos($serverProtocol, '/')));

                $prepedUrl = $urlProtocol ? $urlProtocol . '://' : '//';
                $prepedUrl .= $trimmedUrl;
            }
        }


        return $prepedUrl;
    }
    /**
     * Set environment
     *
     * @param   string  $env
     * @return  string
     */
    private static function setEnv($env = null)
    {
        // Strict mode is on. Must be one of the known environments
        if ($env != self::$default_env && self::$strict && !in_array($env, array_keys(self::$environments))) {
            $valid = array_merge((array) self::$default_env, (array) array_keys(self::$environments));

            $msgHeader = 'Unable to get environment. Environment must be one of the following: ';
            throw new \InvalidArgumentException($msgHeader . implode(', ', $valid));
        }

        return $env;
    }
}
