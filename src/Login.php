<?php

use Facebook\Facebook;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;

namespace FbServer;


/**
 * Class Login
 */
class Login {

    /**
     * Facebook APP ID
     *
     * @var string
     */
    private  $app_id;

    /**
     * Facebook APP Secret
     *
     * @var string
     */
    private $app_secret;

    /**
     * Callback URL used by the API
     *
     * @var string
     */
    private $callback_url;

    /**
     * Access token from Facebook
     *
     * @var string
     */
    private $access_token;

        /**
     * Where we redirect our user after the process
     *
     * @var string
     */
    private $redirect_url;

    /**
     * User details from the API
     */
    public $fb;
    private $permissions;


    /**
     * ApprFacebook constructor.
     */
    //        'persistent_data_handler' => 'session'
    public function __construct($appId, $appSecret, $appCallBack, $permissions) {
      $this->app_id = $appId;
      $this->app_secret = $appSecret;
      $this->callback_url = $appCallBack;
      $this->permissions = $permissions;

        //    'persistent_data_handler' => 'session'
        $this->fb = new \Facebook\Facebook([
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
        $url = $helper->getLoginUrl($this->callback_url, $this->permissions);

        return $url;

    }


    public function getToken() {

        // Assign the Session variable for Facebook
        $helper = $this->fb->getRedirectLoginHelper();

        try {
          $accessToken = $helper->getAccessToken();
        } catch(\Facebook\Exceptions\FacebookResponseException $e) {
          // When Graph returns an error
          $_COOKIE['facebook_message'] = $e->getMessage();
          return null;
         } catch(\Facebook\Exceptions\FacebookSDKException $e) {
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

}

