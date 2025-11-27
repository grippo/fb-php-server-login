<?php

namespace FbServer;

/**
 * Class Login
 * Facebook Login implementation using cURL (no SDK dependency)
 */
class Login {

    /**
     * Facebook APP ID
     *
     * @var string
     */
    private $app_id;

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
     * Permissions requested from Facebook
     *
     * @var array
     */
    private $permissions;

    /**
     * Facebook Graph API version
     *
     * @var string
     */
    private $graph_version = 'v22.0';

    /**
     * Facebook Graph API base URL
     *
     * @var string
     */
    private $graph_url = 'https://graph.facebook.com';

    /**
     * Kept for backward compatibility
     * @var object
     */
    public $fb;

    /**
     * Login constructor.
     *
     * @param string $appId Facebook App ID
     * @param string $appSecret Facebook App Secret
     * @param string $appCallBack Callback URL
     * @param array $permissions Array of permissions
     */
    public function __construct($appId, $appSecret, $appCallBack, $permissions) {
        $this->app_id = $appId;
        $this->app_secret = $appSecret;
        $this->callback_url = $appCallBack;
        $this->permissions = is_array($permissions) ? $permissions : [$permissions];
        
        // Backward compatibility - set a placeholder object
        $this->fb = (object)['app_id' => $this->app_id];
        
        return $this->fb;
    }

