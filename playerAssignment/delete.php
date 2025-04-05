<?php
require_once '../database.php';

// Check if all required parameters are present
if (!isset($_GET['FormationID']) || !isset($_GET['CMN']) || !isset($_GET['PlayerRole'])) {
    header("Location: index.php?error=missing_parameters");
    exit();
}

$formationID = $_GET['FormationID'];
$cmn = $_GET['CMN'];
$playerRole = urldecode($_GET['PlayerRole']);

//delete query
$query = "DELETE FROM kqc353_4.PlayerAssignment 
          WHERE FormationID = ? AND CMN = ? AND PlayerRole = ?";
$stmt = $conn->prepare($query);

if ($stmt === false) {
    header("Location: index.php?error=prepare_failed");
    exit();
}

// Bind parameters and execute
$stmt->bind_param("iis", $formationID, $cmn, $playerRole);

if ($stmt->execute()) {
    // Check if any rows were affected
    if ($stmt->affected_rows > 0) {
        header("Location: index.php?success=3");
    } else {
        header("Location: index.php?error=no_record_found");
    }
} else {
    header("Location: index.php?error=delete_failed");
}

$stmt->close();
exit();
?>