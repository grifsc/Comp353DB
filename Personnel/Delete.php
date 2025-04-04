<?php
require_once '../database.php';

if (!isset($_GET['PersonnelID'])) {
    header("Location: index.php");
    exit();
}

$personnelID = $_GET['PersonnelID'];

//delete query
$query = "DELETE FROM kqc353_4.Personnel WHERE PersonnelID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $personnelID);

if ($stmt->execute()) {
    header("Location: index.php?success=3");
} else {
    header("Location: index.php?error=delete_failed");
}
$stmt->close();
exit();
?>