<?php
session_start();
require_once 'db.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'editor') {
    header('Location: login.php');
    exit;
}

$searchQuery = "";
if (isset($_POST['search_content'])) {
    $searchQuery = "%" . $_POST['search_content'] . "%";
    $stmt_contents = $db->prepare("SELECT * FROM contents WHERE title LIKE :searchQuery OR body LIKE :searchQuery");
    $stmt_contents->execute(['searchQuery' => $searchQuery]);
} else {
    $stmt_contents = $db->prepare("SELECT * FROM contents WHERE status != 'rejected'");
    $stmt_contents->execute();
}
$contents = $stmt_contents->fetchAll(PDO::FETCH_ASSOC);

//  content approve/reject
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $content_id = $_POST['content_id'];
    $status = $_POST['status'];
    $stmt = $db->prepare("UPDATE contents SET status = :status WHERE id = :id");
    $stmt->execute(['status' => $status, 'id' => $content_id]);
    header('Location: editor.php');
    exit;
}

//  content editing 
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_content'])) {
    $content_id = $_POST['content_id'];
    $title = $_POST['title'];
    $body = $_POST['body'];
    $stmt = $db->prepare("UPDATE contents SET title = :title, body = :body WHERE id = :id");
    $stmt->execute(['title' => $title, 'body' => $body, 'id' => $content_id]);
    header('Location: editor.php');
    exit;
}

//  content deletion
if (isset($_GET['delete'])) {
    $content_id = $_GET['delete'];
    $stmt = $db->prepare("DELETE FROM contents WHERE id = :id");
    $stmt->execute(['id' => $content_id]);
    header('Location: editor.php');
    exit;
}

//  comment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_comment'])) {
    $content_id = $_POST['content_id'];
    $comment = $_POST['comment'];
    $user_id = $_SESSION['user_id'];

    $stmt = $db->prepare("INSERT INTO comments (content_id, user_id, comment) VALUES (:content_id, :user_id, :comment)");
    $stmt->execute(['content_id' => $content_id, 'user_id' => $user_id, 'comment' => $comment]);

    header('Location: editor.php');
    exit;
}

//  comment deletion
if (isset($_GET['delete_comment'])) {
    $comment_id = $_GET['delete_comment'];
    $stmt = $db->prepare("DELETE FROM comments WHERE id = :id");
    $stmt->execute(['id' => $comment_id]);

    header('Location: editor.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editor Dashboard</title>
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

        .search-form {
            margin-bottom: 20px;
        }

        .search-form input {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .edit-form textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            height: 150px;
        }

        .edit-form input[type="text"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <header>
        <h2>Editor Dashboard</h2>
        <a href="index.php" class="go-to-main-page">Go to Main Page</a>
    </header>

    <section>
        <h3>Search Content</h3>
        <form method="POST" class="search-form">
            <input type="text" name="search_content" placeholder="Search content by title or body" value="<?php echo $searchQuery; ?>">
            <button type="submit">Search</button>
        </form>

        <h3>Pending Contents</h3>
        <table>
            <tr>
                <th>Title</th>
                <th>Body</th>
                <th>Status</th>
                <th>Actions</th>
                <th>Comments</th>
            </tr>

            <?php foreach ($contents as $content): ?>
                <tr>
                    <td><?php echo htmlspecialchars($content['title']); ?></td>
                    <td><?php echo htmlspecialchars($content['body']); ?></td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="content_id" value="<?php echo $content['id']; ?>">
                            <select name="status">
                                <option value="pending" <?php if ($content['status'] == 'pending') echo 'selected'; ?>>Pending</option>
                                <option value="approved" <?php if ($content['status'] == 'approved') echo 'selected'; ?>>Approved</option>
                                <option value="rejected" <?php if ($content['status'] == 'rejected') echo 'selected'; ?>>Rejected</option>
                            </select>
                            <button type="submit" name="update_status">Update Status</button>
                        </form>
                    </td>
                    <td>
                        <a href="editor.php?edit=<?php echo $content['id']; ?>">Edit</a> | 
                        <a href="editor.php?delete=<?php echo $content['id']; ?>" onclick="return confirm('Are you sure you want to delete this content?')">Delete</a>
                    </td>
                    <td>
                        <h4>Comments:</h4>
                        <ul>
                            <?php
                            $stmt_comments = $db->prepare("SELECT * FROM comments WHERE content_id = :content_id");
                            $stmt_comments->execute(['content_id' => $content['id']]);
                            $comments = $stmt_comments->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($comments as $comment):
                            ?>
                                <li>
                                    <strong>User <?php echo $comment['user_id']; ?>:</strong>
                                    <?php echo htmlspecialchars($comment['comment']); ?>
                                    <a href="editor.php?delete_comment=<?php echo $comment['id']; ?>" onclick="return confirm('Are you sure you want to delete this comment?')">Delete</a>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <form method="POST">
                            <input type="hidden" name="content_id" value="<?php echo $content['id']; ?>">
                            <textarea name="comment" placeholder="Add a comment" required></textarea>
                            <button type="submit" name="add_comment">Add Comment</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </section>

    <?php
    // edit content
    if (isset($_GET['edit'])) {
        $content_id = $_GET['edit'];
        $stmt_edit = $db->prepare("SELECT * FROM contents WHERE id = :id");
        $stmt_edit->execute(['id' => $content_id]);
        $edit_content = $stmt_edit->fetch(PDO::FETCH_ASSOC);
    ?>
        <section>
            <h3>Edit Content</h3>
            <form method="POST" class="edit-form">
                <input type="hidden" name="content_id" value="<?php echo $edit_content['id']; ?>">
                <input type="text" name="title" value="<?php echo htmlspecialchars($edit_content['title']); ?>" placeholder="Title" required>
                <textarea name="body" placeholder="Body" required><?php echo htmlspecialchars($edit_content['body']); ?></textarea>
                <button type="submit" name="edit_content">Save Changes</button>
            </form>
        </section>
    <?php } ?>
</body>
</html>
