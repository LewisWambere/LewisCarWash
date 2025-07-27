<?php
require_once('db/db_connection.php');
session_start();

// Check if user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];
$role = $_SESSION['role'];
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_profile'])) {
        // Get form data and sanitize
        $name = mysqli_real_escape_string($conn, trim($_POST['name']));
        $email = mysqli_real_escape_string($conn, trim($_POST['email']));
        $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
        $address = mysqli_real_escape_string($conn, trim($_POST['address']));
        
        // Handle profile picture upload
        $profile_picture = $user['profile_picture'] ?? ''; // Keep existing if no new upload
        
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/profiles/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                $new_filename = $username . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                    // Delete old profile picture if exists
                    if (!empty($user['profile_picture']) && file_exists($user['profile_picture'])) {
                        unlink($user['profile_picture']);
                    }
                    $profile_picture = $upload_path;
                } else {
                    $error_message = "Error uploading profile picture.";
                }
            } else {
                $error_message = "Invalid file type. Please upload JPG, JPEG, PNG, or GIF files only.";
            }
        }
        
        // Update user profile
        $update_query = "UPDATE users SET name='$name', email='$email', phone='$phone', address='$address', profile_picture='$profile_picture' WHERE username='$username'";
        
        if (mysqli_query($conn, $update_query)) {
            $success_message = "Profile updated successfully!";
        } else {
            $error_message = "Error updating profile: " . mysqli_error($conn);
        }
    }
    
    if (isset($_POST['change_password'])) {
        $current_password = mysqli_real_escape_string($conn, trim($_POST['current_password']));
        $new_password = mysqli_real_escape_string($conn, trim($_POST['new_password']));
        $confirm_password = mysqli_real_escape_string($conn, trim($_POST['confirm_password']));
        
        // Verify current password
        $verify_query = "SELECT password FROM users WHERE username='$username'";
        $verify_result = mysqli_query($conn, $verify_query);
        $user_data = mysqli_fetch_assoc($verify_result);
        
        if ($user_data['password'] === $current_password) {
            if ($new_password === $confirm_password) {
                $password_query = "UPDATE users SET password='$new_password' WHERE username='$username'";
                if (mysqli_query($conn, $password_query)) {
                    $success_message = "Password changed successfully!";
                } else {
                    $error_message = "Error changing password: " . mysqli_error($conn);
                }
            } else {
                $error_message = "New passwords do not match!";
            }
        } else {
            $error_message = "Current password is incorrect!";
        }
    }
}