    /**
     * Generate Facebook Login URL
     *
     * @return string Login URL
     */
    public function getLoginUrl() {
        if (!isset($_SESSION['facebook_access_token'])) {
            $_SESSION['facebook_access_token'] = null;
        }

        // Generate state parameter for CSRF protection
        $state = bin2hex(random_bytes(16));
        $_SESSION['facebook_state'] = $state;

        $params = [
            'client_id' => $this->app_id,
            'redirect_uri' => $this->callback_url,
            'state' => $state,
            'scope' => implode(',', $this->permissions),
            'response_type' => 'code'
        ];

        return 'https://www.facebook.com/' . $this->graph_version . '/dialog/oauth?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for access token
     *
     * @return string|null Access token or null on failure
     */
    public function getToken() {
        // Verify state parameter to prevent CSRF
        if (!isset($_GET['state']) || !isset($_SESSION['facebook_state']) || 
            $_GET['state'] !== $_SESSION['facebook_state']) {
            $_COOKIE['facebook_message'] = 'Invalid state parameter';
            return null;
        }

        // Check for error response
        if (isset($_GET['error'])) {
            $_COOKIE['facebook_message'] = $_GET['error_description'] ?? $_GET['error'];
            return null;
        }

        // Get authorization code
        if (!isset($_GET['code'])) {
            $_COOKIE['facebook_message'] = 'No authorization code received';
            return null;
        }

        $code = $_GET['code'];

        // Exchange code for access token
        $tokenUrl = $this->graph_url . '/' . $this->graph_version . '/oauth/access_token';
        $params = [
            'client_id' => $this->app_id,
            'client_secret' => $this->app_secret,
            'redirect_uri' => $this->callback_url,
            'code' => $code
        ];

        $response = $this->makeCurlRequest($tokenUrl . '?' . http_build_query($params));

        if (!$response) {
            $_COOKIE['facebook_message'] = 'Failed to get access token';
            return null;
        }

        $data = json_decode($response, true);

        if (isset($data['error'])) {
            $_COOKIE['facebook_message'] = $data['error']['message'] ?? 'Unknown error';
            return null;
        }

        if (isset($data['access_token'])) {
            // Store token and expiration info
            $_SESSION['facebook_access_token'] = $data['access_token'];
            $_SESSION['facebook_token_type'] = $data['token_type'] ?? 'bearer';
            
            // Calculate expiration timestamp
            if (isset($data['expires_in'])) {
                $_SESSION['facebook_token_expires'] = time() + $data['expires_in'];
            }
            
            return $data['access_token'];
        }

        $_COOKIE['facebook_message'] = 'No access token in response';
        return null;
    }

    /**
     * Check if access token has expired
     *
     * @param string $accessToken Access token to check
     * @return bool True if expired, false otherwise
     */
    public function isTokenExpired($accessToken = null) {
        // Check session expiration time first (if available)
        if (isset($_SESSION['facebook_token_expires'])) {
            if (time() >= $_SESSION['facebook_token_expires']) {
                return true;
            }
        }

        // Use the provided token or session token
        $token = $accessToken ?? ($_SESSION['facebook_access_token'] ?? null);
        
        if (!$token) {
            return true;
        }

        // Debug token to check expiration
        $debugUrl = $this->graph_url . '/' . $this->graph_version . '/debug_token';
        $params = [
            'input_token' => $token,
            'access_token' => $this->app_id . '|' . $this->app_secret
        ];

        $response = $this->makeCurlRequest($debugUrl . '?' . http_build_query($params));

        if (!$response) {
            return true;
        }

        $data = json_decode($response, true);

        if (isset($data['data']['is_valid']) && $data['data']['is_valid'] === false) {
            return true;
        }

        if (isset($data['data']['expires_at'])) {
            $expiresAt = $data['data']['expires_at'];
            if ($expiresAt > 0 && time() >= $expiresAt) {
                return true;
            }
        }

        return false;
    }

    /**
     * Exchange short-lived token for long-lived token
     *
     * @param string $shortToken Short-lived access token
     * @return string|null Long-lived token or null on failure
     */
    public function getLongLivedToken($shortToken) {
        $tokenUrl = $this->graph_url . '/' . $this->graph_version . '/oauth/access_token';
        $params = [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $this->app_id,
            'client_secret' => $this->app_secret,
            'fb_exchange_token' => $shortToken
        ];

        $response = $this->makeCurlRequest($tokenUrl . '?' . http_build_query($params));

        if (!$response) {
            return null;
        }

        $data = json_decode($response, true);

        if (isset($data['access_token'])) {
            // Update session with new token
            $_SESSION['facebook_access_token'] = $data['access_token'];
            
            if (isset($data['expires_in'])) {
                $_SESSION['facebook_token_expires'] = time() + $data['expires_in'];
            }
            
            return $data['access_token'];
        }

        return null;
    }

    /**
     * Refresh access token if expired
     *
     * @param string $accessToken Current access token
     * @return string|null Refreshed token or null on failure
     */
    public function refreshToken($accessToken = null) {
        $token = $accessToken ?? ($_SESSION['facebook_access_token'] ?? null);
        
        if (!$token) {
            return null;
        }

        // Check if token is expired
        if (!$this->isTokenExpired($token)) {
            return $token; // Token is still valid
        }

        // Try to get long-lived token
        $newToken = $this->getLongLivedToken($token);
        
        if ($newToken) {
            return $newToken;
        }

        // If refresh failed, clear session
        unset($_SESSION['facebook_access_token']);
        unset($_SESSION['facebook_token_expires']);
        
        return null;
    }

    /**
     * Get valid access token (auto-refresh if needed)
     *
     * @return string|null Valid access token or null
     */
    public function getValidToken() {
        if (!isset($_SESSION['facebook_access_token'])) {
            return null;
        }

        $token = $_SESSION['facebook_access_token'];

        // Check and refresh if needed
        if ($this->isTokenExpired($token)) {
            $token = $this->refreshToken($token);
        }

        return $token;
    }

    /**
     * Make cURL request
     *
     * @param string $url URL to request
     * @param array $postData Optional POST data
     * @param array $headers Optional headers
     * @return string|false Response body or false on failure
     */
    private function makeCurlRequest($url, $postData = null, $headers = []) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        if ($postData !== null) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($postData) ? http_build_query($postData) : $postData);
        }
        
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        
        if ($httpCode >= 400 || $response === false) {
            return false;
        }
        
        return $response;
    }

    /**
     * Make Graph API call
     *
     * @param string $endpoint API endpoint (e.g., '/me')
     * @param string $accessToken Access token
     * @param string $method HTTP method (GET, POST, DELETE)
     * @param array $params Additional parameters
     * @return array|null Response data or null on failure
     * Example:  $login->graphApiCall('me', $_SESSION['facebook_access_token'], 'GET', ['fields' => 'id,name,picture,email']);
     */
    public function graphApiCall($endpoint, $accessToken = null, $method = 'GET', $params = []) {
        $token = $accessToken ?? $this->getValidToken();
        
        if (!$token) {
            return null;
        }

        $endpoint = ltrim($endpoint, '/');
        $url = $this->graph_url . '/' . $this->graph_version . '/' . $endpoint;
        
        $params['access_token'] = $token;
        
        if ($method === 'GET') {
            $url .= '?' . http_build_query($params);
            $response = $this->makeCurlRequest($url);
        } else {
            $response = $this->makeCurlRequest($url, $params);
        }
        
        if (!$response) {
            return null;
        }
        
        return json_decode($response, true);
    }
}