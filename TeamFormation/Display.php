<?php
require_once '../database.php';

if (!isset($_GET['FormationID'])) {
    header("Location: index.php");
    exit();
}

$formationID = $_GET['FormationID'];

// Get the team formation details
$formationQuery = "SELECT 
                    tf.FormationID,
                    tf.EventID,
                    tf.TeamName,
                    tf.CaptainCMN,
                    tf.LocationID,
                    tf.Score,
                    tf.Note,
                    e.EventDateTime,
                    e.SessionType,
                    e.Address AS EventAddress,
                    cl.Name AS LocationName,
                    CONCAT(cm.FirstName, ' ', cm.LastName) AS CaptainName
                  FROM kqc353_4.TeamFormation tf
                  JOIN kqc353_4.Event e ON tf.EventID = e.EventID
                  JOIN kqc353_4.ClubLocation cl ON tf.LocationID = cl.LocationID
                  JOIN kqc353_4.ClubMember cm ON tf.CaptainCMN = cm.CMN
                  WHERE tf.FormationID = ?";
$formationStmt = $conn->prepare($formationQuery);

if ($formationStmt === false) {
    die("Error preparing formation query: " . $conn->error);
}

$formationStmt->bind_param("i", $formationID);
if (!$formationStmt->execute()) {
    die("Error executing formation query: " . $formationStmt->error);
}

$formation = $formationStmt->get_result()->fetch_assoc();
$formationStmt->close();

if (!$formation) {
    header("Location: index.php?error=formation_not_found");
    exit();
}

// Get all player assignments for this formation
$playersQuery = "SELECT 
                    pa.CMN,
                    CONCAT(cm.FirstName, ' ', cm.LastName) AS PlayerName,
                    pa.PlayerRole,
                    pa.Note
                 FROM kqc353_4.PlayerAssignment pa
                 JOIN kqc353_4.ClubMember cm ON pa.CMN = cm.CMN
                 WHERE pa.FormationID = ?
                 ORDER BY pa.PlayerRole, cm.LastName";
$playersStmt = $conn->prepare($playersQuery);

if ($playersStmt === false) {
    die("Error preparing players query: " . $conn->error);
}

$playersStmt->bind_param("i", $formationID);
if (!$playersStmt->execute()) {
    die("Error executing players query: " . $playersStmt->error);
}

$players = $playersStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$playersStmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Formation Details</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .detail-section {
            margin-bottom: 30px;
            padding: 20px;
            border-radius: 5px;
        }
        .detail-header {
            border-bottom: 2px solid #ddd;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .detail-row {
            display: flex;
            margin-bottom: 10px;
        }
        .detail-label {
            font-weight: bold;
            width: 200px;
        }
        .detail-value {
            flex: 1;
        }
    </style>
</head>
<body>
    
<h1>MYVC Montreal</h1>

<nav class="navbar">
    <ul>
        <li><a href="../home.php">Home</a></li>
        <li><a href="../ClubLocation/index.php">Club Location</a></li>
        <li><a href="../Personnel/index.php">Personnel</a></li>
        <li><a href="../FamilyMember/index.php">Family Member</a></li>
        <li><a href="../ClubMember/index.php">Club Member</a></li>
        <li><a href="../TeamFormation/index.php">Team Formation</a></li>
        <li><a href="../playerAssignment/index.php">Player Assignments</a></li>
    </ul>
</nav>

<main>
    <h2>Team Formation Details</h2>
    
    <div class="detail-section">
        <div class="detail-header">
            <h3>Formation Information</h3>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">Formation ID:</div>
            <div class="detail-value"><?= htmlspecialchars($formation['FormationID']) ?></div>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">Event:</div>
            <div class="detail-value">
                Event #<?= htmlspecialchars($formation['EventID']) ?> - 
                <?= htmlspecialchars(date('M j, Y H:i', strtotime($formation['EventDateTime']))) ?> - 
                <?= htmlspecialchars($formation['SessionType']) ?>
            </div>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">Team Name:</div>
            <div class="detail-value"><?= htmlspecialchars($formation['TeamName']) ?></div>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">Captain:</div>
            <div class="detail-value">
                <?= htmlspecialchars($formation['CaptainName']) ?> (CMN: <?= htmlspecialchars($formation['CaptainCMN']) ?>)
            </div>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">Location:</div>
            <div class="detail-value">
                <a href="../ClubLocation/display.php?LocationID=<?= $formation['LocationID'] ?>">
                    <?= htmlspecialchars($formation['LocationName']) ?> (ID: <?= htmlspecialchars($formation['LocationID']) ?>)
                </a>
            </div>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">Score:</div>
            <div class="detail-value"><?= htmlspecialchars($formation['Score'] ?? 'N/A') ?></div>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">Notes:</div>
            <div class="detail-value"><?= htmlspecialchars($formation['Note'] ?? 'N/A') ?></div>
        </div>
    </div>
    
    <!-- Players Section -->
    <div class="detail-section">
        <div class="detail-header">
            <h3>Team Players</h3>
        </div>
        
        <?php if (count($players) > 0): ?>
            <table class="sub-table">
                <tr>
                    <th>Player Name</th>
                    <th>CMN</th>
                    <th>Role</th>
                    <th>Notes</th>
                </tr>
                <?php foreach ($players as $player): ?>
                    <tr class="<?= $player['CMN'] == $formation['CaptainCMN'] ? 'highlight' : '' ?>">
                        <td>
                            <a href="../ClubMember/display.php?CMN=<?= $player['CMN'] ?>">
                                <?= htmlspecialchars($player['PlayerName']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($player['CMN']) ?></td>
                        <td><?= htmlspecialchars($player['PlayerRole']) ?></td>
                        <td><?= htmlspecialchars($player['Note'] ?? 'N/A') ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>No players assigned to this formation.</p>
        <?php endif; ?>
    </div>
    
    <div class="form-actions">
        <a href="index.php" class="return-button">Back to Formations</a>
    </div>
</main>

</body>
</html>