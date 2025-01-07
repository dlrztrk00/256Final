<?php
session_start();
require_once 'db.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'content_creator') {
    header('Location: login.php'); 
    exit;
}
 
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_content'])) {
    $title = $_POST['title'];
    $body = $_POST['body'];
    $image = '';

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $targetDir = "uploads/"; 
        $imageName = basename($_FILES['image']['name']); 
        $imagePath = $targetDir . $imageName; 
        move_uploaded_file($_FILES['image']['tmp_name'], $imagePath);
        $image = $imageName;
    }

    $stmt = $db->prepare("INSERT INTO contents (user_id, title, body, image) VALUES (:user_id, :title, :body, :image)");
    $stmt->execute(['user_id' => $_SESSION['user_id'], 'title' => $title, 'body' => $body, 'image' => $image]);

    header('Location: content_creator.php'); 
    exit;
}

if (isset($_GET['delete'])) {
    $content_id = $_GET['delete'];

    $stmt = $db->prepare("DELETE FROM contents WHERE id = :id");
    $stmt->execute(['id' => $content_id]);

    header('Location: content_creator.php'); 
    exit;
}

if (isset($_GET['edit'])) {
    $content_id = $_GET['edit'];

    $stmt = $db->prepare("SELECT * FROM contents WHERE id = :id");
    $stmt->execute(['id' => $content_id]);
    $content = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($_SESSION['user_id'] != $content['user_id']) {
        $error_message = "You cannot edit this content.";
        $can_edit = false;
    } else {
        $can_edit = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_content'])) {
    $content_id = $_POST['content_id'];
    $title = $_POST['title'];
    $body = $_POST['body'];
    $image = '';

    $stmt = $db->prepare("SELECT * FROM contents WHERE id = :id");
    $stmt->execute(['id' => $content_id]);
    $content = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($_SESSION['user_id'] != $content['user_id']) {
        $error_message = "You cannot edit this content.";
        $can_edit = false;
    }

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $targetDir = "uploads/";
        $imageName = basename($_FILES['image']['name']); 
        $imagePath = $targetDir . $imageName; 
        move_uploaded_file($_FILES['image']['tmp_name'], $imagePath); 
        $image = $imageName;
    }

    $stmt = $db->prepare("UPDATE contents SET title = :title, body = :body, image = :image WHERE id = :id AND user_id = :user_id");
    $stmt->execute(['id' => $content_id, 'user_id' => $_SESSION['user_id'], 'title' => $title, 'body' => $body, 'image' => $image]);

    header('Location: content_creator.php'); 
    exit;
}

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

if ($filter == 'all') {
    $stmt = $db->prepare("SELECT * FROM contents WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
} else {
    $stmt = $db->prepare("SELECT * FROM contents WHERE user_id = :user_id AND status = :status");
    $stmt->execute(['user_id' => $_SESSION['user_id'], 'status' => $filter]);
}

$contents = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content Creator Dashboard</title>
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

        .go-to-main {
            position: absolute;
            top: 20px;
            right: 20px;
        }

        .go-to-main a {
            color: white;
            font-size: 16px;
            text-decoration: none;
        }

        .go-to-main a:hover {
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
        input[type="file"],
        textarea,
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

        .alert {
            background-color: #f44336;
            color: white;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-size: 16px;
            text-align: center;
        }

        .alert.close {
            background-color: #555;
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
        <h2>Content Creator Dashboard</h2>
        <div class="go-to-main">
            <a href="index.php">Go to Main Page</a>
        </div>
    </header>

    <?php if (isset($error_message)): ?>
        <div class="alert">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <section>
        <h3>Add New Content</h3>
        <form method="POST" enctype="multipart/form-data">
            <label for="title">Title</label>
            <input type="text" name="title" required>

            <label for="body">Content Body</label>
            <textarea name="body" required></textarea>

            <label for="image">Upload Image</label>
            <input type="file" name="image">

            <button type="submit" name="add_content">Add Content</button>
        </form>
    </section>

    <?php if (isset($content) && $can_edit): ?>
        <section>
            <h3>Edit Content</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="content_id" value="<?php echo $content['id']; ?>">

                <label for="title">Title</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($content['title']); ?>" required>

                <label for="body">Content Body</label>
                <textarea name="body" required><?php echo htmlspecialchars($content['body']); ?></textarea>

                <label for="image">Upload New Image (optional)</label>
                <input type="file" name="image">

                <button type="submit" name="edit_content">Update Content</button>
            </form>
        </section>
    <?php endif; ?>

    <section>
        <h3>Your Contents</h3>

        <form method="GET" action="content_creator.php">
            <label for="filter">Filter by Status:</label>
            <select id="filter" name="filter" onchange="this.form.submit()">
                <option value="all" <?php if ($filter == 'all') echo 'selected'; ?>>All</option>
                <option value="pending" <?php if ($filter == 'pending') echo 'selected'; ?>>Pending</option>
                <option value="approved" <?php if ($filter == 'approved') echo 'selected'; ?>>Approved</option>
                <option value="rejected" <?php if ($filter == 'rejected') echo 'selected'; ?>>Rejected</option>
            </select>
        </form>

        <table>
            <tr>
                <th>Title</th>
                <th>Body</th>
                <th>Image</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($contents as $content): ?>
                <tr>
                    <td><?php echo htmlspecialchars($content['title']); ?></td>
                    <td><?php echo htmlspecialchars($content['body']); ?></td>
                    <td>
                        <?php if ($content['image']): ?>
                            <img src="<?php echo htmlspecialchars($content['image']); ?>" alt="Image" width="50">
                        <?php else: ?>
                            No Image
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="content_creator.php?edit=<?php echo $content['id']; ?>">Edit</a> |
                        <a href="content_creator.php?delete=<?php echo $content['id']; ?>" onclick="return confirm('Are you sure you want to delete this content?')">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </section>
</body>
</html>
