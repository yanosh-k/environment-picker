<?php

    namespace YanoshK\EnvironmentPicker;

    /**
     * Choose a labeled environment based on environment variables or
     * URL.
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
        static protected $env;

        /**
         * Known environments
         *
         * @var array
         */
        static protected $environments = [];

        /**
         * Strict environments (only allow known environments to be used)
         *
         * @var bool
         */
        static protected $strict = false;

        /**
         * Environment variable name set in .htaccess (or equivalent)
         *
         * @var string
         */
        static protected $htaccess_env = 'ENGINE_ENV';

        /**
         * Default environment name
         *
         * @var string
         */
        static protected $default_env = 'production';

        /**
         * Magic method for testing environment. Does not work in PHP < 5.3. Causes fatal error
         * ex: Environment::isLocal() === Environment::is('local')
         *
         * @param   $name
         * @param   $arguments
         * @return  bool
         */
        static public function __callStatic($name, $arguments)
        {
            if (substr($name, 0, 2) == 'is')
            {
                $env = strtolower(substr($name, 2));

                $url = isset($arguments[0]) ? $arguments[0] : null;

                return self::is($env, $url);
            }
        }

        /**
         * Set environment(s)
         *
         * @param   array|string    $env
         * @param   string          $regex
         * @return  bool
         */
        static public function init($env = [], $regex = '')
        {
            return self::add($env, $regex);
        }

        /**
         * Add environment(s)
         *
         * @param   array|string    $env
         * @param   string          $regex
         * @return  bool
         */
        static public function add($env = [], $regex = '')
        {
            // Array of environments
            if (is_array($env))
            {
                array_change_key_case($env, CASE_LOWER);
            }

            // Single environment
            else
            {
                $env = [
                    strtolower($env) => $regex
                ];
            }

            // Combine the existing environments with the newly defined ones
            self::$environments = array_merge(self::$environments, $env);

            return true;
        }

        /**
         * Strict environment
         *
         * @param   null|bool   $strict
         * @return  bool
         */
        static public function strict($strict = null)
        {
            // Value not set. Return the strict value
            if (is_null($strict))
            {
                return self::$strict;
            }

            // Value set. If it is a boolean, set it. Otherwise, skip it
            if (is_bool($strict))
            {
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
        static public function envVarKey($var = null)
        {
            // Value not set. Return the .htaccess environment var
            if (is_null($var))
            {
                return self::$htaccess_env;
            }

            // Value set. If it is not empty, set it. Otherwise, skip it
            if (!empty($var))
            {
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
        static public function envVar($default = null)
        {
            return (getenv(self::$htaccess_env)) ? strtolower(getenv(self::$htaccess_env)) : $default;
        }

        /**
         * Get current environment
         *
         * @param   string  $url
         * @return  string
         */
        static public function get($url = null)
        {
            // extract the host from the passed URL
            if ($url)
            {
                $prepedUrl = self::_prepUrl($url);
                $host      = parse_url($prepedUrl, PHP_URL_HOST);
            }
            // .htaccess environment variable is set. Make that the current environment
            else if ($getnev = self::envVar())
            {
                return self::_setEnv($getenv);
            }
            // try to load the host from the request uri
            else if (!$url)
            {
                $host = parse_url(self::getCurrentURL(), PHP_URL_HOST);
            }

            
            // Determine the environment based on the host
            if (isset($host))
            {
                foreach (self::$environments as $env => $regex)
                {
                    if (preg_match($regex, $host))
                    {
                        return self::_setEnv($env);
                    }
                }
            }


            // No enivronment cloud be determined, so just return the default one
            return self::_setEnv(self::$default_env);
        }

        /**
         * Check is specific environment
         *
         * @param   string  $env
         * @param   string  $url
         * @return  bool
         */
        static public function is($env = null, $url = null)
        {
            // Current environment
            $current = self::get($url);

            // Environment to check for
            $env = strtolower($env);

            // Are they the same?
            return ($current === $env) ? true : false;
        }

        /**
         * Is running through command line
         *
         * @return  bool
         */
        static public function isCLI()
        {
            if ((defined('PHP_SAPI') && PHP_SAPI == 'cli') || (isset($_SERVER['argc']) && $_SERVER['argc'] >= 1))
            {
                return true;
            }

            return false;
        }

        /**
         * Is running on web (not command line )
         *
         * @return  bool
         */
        static public function isWeb()
        {
            return !self::isCLI();
        }

        /**
         * Determine the full URL that was requested by the client.
         * 
         * @return string|boolean
         */
        static public function getCurrentURL()
        {
            $currentURL = false;

            // Try to determine the current URL only when this is a web request
            if (self::isWeb())
            {
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
         * Set environment
         *
         * @param   string  $env
         * @return  string
         */
        static private function _setEnv($env = null)
        {
            // Strict mode is on. Must be one of the known environments
            if ($env != self::$default_env && self::$strict && !in_array($env, array_keys(self::$environments)))
            {
                $valid = array_merge((array) self::$default_env, (array) array_keys(self::$environments));

                throw new \InvalidArgumentException('Unable to get environment. Environment must be one of the following: ' . implode(', ', $valid));
            }

            return $env;
        }

        /**
         * Check if valid URL
         *
         * @param   string  $url
         * @return  bool
         */
        static private function _isUrl($url = '')
        {
            return (filter_var($url, FILTER_VALIDATE_URL)) ? true : false;
        }

        /**
         * Prepare the URL in case it's not an actual URL
         *
         * @param   string  $url
         * @return  string
         */
        static private function _prepUrl($url = '')
        {
            $url = trim($url);

            if ($url)
            {
                if (!preg_match('/^https?:\/\//i', $url))
                {
                    $url = 'http://' . $url;
                }
            }

            return $url;
        }

    }
    