<?php
require_once '../database.php';

if (!isset($_GET['LocationID'])) {
    header("Location: index.php");
    exit();
}

$locationID = $_GET['LocationID'];

// Perform the deletion
$query = "DELETE FROM kqc353_4.ClubLocation WHERE LocationID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $locationID);

if ($stmt->execute()) {
    header("Location: index.php?success=3");
} else {
    header("Location: index.php?error=delete_failed");
}
$stmt->close();
exit();
?>