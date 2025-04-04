<?php
require_once '../database.php';

if (!isset($_GET['FamilyMemberID'])) {
    header("Location: index.php");
    exit();
}

$familyMemberID = $_GET['FamilyMemberID'];

//delete query
$query = "DELETE FROM kqc353_4.FamilyMember WHERE FamilyMemberID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $familyMemberID);

if ($stmt->execute()) {
    header("Location: index.php?success=3");
} else {
    header("Location: index.php?error=delete_failed");
}
$stmt->close();
exit();
?>