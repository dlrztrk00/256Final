<?php
require "./db.php";

$contentQuery = "SELECT * FROM contents WHERE status = 'approved'";
$stmt = $db->prepare($contentQuery);
$stmt->execute();

$contents = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($contents);
?>
