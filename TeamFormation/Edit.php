<?php
require_once '../database.php';

// Check if FormationID is provided
if (!isset($_GET['FormationID'])) {
    header("Location: index.php");
    exit();
}

$formationID = $_GET['FormationID'];

// Initialize variables
$formData = [];
$errors = [];

// Fetch required data for dropdowns
$events = [];
$teams = [];
$captains = [];
$locations = [];

// Get available events
$eventsQuery = "SELECT EventID, CONCAT('Event #', EventID, ' - ', DATE_FORMAT(EventDateTime, '%Y-%m-%d %H:%i'), ' - ', SessionType) AS EventInfo FROM kqc353_4.Event";
$eventsResult = $conn->query($eventsQuery);
if ($eventsResult) {
    while ($row = $eventsResult->fetch_assoc()) {
        $events[$row['EventID']] = $row['EventInfo'];
    }
}

// Get available teams
$teamsQuery = "SELECT TeamName FROM kqc353_4.Team";
$teamsResult = $conn->query($teamsQuery);
if ($teamsResult) {
    while ($row = $teamsResult->fetch_assoc()) {
        $teams[] = $row['TeamName'];
    }
}

// Get available captains (club members)
$captainsQuery = "SELECT CMN, CONCAT(FirstName, ' ', LastName) AS FullName FROM kqc353_4.ClubMember";
$captainsResult = $conn->query($captainsQuery);
if ($captainsResult) {
    while ($row = $captainsResult->fetch_assoc()) {
        $captains[$row['CMN']] = $row['FullName'];
    }
}

// Get available locations
$locationsQuery = "SELECT LocationID, Name FROM kqc353_4.ClubLocation";
$locationsResult = $conn->query($locationsQuery);
if ($locationsResult) {
    while ($row = $locationsResult->fetch_assoc()) {
        $locations[$row['LocationID']] = $row['Name'];
    }
}

// Fetch existing team formation data
$formationQuery = "SELECT * FROM kqc353_4.TeamFormation WHERE FormationID = ?";
$stmt = $conn->prepare($formationQuery);
$stmt->bind_param("i", $formationID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php");
    exit();
}

