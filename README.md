# Facebook Login for PHP (v1)

This repository contains the open source PHP files that allows you to access the Facebook Platform from your PHP app, server to server, without Javascript.

## Installation

The Facebook Login PHP can be installed with [Composer](https://getcomposer.org/). Run this command:

```sh
composer require facebook/graph-sdk
composer require grippo/fb-php-server-login
```

## Usage: INI File 

Create a ".config.ini" file with facebook credentials and permissions. Permissions is an array, add how much as you need.

```sh
[facebook]
app_id={{ the app id }}
app_secret={{ the app secret }}
callback_url={{ the callback url }}
permissions[]=email
permissions[]={{ other permission }}
permissions[]={{ other permission }}

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

## Usage: Making calls to Facebook Graph API


```php

        try {
          $response = $this->fb->get(
            '/me',
            $_SESSION['facebook_access_token'];
          );
        } catch(Facebook\Exceptions\FacebookResponseException $e) {
          echo $e->getMessage();
        } catch(Facebook\Exceptions\FacebookSDKException $e) {
          echo $e->getMessage();
        }
        $retValue = $response->getGraphUser();
        print_r($retValue);
 
```

## Contributing



## License

Open source.

## Security Vulnerabilities

If you have found a security issue, please contact the maintainers directly at [jorge@grippo.com](mailto:jorge@grippo.com).