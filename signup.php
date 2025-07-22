<?php
// signup.php
include 'db_connection.php';

$msg = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);
    $role = $_POST["role"];

    if (empty($username) || empty($password) || empty($role)) {
        $error = "Please fill in all fields.";
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
            <select name="role" required>
                <option value="">Select Role</option>
                <option value="admin">Admin</option>
                <option value="attendant">Attendant</option>
            </select>

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
</body>
</html>
