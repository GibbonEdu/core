# sms-client
[![Build Status](https://travis-ci.org/matthewbdaly/sms-client.svg?branch=master)](https://travis-ci.org/matthewbdaly/sms-client)

A generic SMS client library. Supports multiple swappable drivers, so that you're never tied to just one provider.

This library is aimed squarely at sending SMS messages only, and I don't plan to add support for other functionality. The idea is to create one library that should be able to work with any provider that has a driver for the purpose of sending SMS messages.

Drivers
-------

It currently ships with the following drivers:

* Clockwork
* Nexmo
* TextLocal
* Twilio
* AWS SNS (requires installation of `aws/aws-sdk-php`)
* Mail (for mail-to-SMS gateways)

In addition, it also has the following drivers for test purposes:

* RequestBin
* Null
* Log

The RequestBin sends the POST request to the specified RequestBin path for debugging. The Null driver does nothing, while the Log driver accepts a PSR3 logger and uses it to log the request.

Example Usage
-----

**Null**

```php
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Response;
use Matthewbdaly\SMS\Drivers\Null;
use Matthewbdaly\SMS\Client;

$guzzle = new GuzzleClient;
$resp = new Response;
$driver = new Null($guzzle, $resp);
$client = new Client($driver);
$msg = [
    'to'      => '+44 01234 567890',
    'content' => 'Just testing',
];
$client->send($msg);
```

**Log**

```php
use Matthewbdaly\SMS\Drivers\Log;
use Matthewbdaly\SMS\Client;
use Psr\Log\LoggerInterface;

$driver = new Log($logger); // $logger should be an implementation of Psr\Log\LoggerInterface
$client = new Client($driver);
$msg = [
    'to'      => '+44 01234 567890',
    'content' => 'Just testing',
];
$client->send($msg);

```

**RequestBin**

```php
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Response;
use Matthewbdaly\SMS\Drivers\RequestBin;
use Matthewbdaly\SMS\Client;

$guzzle = new GuzzleClient;
$resp = new Response;
$driver = new RequestBin($guzzle, $resp, [
    'path' => 'MY_REQUESTBIN_PATH',
]);
$client = new Client($driver);
$msg = [
    'to'      => '+44 01234 567890',
    'content' => 'Just testing',
];
$client->send($msg);
```

**Clockwork**

```php
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Response;
use Matthewbdaly\SMS\Drivers\Clockwork;
use Matthewbdaly\SMS\Client;

$guzzle = new GuzzleClient;
$resp = new Response;
$driver = new Clockwork($guzzle, $resp, [
    'api_key' => 'MY_CLOCKWORK_API_KEY',
]);
$client = new Client($driver);
$msg = [
    'to'      => '+44 01234 567890',
    'content' => 'Just testing',
];
$client->send($msg);
```

**Nexmo**

```php
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Response;
use Matthewbdaly\SMS\Drivers\Nexmo;
use Matthewbdaly\SMS\Client;

$guzzle = new GuzzleClient;
$resp = new Response;
$driver = new Nexmo($guzzle, $resp, [
    'api_key' => 'MY_NEXMO_API_KEY',
    'api_secret' => 'MY_NEXMO_API_SECRET',
]);
$client = new Client($driver);
$msg = [
    'to'      => '+44 01234 567890',
    'from'    => 'Test User',
    'content' => 'Just testing',
];
$client->send($msg);
```

**AWS SNS**

```php
use Matthewbdaly\SMS\Client;
use Matthewbdaly\SMS\Drivers\Aws;

$config = [
    'api_key'    => 'foo',
    'api_secret' => 'bar',
    'api_region' => 'ap-southeast-2'
];
$driver = new Aws($config);
$client = new Client($driver);
$msg = [
    'to'      => '+44 01234 567890',
    'from'    => 'Test User',
    'content' => 'Just testing',
];
$client->send($msg);
```

**Mail**

```php
use Matthewbdaly\SMS\Client;
use Matthewbdaly\SMS\Drivers\Mail;
use Matthewbdaly\SMS\Contracts\Mailer;

$config = [
    'domain' => 'my.sms-gateway.com'
];
$driver = new Mail($config);
$client = new Client($driver);
$msg = [
    'to'      => '+44 01234 567890',
    'content' => 'Just testing',
];
$client->send($msg);
```

**TextLocal**

```php
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Response;
use Matthewbdaly\SMS\Drivers\TextLocal;
use Matthewbdaly\SMS\Client;

$guzzle = new GuzzleClient;
$resp = new Response;
$driver = new TextLocal($guzzle, $resp, [
    'api_key' => 'MY_TEXTLOCAL_API_KEY',
]);
$client = new Client($driver);
$msg = [
    'to'      => '+44 01234 567890',
    'from'    => 'Test User',
    'content' => 'Just testing',
];
$client->send($msg);
```

**Twilio**

```php
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Response;
use Matthewbdaly\SMS\Drivers\Twilio;
use Matthewbdaly\SMS\Client;

$guzzle = new GuzzleClient;
$resp = new Response;
$driver = new Twilio($guzzle, $resp, [
    'account_id' => 'MY_TWILIO_ACCOUNT_ID',
    'api_token' => 'MY_TWILIO_API_TOKEN',
]);
$client = new Client($driver);
$msg = [
    'to'      => '+44 01234 567890',
    'from'      => '+44 01234 567890',
    'content' => 'Just testing',
];
$client->send($msg);
```

Mail driver
-----------

I have implemented a mail driver at `Matthewbdaly\SMS\Drivers\Mail`, but it's very basic and may not work with a lot of mail-to-SMS gateways out of the box. It accepts an instance of the `Matthewbdaly\SMS\Contracts\Mailer` interface as the first argument, and the config array as the second.

I've included the class `Matthewbdaly\SMS\PHPMailAdapter` in the library as a very basic implementation of the mailer interface, but it's deliberately very basic - it's just a very thin wrapper around the PHP `mail()` function. You will almost certainly want to create your own implementation for your own use case - for instance, if you're using Laravel you might create a wrapper class for the `Mail` facade.

The mail driver will nearly always be slower and less reliable than the HTTP-based ones, so if you have to integrate with a provider that doesn't yet have a driver, but does have a REST API, you're probably better off creating an API driver for it. If you do need to work with a mail-to-SMS gateway, you're quite likely to find that you need to extend `Matthewbdaly\SMS\Drivers\Mail` to amend the functionality.

Laravel and Lumen integration
-------------------

Using Laravel or Lumen? You probably want to use [my integration package](https://packagist.org/packages/matthewbdaly/laravel-sms) rather than this one, since that includes a service provider, as well as the `SMS` facade and easier configuration.

Creating your own driver
------------------------

It's easy to create your own driver - just implement the `Matthewbdaly\SMS\Contracts\Driver` interface. You can use whatever method is most appropriate for sending the SMS - for instance, if your provider has a mail-to-SMS gateway, you can happily use Swiftmailer or PHPMailer in your driver to send emails, or if they have a REST API you can use Guzzle.

You can pass any configuration options required in the `config` array in the constructor of the driver. Please ensure that your driver has tests using PHPSpec (see the existing drivers for examples), and that it meets the coding standard (the package includes a PHP Codesniffer configuration for that reason).

If you've created a new driver, feel free to submit a pull request and I'll consider including it.

TODO
----

I have plans for a 2.0 release which include:

* More drivers! If you're using an SMS provider that isn't on the list and you'd like to see support for it in this library, go ahead and create your own driver and submit a pull request for it.
* Remove dependency on Guzzle and replace it with HTTPlug so it doesn't need a specific implementation.
* Add a factory for resolving the drivers automatically.
