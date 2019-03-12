<?php

declare(strict_types = 1);
use PHPUnit\Framework\TestCase;
use YanoshK\EnvironmentPicker\EnvironmentPicker;

final class EnivronmentPickerTest extends TestCase
{
    public $environments  = [
        'production' => '/^(www\.)?my-website\.com$/i'
        , 'staging'    => '/^(demo\.my-website\.com)|(94\.155\.192\.11)$/i'
        , 'local'      => '/^(localhost(:{d}+)?)|(127\.0\.0\.1(:{d}+)?)$/i'
    ];
    public $additionalEnv = [
        'test' => '/test\.foo/'
    ];

    ////////////////////////////////////////////////////////////////////////
    /**
     * This method should always be called first, as it sets the environments
     * for the next tests.
     *
     * @return void
     */
    public function testCanAddEnvironments(): void
    {
        $this->assertEquals($this->environments, EnvironmentPicker::add($this->environments));

        $environments = array_merge($this->environments, $this->additionalEnv);
        $this->assertEquals(
            $environments,
            EnvironmentPicker::add(key($this->additionalEnv), current($this->additionalEnv))
        );
    }

    ////////////////////////////////////////////////////////////////////////
    public function testGetEnvironmentFromHtaccess(): void
    {
        $envVar = EnvironmentPicker::envVarKey();

        putenv("{$envVar}=staging");
        $this->assertEquals('staging', EnvironmentPicker::get());


        putenv("{$envVar}=local");
        $this->assertEquals('local', EnvironmentPicker::get());


        putenv("{$envVar}=non-defined");
        EnvironmentPicker::strict(false);
        $this->assertEquals('non-defined', EnvironmentPicker::get());


        putenv("{$envVar}=non-existent");
        $this->expectException(\InvalidArgumentException::class);
        EnvironmentPicker::strict(true);
        EnvironmentPicker::get();
    }

    ////////////////////////////////////////////////////////////////////////
    public function testGetEnvironmentFromPassedUrl(): void
    {
        $this->assertEquals('production', EnvironmentPicker::get('http://my-webste.com'));
        $this->assertEquals('production', EnvironmentPicker::get('not-well-formated-url'));
        $this->assertEquals('staging', EnvironmentPicker::get('http://demo.my-website.com'));
        $this->assertEquals('local', EnvironmentPicker::get('localhost'));
        $this->assertEquals('local', EnvironmentPicker::get('https://localhost'));
    }

    ////////////////////////////////////////////////////////////////////////
    public function testGetEnvironmentFromCurrentUrl(): void
    {
        // Clear the env var
        putenv(EnvironmentPicker::envVarKey());
        // Tell the app that we are simulating a web request
        putenv('PHPUNIT_SIMULATE_AS_WEB_REQUEST=1');

        $_SERVER['HTTPS']           = 'on';
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['SERVER_PORT']     = '443';
        $_SERVER['HTTP_HOST']       = 'localhost';
        $_SERVER['SERVER_NAME']     = 'localhost';
        $_SERVER['REQUEST_URI']     = '/foo/bar/bazz';


        $this->assertEquals('https://localhost/foo/bar/bazz', EnvironmentPicker::getCurrentURL());
        $this->assertEquals('local', EnvironmentPicker::get());


        $_SERVER['HTTP_HOST'] = 'my-website.com';
        $this->assertEquals('production', EnvironmentPicker::get());
        $this->assertEquals(true, EnvironmentPicker::isProduction());


        $_SERVER['HTTP_HOST'] = 'demo.my-website.com';
        $this->assertEquals('staging', EnvironmentPicker::get());


        $_SERVER['HTTP_HOST'] = 'non-existen.com';
        $this->assertEquals('production', EnvironmentPicker::get());
    }

    ////////////////////////////////////////////////////////////////////////
    public function testCliDetection(): void
    {
        putenv('PHPUNIT_SIMULATE_AS_WEB_REQUEST');
        $this->assertEquals(true, EnvironmentPicker::isCLI());
        $this->assertEquals(false, EnvironmentPicker::isWeb());


        putenv('PHPUNIT_SIMULATE_AS_WEB_REQUEST=1');
        $this->assertEquals(false, EnvironmentPicker::isCLI());
        $this->assertEquals(true, EnvironmentPicker::isWeb());
    }

    ////////////////////////////////////////////////////////////////////////
    public function testPrepUrl(): void
    {
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $this->assertEquals('http://demo.my-website.com', EnvironmentPicker::prepURL('demo.my-website.com'));


        $this->assertEquals('http://localhost', EnvironmentPicker::prepURL('localhost'));
        $this->assertEquals('https://localhost', EnvironmentPicker::prepURL('https://localhost'));

        // Note that even if the request is made trough HTTPS the preped URL will show http://
        $_SERVER['HTTPS'] = 'on';
        $this->assertEquals('http://localhost', EnvironmentPicker::prepURL('localhost'));
    }

    ////////////////////////////////////////////////////////////////////////
    public function testGetEnvVarKey(): void
    {
        $this->assertEquals('ENGINE_ENV', EnvironmentPicker::envVarKey());

        EnvironmentPicker::envVarKey('FOO_BAR');
        $this->assertEquals('FOO_BAR', EnvironmentPicker::envVarKey());
    }
}
