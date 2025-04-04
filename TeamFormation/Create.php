<?php
require_once '../database.php';

//initialize variables
$formData = [
    'eventID' => '',
    'TeamName' => '', 
    'captainCMN' => '',
    'locationID' => '',
    'score' => 0,
    'note' => ''
];
$errors = [];

//fetch required data for dropdowns
$events = [];
$teams = [];
$captains = [];
$locations = [];

//get available events
$eventsQuery = "SELECT EventID, CONCAT('Event #', EventID, ' - ', DATE_FORMAT(EventDateTime, '%Y-%m-%d %H:%i'), ' - ', SessionType) AS EventInfo FROM kqc353_4.Event";
$eventsResult = $conn->query($eventsQuery);
if ($eventsResult) {
    while ($row = $eventsResult->fetch_assoc()) {
        $events[$row['EventID']] = $row['EventInfo'];
    }
}

//get available teams
$teamsQuery = "SELECT TeamName FROM kqc353_4.Team";
$teamsResult = $conn->query($teamsQuery);
if ($teamsResult) {
    while ($row = $teamsResult->fetch_assoc()) {
        $teams[] = $row['TeamName'];
    }
}

//get available captains (club members)
$captainsQuery = "SELECT CMN, CONCAT(FirstName, ' ', LastName) AS FullName FROM kqc353_4.ClubMember";
$captainsResult = $conn->query($captainsQuery);
if ($captainsResult) {
    while ($row = $captainsResult->fetch_assoc()) {
        $captains[$row['CMN']] = $row['FullName'];
    }
}

//get available locations
$locationsQuery = "SELECT LocationID, Name FROM kqc353_4.ClubLocation";
$locationsResult = $conn->query($locationsQuery);
if ($locationsResult) {
    while ($row = $locationsResult->fetch_assoc()) {
        $locations[$row['LocationID']] = $row['Name'];
    }
}

//process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize all inputs
    foreach ($_POST as $key => $value) {
        $formData[$key] = trim($value);
    }

    //validate required fields
    $requiredFields = ['eventID', 'TeamName', 'captainCMN', 'locationID'];
    
    foreach ($requiredFields as $field) {
        if (empty($formData[$field])) {
            $errors[$field] = 'This field is required';
        }
    }

    //validate numeric fields
    if (!empty($formData['score']) && !is_numeric($formData['score'])) {
        $errors['score'] = 'Score must be a number';
    }

    //verify foreign keys exist
    if (empty($errors)) {
        //check Event exists
        $checkEvent = $conn->prepare("SELECT COUNT(*) AS count FROM kqc353_4.Event WHERE EventID = ?");
        $checkEvent->bind_param("i", $formData['eventID']);
        $checkEvent->execute();
        $eventResult = $checkEvent->get_result()->fetch_assoc();
        if ($eventResult['count'] == 0) {
            $errors['eventID'] = "Selected event does not exist";
        }
        $checkEvent->close();

        //check Team exists (by TeamName)
        $checkTeam = $conn->prepare("SELECT COUNT(*) AS count FROM kqc353_4.Team WHERE TeamName = ?");
        $checkTeam->bind_param("s", $formData['TeamName']);
        $checkTeam->execute();
        $teamResult = $checkTeam->get_result()->fetch_assoc();
        if ($teamResult['count'] == 0) {
            $errors['TeamName'] = "Selected team does not exist";
        }
        $checkTeam->close();

        //check if Captain exists
        $checkCaptain = $conn->prepare("SELECT COUNT(*) AS count FROM kqc353_4.ClubMember WHERE CMN = ?");
        $checkCaptain->bind_param("i", $formData['captainCMN']);
        $checkCaptain->execute();
        $captainResult = $checkCaptain->get_result()->fetch_assoc();
        if ($captainResult['count'] == 0) {
            $errors['captainCMN'] = "Selected captain does not exist";
        }
        $checkCaptain->close();

        //check Location exists
        $checkLocation = $conn->prepare("SELECT COUNT(*) AS count FROM kqc353_4.ClubLocation WHERE LocationID = ?");
        $checkLocation->bind_param("i", $formData['locationID']);
        $checkLocation->execute();
        $locationResult = $checkLocation->get_result()->fetch_assoc();
        if ($locationResult['count'] == 0) {
            $errors['locationID'] = "Selected location does not exist";
        }
        $checkLocation->close();
    }

    //if no errors, insert into database
    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            $query = "INSERT INTO kqc353_4.TeamFormation (
                EventID, TeamName, CaptainCMN, LocationID, Score, Note
            ) VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($query);
            
            if ($stmt) {
                $stmt->bind_param(
                    "isiiss",
                    $formData['eventID'],
                    $formData['TeamName'],
                    $formData['captainCMN'],
                    $formData['locationID'],
                    $formData['score'],
                    $formData['note']
                );
                
                if ($stmt->execute()) {
                    $conn->commit();
                    header("Location: index.php?success=1");
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
            $errors['database'] = "Database operation failed. Please try again. ";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Team Formation</title>
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
    </ul>
</nav>

<main>
    <h2>Create New Team Formation</h2>
    
    <?php if (!empty($errors['database'])): ?>
        <div class="error-message">
            <strong>Error:</strong> <?= htmlspecialchars($errors['database']) ?>
            <?php if (strpos($errors['database'], 'foreign key constraint') !== false): ?>
                <p>Please verify all selected references (Event, Team, Captain, Location) exist in the database.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <form method="post" action="create.php">
        <div class="form-container">
            <!-- Column 1 -->
            <div>
                <div class="form-group">
                    <label for="eventID">Event*:</label>
                    <select id="eventID" name="eventID" required>
                        <option value="">-- Select Event --</option>
                        <?php foreach ($events as $id => $info): ?>
                            <option value="<?= $id ?>" <?= $id == $formData['eventID'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($info) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['eventID'])): ?>
                        <span class="error"><?= $errors['eventID'] ?></span>
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
                    <label for="captainCMN">Captain*:</label>
                    <select id="captainCMN" name="captainCMN" required>
                        <option value="">-- Select Captain --</option>
                        <?php foreach ($captains as $id => $name): ?>
                            <option value="<?= $id ?>" <?= $id == $formData['captainCMN'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['captainCMN'])): ?>
                        <span class="error"><?= $errors['captainCMN'] ?></span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Column 2 -->
            <div>
                <div class="form-group">
                    <label for="locationID">Location*:</label>
                    <select id="locationID" name="locationID" required>
                        <option value="">-- Select Location --</option>
                        <?php foreach ($locations as $id => $name): ?>
                            <option value="<?= $id ?>" <?= $id == $formData['locationID'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['locationID'])): ?>
                        <span class="error"><?= $errors['locationID'] ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="score">Score:</label>
                    <input type="number" id="score" name="score" 
                           value="<?= htmlspecialchars($formData['score']) ?>">
                    <?php if (!empty($errors['score'])): ?>
                        <span class="error"><?= $errors['score'] ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="note">Notes:</label>
                    <textarea id="note" name="note" rows="3"><?= htmlspecialchars($formData['note']) ?></textarea>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="submit-button">Create Formation</button>
            <a href="index.php" class="cancel-button">Cancel</a>
        </div>
    </form>
</main>

</body>
</html>