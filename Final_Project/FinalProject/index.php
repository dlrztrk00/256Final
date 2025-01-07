<?php
require "./db.php";
session_start();

// logged in
$isLoggedIn = isset($_SESSION['user_id']);
$isContentCreator = $isLoggedIn && $_SESSION['role'] === 'content_creator';
$userName = $isLoggedIn ? $_SESSION['username'] : ''; // Kullanıcı adı, giriş yaptıysa alınacak

// search for content
$searchQuery = isset($_POST['search']) ? $_POST['search'] : '';
$whereClause = '';

if ($searchQuery) {
    $whereClause = "AND (title LIKE :search OR body LIKE :search)";
}

// display only approwed
$contentQuery = "SELECT * FROM contents WHERE status = 'approved' $whereClause";
$stmt = $db->prepare($contentQuery);

if ($searchQuery) {
    $stmt->execute(['search' => "%$searchQuery%"]);
} else {
    $stmt->execute();
}

$contents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// search with ajax
if (isset($_POST['ajax']) && $_POST['ajax'] == 1) {
    if (empty($contents)) {
        echo "<p style='text-align: center; color: #555;'>We couldn't find your item.</p>";
    } else {
        foreach ($contents as $content) :
            ?>
            <div class="content-wrapper">
                <h3><?= htmlspecialchars($content["title"]) ?></h3>
                <div class="content-image">
                    <img src="img/<?= htmlspecialchars($content['image']) ?>" alt="<?= htmlspecialchars($content['title']) ?>">
                </div>
                <div class="content-description">
                    <?= $content["body"] ?>
                </div>

                <?php if ($isContentCreator): ?>
                    <div class="btn-edit">
                        <a href="content_creator.php?edit=<?= $content['id'] ?>">Edit Content</a>
                    </div>
                <?php endif; ?>
            </div>
            <?php
        endforeach;
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Featured Content</title>
    <link href="style.css" rel="stylesheet" type="text/css"/>
    <script src="jquery-3.1.1.js" type="text/javascript"></script>
    <style>
      
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 0;
                background-color: #f4f7fb;
            }

            header {
                background-color: #fff;
                padding: 15px 30px;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                display: flex;
                justify-content: space-between;
                align-items: center;
                position: sticky;
                top: 0;
                z-index: 100;
            }

            #logo img {
                width: 170px;
                height: auto;
            }

            .header-links {
                display: flex;
                align-items: center;
            }

            .header-links a {
                text-decoration: none;
                color: #333;
                font-size: 14px;
                margin-left: 20px;
                transition: color 0.3s ease;
            }

            .header-links a:hover {
                color: #4CAF50;
            }

            .user-info {
                font-size: 14px;
                color: #555;
            }

            .search-container {
                flex-grow: 1;
                text-align: center;
            }

            .search-container form {
                display: inline-block;
            }

            .search-container input[type="text"] {
                padding: 10px;
                font-size: 16px;
                width: 280px;
                border: 1px solid #ddd;
                border-radius: 4px;
                outline: none;
            }

            .search-container input[type="text"]:focus {
                border-color: #4CAF50;
            }

            .search-container button {
                padding: 10px 15px;
                background-color: #4CAF50;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                margin-left: 10px;
                font-size: 16px;
            }

            .search-container button:hover {
                background-color: #45a049;
            }

            .content-container {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(470px, 1fr));
                gap: 20px;
                padding: 20px;
            }

            .content-wrapper {
                padding: 20px;
                border: 1px solid #ddd;
                border-radius: 8px;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                background-color: #fff;
                text-align: center;
                transition: transform 0.3s ease;
            }

            .content-wrapper:hover {
                transform: translateY(-5px);
            }

            .content-wrapper img {
                width: 200px;
                height: 200px;
                object-fit: cover;
                border-radius: 8px;
                margin-bottom: 15px;
            }

            .content-wrapper h3 {
                font-size: 20px;
                color: #333;
                margin-bottom: 10px;
            }

            .content-description {
                font-size: 14px;
                color: #555;
                margin-bottom: 15px;
            }

            .btn-edit a {
                background-color: #4CAF50;
                padding: 10px 20px;
                color: white;
                text-decoration: none;
                border-radius: 4px;
                font-size: 14px;
                transition: background-color 0.3s ease;
            }

            .btn-edit a:hover {
                background-color: #45a049;
            }
            .btn-add {
                text-align: center;
                margin-top: 40px;
            }

            .btn-add a {
                background-color: #4CAF50;
                color: white;
                padding: 12px 25px;
                text-decoration: none;
                border-radius: 4px;
                font-size: 16px;
                display: inline-block;
                transition: background-color 0.3s ease;
            }

            .btn-add a:hover {
                background-color: #45a049;
            }

            .h3-heading {
                text-align: center;
                font-size: 28px;
                color: #333;
                margin: 30px 0;
                text-transform: uppercase;
            }

            .json-buttons {
                text-align: center;
                margin-top: 40px;
            }

            .json-buttons .btn-json {
                margin-bottom: 20px;
            }

            .json-buttons a,
            .json-buttons button {
                background-color: #4CAF50;
                color: white;
                padding: 12px 25px;
                text-decoration: none;
                border-radius: 4px;
                margin: 10px 5px;
                font-size: 16px;
                display: inline-block;
                transition: background-color 0.3s ease;
            }

            .json-buttons a:hover,
            .json-buttons button:hover {
                background-color: #45a049;
            }

            .json-buttons form {
                display: inline-block;
                margin-top: 10px;
            }

            .json-buttons input[type="text"] {
                padding: 10px;
                font-size: 16px;
                width: 250px;
                border: 1px solid #ddd;
                border-radius: 4px;
                margin-right: 10px;
                outline: none;
            }

            .json-buttons input[type="text"]:focus {
                border-color: #4CAF50;
            }

            .json-buttons button {
                padding: 10px 15px;
                background-color: #4CAF50;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 16px;
            }

            .json-buttons button:hover {
                background-color: #45a049;
            }

            footer {
                background-color: #f9f9f9;
                padding: 20px 0;
                text-align: center;
                font-size: 14px;
                color: #777;
                margin-top: 50px;
            }

    </style>
