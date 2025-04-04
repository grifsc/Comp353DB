<?php
require_once '../database.php';

if (!isset($_GET['CMN'])) {
    header("Location: index.php");
    exit();
}

$cmn = $_GET['CMN'];

//delete query
$query = "DELETE FROM kqc353_4.ClubMember WHERE CMN = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $cmn);

if ($stmt->execute()) {
    header("Location: index.php?success=3");
} else {
    header("Location: index.php?error=delete_failed");
}
$stmt->close();
exit();
?>