<?php
// Google API Client Manual Implementation
// Versi sederhana tanpa Composer

class Google_Client {
    private $clientId;
    private $clientSecret;
    private $redirectUri;
    private $authUri;
    private $tokenUri;
    private $accessToken;
    private $accessType;
    private $approvalPrompt;
    private $scopes = [];
    
    public function setClientId($clientId) {
        $this->clientId = $clientId;
    }
    
    public function setClientSecret($clientSecret) {
        $this->clientSecret = $clientSecret;
    }
    
    public function setRedirectUri($redirectUri) {
        $this->redirectUri = $redirectUri;
    }
    
    public function setAuthConfig($configFile) {
        $config = json_decode(file_get_contents($configFile), true);
        $this->clientId = $config['web']['client_id'];
        $this->clientSecret = $config['web']['client_secret'];
        $this->redirectUri = $config['web']['redirect_uris'][0]; // Gunakan yang pertama sebagai default
        $this->authUri = $config['web']['auth_uri'];
        $this->tokenUri = $config['web']['token_uri'];
    }
    
    public function addScope($scope) {
        $this->scopes[] = $scope;
    }
    
    public function createAuthUrl() {
        $scope = implode(' ', $this->scopes);
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => $scope,
            'response_type' => 'code'
        ];
        
        // Tambahkan access_type jika sudah di-set
        if (isset($this->accessType)) {
            $params['access_type'] = $this->accessType;
        }
        
        // Tambahkan approval_prompt jika sudah di-set
        if (isset($this->approvalPrompt)) {
            $params['approval_prompt'] = $this->approvalPrompt;
        }
        
        return $this->authUri . '?' . http_build_query($params);
    }
    
    public function fetchAccessTokenWithAuthCode($code) {
        $postdata = http_build_query([
            'code' => $code,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'grant_type' => 'authorization_code'
        ]);
        
        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => $postdata,
                'ignore_errors' => true
            ]
        ];
        
        $context = stream_context_create($opts);
        $response = file_get_contents($this->tokenUri, false, $context);
        $result = json_decode($response, true);
        
        // Validasi response
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Response JSON tidak valid: ' . json_last_error_msg());
        }
        
        if (isset($result['error'])) {
            throw new Exception('Error dari Google API: ' . $result['error_description']);
        }
        
        return $result;
    }
    
    public function setAccessToken($token) {
        $this->accessToken = $token;
    }
    
    public function getAccessToken() {
        return $this->accessToken;
    }
    
    public function setAccessType($accessType) {
        $this->accessType = $accessType;
    }
    
    public function setApprovalPrompt($approvalPrompt) {
        $this->approvalPrompt = $approvalPrompt;
    }
}

class Google_Service_Oauth2 {
    private $client;
    
    public function __construct($client) {
        $this->client = $client;
    }
    
    public function userinfo() {
        return new Google_Service_Oauth2_Userinfo($this->client);
    }
}

class Google_Service_Oauth2_Userinfo {
    private $client;
    
    public function __construct($client) {
        $this->client = $client;
    }
    
    public function get() {
        $accessToken = $this->client->getAccessToken();
        if (empty($accessToken)) {
            throw new Exception('Token akses tidak tersedia');
        }
        
        $opts = [
            'http' => [
                'header' => 'Authorization: Bearer ' . $accessToken,
                'ignore_errors' => true
            ]
        ];
        
        $context = stream_context_create($opts);
        $response = file_get_contents('https://www.googleapis.com/oauth2/v2/userinfo', false, $context);
        
        if ($response === false) {
            throw new Exception('Gagal mengambil informasi pengguna dari Google API');
        }
        
        $result = json_decode($response);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Response JSON tidak valid: ' . json_last_error_msg());
        }
        
        return $result;
    }
}

// Konstanta untuk scope
define('USERINFO_PROFILE', 'https://www.googleapis.com/auth/userinfo.profile');
define('USERINFO_EMAIL', 'https://www.googleapis.com/auth/userinfo.email');