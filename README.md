# Facebook Login for PHP (v1)

This repository contains the open source PHP files that allows you to access the Facebook Platform from your PHP app, server to server, without Javascript.

## Installation

The Facebook Login PHP can be installed with [Composer](https://getcomposer.org/). Run this command:

```sh
composer require facebook/graph-sdk
composer require grippo/fb-php-server-login
```

## Usage: login


```php

if(!session_id()) {
    session_start();
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../vendor/grippo/fb-php-server-login/src/FbServer/Login.php';

use Login;

// Is user logged in?
if (array_key_exists('facebook_access_token', $_SESSION) && isset($_SESSION['facebook_access_token']))
{
	// user is logged in
} else {
	$config = parse_ini_file('../.config.ini', true);
    $login = new Login($config['facebook']['app_id'], $config['facebook']['app_secret'], $config['facebook']['callback_url'], $config['facebook']['permissions']);
	$url = $login->getLoginUrl();
	// redirect to $url to login
}


```

## Usage: Callback


```php

   $config = parse_ini_file('../.config.ini', true);
   $login = new Login($config['facebook']['app_id'], $config['facebook']['app_secret'], $config['facebook']['callback_url'], $config['facebook']['permissions']);
   $accessToken = $login->getToken(); 
   if (isset($accessToken)) {
        $_SESSION['facebook_access_token'] = (string) $accessToken;
        echo "User logged in!"
    } else {
        echo "Not logged in: ". $_COOKIE['facebook_message'] . "\n";
    } 
 

```

## Contributing



## License

Open source.

## Security Vulnerabilities

If you have found a security issue, please contact the maintainers directly at [jorge@grippo.com](mailto:jorge@grippo.com).