<?php

use Facebook\Facebook;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;

/**
 * Class ApprFacebook
 */
class Login {

    /**
     * Facebook APP ID
     *
     * @var string
     */
    private  $app_id = '669394020144537';

    /**
     * Facebook APP Secret
     *
     * @var string
     */
    private $app_secret = '2585852455f21712d38c3d31b5112744';

    /**
     * Callback URL used by the API
     *
     * @var string
     */
    private $callback_url = 'https://ads.ranktool.org/fb/callback/';

    /**
     * Access token from Facebook
     *
     * @var string
     */
    private $access_token;

    private $app_access_token = 'EAAJgz3DJFZAkBALZAZBA9FayyFyGEhRTHgcHJeQdJzdYgqmjMEPfa5mn4FbLzZA4oGZCZB8w5z6dJV4Rm6Vyi6H9a6oQwAy8MZC7QYRl9FHzceZALNNzWowsTqBKLfbyhpC0XZBms1KfUXZBXZBjVIZB8bRywP2C4F3GX0NBZBOTzwLZC2JgZDZD';
    private $sandbox_ad_account_access_token = 'EAAJgz3DJFZAkBABZB822ZB8EA8L3tVQhCCFKknyZBdlh1zAt2HjoOpf95yUnmZCyt1cC5SxOoYcXo2VPQoKegpsG6dHpvhGIv5fYH4I8j65ZCfAStzO91Sx4gyXoiDEZBSIFekkeeu0S7s5LZCrrdKQLSjwbJueqOn9By2qY5AqArIWGfGoZAPqZCt3dwC5elhD97ZAxkXvuCnMwYZBYtZBY40J9Y';

        /**
     * Where we redirect our user after the process
     *
     * @var string
     */
    private $redirect_url;

    /**
     * User details from the API
     */
    private $facebook_details;
    public $fb;


    /**
     * ApprFacebook constructor.
     */
    //        'persistent_data_handler' => 'session'
    public function __construct()
    {

        //    'persistent_data_handler' => 'session'
        $this->fb = new Facebook([
            'app_id' => $this->app_id,
            'app_secret' => $this->app_secret,
            'default_graph_version' => 'v3.2'
        ]);
        return $this->fb;

 
    }

    public function getLoginUrl() {


        $helper = $this->fb->getRedirectLoginHelper();
        if (!isset($_SESSION['facebook_access_token'])) {
          $_SESSION['facebook_access_token'] = null;
        }

        // Optional permissions
        // $permissions = ['email', 'ads_read', 'ads_management', 'manage_pages'];
        $permissions = ['email', 'business_management', 'manage_pages', 'pages_show_list', 'ads_management', 'business_management', 'public_profile', 'ads_read'];
        $url = $helper->getLoginUrl($this->callback_url, $permissions);

        return $url;

    }


    public function getToken($response) {

        // Assign the Session variable for Facebook
        $helper = $this->fb->getRedirectLoginHelper();

        try {
          $accessToken = $helper->getAccessToken();
        } catch(Facebook\Exceptions\FacebookResponseException $e) {
          // When Graph returns an error
          $_COOKIE['facebook_message'] = $e->getMessage();
          return null;
         } catch(Facebook\Exceptions\FacebookSDKException $e) {
          // When validation fails or other local issues
          $_COOKIE['facebook_message'] = $e->getMessage();
          return null;
        }

        if (isset($accessToken)) {
          // Logged in!
          return $accessToken;
          // Now you can redirect to another page and use the
          // access token from $_SESSION['facebook_access_token']
        } elseif ($helper->getError()) {
          $_COOKIE['facebook_message'] = $helper->getError();
            return null;
        }

   }


    public function id() {

        try {
          // Returns a `Facebook\FacebookResponse` object
          $response = $this->fb->get(
            '/me'
          );
        } catch(Facebook\Exceptions\FacebookResponseException $e) {
          echo 'Graph returned an error: ' . $e->getMessage();
          exit;
        } catch(Facebook\Exceptions\FacebookSDKException $e) {
          echo 'Facebook SDK returned an error: ' . $e->getMessage();
          exit;
        }


        $retValue = $response->getGraphUser();

    }
}

