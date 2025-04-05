<?php
require_once '../database.php';

if (!isset($_GET['LocationID'])) {
    header("Location: index.php");
    exit();
}

$locationID = $_GET['LocationID'];

// Get the club location details
$locationQuery = "SELECT * FROM kqc353_4.ClubLocation WHERE LocationID = ?";
$locationStmt = $conn->prepare($locationQuery);

if ($locationStmt === false) {
    die("Error preparing location query: " . $conn->error);
}

$locationStmt->bind_param("i", $locationID);
if (!$locationStmt->execute()) {
    die("Error executing location query: " . $locationStmt->error);
}

$location = $locationStmt->get_result()->fetch_assoc();
$locationStmt->close();

if (!$location) {
    header("Location: index.php?error=location_not_found");
    exit();
}

// Get phone numbers for this location
$phonesQuery = "SELECT PhoneNumber FROM kqc353_4.Phone WHERE LocationID = ?";
$phonesStmt = $conn->prepare($phonesQuery);

if ($phonesStmt === false) {
    die("Error preparing phones query: " . $conn->error);
}

$phonesStmt->bind_param("i", $locationID);
if (!$phonesStmt->execute()) {
    die("Error executing phones query: " . $phonesStmt->error);
}

$phones = $phonesStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$phonesStmt->close();

// Get events happening at this location
$eventsQuery = "SELECT 
                e.EventID,
                DATE_FORMAT(e.EventDateTime, '%Y-%m-%d %H:%i') AS EventDateTime,
                e.SessionType,
                e.Address,
                e.Note
               FROM kqc353_4.Event e
               WHERE e.Address LIKE CONCAT('%', ?, '%')
               ORDER BY e.EventDateTime DESC";
$eventsStmt = $conn->prepare($eventsQuery);

if ($eventsStmt === false) {
    die("Error preparing events query: " . $conn->error);
}

$eventsStmt->bind_param("s", $location['Address']);
if (!$eventsStmt->execute()) {
    die("Error executing events query: " . $eventsStmt->error);
}

$events = $eventsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$eventsStmt->close();

// Get teams based at this location - MODIFIED to use TeamName instead of TeamID
$teamsQuery = "SELECT 
                t.TeamName,
                t.Gender,
                t.Coach,
                COUNT(tf.FormationID) AS EventCount
               FROM kqc353_4.Team t
               LEFT JOIN kqc353_4.TeamFormation tf ON t.TeamName = tf.TeamName
               WHERE t.LocationID = ?
               GROUP BY t.TeamName, t.Gender, t.Coach
               ORDER BY t.TeamName";
$teamsStmt = $conn->prepare($teamsQuery);

if ($teamsStmt === false) {
    die("Error preparing teams query: " . $conn->error);
}

$teamsStmt->bind_param("i", $locationID);
if (!$teamsStmt->execute()) {
    die("Error executing teams query: " . $teamsStmt->error);
}

$teams = $teamsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$teamsStmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Location Details</title>
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
            width: 150px;
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
    <h2>Club Location Details</h2>
    
    <div class="detail-section">
        <div class="detail-header">
            <h3>Location Information</h3>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">Location ID:</div>
            <div class="detail-value"><?= htmlspecialchars($location['LocationID']) ?></div>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">Type:</div>
            <div class="detail-value"><?= htmlspecialchars($location['LocationType']) ?></div>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">Name:</div>
            <div class="detail-value"><?= htmlspecialchars($location['Name']) ?></div>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">Address:</div>
            <div class="detail-value"><?= htmlspecialchars($location['Address']) ?></div>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">City:</div>
            <div class="detail-value"><?= htmlspecialchars($location['City']) ?></div>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">Province:</div>
            <div class="detail-value"><?= htmlspecialchars($location['Province']) ?></div>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">Postal Code:</div>
            <div class="detail-value"><?= htmlspecialchars($location['PostalCode']) ?></div>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">Web Address:</div>
            <div class="detail-value">
                <?php if ($location['WebAddress']): ?>
                    <a href="<?= htmlspecialchars($location['WebAddress']) ?>" target="_blank">
                        <?= htmlspecialchars($location['WebAddress']) ?>
                    </a>
                <?php else: ?>
                    N/A
                <?php endif; ?>
            </div>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">Max Capacity:</div>
            <div class="detail-value"><?= htmlspecialchars($location['MaxCapacity']) ?></div>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">Phone Numbers:</div>
            <div class="detail-value">
                <?php if (count($phones) > 0): ?>
                    <ul>
                        <?php foreach ($phones as $phone): ?>
                            <li><?= htmlspecialchars($phone['PhoneNumber']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    No phone numbers registered
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Events Section -->
    <div class="detail-section">
        <div class="detail-header">
            <h3>Upcoming Events at This Location</h3>
        </div>
        
        <?php if (count($events) > 0): ?>
            <table class="sub-table">
                <tr>
                    <th>Event Date/Time</th>
                    <th>Session Type</th>
                    <th>Address</th>
                    <th>Notes</th>
                </tr>
                <?php foreach ($events as $event): ?>
                    <tr>
                        <td><?= htmlspecialchars($event['EventDateTime']) ?></td>
                        <td><?= htmlspecialchars($event['SessionType']) ?></td>
                        <td><?= htmlspecialchars($event['Address']) ?></td>
                        <td><?= htmlspecialchars($event['Note'] ?? 'N/A') ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>No upcoming events scheduled at this location.</p>
        <?php endif; ?>
    </div>
    
    <!-- Teams Section -->
    <div class="detail-section">
        <div class="detail-header">
            <h3>Teams Based at This Location</h3>
        </div>
        
        <?php if (count($teams) > 0): ?>
            <table class="sub-table">
                <tr>
                    <th>Team Name</th>
                    <th>Gender</th>
                    <th>Coach</th>
                    <th>Events Participated</th>
                </tr>
                <?php foreach ($teams as $team): ?>
                    <tr>
                        <td><?= htmlspecialchars($team['TeamName']) ?></td>
                        <td><?= htmlspecialchars($team['Gender']) ?></td>
                        <td><?= htmlspecialchars($team['Coach'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($team['EventCount']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>No teams currently based at this location.</p>
        <?php endif; ?>
    </div>
    
    <div class="form-actions">
        <a href="index.php" class="return-button">Back to Locations</a>
    </div>
</main>

</body>
</html>