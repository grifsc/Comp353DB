<?php
require_once '../database.php';

if (!isset($_GET['PersonnelID'])) {
    header("Location: index.php");
    exit();
}

$personnelID = $_GET['PersonnelID'];

// Get the personnel details
$personnelQuery = "SELECT * FROM kqc353_4.Personnel WHERE PersonnelID = ?";
$personnelStmt = $conn->prepare($personnelQuery);

if ($personnelStmt === false) {
    die("Error preparing personnel query: " . $conn->error);
}

$personnelStmt->bind_param("i", $personnelID);
if (!$personnelStmt->execute()) {
    die("Error executing personnel query: " . $personnelStmt->error);
}

$personnel = $personnelStmt->get_result()->fetch_assoc();
$personnelStmt->close();

if (!$personnel) {
    header("Location: index.php?error=personnel_not_found");
    exit();
}

// Get current location assignment
$locationQuery = "SELECT 
                    cl.LocationID, 
                    cl.Name AS LocationName,
                    cl.LocationType,
                    pl.StartDate,
                    pl.EndDate
                 FROM kqc353_4.PersonnelLocation pl
                 JOIN kqc353_4.ClubLocation cl ON pl.LocationID = cl.LocationID
                 WHERE pl.PersonnelID = ?
                 AND (pl.EndDate IS NULL OR pl.EndDate >= CURDATE())
                 ORDER BY pl.StartDate DESC
                 LIMIT 1";
$locationStmt = $conn->prepare($locationQuery);

if ($locationStmt === false) {
    die("Error preparing location query: " . $conn->error);
}

$locationStmt->bind_param("i", $personnelID);
if (!$locationStmt->execute()) {
    die("Error executing location query: " . $locationStmt->error);
}

$currentLocation = $locationStmt->get_result()->fetch_assoc();
$locationStmt->close();

// Get all location assignments history
$locationsHistoryQuery = "SELECT 
                            cl.LocationID, 
                            cl.Name AS LocationName,
                            cl.LocationType,
                            pl.StartDate,
                            pl.EndDate
                         FROM kqc353_4.PersonnelLocation pl
                         JOIN kqc353_4.ClubLocation cl ON pl.LocationID = cl.LocationID
                         WHERE pl.PersonnelID = ?
                         ORDER BY pl.StartDate DESC";
$locationsHistoryStmt = $conn->prepare($locationsHistoryQuery);

if ($locationsHistoryStmt === false) {
    die("Error preparing locations history query: " . $conn->error);
}

$locationsHistoryStmt->bind_param("i", $personnelID);
if (!$locationsHistoryStmt->execute()) {
    die("Error executing locations history query: " . $locationsHistoryStmt->error);
}

$locationsHistory = $locationsHistoryStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$locationsHistoryStmt->close();

// Get personnel roles
$rolesQuery = "SELECT 
                r.RoleName,
                pr.Mandate,
                pr.StartDate,
                pr.EndDate
              FROM kqc353_4.Personnel_Role pr
              JOIN kqc353_4.Role r ON pr.RoleID = r.RoleID
              WHERE pr.PersonnelID = ?
              ORDER BY pr.StartDate DESC";
$rolesStmt = $conn->prepare($rolesQuery);

if ($rolesStmt === false) {
    die("Error preparing roles query: " . $conn->error);
}

$rolesStmt->bind_param("i", $personnelID);
if (!$rolesStmt->execute()) {
    die("Error executing roles query: " . $rolesStmt->error);
}

$roles = $rolesStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$rolesStmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personnel Details</title>
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
        <li><a href="../otherQueries/index.php">Other Queries</a></li>
    </ul>
</nav>

