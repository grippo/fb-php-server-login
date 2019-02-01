# Facebook Login for PHP (v1)

This repository contains the open source PHP files that allows you to access the Facebook Platform from your PHP app, server to server, without Javascript.

## Installation

The Facebook Login PHP can be installed with [Composer](https://getcomposer.org/). Run this command:

```sh
composer require grippo/fb-php-server-login
```

## Usage

Simple GET example of a user's profile.

```php
require_once __DIR__ . '/vendor/autoload.php'; // change path as needed

$fb = new \Facebook\Facebook([
  'app_id' => '{app-id}',
  'app_secret' => '{app-secret}',
  'default_graph_version' => 'v2.10',
  //'default_access_token' => '{access-token}', // optional
]);

$me = $response->getGraphUser();
echo 'Logged in as ' . $me->getName();
```

## Contributing



## License

Open source.

## Security Vulnerabilities

If you have found a security issue, please contact the maintainers directly at [jorge@grippo.com](mailto:jorge@grippo.com).