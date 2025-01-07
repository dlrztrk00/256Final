<?php
session_start();
require './db.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$users = $db->query("SELECT * FROM users")->fetchAll(PDO::FETCH_ASSOC);
$contents = $db->query("SELECT * FROM contents")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (:username, :password, :role)");
    $stmt->execute(['username' => $username, 'password' => $password, 'role' => $role]);

    header("Location: adminpanel.php");
    exit;
}

// approve 
if (isset($_GET['approve']) && $_GET['approve'] > 0) {
    $stmt = $db->prepare("UPDATE contents SET status = 'approved' WHERE id = :id");
    $stmt->execute(['id' => $_GET['approve']]);
    header("Location: adminpanel.php");
    exit;
}

// reject 
if (isset($_GET['reject']) && $_GET['reject'] > 0) {
    $stmt = $db->prepare("UPDATE contents SET status = 'rejected' WHERE id = :id");
    $stmt->execute(['id' => $_GET['reject']]);
    header("Location: adminpanel.php");
    exit;
}

// delete user
if (isset($_GET['delete_user']) && $_GET['delete_user'] > 0) {
    $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
    $stmt->execute(['id' => $_GET['delete_user']]);
    header("Location: adminpanel.php");
    exit;
}

// delete
if (isset($_GET['delete_content']) && $_GET['delete_content'] > 0) {
    $stmt = $db->prepare("DELETE FROM contents WHERE id = :id");
    $stmt->execute(['id' => $_GET['delete_content']]);
    header("Location: adminpanel.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            color: #333;
            margin: 0;
            padding: 0;
        }

        header {
            background-color: #4CAF50;
            color: white;
            padding: 20px;
            text-align: center;
            position: relative;
        }

        header h2 {
            margin: 0;
        }

        .go-to-main-page {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 16px;
            color: white;
            text-decoration: none;
        }

        .go-to-main-page:hover {
            text-decoration: underline;
        }

        section {
            margin: 20px;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h3 {
            color: #333;
            font-size: 24px;
            margin-bottom: 20px;
        }

        input[type="text"],
        input[type="password"],
        select {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 12px 20px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 4px;
            width: 100%;
        }

        button:hover {
            background-color: #45a049;
        }

        table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }

        table th, table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        table th {
            background-color: #4CAF50;
            color: white;
        }

        table td a {
            color: #4CAF50;
            text-decoration: none;
        }

        table td a:hover {
            text-decoration: underline;
        }

        a {
            color: #4CAF50;
            font-size: 14px;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            header h2 {
                font-size: 20px;
            }

            section {
                padding: 15px;
            }

            button {
                padding: 10px 15px;
            }

            table th, table td {
                font-size: 14px;
            }
        }
    </style>

</head>
<body>

<header>
    <h2>Admin Panel</h2>
    <a href="index.php" class="go-to-main-page">Go to Main Page</a>
</header>

<section>
    <h3>Users</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Role</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user) : ?>
                <tr>
                    <td><?= $user['id'] ?></td>
                    <td><?= $user['username'] ?></td>
                    <td><?= $user['role'] ?></td>
                    <td>
                        <a href="adminpanel.php?delete_user=<?= $user['id'] ?>">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h3>Add New User</h3>
    <form method="POST">
        <label for="username">Username:</label>
        <input type="text" name="username" required><br>

        <label for="password">Password:</label>
        <input type="password" name="password" required><br>

        <label for="role">Role:</label>
        <select name="role" required>
            <option value="content_creator">Content Creator</option>
            <option value="editor">Editor</option>
        </select><br>

        <button type="submit" name="create_user">Create User</button>
    </form>
</section>

<section>
    <h3>Contents</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($contents as $content) : ?>
                <tr>
                    <td><?= $content['id'] ?></td>
                    <td><?= $content['title'] ?></td>
                    <td><?= $content['status'] ?></td>
                    <td>
                        <?php if ($content['status'] === 'pending'): ?>
                            <a href="adminpanel.php?approve=<?= $content['id'] ?>">Approve</a> |
                            <a href="adminpanel.php?reject=<?= $content['id'] ?>">Reject</a>
                        <?php endif; ?>
                        <a href="adminpanel.php?delete_content=<?= $content['id'] ?>">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

</body>
</html>
