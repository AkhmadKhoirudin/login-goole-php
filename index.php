<?php
session_start();
require_once 'google-api-client.php';
require_once 'config.php';
require_once 'auth.php';
require_once 'db_config.php';

// Jika sudah login, redirect ke home
if (isLoggedIn()) {
    header('Location: home.php');
    exit;
}

// Handle Google login
$client = getGoogleClient();
if (isset($_GET['code'])) {
    // Redirect ke callback.php untuk menangani proses login
    header('Location:callback.php?code=' . $_GET['code']);
    exit;
}

// Handle login dengan username dan password
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'];
    
    // Validasi input
    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi';
    } else {
        // Proses autentikasi dengan MySQL
        $authResult = authenticateUser($username, $password);
        if ($authResult === true) {
            header('Location: home.php');
            exit;
        } elseif ($authResult === 'blocked') {
            $error = '<i class="fas fa-ban"></i> Akun Anda telah <strong>diblokir</strong> oleh administrator. Silakan hubungi administrator untuk aktivasi kembali.';
        } elseif ($authResult === 'pending') {
            $error = '<i class="fas fa-clock"></i> Akun Anda masih <strong>menunggu persetujuan</strong> administrator. Silakan tunggu paling lama 1x24 jam.';
        } elseif ($authResult === 'not_found') {
            $error = 'Username tidak ditemukan';
        } else {
            $error = 'Username atau password salah';
        }
    }
}

// Tampilkan halaman login
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Kami</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3a0ca3;
            --accent: #f72585;
            --light: #f8f9fa;
            --dark: #212529;
            --success: #4cc9f0;
            --border-radius: 12px;
            --box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            width: 100%;
            max-width: 420px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            transition: var(--transition);
        }
        
        .login-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }
        
        .login-header {
            background: var(--primary);
            color: white;
            text-align: center;
            padding: 30px 20px;
        }
        
        .login-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .login-header p {
            opacity: 0.9;
        }
        
        .login-form {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        
        .form-control {
            width: 100%;
            padding: 14px 15px 14px 45px;
            border: 2px solid #e1e5eb;
            border-radius: var(--border-radius);
            font-size: 16px;
            transition: var(--transition);
        }
        
        .form-control:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
        }
        
        .btn {
            display: block;
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: var(--border-radius);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--secondary);
        }
        
        .btn-google {
            background: #fff;
            color: #757575;
            border: 1px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-google:hover {
            background: #f5f5f5;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 25px 0;
            color: #6c757d;
        }
        
        .divider::before,
        .divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background: #e1e5eb;
        }
        
        .divider span {
            padding: 0 15px;
            font-size: 14px;
        }
        
        .login-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #6c757d;
        }
        
        .login-footer a {
            color: var(--primary);
            text-decoration: none;
        }
        
        .login-footer a:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-error {
            background: #ffeaea;
            color: #d93025;
            border: 1px solid #ffd1d1;
        }
        
        .alert-blocked {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .alert-pending {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .alert i {
            font-size: 20px;
        }
        
        .alert strong {
            font-weight: 600;
        }
        
        @media (max-width: 480px) {
            .login-container {
                max-width: 100%;
            }
            
            .login-form {
                padding: 20px;
            }
        }

        .password-strength {
            margin-top: 8px;
            height: 5px;
            border-radius: 3px;
            background: #eee;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s ease;
        }

        .strength-weak {
            background: #ff4d4d;
            width: 33%;
        }

        .strength-medium {
            background: #ffa500;
            width: 66%;
        }

        .strength-strong {
            background: #0fce5c;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Selamat Datang</h1>
            <p>Masuk ke akun Anda untuk melanjutkan</p>
        </div>
        
        <div class="login-form">
            <?php if (!empty($error)): ?>
                <?php
                // Tentukan kelas alert berdasarkan pesan error
                if (strpos($error, 'diblokir') !== false) {
                    $alertClass = 'alert-blocked';
                } elseif (strpos($error, 'menunggu persetujuan') !== false) {
                    $alertClass = 'alert-pending';
                } else {
                    $alertClass = 'alert-error';
                }
                ?>
                <div class="alert <?php echo $alertClass; ?>">
                    <?php echo $error; ?>
                </div>
            <?php elseif (isset($_SESSION['login_error'])): ?>
                <?php
                $error = $_SESSION['login_error'];
                // Hapus error dari session setelah ditampilkan
                unset($_SESSION['login_error']);
                
                // Tentukan kelas alert berdasarkan pesan error
                if (strpos($error, 'diblokir') !== false) {
                    $alertClass = 'alert-blocked';
                } elseif (strpos($error, 'menunggu persetujuan') !== false) {
                    $alertClass = 'alert-pending';
                } else {
                    $alertClass = 'alert-error';
                }
                ?>
                <div class="alert <?php echo $alertClass; ?>">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" id="username" name="username" class="form-control" placeholder="Masukkan username" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Masukkan password" required>
                        <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                    </div>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="passwordStrengthBar"></div>
                    </div>
                </div>
                
                <button type="submit" name="login" class="btn btn-primary">Masuk</button>
            </form>
            
            <div class="divider">
                <span>Atau lanjutkan dengan</span>
            </div>
            
            <a href="<?php echo $client->createAuthUrl(); ?>" class="btn btn-google">
                <i class="fab fa-google"></i> Google
            </a>
            
            <div class="login-footer">
                Belum punya akun? <a href="register.php">Daftar di sini</a><br>
                <!-- <a href="forgot-password.php">Lupa password?</a> -->
            </div>
        </div>
    </div>

    <script>
        // Toggle visibility password
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');
        const strengthBar = document.getElementById('passwordStrengthBar');
        
        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
        
        // Password strength indicator
        password.addEventListener('input', function() {
            const value = password.value;
            let strength = 0;
            
            if (value.length > 0) {
                if (value.length >= 8) strength += 1;
                if (/[A-Z]/.test(value)) strength += 1;
                if (/[0-9]/.test(value)) strength += 1;
                if (/[^A-Za-z0-9]/.test(value)) strength += 1;
                
                // Update strength bar
                strengthBar.className = 'password-strength-bar';
                if (value.length > 0) {
                    if (strength < 2) {
                        strengthBar.classList.add('strength-weak');
                    } else if (strength < 4) {
                        strengthBar.classList.add('strength-medium');
                    } else {
                        strengthBar.classList.add('strength-strong');
                    }
                }
            } else {
                strengthBar.className = 'password-strength-bar';
            }
        });
    </script>
</body>
</html>
<?php
exit;