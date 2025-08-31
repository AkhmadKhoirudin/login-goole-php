<?php
require_once 'google-api-client.php';
require_once 'config.php';
require_once 'db_config.php';
require_once 'url_config.php';

function getConfig() {
    return require 'config.php';
}

function getBaseUrl() {
    $urlConfig = require 'url_config.php';
    $currentHost = $_SERVER['HTTP_HOST'];
    
    if ($currentHost === 'localhost' || $currentHost === '127.0.0.1') {
        return $urlConfig['base_url']['localhost'];
    } else {
        return $urlConfig['base_url']['test'];
    }
}

function getRedirectUri() {
    $urlConfig = require 'url_config.php';
    $currentHost = $_SERVER['HTTP_HOST'];
    
    if ($currentHost === 'localhost' || $currentHost === '127.0.0.1') {
        return $urlConfig['redirect_uris'][0];
    } else {
        return $urlConfig['redirect_uris'][1];
    }
}

function getGoogleClient() {
    $config = getConfig();
    
    $client = new Google_Client();
    $client->setClientId($config['client_id']);
    $client->setClientSecret($config['client_secret']);
    $client->setAuthConfig('client_secret_947639411300-viiim4qjvq44ljb7t8e2ihfn4a4ivv7n.apps.googleusercontent.com.json');
    $client->addScope(USERINFO_PROFILE);
    $client->addScope(USERINFO_EMAIL);
    
    // Tentukan redirect URI berdasarkan domain yang sedang digunakan
    $currentHost = $_SERVER['HTTP_HOST'];
    $scriptPath = dirname($_SERVER['PHP_SELF']);
    
    // Pastikan path dimulai dengan /
    if ($scriptPath != '/') {
        $scriptPath = rtrim($scriptPath, '/') . '/';
    }
    
    if ($currentHost === 'localhost' || $currentHost === '127.0.0.1') {
        $redirectUri = 'http://localhost' . $scriptPath . 'callback.php';
    } else {
        $redirectUri = 'http://' . $currentHost . $scriptPath . 'callback.php';
    }
    $client->setRedirectUri($redirectUri);
    
    // Tambahkan access type offline untuk refresh token
    $client->setAccessType('offline');
    $client->setApprovalPrompt('force');
    
    return $client;
}

function handleGoogleLogin($client) {
    if (isset($_GET['code'])) {
        try {
            $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
            
            // Validasi token
            if (!isset($token['access_token']) || empty($token['access_token'])) {
                throw new Exception('Token akses tidak valid atau kosong');
            }
            
            $client->setAccessToken($token['access_token']);

            // Dapatkan informasi pengguna
            $oauth2 = new Google_Service_Oauth2($client);
            $userInfo = $oauth2->userinfo()->get();
            
            // Validasi informasi pengguna
            if (!isset($userInfo->email) || !isset($userInfo->name)) {
                throw new Exception('Informasi pengguna tidak lengkap dari Google API');
            }

            // Simpan informasi pengguna ke database MySQL
            $db = getDbConnection();
            createUsersTable($db);
            
            // Cek apakah user sudah ada
            $stmt = $db->prepare("SELECT id, role FROM users WHERE email = :email");
            $stmt->bindParam(':email', $userInfo->email);
            $stmt->execute();
            
            if ($stmt->fetch() === false) {
                // User belum ada, insert baru dengan role pending
                $stmt = $db->prepare("INSERT INTO users (name, email, username, password, is_blocked, role) VALUES (:name, :email, :username, :password, 0, 'pending')");
                $username = strtolower(str_replace(' ', '_', $userInfo->name)) . rand(1000, 9999);
                $password = ''; // Google login tidak memerlukan password
                $stmt->bindParam(':name', $userInfo->name);
                $stmt->bindParam(':email', $userInfo->email);
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':password', $password);
                $stmt->execute();
                
                // User baru, role pending
                return 'pending';
            } else {
                // User sudah ada, cek role-nya
                $stmt = $db->prepare("SELECT role FROM users WHERE email = :email");
                $stmt->bindParam(':email', $userInfo->email);
                $stmt->execute();
                $userRole = $stmt->fetchColumn();
                
                if ($userRole === 'pending') {
                    // User masih pending
                    return 'pending';
                } elseif ($userRole === 'blocked') {
                    // User diblokir
                    return 'blocked';
                }
                
                // User sudah disetujui, set session
                $_SESSION['user'] = [
                    'name' => $userInfo->name,
                    'email' => $userInfo->email,
                    'role' => $userRole
                ];
                
                return true;
            }
        } catch (Exception $e) {
            // Log error untuk debugging
            error_log('Google Login Error: ' . $e->getMessage());
            return false;
        }
    }
    return false;
}

function isLoggedIn() {
    return isset($_SESSION['user']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}

function authenticateUser($username, $password) {
    try {
        // Debug log
        error_log('Authenticating user: ' . $username);
        
        $db = getDbConnection();
        // Cek apakah user ada, status blokir, dan role-nya
        $stmt = $db->prepare("SELECT id, name, email, username, password, is_blocked, role FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        $user = $stmt->fetch();
        
        // Debug log
        error_log('User found: ' . ($user ? 'Yes' : 'No'));
        
        if (!$user) {
            error_log('User not found: ' . $username);
            return 'not_found';
        }
        
        // Cek apakah user diblokir
        if ($user['is_blocked'] == 1) {
            error_log('User is blocked: ' . $username);
            return 'blocked';
        }
        
        // Cek apakah role-nya pending
        if ($user['role'] == 'pending') {
            error_log('User role is pending: ' . $username);
            return 'pending';
        }
        
        $passwordVerified = password_verify($password, $user['password']);
        error_log('Password verified: ' . ($passwordVerified ? 'Yes' : 'No'));
        
        if ($passwordVerified) {
            // Set session user
            $_SESSION['user'] = [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'username' => $user['username'],
                'role' => $user['role']
            ];
            error_log('Authentication successful for user: ' . $username);
            return true;
        }
        
        error_log('Authentication failed for user: ' . $username);
        return false;
    } catch (Exception $e) {
        error_log('Authentication Error: ' . $e->getMessage());
        return false;
    }
}