<main>
    <h2>Personnel Details</h2>
    
    <div class="detail-section">
        <div class="detail-header">
            <h3>Basic Information</h3>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">Personnel ID:</div>
            <div class="detail-value"><?= htmlspecialchars($personnel['PersonnelID']) ?></div>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">Name:</div>
            <div class="detail-value"><?= htmlspecialchars($personnel['FirstName'] . ' ' . $personnel['LastName']) ?></div>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">Birth Date:</div>
            <div class="detail-value"><?= htmlspecialchars($personnel['BirthDate']) ?></div>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">SIN:</div>
            <div class="detail-value"><?= htmlspecialchars($personnel['SIN']) ?></div>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">Medicare Card Number:</div>
            <div class="detail-value"><?= htmlspecialchars($personnel['MedicareCardNumber']) ?></div>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">Contact Information:</div>
            <div class="detail-value">
                <p>Phone: <?= htmlspecialchars($personnel['Telephone']) ?></p>
                <p>Email: <?= htmlspecialchars($personnel['Email']) ?></p>
                <p>Address: <?= htmlspecialchars($personnel['Address'] . ', ' . $personnel['City'] . ', ' . $personnel['Province'] . ' ' . $personnel['PostalCode']) ?></p>
            </div>
        </div>
    </div>
    
    <!-- Current Location Section -->
    <div class="detail-section">
        <div class="detail-header">
            <h3>Current Location Assignment</h3>
        </div>
        
        <?php if ($currentLocation): ?>
            <div class="detail-row">
                <div class="detail-label">Location:</div>
                <div class="detail-value">
                    <?= htmlspecialchars($currentLocation['LocationName']) ?> (<?= htmlspecialchars($currentLocation['LocationType']) ?>)
                </div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Location ID:</div>
                <div class="detail-value">
                    <a href="../ClubLocation/display.php?LocationID=<?= $currentLocation['LocationID'] ?>">
                        <?= htmlspecialchars($currentLocation['LocationID']) ?>
                    </a>
                </div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Assignment Period:</div>
                <div class="detail-value">
                    <?= htmlspecialchars($currentLocation['StartDate']) ?> to 
                    <?= $currentLocation['EndDate'] ? htmlspecialchars($currentLocation['EndDate']) : 'Present' ?>
                </div>
            </div>
        <?php else: ?>
            <p>No current location assignment found.</p>
        <?php endif; ?>
    </div>
    
    <!-- Location History Section -->
    <div class="detail-section">
        <div class="detail-header">
            <h3>Location Assignment History</h3>
        </div>
        
        <?php if (count($locationsHistory) > 0): ?>
            <table class="sub-table">
                <tr>
                    <th>Location</th>
                    <th>Type</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                </tr>
                <?php foreach ($locationsHistory as $location): ?>
                    <tr class="<?= ($location['EndDate'] === null || $location['EndDate'] >= date('Y-m-d')) ? 'current' : 'past' ?>">
                        <td>
                            <a href="../ClubLocation/display.php?LocationID=<?= $location['LocationID'] ?>">
                                <?= htmlspecialchars($location['LocationName']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($location['LocationType']) ?></td>
                        <td><?= htmlspecialchars($location['StartDate']) ?></td>
                        <td><?= $location['EndDate'] ? htmlspecialchars($location['EndDate']) : 'Present' ?></td>
                        <td><?= ($location['EndDate'] === null || $location['EndDate'] >= date('Y-m-d')) ? 'Current' : 'Past' ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>No location assignment history found.</p>
        <?php endif; ?>
    </div>
    
    <!-- Roles Section -->
    <div class="detail-section">
        <div class="detail-header">
            <h3>Roles</h3>
        </div>
        
        <?php if (count($roles) > 0): ?>
            <table class="sub-table">
                <tr>
                    <th>Role</th>
                    <th>Mandate</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                </tr>
                <?php foreach ($roles as $role): ?>
                    <tr class="<?= ($role['EndDate'] === null || $role['EndDate'] >= date('Y-m-d')) ? 'current' : 'past' ?>">
                        <td><?= htmlspecialchars($role['RoleName']) ?></td>
                        <td><?= htmlspecialchars($role['Mandate']) ?></td>
                        <td><?= htmlspecialchars($role['StartDate']) ?></td>
                        <td><?= $role['EndDate'] ? htmlspecialchars($role['EndDate']) : 'Present' ?></td>
                        <td><?= ($role['EndDate'] === null || $role['EndDate'] >= date('Y-m-d')) ? 'Current' : 'Past' ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>No roles assigned.</p>
        <?php endif; ?>
    </div>
    
    <div class="form-actions">
        <a href="index.php" class="return-button">Back to Personnel</a>
    </div>
</main>

</body>
</html>