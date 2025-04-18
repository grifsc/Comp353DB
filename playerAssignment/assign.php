<?php
require_once '../database.php';

// Initialize variables
$formData = [
    'formationID' => '',
    'cmn' => '',
    'playerRole' => '',
    'note' => ''
];
$errors = [];

// Fetch required data for dropdowns
$events = [];
$members = [];
$roles = ['Outside Hitter', 'Setter', 'Middle Blocker', 'Opposite', 'Libero', 'Defensive Specialist'];

// Get available events with their team formations
$eventsQuery = "SELECT 
                e.EventID,
                DATE_FORMAT(e.EventDateTime, '%Y-%m-%d %H:%i') AS EventDateTime,
                e.SessionType,
                e.Address
               FROM kqc353_4.Event e
               ORDER BY e.EventDateTime DESC";
$eventsResult = $conn->query($eventsQuery);
if ($eventsResult) {
    while ($row = $eventsResult->fetch_assoc()) {
        $events[$row['EventID']] = $row;
    }
}

// Get all team formations with their event info
$formationsQuery = "SELECT 
                    tf.FormationID, 
                    tf.TeamName,
                    tf.EventID,
                    CONCAT(cm.FirstName, ' ', cm.LastName) AS CaptainName,
                    DATE_FORMAT(e.EventDateTime, '%Y-%m-%d %H:%i') AS EventDateTime,
                    e.SessionType,
                    e.Address
                   FROM kqc353_4.TeamFormation tf
                   JOIN kqc353_4.Event e ON tf.EventID = e.EventID
                   JOIN kqc353_4.ClubMember cm ON tf.CaptainCMN = cm.CMN
                   ORDER BY e.EventDateTime DESC";
$formationsResult = $conn->query($formationsQuery);
$allFormations = [];
if ($formationsResult) {
    while ($row = $formationsResult->fetch_assoc()) {
        $allFormations[$row['FormationID']] = $row;
    }
}

// Group formations by event for the dropdown
$formationsByEvent = [];
foreach ($allFormations as $formationID => $formation) {
    $eventID = $formation['EventID'];
    if (!isset($formationsByEvent[$eventID])) {
        $formationsByEvent[$eventID] = [];
    }
    $formationsByEvent[$eventID][] = $formation;
}

