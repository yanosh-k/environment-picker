<?php

    declare(strict_types=1);

    use PHPUnit\Framework\TestCase;
    use YanoshK\EnvironmentPicker\EnvironmentPicker;

    final class EnivronmentPickerTest extends TestCase
    {

        public $environments = [
            'production' => '/^(www\.)?my-website\.com$/i'
            , 'staging'    => '/^(demo\.my-website\.com)|(94\.155\.192\.11)$/i'
            , 'local'      => '/^(localhost(:{d}+)?)|(127\.0\.0\.1(:{d}+)?)$/i'
        ];
        
        public $additionalEnv = [
            'test' => '/test\.foo/'
        ];

        public function testCanAddEnvironments(): void
        {
            $this->assertEquals($this->environments, EnvironmentPicker::add($this->environments));
            
            $environments = array_merge($this->environments, $this->additionalEnv);
            $this->assertEquals($environments, EnvironmentPicker::add(key($this->additionalEnv), current($this->additionalEnv)));
        }
        
        public function testGetEnvironmentFromHtaccess()
        {
            putenv('ENGINE_ENV=staging');
            $this->assertEquals('staging', EnvironmentPicker::get());
            
            
            $this->assertEquals('production', EnvironmentPicker::get('http://my-webste.com'));
            $this->assertEquals('production', EnvironmentPicker::get('not-well-formated-url'));
            $this->assertEquals('staging', EnvironmentPicker::get('http://demo.my-website.com'));
            $this->assertEquals('local', EnvironmentPicker::get('localhost'));
            $this->assertEquals('local', EnvironmentPicker::get('https://localhost'));
            
            
            putenv('ENGINE_ENV=demo');
            $this->expectException(\InvalidArgumentException::class);
            EnvironmentPicker::get();
        }

    }
    