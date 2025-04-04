<?php
require_once '../database.php';

if (!isset($_GET['FormationID'])) {
    header("Location: index.php");
    exit();
}

$formationID = $_GET['FormationID'];

//delete query
$query = "DELETE FROM kqc353_4.TeamFormation WHERE FormationID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $formationID);

if ($stmt->execute()) {
    header("Location: index.php?success=3");
} else {
    header("Location: index.php?error=delete_failed");
}
$stmt->close();
exit();
?>