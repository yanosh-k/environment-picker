<?php

// Environments
$environments = array(
	'local'   => '/\.[local(host)?|dev|10?]+$/i',
	'staging' => '/\w\.(staging|branch)\.mywebsite\.com$/i'
);

// Initialize environments
Environment::init($environments);


var_dump( Environment::is('production') );
var_dump( Environment::is('staging') );
var_dump( Environment::is('foo') );
//var_dump( Environment::isProduction() ); // Does not work in PHP < 5.3. Causes fatal error
//var_dump( Environment::isLocal() ); // Does not work in PHP < 5.3. Causes fatal error
//var_dump( Environment::isFoo() ); // Does not work in PHP < 5.3. Causes fatal error

var_dump( Environment::is('production', 'something.com') );
var_dump( Environment::is('production', 'something.dev') );
var_dump( Environment::is('foo', 'something.com') );
var_dump( Environment::is('foo', 'something.dev') );

//var_dump( Environment::isProduction('something.dev') ); // Does not work in PHP < 5.3. Causes fatal error
//var_dump( Environment::isProduction('something.com') ); // Does not work in PHP < 5.3. Causes fatal error
//var_dump( Environment::isFoo('something.dev') ); // Does not work in PHP < 5.3. Causes fatal error
//var_dump( Environment::isFoo('something.com') ); // Does not work in PHP < 5.3. Causes fatal error


var_dump( Environment::envVarKey() );


var_dump( Environment::envVar() );


var_dump( Environment::strict() );
var_dump( Environment::strict(true) );
var_dump( Environment::strict(false) );


var_dump( Environment::get() );


var_dump( Environment::isCLI() );


var_dump( Environment::isWeb() );