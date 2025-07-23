<?php
// signup.php
include 'db_connection.php';

$msg = '';
$error = '';

// Admin secret code (you can change this to something only you know)
$adminSecretCode = "0000";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);
    $role = $_POST["role"];
    $admin_code = isset($_POST["admin_code"]) ? trim($_POST["admin_code"]) : "";

    if (empty($username) || empty($password) || empty($role)) {
        $error = "Please fill in all fields.";
    } elseif ($role === "admin" && $admin_code !== $adminSecretCode) {
        $error = "Invalid admin code.";
    } else {
        $password_hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $password_hashed, $role);

        if ($stmt->execute()) {
            $msg = "User registered successfully!";
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Signup - Lewis Car Wash</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/signup.css">
    <style>
        body {
            background: #f4f7f8;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px #ccc;
            width: 350px;
        }
        h2 {
            text-align: center;
            color: #333;
        }
        label {
            font-weight: bold;
        }
        input[type="text"],
        input[type="password"],
        select {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            margin-bottom: 15px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #28a745;
            color: white;
            font-weight: bold;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #218838;
        }
        .message.error {
            color: red;
            text-align: center;
            font-weight: bold;
        }
        .message.success {
            color: green;
            text-align: center;
            font-weight: bold;
        }
        .login-link {
            text-align: center;
            margin-top: 10px;
        }
        .login-link a {
            color: #007BFF;
            text-decoration: none;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Sign Up</h2>
        <form method="POST" action="signup.php">
            <label>Username:</label>
            <input type="text" name="username" required>

            <label>Password:</label>
            <input type="password" name="password" required>

            <label>Role:</label>
            <select name="role" onchange="toggleAdminCodeField()" required>
                <option value="">Select Role</option>
                <option value="admin">Admin</option>
                <option value="attendant">Attendant</option>
            </select>

            <div id="admin_code_field" style="display: none;">
                <label>Admin Code:</label>
                <input type="text" name="admin_code">
            </div>

            <button type="submit">Sign Up</button>
        </form>

        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($msg): ?>
            <div class="message success"><?php echo $msg; ?></div>
        <?php endif; ?>

        <div class="login-link">
            Already have an account? <a href="login.php">Back to Login</a>
        </div>
    </div>

    <script>
    function toggleAdminCodeField() {
        const roleSelect = document.querySelector("select[name='role']");
        const adminCodeField = document.getElementById("admin_code_field");
        adminCodeField.style.display = roleSelect.value === "admin" ? "block" : "none";
    }
    </script>
</body>
</html>
