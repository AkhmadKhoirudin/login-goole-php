<?php
session_start();
require_once 'auth.php';
require_once 'db_config.php';
requireLogin();

// Database connection
$db = getDbConnection();
createUsersTable($db);

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'block_user':
                blockUser($db, $_POST['user_id']);
                break;
            case 'unblock_user':
                unblockUser($db, $_POST['user_id']);
                break;
            case 'delete_user':
                deleteUser($db, $_POST['user_id']);
                break;
            case 'create_user':
                createUser($db, $_POST['username'], $_POST['password'], $_POST['nama'] ?? '', $_POST['alamat'] ?? '');
                break;
            case 'edit_user':
                editUser($db, $_POST['user_id'], $_POST['nama'], $_POST['username'], $_POST['alamat']);
                break;
            case 'update_password':
                updatePassword($db, $_POST['user_id'], $_POST['new_password']);
                break;
            case 'update_role':
                updateUserRole($db, $_POST['user_id'], $_POST['role']);
                break;
        }
        header('Location: users.php');
        exit;
    }
}

// Function to block user
function blockUser($db, $userId) {
    $stmt = $db->prepare("UPDATE users SET is_blocked = 1 WHERE id = :id");
    $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();
}

// Function to unblock user
function unblockUser($db, $userId) {
    $stmt = $db->prepare("UPDATE users SET is_blocked = 0 WHERE id = :id");
    $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();
}

// Function to delete user
function deleteUser($db, $userId) {
    $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
    $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();
}

// Function to create user
function createUser($db, $username, $password, $nama = '', $alamat = '', $role = 'pending') {
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Check if username already exists
    $stmt = $db->prepare("SELECT id FROM users WHERE username = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    
    if ($stmt->fetch()) {
        $_SESSION['error'] = "Username sudah digunakan!";
        return false;
    }
    
    // Insert new user
    $stmt = $db->prepare("INSERT INTO users (name, username, password, is_blocked, role, alamat) VALUES (:name, :username, :password, 0, :role, :alamat)");
    $stmt->bindParam(':name', $nama);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':password', $hashedPassword);
    $stmt->bindParam(':role', $role);
    $stmt->bindParam(':alamat', $alamat);
    $stmt->execute();
    
    $_SESSION['success'] = "User berhasil dibuat!";
    return true;
}

function updateUserRole($db, $userId, $role) {
    $stmt = $db->prepare("UPDATE users SET role = :role WHERE id = :id");
    $stmt->bindParam(':role', $role);
    $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    
    $_SESSION['success'] = "Role user berhasil diupdate!";
    return true;
}

// Function to edit user
function editUser($db, $userId, $nama, $username, $alamat) {
    // Periksa apakah username sudah digunakan oleh user lain
    $stmt = $db->prepare("SELECT id FROM users WHERE username = :username AND id != :id");
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->fetch()) {
        $_SESSION['error'] = "Username sudah digunakan oleh user lain!";
        return false;
    }
    
    // Update user data
    $stmt = $db->prepare("UPDATE users SET name = :name, username = :username, alamat = :alamat WHERE id = :id");
    $stmt->bindParam(':name', $nama);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':alamat', $alamat);
    $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    
    $_SESSION['success'] = "User berhasil diperbarui!";
    return true;
}

// Function to update password
function updatePassword($db, $userId, $newPassword) {
    if (empty($newPassword) || strlen($newPassword) < 6) {
        $_SESSION['error'] = "Password minimal 6 karakter!";
        return false;
    }
    
    // Hash new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Update password
    $stmt = $db->prepare("UPDATE users SET password = :password WHERE id = :id");
    $stmt->bindParam(':password', $hashedPassword);
    $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    
    $_SESSION['success'] = "Password berhasil diperbarui!";
    return true;
}

// Check if role column exists, if not add it
try {
    $db->exec("ALTER TABLE users ADD COLUMN role ENUM('admin', 'sekben1', 'sekben2', 'pending', 'TU') DEFAULT 'pending'");
} catch (PDOException $e) {
    // Column might already exist
}