</head>
<body>
    <header>
    <div id="logo">
        <!-- Logo tıklanabilir hale getirildi -->
        <a href="index.php">
            <img src="img/logo.jpg" alt="Logo">
        </a>
    </div>

        <div class="search-container">
            <form id="searchForm">
                <input type="text" name="search" id="search" placeholder="Search for content..." value="<?= htmlspecialchars($searchQuery) ?>">
                <button type="submit">Search</button>
            </form>
        </div>

        <div class="header-links">
            <?php if ($isLoggedIn): ?>
                <div class="user-info">Welcome, <?= htmlspecialchars($userName) ?>!</div>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
            <?php endif; ?>
        </div>
    </header>

    <section>
        <h3 class="h3-heading">FEATURED CONTENT</h3>
    </section>

    <section id="contentContainer" class="content-container">
        <?php if (empty($contents)): ?>
            <p style="text-align: center; color: #555;">We couldn't find your item.</p>
        <?php else: ?>
            <?php foreach ($contents as $content) : ?>
                <div class="content-wrapper">
                    <h3><?= htmlspecialchars($content["title"]) ?></h3>
                    <div class="content-image">
                        <img src="img/<?= htmlspecialchars($content['image']) ?>" alt="<?= htmlspecialchars($content['title']) ?>">
                    </div>
                    <div class="content-description">
                        <?= $content["body"] ?>
                    </div>

                    <?php if ($isContentCreator): ?>
                        <div class="btn-edit">
                            <a href="content_creator.php?edit=<?= $content['id'] ?>">Edit Content</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <?php if ($isContentCreator): ?>
        <div class="btn-add">
            <a href="content_creator.php">Add New Content</a>
        </div>
    <?php endif; ?>

    <!-- JSON buttons -->
    <section class="json-buttons">
        <div class="btn-json">
            <a href="get_all_contents.php" target="_blank">View All Contents in JSON</a>
        </div>
        <div class="btn-json">
            <form id="creatorForm" action="get_creator_contents.php" method="GET" target="_blank">
                <input type="text" name="creator_id" id="creator_id" placeholder="Enter creator ID" required>
                <button type="submit">View Creator's Contents in JSON</button>
            </form>
        </div>
    </section>

    <script>
    $(document).on('submit', '#searchForm', function(e) {
        e.preventDefault(); 
        var searchQuery = $('#search').val();

        $.ajax({
            url: 'index.php', 
            method: 'POST',    
            data: { 
                search: searchQuery, 
                ajax: 1 
            },
            success: function(response) {
                $('#contentContainer').html(response); 
                if (response.includes("We couldn't find your item")) {
                    alert("We couldn't find your item.");
                }
            }
        });
    });
    </script>

</body>
</html>