// Fetch current user data
$user_query = "SELECT * FROM users WHERE username='$username'";
$user_result = mysqli_query($conn, $user_query);
$user = mysqli_fetch_assoc($user_result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Lewis Car Wash</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #1a1a1a;
            color: #ffffff;
            min-height: 100vh;
            padding: 2rem;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #333;
        }

        .header h1 {
            color: #ffd700;
            font-size: 2.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .back-btn {
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #ffd700 0%, #ffed4a 100%);
            color: #1a1a1a;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.3);
        }

        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .profile-card {
            background: linear-gradient(135deg, #2a2a2a 0%, #1f1f1f 100%);
            border-radius: 20px;
            padding: 2rem;
            border: 1px solid #333;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .profile-card h2 {
            color: #ffd700;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #cccccc;
        }

        .form-control {
            width: 100%;
            padding: 1rem;
            border: 2px solid #444;
            border-radius: 12px;
            font-size: 1rem;
            background-color: #333;
            color: #ffffff;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #ffd700;
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
        }

        .form-control:disabled {
            background-color: #2a2a2a;
            color: #888;
            cursor: not-allowed;
        }

        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4a 100%);
            color: #1a1a1a;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.3);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #666 0%, #555 100%);
            color: #ffffff;
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, #777 0%, #666 100%);
            transform: translateY(-2px);
        }

        .alert {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .alert-success {
            background-color: rgba(72, 187, 120, 0.1);
            color: #48bb78;
            border: 1px solid rgba(72, 187, 120, 0.3);
        }

        .alert-danger {
            background-color: rgba(245, 101, 101, 0.1);
            color: #f56565;
            border: 1px solid rgba(245, 101, 101, 0.3);
        }

        .user-info {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4a 100%);
            color: #1a1a1a;
            padding: 2rem;
            border-radius: 20px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .user-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid #1a1a1a;
            object-fit: cover;
            background: #1a1a1a;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #ffd700;
            flex-shrink: 0;
        }

        .user-details h3 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .user-details .role-badge {
            background: rgba(26, 26, 26, 0.8);
            color: #ffd700;
            padding: 0.3rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: inline-block;
            margin-bottom: 1rem;
        }

        .user-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            font-size: 0.95rem;
        }

        .user-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .user-meta-item i {
            width: 20px;
            color: rgba(26, 26, 26, 0.7);
        }

        .profile-display-card {
            background: linear-gradient(135deg, #2a2a2a 0%, #1f1f1f 100%);
            border-radius: 20px;
            padding: 2rem;
            border: 1px solid #333;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            margin-bottom: 2rem;
        }

        .profile-display-card h2 {
            color: #ffd700;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .profile-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .info-item {
            background: rgba(255, 215, 0, 0.1);
            padding: 1rem;
            border-radius: 12px;
            border-left: 4px solid #ffd700;
        }

        .info-item label {
            color: #ffd700;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
            display: block;
        }

        .info-item .value {
            color: #ffffff;
            font-size: 1.1rem;
            word-break: break-word;
        }

        .info-item .value.empty {
            color: #888;
            font-style: italic;
        }

        .image-upload-container {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .current-image {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 3px solid #ffd700;
            object-fit: cover;
            background: #333;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #ffd700;
        }

        .upload-btn {
            position: relative;
            overflow: hidden;
            display: inline-block;
            background: linear-gradient(135deg, #666 0%, #555 100%);
            color: #ffffff;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .upload-btn:hover {
            background: linear-gradient(135deg, #777 0%, #666 100%);
            transform: translateY(-2px);
        }

        .upload-btn input[type="file"] {
            position: absolute;
            left: -9999px;
        }

        .tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .tab-btn {
            padding: 1rem 2rem;
            background: #333;
            color: #ccc;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .tab-btn.active {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4a 100%);
            color: #1a1a1a;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        @media (max-width: 768px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                gap: 1rem;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .tabs {
                flex-direction: column;
            }
            
            body {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo-section">
                <?php
                $dashboard_url = 'dashboard.php';
                if (isset($_SESSION['role'])) {
                    if ($_SESSION['role'] === 'attendant') {
                        $dashboard_url = 'attendant_dashboard.php';
                    } elseif ($_SESSION['role'] === 'customer') {
                        $dashboard_url = 'customer_dashboard.php';
                    }
                }
                ?>
                <a href="<?= $dashboard_url ?>" style="text-decoration: none;">
                    <h1><i class="fas fa-car-wash"></i> Lewis Car Wash</h1>
                </a>
            </div>
            <a href="<?= $role === 'admin' ? 'dashboard.php' : ($role === 'attendant' ? 'attendant_dashboard.php' : 'customer_dashboard.php') ?>" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <div class="user-info">
            <?php 
            $profile_pic = !empty($user['profile_picture']) && file_exists($user['profile_picture']) 
                          ? $user['profile_picture'] 
                          : null;
            ?>
            
            <?php if ($profile_pic): ?>
                <img src="<?= htmlspecialchars($profile_pic) ?>" alt="Profile Picture" class="user-avatar">
            <?php else: ?>
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
            <?php endif; ?>
            
            <div class="user-details">
                <h3><?= htmlspecialchars($user['name'] ?? $username) ?></h3>
                <span class="role-badge"><?= strtoupper($role) ?></span>
                
                <div class="user-meta">
                    <?php if (!empty($user['email'])): ?>
                        <div class="user-meta-item">
                            <i class="fas fa-envelope"></i>
                            <span><?= htmlspecialchars($user['email']) ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($user['phone'])): ?>
                        <div class="user-meta-item">
                            <i class="fas fa-phone"></i>
                            <span><?= htmlspecialchars($user['phone']) ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="user-meta-item">
                        <i class="fas fa-user-tag"></i>
                        <span>@<?= htmlspecialchars($username) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= $success_message ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= $error_message ?>
            </div>
        <?php endif; ?>

        <div class="tabs">
            <button class="tab-btn active" onclick="showTab('overview')">
                <i class="fas fa-user-circle"></i> Profile Overview
            </button>
            <button class="tab-btn" onclick="showTab('profile')">
                <i class="fas fa-user-edit"></i> Edit Profile
            </button>
            <button class="tab-btn" onclick="showTab('security')">
                <i class="fas fa-lock"></i> Security Settings
            </button>
        </div>

        <div id="overview-tab" class="tab-content active">
            <div class="profile-display-card">
                <h2><i class="fas fa-id-card"></i> Profile Information</h2>
                <div class="profile-info-grid">
                    <div class="info-item">
                        <label>Full Name</label>
                        <div class="value <?= empty($user['name']) ? 'empty' : '' ?>">
                            <?= !empty($user['name']) ? htmlspecialchars($user['name']) : 'Not provided' ?>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <label>Username</label>
                        <div class="value"><?= htmlspecialchars($username) ?></div>
                    </div>
                    
                    <div class="info-item">
                        <label>Email Address</label>
                        <div class="value <?= empty($user['email']) ? 'empty' : '' ?>">
                            <?= !empty($user['email']) ? htmlspecialchars($user['email']) : 'Not provided' ?>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <label>Phone Number</label>
                        <div class="value <?= empty($user['phone']) ? 'empty' : '' ?>">
                            <?= !empty($user['phone']) ? htmlspecialchars($user['phone']) : 'Not provided' ?>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <label>Role</label>
                        <div class="value"><?= strtoupper($role) ?></div>
                    </div>
                    
                    <div class="info-item">
                        <label>Member Since</label>
                        <div class="value">
                            <?= isset($user['created_at']) ? date('F j, Y', strtotime($user['created_at'])) : 'N/A' ?>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($user['address'])): ?>
                    <div style="margin-top: 1.5rem;">
                        <div class="info-item">
                            <label>Address</label>
                            <div class="value"><?= htmlspecialchars($user['address']) ?></div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div style="margin-top: 2rem; text-align: center;">
                    <button class="btn btn-primary" onclick="showTab('profile')">
                        <i class="fas fa-edit"></i> Edit Profile Information
                    </button>
                </div>
            </div>
        </div>

        <div id="profile-tab" class="tab-content">
            <div class="profile-card">
                <h2><i class="fas fa-user-edit"></i> Update Profile Information</h2>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Profile Picture</label>
                        <div class="image-upload-container">
                            <?php 
                            $current_pic = !empty($user['profile_picture']) && file_exists($user['profile_picture']) 
                                          ? $user['profile_picture'] 
                                          : null;
                            ?>
                            
                            <?php if ($current_pic): ?>
                                <img src="<?= htmlspecialchars($current_pic) ?>" alt="Current Profile Picture" class="current-image">
                            <?php else: ?>
                                <div class="current-image">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                            
                            <label for="profile_picture" class="upload-btn">
                                <i class="fas fa-camera"></i> Choose New Photo
                                <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
                            </label>
                        </div>
                        <small style="color: #888; font-size: 0.85rem;">
                            Supported formats: JPG, PNG, GIF. Max size: 5MB
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($username) ?>" disabled>
                    </div>
                    
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" class="form-control" 
                               value="<?= htmlspecialchars($user['name'] ?? '') ?>" placeholder="Enter your full name">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?= htmlspecialchars($user['email'] ?? '') ?>" placeholder="Enter your email">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" class="form-control" 
                               value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="Enter your phone number">
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" class="form-control" rows="3" 
                                  placeholder="Enter your address"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="role">Role</label>
                        <input type="text" class="form-control" value="<?= strtoupper($role) ?>" disabled>
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Profile
                    </button>
                </form>
            </div>
        </div>

        <div id="security-tab" class="tab-content">
            <div class="profile-card">
                <h2><i class="fas fa-key"></i> Change Password</h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" 
                               class="form-control" placeholder="Enter current password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" 
                               class="form-control" placeholder="Enter new password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" 
                               class="form-control" placeholder="Confirm new password" required>
                    </div>
                    
                    <button type="submit" name="change_password" class="btn btn-primary">
                        <i class="fas fa-lock"></i> Change Password
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all tab buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Add active class to clicked button
            event.target.classList.add('active');
        }

        // Profile picture preview
        document.getElementById('profile_picture').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const currentImage = document.querySelector('.current-image');
                    if (currentImage.tagName === 'IMG') {
                        currentImage.src = e.target.result;
                    } else {
                        // Replace div with img
                        const newImg = document.createElement('img');
                        newImg.src = e.target.result;
                        newImg.alt = 'Profile Picture Preview';
                        newImg.className = 'current-image';
                        currentImage.parentNode.replaceChild(newImg, currentImage);
                    }
                };
                reader.readAsDataURL(file);
            }
        });

        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });

        // Form submission loading states
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const button = this.querySelector('button[type="submit"]');
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            });
        });
    </script>
</body>
</html>