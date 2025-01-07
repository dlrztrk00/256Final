<?php
require "./db.php";

$creator_id = isset($_GET['creator_id']) ? $_GET['creator_id'] : null;

if ($creator_id) {
    $contentQuery = "SELECT * FROM contents WHERE user_id = :creator_id AND status = 'approved'";
    $stmt = $db->prepare($contentQuery);
    $stmt->execute(['creator_id' => $creator_id]);

    $contents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($contents);
} else {
    echo json_encode(['error' => 'No creator ID specified']);
}
?>