$formData = $result->fetch_assoc();
$stmt->close();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize all inputs
    foreach ($_POST as $key => $value) {
        $formData[$key] = trim($value);
    }

    // Validate required fields
    $requiredFields = ['EventID', 'TeamName', 'CaptainCMN', 'LocationID'];
    
    foreach ($requiredFields as $field) {
        if (empty($formData[$field])) {
            $errors[$field] = 'This field is required';
        }
    }

    // Validate numeric fields
    if (!empty($formData['Score']) && !is_numeric($formData['Score'])) {
        $errors['Score'] = 'Score must be a number';
    }

    // Verify foreign keys exist
    if (empty($errors)) {
        // Check Event exists
        $checkEvent = $conn->prepare("SELECT COUNT(*) AS count FROM kqc353_4.Event WHERE EventID = ?");
        $checkEvent->bind_param("i", $formData['EventID']);
        $checkEvent->execute();
        $eventResult = $checkEvent->get_result()->fetch_assoc();
        if ($eventResult['count'] == 0) {
            $errors['EventID'] = "Selected event does not exist";
        }
        $checkEvent->close();

        // Check Team exists (by TeamName)
        $checkTeam = $conn->prepare("SELECT COUNT(*) AS count FROM kqc353_4.Team WHERE TeamName = ?");
        $checkTeam->bind_param("s", $formData['TeamName']);
        $checkTeam->execute();
        $teamResult = $checkTeam->get_result()->fetch_assoc();
        if ($teamResult['count'] == 0) {
            $errors['TeamName'] = "Selected team does not exist";
        }
        $checkTeam->close();

        // Check if Captain exists
        $checkCaptain = $conn->prepare("SELECT COUNT(*) AS count FROM kqc353_4.ClubMember WHERE CMN = ?");
        $checkCaptain->bind_param("i", $formData['CaptainCMN']);
        $checkCaptain->execute();
        $captainResult = $checkCaptain->get_result()->fetch_assoc();
        if ($captainResult['count'] == 0) {
            $errors['CaptainCMN'] = "Selected captain does not exist";
        }
        $checkCaptain->close();

        // Check Location exists
        $checkLocation = $conn->prepare("SELECT COUNT(*) AS count FROM kqc353_4.ClubLocation WHERE LocationID = ?");
        $checkLocation->bind_param("i", $formData['LocationID']);
        $checkLocation->execute();
        $locationResult = $checkLocation->get_result()->fetch_assoc();
        if ($locationResult['count'] == 0) {
            $errors['LocationID'] = "Selected location does not exist";
        }
        $checkLocation->close();
    }

    // If no errors, update database
    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            $query = "UPDATE kqc353_4.TeamFormation SET
                EventID = ?,
                TeamName = ?,
                CaptainCMN = ?,
                LocationID = ?,
                Score = ?,
                Note = ?
                WHERE FormationID = ?";
            
            $stmt = $conn->prepare($query);
            
            if ($stmt) {
                $stmt->bind_param(
                    "isiissi",
                    $formData['EventID'],
                    $formData['TeamName'],
                    $formData['CaptainCMN'],
                    $formData['LocationID'],
                    $formData['Score'],
                    $formData['Note'],
                    $formationID
                );
                
                if ($stmt->execute()) {
                    $conn->commit();
                    header("Location: index.php?success=2");
                    exit();
                } else {
                    throw new Exception("Execute failed: " . $stmt->error);
                }
                
                $stmt->close();
            } else {
                throw new Exception("Prepare failed: " . $conn->error);
            }
        } catch (Exception $e) {
            $conn->rollback();
            $errors['database'] = "Database operation failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Team Formation</title>
    <link rel="stylesheet" href="../style.css">
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
        <li><a href="../otherQueries/index.php">Other Queries</a></li>
    </ul>
</nav>

<main>
    <h2>Edit Team Formation (ID: <?= htmlspecialchars($formationID) ?>)</h2>
    
    <?php if (!empty($errors['database'])): ?>
        <div class="error-message">
            <strong>Error:</strong> <?= htmlspecialchars($errors['database']) ?>
            <?php if (strpos($errors['database'], 'foreign key constraint') !== false): ?>
                <p>Please verify all selected references (Event, Team, Captain, Location) exist in the database.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <form method="post" action="edit.php?FormationID=<?= $formationID ?>">
        <div class="form-container">
            <!-- Column 1 -->
            <div>
                <div class="form-group">
                    <label for="EventID">Event*:</label>
                    <select id="EventID" name="EventID" required>
                        <option value="">-- Select Event --</option>
                        <?php foreach ($events as $id => $info): ?>
                            <option value="<?= $id ?>" <?= $id == $formData['EventID'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($info) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['EventID'])): ?>
                        <span class="error"><?= $errors['EventID'] ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="TeamName">Team*:</label>
                    <select id="TeamName" name="TeamName" required>
                        <option value="">-- Select Team --</option>
                        <?php foreach ($teams as $name): ?>
                            <option value="<?= htmlspecialchars($name) ?>" <?= $name == $formData['TeamName'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['TeamName'])): ?>
                        <span class="error"><?= $errors['TeamName'] ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="CaptainCMN">Captain*:</label>
                    <select id="CaptainCMN" name="CaptainCMN" required>
                        <option value="">-- Select Captain --</option>
                        <?php foreach ($captains as $id => $name): ?>
                            <option value="<?= $id ?>" <?= $id == $formData['CaptainCMN'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['CaptainCMN'])): ?>
                        <span class="error"><?= $errors['CaptainCMN'] ?></span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Column 2 -->
            <div>
                <div class="form-group">
                    <label for="LocationID">Location*:</label>
                    <select id="LocationID" name="LocationID" required>
                        <option value="">-- Select Location --</option>
                        <?php foreach ($locations as $id => $name): ?>
                            <option value="<?= $id ?>" <?= $id == $formData['LocationID'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['LocationID'])): ?>
                        <span class="error"><?= $errors['LocationID'] ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="Score">Score:</label>
                    <input type="number" id="Score" name="Score" 
                           value="<?= htmlspecialchars($formData['Score']) ?>">
                    <?php if (!empty($errors['Score'])): ?>
                        <span class="error"><?= $errors['Score'] ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="Note">Notes:</label>
                    <textarea id="Note" name="Note" rows="3"><?= htmlspecialchars($formData['Note']) ?></textarea>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="submit-button">Update Formation</button>
            <a href="index.php" class="cancel-button">Cancel</a>
        </div>
    </form>
</main>

</body>
</html>