// Get available club members
$membersQuery = "SELECT CMN, CONCAT(FirstName, ' ', LastName) AS FullName FROM kqc353_4.ClubMember";
$membersResult = $conn->query($membersQuery);
if ($membersResult) {
    while ($row = $membersResult->fetch_assoc()) {
        $members[$row['CMN']] = $row['FullName'];
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize all inputs
    foreach ($_POST as $key => $value) {
        $formData[$key] = trim($value);
    }

    // Validate required fields
    $requiredFields = ['formationID', 'cmn', 'playerRole'];
    
    foreach ($requiredFields as $field) {
        if (empty($formData[$field])) {
            $errors[$field] = 'This field is required';
        }
    }

    // Validate player role
    if (empty($errors['playerRole']) && !in_array($formData['playerRole'], $roles)) {
        $errors['playerRole'] = "Invalid player role selected";
    }

    // Verify foreign keys exist
    if (empty($errors)) {
        // Check Formation exists
        $checkFormation = $conn->prepare("SELECT COUNT(*) AS count FROM kqc353_4.TeamFormation WHERE FormationID = ?");
        $checkFormation->bind_param("i", $formData['formationID']);
        $checkFormation->execute();
        $formationResult = $checkFormation->get_result()->fetch_assoc();
        if ($formationResult['count'] == 0) {
            $errors['formationID'] = "Selected formation does not exist";
        }
        $checkFormation->close();

        // Check Member exists
        $checkMember = $conn->prepare("SELECT COUNT(*) AS count FROM kqc353_4.ClubMember WHERE CMN = ?");
        $checkMember->bind_param("i", $formData['cmn']);
        $checkMember->execute();
        $memberResult = $checkMember->get_result()->fetch_assoc();
        if ($memberResult['count'] == 0) {
            $errors['cmn'] = "Selected member does not exist";
        }
        $checkMember->close();
    }

    // If no errors, insert into database
    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            $query = "INSERT INTO kqc353_4.PlayerAssignment (
                FormationID, CMN, PlayerRole, Note
            ) VALUES (?, ?, ?, ?)";
            
            $stmt = $conn->prepare($query);
            
            if ($stmt) {
                $stmt->bind_param(
                    "iiss",
                    $formData['formationID'],
                    $formData['cmn'],
                    $formData['playerRole'],
                    $formData['note']
                );
                
                if ($stmt->execute()) {
                    $conn->commit();
                    header("Location: index.php?success=1");
                    exit();
                } else {
                    // Check for duplicate entry error
                    if ($stmt->errno == 1062) {
                        $errors['database'] = "This player is already assigned to this formation with the same role";
                    } else {
                        $errors['database'] = "Database error: " . $stmt->error;
                    }
                }
                
                $stmt->close();
            } else {
                $errors['database'] = "Database error: " . $conn->error;
            }
        } catch (Exception $e) {
            $conn->rollback();
            $errors['database'] = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Player</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .form-container {
            display: flex;
            gap: 20px;
        }
        .form-column {
            flex: 1;
        }
        .form-group {
            margin-bottom: 15px;
        }
        select, textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .formation-option {
            display: flex;
            justify-content: space-between;
        }
        .formation-team {
            font-weight: bold;
        }
        .formation-details {
            color: #666;
            font-size: 0.9em;
        }
        .error-message {
            color: #d9534f;
            background-color: #f2dede;
            border: 1px solid #ebccd1;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
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
        <li><a href="index.php">Player Assignments</a></li>
        <li><a href="../otherQueries/index.php">Other Queries</a></li>
    </ul>
</nav>

<main>
    <h2>Assign Player to Formation</h2>
    
    <?php if (!empty($errors['database'])): ?>
        <div class="error-message">
            <strong>Error:</strong> <?= htmlspecialchars($errors['database']) ?>
        </div>
    <?php endif; ?>
    
    <form method="post" action="assign.php">
        <div class="form-container">
            <div class="form-column">
                <div class="form-group">
                    <label for="formationID">Team Formation*:</label>
                    <select id="formationID" name="formationID" required>
                        <option value="">-- Select a Team Formation --</option>
                        <?php foreach ($formationsByEvent as $eventID => $formations): ?>
                            <?php $event = $events[$eventID]; ?>
                            <optgroup label="<?= htmlspecialchars($event['EventDateTime'] . ' - ' . $event['SessionType'] . ' at ' . $event['Address']) ?>">
                                <?php foreach ($formations as $formation): ?>
                                    <option value="<?= $formation['FormationID'] ?>" <?= $formation['FormationID'] == $formData['formationID'] ? 'selected' : '' ?>>
                                        <span class="formation-option">
                                            <span class="formation-team"><?= htmlspecialchars($formation['TeamName']) ?></span>
                                            <span class="formation-details">
                                                (Captain: <?= htmlspecialchars($formation['CaptainName']) ?>)
                                            </span>
                                        </span>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['formationID'])): ?>
                        <span class="error"><?= $errors['formationID'] ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="cmn">Club Member*:</label>
                    <select id="cmn" name="cmn" required>
                        <option value="">-- Select Member --</option>
                        <?php foreach ($members as $id => $name): ?>
                            <option value="<?= $id ?>" <?= $id == $formData['cmn'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['cmn'])): ?>
                        <span class="error"><?= $errors['cmn'] ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-column">
                <div class="form-group">
                    <label for="playerRole">Player Role*:</label>
                    <select id="playerRole" name="playerRole" required>
                        <option value="">-- Select Role --</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= htmlspecialchars($role) ?>" <?= $role == $formData['playerRole'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($role) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['playerRole'])): ?>
                        <span class="error"><?= $errors['playerRole'] ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="note">Notes:</label>
                    <textarea id="note" name="note" rows="5"><?= htmlspecialchars($formData['note']) ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="submit-button">Assign Player</button>
                    <a href="index.php" class="cancel-button">Cancel</a>
                </div>
            </div>
        </div>
    </form>
</main>

</body>
</html>