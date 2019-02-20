Environment Picker Class for PHP
================================

This class lets your applications determine which labeled environment is it running in, based on:
- an environment variable (detected with `getenv()`)
- a passed URL to the EnvironmentPicker::get() function
- the current URL (when not run trouh CLI)


Usage
=====

By default, if an environment could not be determined from an environment variable
or an URL, the class returns a default label, which is `production`.

Using host detection
--------------------

Please note, that when using `EnvironmentPicker::get()` the `getenv()` value always
gets precedence when both the environment value and labeled regex expressions are present.

```php
$environments = array(
    'local'   => '/^(localhost(:{d}+)?)|(127\.0\.0\.1(:{d}+)?)$/i',
    'staging' => '/^(demo\.my-website\.com)|(8\.8\.8\.8)$/i'
);

// Initialize environments
\YanoshK\EnvironmentPicker\EnvironmentPicker::init($environments);

// Returns the label of the environment for the current URL
\YanoshK\EnvironmentPicker\EnvironmentPicker::get();


// Return the label of the environment for the given URL
\YanoshK\EnvironmentPicker\EnvironmentPicker::get('https://demo.my-website.com/foo/bar');
```


Using a predefined environment value
------------------------------------

You can set the environment variable in your server configurations, in your .htaccess file
(if you are using Apache) or on runtime while calling `export ENGINE_ENV=staging`.

Be careful and initialize the allowed environments beforehand. By default only 
environments from the list are allowed as values. If you would like to allow `ENGINE_ENV`
to take any value, you should call `EnvironmentPicker::strict(false)`

```php
$environments = array(
    'local'   => null,
    'staging' => null
);

// Initialize environments
\YanoshK\EnvironmentPicker\EnvironmentPicker::init($environments);

// Before getting the current environment make sure that you have the right variable name
\YanoshK\EnvironmentPicker\EnvironmentPicker::envVarKey('MY_VAR');

\YanoshK\EnvironmentPicker\EnvironmentPicker::get();
```

You can set labled regex values as a fallback, if the variable is not set or empty.

```php
$environments = array(
    'local'   => '/^(localhost(:{d}+)?)|(127\.0\.0\.1(:{d}+)?)$/i',
    'staging' => '/^(demo\.my-website\.com)|(8\.8\.8\.8)$/i'
);

// Initialize environments
\YanoshK\EnvironmentPicker\EnvironmentPicker::init($environments);

// Before getting the current environment make sure that you have the right variable name
\YanoshK\EnvironmentPicker\EnvironmentPicker::envVarKey('MY_VAR');

\YanoshK\EnvironmentPicker\EnvironmentPicker::get();
```