// Get all users with their roles
$stmt = $db->query("SELECT * FROM users ORDER BY id DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Table creation is handled by createUsersTable() function
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #007bff;
            --secondary: #6c757d;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
            --light: #f8f9fa;
            --dark: #343a40;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: #333;
        }
        
        .container {
            max-width: 1400px;
            margin: 20px auto;
            background-color: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #2c3e50;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eaeaea;
            font-weight: 600;
        }
        
        .user-form {
            background-color: #f9f9f9;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }
        
        .form-section-title {
            font-size: 1.2em;
            color: #2c3e50;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
        }
        
        input[type="text"], input[type="email"], input[type="password"], textarea, select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 15px;
            transition: border-color 0.3s;
        }
        
        input:focus, textarea:focus, select:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.15);
        }
        
        button, .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 18px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #0069d9;
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background-color: var(--danger);
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
            transform: translateY(-2px);
        }
        
        .btn-success {
            background-color: var(--success);
            color: white;
        }
        
        .btn-success:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }
        
        .btn-warning {
            background-color: var(--warning);
            color: #212529;
        }
        
        .btn-warning:hover {
            background-color: #e0a800;
            transform: translateY(-2px);
        }
        
        .btn-info {
            background-color: var(--info);
            color: white;
        }
        
        .btn-info:hover {
            background-color: #138496;
            transform: translateY(-2px);
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 14px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }
        
        th, td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid #eaeaea;
        }
        
        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #495057;
            position: sticky;
            top: 0;
        }
        
        tr:hover {
            background-color: #f8f9fa;
        }
        
        .blocked {
            color: var(--danger);
            font-weight: bold;
        }
        
        .active {
            color: var(--success);
            font-weight: 500;
        }
        
        .error {
            color: #721c24;
            background-color: #f8d7da;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #f5c6cb;
        }
        
        .success {
            color: #155724;
            background-color: #d4edda;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #c3e6cb;
        }
        
        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .dropdown {
            position: relative;
            display: inline-block;
        }
        
        .dropdown-toggle {
            background-color: var(--warning);
            color: #212529;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .dropdown-toggle:hover {
            background-color: #e0a800;
        }
        
        .dropdown-menu {
            display: none;
            position: absolute;
            background-color: white;
            min-width: 160px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.15);
            z-index: 100;
            border-radius: 6px;
            padding: 8px 0;
            border: 1px solid #eee;
        }
        
        .dropdown-menu a {
            color: #333;
            padding: 10px 16px;
            text-decoration: none;
            display: block;
            font-size: 14px;
            transition: background-color 0.2s;
        }
        
        .dropdown-menu a:hover {
            background-color: #f8f9fa;
        }
        
        .dropdown.active .dropdown-menu {
            display: block;
        }
        
        .badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-warning {
            background-color: var(--warning);
            color: #212529;
        }
        
        .badge-danger {
            background-color: var(--danger);
            color: white;
        }
        
        .badge-primary {
            background-color: var(--primary);
            color: white;
        }
        
        .badge-info {
            background-color: var(--info);
            color: white;
        }
        
        .badge-success {
            background-color: var(--success);
            color: white;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            overflow: auto;
            animation: fadeIn 0.3s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 0;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            animation: slideIn 0.3s;
        }
        
        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .modal-header {
            padding: 16px 20px;
            border-bottom: 1px solid #eaeaea;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f8f9fa;
            border-radius: 10px 10px 0 0;
        }
        
        .modal-title {
            margin: 0;
            font-size: 1.4em;
            color: #2c3e50;
            font-weight: 600;
        }
        
        .close {
            color: #aaa;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
        }
        
        .close:hover {
            color: #000;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-footer {
            padding: 16px 20px;
            border-top: 1px solid #eaeaea;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            background-color: #f8f9fa;
            border-radius: 0 0 10px 10px;
        }
        
        .search-container {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        
        .search-input {
            flex: 1;
            max-width: 300px;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 8px;
        }
        
        .pagination button {
            min-width: 40px;
        }
        
        .active-page {
            background-color: var(--primary);
            color: white;
        }
        
        .user-count {
            margin: 15px 0;
            color: #6c757d;
            font-style: italic;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
                margin: 10px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .actions {
                flex-direction: column;
                align-items: flex-start;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
            
            .modal-content {
                width: 95%;
                margin: 10% auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-users-cog"></i> Manajemen User</h1>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error"><i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success"><i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <!-- Form Create User -->
        <div class="user-form">
            <h2 class="form-section-title"><i class="fas fa-user-plus"></i> Buat User Baru</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="create_user">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nama"><i class="fas fa-user"></i> Nama Lengkap:</label>
                        <input type="text" id="nama" name="nama" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="username"><i class="fas fa-at"></i> Username:</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password"><i class="fas fa-key"></i> Password:</label>
                        <input type="password" id="password" name="password" required>
                        <small style="color: #666;">Minimal 6 karakter</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="alamat"><i class="fas fa-map-marker-alt"></i> Alamat:</label>
                        <textarea id="alamat" name="alamat" rows="3" placeholder="Masukkan alamat lengkap"></textarea>
                    </div>
                </div>
                
                <button type="submit" class="btn-primary"><i class="fas fa-plus"></i> Buat User</button>
            </form>
        </div>

        <!-- Modal Edit User -->
        <div id="editUserModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title"><i class="fas fa-edit"></i> Edit User</h3>
                    <span class="close">&times;</span>
                </div>
                <form method="POST" action="" id="editUserForm">
                    <input type="hidden" name="action" value="edit_user">
                    <input type="hidden" id="edit_user_id" name="user_id">
                    
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="edit_nama">Nama Lengkap:</label>
                            <input type="text" id="edit_nama" name="nama" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_username">Username:</label>
                            <input type="text" id="edit_username" name="username" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_alamat">Alamat:</label>
                            <textarea id="edit_alamat" name="alamat" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-secondary close-btn"><i class="fas fa-times"></i> Batal</button>
                        <button type="submit" class="btn-success"><i class="fas fa-save"></i> Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Update Password -->
        <div id="changePasswordModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title"><i class="fas fa-key"></i> Update Password</h3>
                    <span class="close">&times;</span>
                </div>
                <form method="POST" action="" id="changePasswordForm" onsubmit="return validatePasswordForm()">
                    <input type="hidden" name="action" value="update_password">
                    <input type="hidden" id="password_user_id" name="user_id">
                    
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="new_password">Password Baru:</label>
                            <input type="password" id="new_password" name="new_password" required>
                            <small style="color: #666;">Minimal 6 karakter</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Konfirmasi Password:</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-secondary close-btn"><i class="fas fa-times"></i> Batal</button>
                        <button type="submit" class="btn-success"><i class="fas fa-save"></i> Update Password</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- User List -->
        <div class="user-list">
            <h2><i class="fas fa-list"></i> Daftar User</h2>
            
            <div class="search-container">
                <input type="text" id="searchInput" class="search-input" placeholder="Cari user..." onkeyup="searchUsers()">
                <button class="btn-info" onclick="clearSearch()"><i class="fas fa-times"></i> Clear</button>
            </div>
            
            <div class="user-count">Total: <?php echo count($users); ?> user</div>
            
            <table id="usersTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Dibuat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($user['username'] ?? 'N/A'); ?></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="update_role">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <select name="role" onchange="this.form.submit()" class="badge 
                                        <?php 
                                        if ($user['role'] == 'pending') echo 'badge-warning';
                                        elseif ($user['role'] == 'admin') echo 'badge-danger';
                                        elseif ($user['role'] == 'sekben1') echo 'badge-primary';
                                        elseif ($user['role'] == 'sekben2') echo 'badge-info';
                                        elseif ($user['role'] == 'TU') echo 'badge-success';
                                        ?>"
                                        style="border: none; padding: 6px 10px; cursor: pointer;">
                                        <option value="pending" <?php echo $user['role'] == 'pending' ? 'selected' : ''; ?>>Menunggu</option>
                                        <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                        <option value="sekben1" <?php echo $user['role'] == 'sekben1' ? 'selected' : ''; ?>>Sekben 1</option>
                                        <option value="sekben2" <?php echo $user['role'] == 'sekben2' ? 'selected' : ''; ?>>Sekben 2</option>
                                        <option value="TU" <?php echo $user['role'] == 'TU' ? 'selected' : ''; ?>>TU</option>
                                    </select>
                                </form>
                            </td>
                            <td class="<?php echo isset($user['is_blocked']) && $user['is_blocked'] ? 'blocked' : 'active'; ?>">
                                <?php echo isset($user['is_blocked']) && $user['is_blocked'] ? 'Diblokir' : 'Aktif'; ?>
                            </td>
                            <td><?php echo isset($user['created_at']) ? date('d/m/Y H:i', strtotime($user['created_at'])) : '-'; ?></td>
                            <td class="actions">
                                <button type="button" class="btn-warning btn-sm" onclick="openEditModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['name'] ?? ''); ?>', '<?php echo htmlspecialchars($user['username'] ?? ''); ?>', '<?php echo htmlspecialchars($user['alamat'] ?? ''); ?>')">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                
                                <button type="button" class="btn-info btn-sm" onclick="openPasswordModal(<?php echo $user['id']; ?>)">
                                    <i class="fas fa-key"></i> Password
                                </button>
                                
                                <?php if (isset($user['is_blocked']) && $user['is_blocked']): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="unblock_user">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="btn-success btn-sm"><i class="fas fa-check-circle"></i> Aktifkan</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="block_user">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="btn-warning btn-sm"><i class="fas fa-ban"></i> Blokir</button>
                                    </form>
                                <?php endif; ?>
                                
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" class="btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus user ini?')"><i class="fas fa-trash"></i> Hapus</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="pagination" id="pagination">
                <!-- Pagination will be generated by JavaScript -->
            </div>
        </div>
    </div>

    <script>
        // Toggle dropdown
        document.addEventListener('DOMContentLoaded', function() {
            // Close modal when clicking the close button or outside the modal
            document.querySelectorAll('.close, .close-btn').forEach(closeBtn => {
                closeBtn.addEventListener('click', function() {
                    document.querySelectorAll('.modal').forEach(modal => {
                        modal.style.display = 'none';
                    });
                });
            });
            
            window.addEventListener('click', function(event) {
                if (event.target.classList.contains('modal')) {
                    event.target.style.display = 'none';
                }
            });
            
            // Initialize pagination
            initPagination();
        });
        
        // User data for search and pagination
        const allUsers = <?php echo json_encode($users); ?>;
        let currentPage = 1;
        const usersPerPage = 10;
        
        function initPagination() {
            const totalPages = Math.ceil(allUsers.length / usersPerPage);
            const paginationContainer = document.getElementById('pagination');
            paginationContainer.innerHTML = '';
            
            if (totalPages <= 1) return;
            
            // Previous button
            if (totalPages > 1) {
                const prevButton = document.createElement('button');
                prevButton.innerHTML = '&laquo;';
                prevButton.classList.add('btn', 'btn-sm');
                prevButton.addEventListener('click', () => changePage(currentPage - 1));
                paginationContainer.appendChild(prevButton);
            }
            
            // Page buttons
            for (let i = 1; i <= totalPages; i++) {
                const pageButton = document.createElement('button');
                pageButton.textContent = i;
                pageButton.classList.add('btn', 'btn-sm');
                if (i === currentPage) {
                    pageButton.classList.add('active-page');
                }
                pageButton.addEventListener('click', () => changePage(i));
                paginationContainer.appendChild(pageButton);
            }
            
            // Next button
            if (totalPages > 1) {
                const nextButton = document.createElement('button');
                nextButton.innerHTML = '&raquo;';
                nextButton.classList.add('btn', 'btn-sm');
                nextButton.addEventListener('click', () => changePage(currentPage + 1));
                paginationContainer.appendChild(nextButton);
            }
            
            // Show first page
            changePage(1);
        }
        
        function changePage(page) {
            const totalPages = Math.ceil(allUsers.length / usersPerPage);
            if (page < 1 || page > totalPages) return;
            
            currentPage = page;
            const startIndex = (page - 1) * usersPerPage;
            const endIndex = Math.min(startIndex + usersPerPage, allUsers.length);
            
            // Update table
            const tbody = document.querySelector('#usersTable tbody');
            tbody.innerHTML = '';
            
            for (let i = startIndex; i < endIndex; i++) {
                const user = allUsers[i];
                const row = document.createElement('tr');
                
                row.innerHTML = `
                    <td>${user.id}</td>
                    <td>${escapeHtml(user.name || 'N/A')}</td>
                    <td>${escapeHtml(user.username || 'N/A')}</td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="update_role">
                            <input type="hidden" name="user_id" value="${user.id}">
                            <select name="role" onchange="this.form.submit()" class="badge ${getRoleBadgeClass(user.role)}"
                                style="border: none; padding: 6px 10px; cursor: pointer;">
                                <option value="pending" ${user.role == 'pending' ? 'selected' : ''}>Menunggu</option>
                                <option value="admin" ${user.role == 'admin' ? 'selected' : ''}>Admin</option>
                                <option value="sekben1" ${user.role == 'sekben1' ? 'selected' : ''}>Sekben 1</option>
                                <option value="sekben2" ${user.role == 'sekben2' ? 'selected' : ''}>Sekben 2</option>
                                <option value="TU" ${user.role == 'TU' ? 'selected' : ''}>TU</option>
                            </select>
                        </form>
                    </td>
                    <td class="${user.is_blocked ? 'blocked' : 'active'}">
                        ${user.is_blocked ? 'Diblokir' : 'Aktif'}
                    </td>
                    <td>${user.created_at ? formatDate(user.created_at) : '-'}</td>
                    <td class="actions">
                        <button type="button" class="btn-warning btn-sm" onclick="openEditModal(${user.id}, '${escapeHtml(user.name || '')}', '${escapeHtml(user.username || '')}', '${escapeHtml(user.alamat || '')}')">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        
                        <button type="button" class="btn-info btn-sm" onclick="openPasswordModal(${user.id})">
                            <i class="fas fa-key"></i> Password
                        </button>
                        
                        ${user.is_blocked ? 
                            `<form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="unblock_user">
                                <input type="hidden" name="user_id" value="${user.id}">
                                <button type="submit" class="btn-success btn-sm"><i class="fas fa-check-circle"></i> Aktifkan</button>
                            </form>` : 
                            `<form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="block_user">
                                <input type="hidden" name="user_id" value="${user.id}">
                                <button type="submit" class="btn-warning btn-sm"><i class="fas fa-ban"></i> Blokir</button>
                            </form>`
                        }
                        
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="delete_user">
                            <input type="hidden" name="user_id" value="${user.id}">
                            <button type="submit" class="btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus user ini?')"><i class="fas fa-trash"></i> Hapus</button>
                        </form>
                    </td>
                `;
                
                tbody.appendChild(row);
            }
            
            // Update pagination buttons
            document.querySelectorAll('#pagination button').forEach(button => {
                button.classList.remove('active-page');
            });
            
            const pageButtons = document.querySelectorAll('#pagination button');
            if (pageButtons.length > 2) { // Previous, pages, next
                pageButtons[currentPage].classList.add('active-page');
            }
        }
        
        function getRoleBadgeClass(role) {
            switch(role) {
                case 'pending': return 'badge-warning';
                case 'admin': return 'badge-danger';
                case 'sekben1': return 'badge-primary';
                case 'sekben2': return 'badge-info';
                case 'TU': return 'badge-success';
                default: return 'badge-warning';
            }
        }
        
        function formatDate(dateString) {
            const date = new Date(dateString);
            return `${date.getDate().toString().padStart(2, '0')}/${(date.getMonth()+1).toString().padStart(2, '0')}/${date.getFullYear()} ${date.getHours().toString().padStart(2, '0')}:${date.getMinutes().toString().padStart(2, '0')}`;
        }
        
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }
        
        function searchUsers() {
            const searchText = document.getElementById('searchInput').value.toLowerCase();
            
            if (!searchText) {
                // If search is empty, show all users
                changePage(1);
                initPagination();
                return;
            }
            
            const filteredUsers = allUsers.filter(user => 
                (user.name && user.name.toLowerCase().includes(searchText)) ||
                (user.username && user.username.toLowerCase().includes(searchText)) ||
                (user.role && user.role.toLowerCase().includes(searchText)) ||
                (user.created_at && user.created_at.toLowerCase().includes(searchText))
            );
            
            // Update table with filtered results
            const tbody = document.querySelector('#usersTable tbody');
            tbody.innerHTML = '';
            
            filteredUsers.forEach(user => {
                const row = document.createElement('tr');
                
                row.innerHTML = `
                    <td>${user.id}</td>
                    <td>${escapeHtml(user.name || 'N/A')}</td>
                    <td>${escapeHtml(user.username || 'N/A')}</td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="update_role">
                            <input type="hidden" name="user_id" value="${user.id}">
                            <select name="role" onchange="this.form.submit()" class="badge ${getRoleBadgeClass(user.role)}"
                                style="border: none; padding: 6px 10px; cursor: pointer;">
                                <option value="pending" ${user.role == 'pending' ? 'selected' : ''}>Menunggu</option>
                                <option value="admin" ${user.role == 'admin' ? 'selected' : ''}>Admin</option>
                                <option value="sekben1" ${user.role == 'sekben1' ? 'selected' : ''}>Sekben 1</option>
                                <option value="sekben2" ${user.role == 'sekben2' ? 'selected' : ''}>Sekben 2</option>
                                <option value="TU" ${user.role == 'TU' ? 'selected' : ''}>TU</option>
                            </select>
                        </form>
                    </td>
                    <td class="${user.is_blocked ? 'blocked' : 'active'}">
                        ${user.is_blocked ? 'Diblokir' : 'Aktif'}
                    </td>
                    <td>${user.created_at ? formatDate(user.created_at) : '-'}</td>
                    <td class="actions">
                        <button type="button" class="btn-warning btn-sm" onclick="openEditModal(${user.id}, '${escapeHtml(user.name || '')}', '${escapeHtml(user.username || '')}', '${escapeHtml(user.alamat || '')}')">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        
                        <button type="button" class="btn-info btn-sm" onclick="openPasswordModal(${user.id})">
                            <i class="fas fa-key"></i> Password
                        </button>
                        
                        ${user.is_blocked ? 
                            `<form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="unblock_user">
                                <input type="hidden" name="user_id" value="${user.id}">
                                <button type="submit" class="btn-success btn-sm"><i class="fas fa-check-circle"></i> Aktifkan</button>
                            </form>` : 
                            `<form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="block_user">
                                <input type="hidden" name="user_id" value="${user.id}">
                                <button type="submit" class="btn-warning btn-sm"><i class="fas fa-ban"></i> Blokir</button>
                            </form>`
                        }
                        
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="delete_user">
                            <input type="hidden" name="user_id" value="${user.id}">
                            <button type="submit" class="btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus user ini?')"><i class="fas fa-trash"></i> Hapus</button>
                        </form>
                    </td>
                `;
                
                tbody.appendChild(row);
            });
            
            // Update user count
            document.querySelector('.user-count').textContent = `Hasil pencarian: ${filteredUsers.length} user`;
            
            // Hide pagination when searching
            document.getElementById('pagination').innerHTML = '';
        }
        
        function clearSearch() {
            document.getElementById('searchInput').value = '';
            changePage(1);
            initPagination();
            document.querySelector('.user-count').textContent = `Total: ${allUsers.length} user`;
        }
        
        function openEditModal(userId, userName, username, alamat) {
            document.getElementById('edit_user_id').value = userId;
            document.getElementById('edit_nama').value = userName || '';
            document.getElementById('edit_username').value = username || '';
            document.getElementById('edit_alamat').value = alamat || '';
            
            // Set form action
            document.getElementById('editUserForm').action = '';
            
            document.getElementById('editUserModal').style.display = 'block';
        }
        
        function openPasswordModal(userId) {
            document.getElementById('password_user_id').value = userId;
            document.getElementById('new_password').value = '';
            document.getElementById('confirm_password').value = '';
            
            // Set form action
            document.getElementById('changePasswordForm').action = '';
            
            document.getElementById('changePasswordModal').style.display = 'block';
        }
        
        function validatePasswordForm() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                alert('Password dan konfirmasi password tidak cocok!');
                return false;
            }
            
            if (newPassword.length < 6) {
                alert('Password minimal 6 karakter!');
                return false;
            }
            
            return true;
        }
    </script>
</body>
</html>