# PiwikProxyServiceProvider

[![Build Status](https://travis-ci.org/tiraeth/piwik-proxy-service-provider.png)](https://travis-ci.org/tiraeth/piwik-proxy-service-provider)

PiwikProxyServiceProvider allows you to quickly create a proxying route for Piwik tracking to hide the location of Piwik's server.

## Requirements

The only requirement is to have ```file_get_contents``` switched to enable URL fetching. But you can ommit this requirement by providing own implementation of ```Mach\Silex\Piwik\RemoteContentInterface``` which is used to execute calls to Piwik's installation. By default it uses ```file_get_contents``` but you can provide your own version which will use ```cURL``` for instance.

## Installation

Add requirement to ```composer.json```:

```json
{
    "require": {
        "mach/piwik-proxy-service-provider": "~1.0"
    }
}
```

And update it ```php composer.phar update mach/silex-proxy-service-provider```.

## Configuration

Below is a structure of configuration array for the provider, and descriptions for each keys.

```json
{
    "piwik.proxy.url": "<url-to-piwik-installation>",
    "piwik.proxy.token": "<token-with-admin-access-to-sites>",
    "piwik.proxy.timeout": <timeout-in-seconds-for-request>",
    "piwik.proxy.cache": "<cache-in-seconds-for-js-file>"
}
```

By default, ```piwik.proxy.timeout``` is set to ```5```, and ```piwik.proxy.cache``` to ```86400``` which gives us a daily cache for requesting javascript tracker.

## Usage

To use the provider you have to register it first and provide options:

```php
<?php

use Silex\Application;
use Mach\Silex\Piwik\PiwikProxyServiceProvider;

$app = new Application();
$app->register(new PiwikProxyServiceProvider(), array(
    'piwik.proxy.url' => 'http://piwik.example.com',
    'piwik.proxy.token' => 'xyz',
));
```

The provider registers a closure under ```$app['piwik.proxy']```. The closure always returns an instance of ```Symfony\Component\HttpFoundation\Response```.

Next step is to create a route (e.g. ```/tracker```) that will serve the JavaScript file and handle tracking requests.

```php
<?php

$app->get('/tracker', function(Request $request) use ($app) {
    return $app['piwik.proxy']($request);
});
```

To override default settings, you can pass array of options as second argument to the closure:

```php
<?php

$app->get('/tracker/{id}', function(Request $request, $id) use ($app) {
    return $app['piwik.proxy']($request, array(
        'url' => "https://piwik-$id.example.com"
    ));
});
```

## License

PiwikProxyServiceProvider is licensed under the MIT license.