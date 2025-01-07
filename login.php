<?php
session_start();
require "./db.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    // hashing
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // autenthication
    $stmt = $db->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingUser) {
        // Register işlemi sırasında hata mesajı gösterme
        $_SESSION['register_error'] = "Username already exists!";
    } else {
        // Yeni kullanıcıyı veritabanına ekle
        $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (:username, :password, :role)");
        $stmt->execute(['username' => $username, 'password' => $hashedPassword, 'role' => $role]);
        header("Location: login.php");
        exit;
    }
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $db->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        if ($_SESSION['role'] === 'admin') {
            header("Location: adminpanel.php"); 
        } elseif ($_SESSION['role'] === 'editor') {
            header("Location: editor.php"); 
        } elseif ($_SESSION['role'] === 'content_creator') {
            header("Location: content_creator.php"); 
        } else {
            header("Location: index.php"); 
        }
        exit;
    } else {
        $error = "Invalid username or password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login / Register</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .auth-container {
            background-color: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 300px;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        label {
            font-size: 14px;
            color: #333;
        }

        input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        button {
            width: 100%;
            padding: 10px;
            background-color: #4CAF50;
            border: none;
            color: white;
            font-size: 16px;
            cursor: pointer;
            border-radius: 4px;
        }

        button:hover {
            background-color: #45a049;
        }

        .error {
            color: red;
            text-align: center;
        }

        .auth-link {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>

<div class="auth-container">
    <h2>Login</h2>
    <form method="POST">
        <label for="username">Username:</label>
        <input type="text" name="username" value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>" required><br>

        <label for="password">Password:</label>
        <input type="password" name="password" required><br>

        <button type="submit" name="login">Login</button>
    </form>

    <?php if (isset($error)): ?>
        <p class="error"><?php echo $error; ?></p>
    <?php endif; ?>

    <div class="auth-link">
        <p>Don't have an account? <a href="#register-section" onclick="showRegisterForm()">Register here</a></p>
    </div>

    <div id="register-section" style="display: none; margin-top: 40px;">
        <h2>Register</h2>
        <form method="POST">
            <label for="username">Username:</label>
            <input type="text" name="username" value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>" required><br>

            <label for="password">Password:</label>
            <input type="password" name="password" required><br>

            <label for="role">Role:</label>
            <select name="role" required>
                <option value="content_creator" <?= isset($_POST['role']) && $_POST['role'] == 'content_creator' ? 'selected' : '' ?>>Content Creator</option>
                <option value="editor" <?= isset($_POST['role']) && $_POST['role'] == 'editor' ? 'selected' : '' ?>>Editor</option>
            </select><br>

            <button type="submit" name="register">Register</button>
        </form>

        <?php if (isset($_SESSION['register_error'])): ?>
            <p class="error"><?php echo $_SESSION['register_error']; unset($_SESSION['register_error']); ?></p>
        <?php endif; ?>
    </div>
</div>

<script>
    function showRegisterForm() {
        document.getElementById('register-section').style.display = 'block';
    }
</script>

</body>
</html>
