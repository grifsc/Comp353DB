<?php
require_once '../database.php';

// Check if CMN is provided
if (!isset($_GET['CMN'])) {
    header("Location: index.php");
    exit();
}

$cmn = $_GET['CMN'];

// Fetch member data
$memberQuery = "SELECT cm.*, cl.Name AS LocationName 
                FROM kqc353_4.ClubMember cm
                JOIN kqc353_4.ClubLocation cl ON cm.CurrentLocationID = cl.LocationID
                WHERE cm.CMN = ?";
$stmt = $conn->prepare($memberQuery);
$stmt->bind_param("i", $cmn);
$stmt->execute();
$memberResult = $stmt->get_result();

if ($memberResult->num_rows === 0) {
    header("Location: index.php");
    exit();
}

$member = $memberResult->fetch_assoc();
$stmt->close();

// Fetch primary family members
$primaryFamilyQuery = "SELECT fm.*, fr.RelationshipType, fr.RelationshipStartDate, fr.RelationshipEndDate
                      FROM kqc353_4.FamilyRelationship fr
                      JOIN kqc353_4.FamilyMember fm ON fr.FamilyMemberID = fm.FamilyMemberID
                      WHERE fr.ChildMemberCMN = ?";
$stmt = $conn->prepare($primaryFamilyQuery);
$stmt->bind_param("i", $cmn);
$stmt->execute();
$primaryFamily = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch secondary family members through primary relationships
$secondaryFamilyQuery = "SELECT sfm.*, psr.RelationshipStartDate, 
                        pfm.FirstName AS PrimaryFirstName, pfm.LastName AS PrimaryLastName,
                        sfm.RelationshipType AS SecondaryRelationship
                        FROM kqc353_4.PrimarySecondaryRelationship psr
                        JOIN kqc353_4.FamilyMember pfm ON psr.PrimaryFamilyMemberID = pfm.FamilyMemberID
                        JOIN kqc353_4.SecondaryFamilyMember sfm ON psr.SecondaryFamilyMemberID = sfm.SecFamilyMemberID
                        JOIN kqc353_4.FamilyRelationship fr ON pfm.FamilyMemberID = fr.FamilyMemberID
                        WHERE fr.ChildMemberCMN = ?";
$stmt = $conn->prepare($secondaryFamilyQuery);
$stmt->bind_param("i", $cmn);
$stmt->execute();
$secondaryFamily = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Club Member</title>
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
    <h2>Club Member Details (CMN: <?= htmlspecialchars($cmn) ?>)</h2>
    
    <div class="member-details">
        <h3>Member Information</h3>
        <table class="display-table">
            <tr>
                <th>Member Number</th>
                <td><?= htmlspecialchars($member['CMN']) ?></td>
            </tr>
            <tr>
                <th>Name</th>
                <td><?= htmlspecialchars($member['FirstName'] . ' ' . htmlspecialchars($member['LastName'])) ?></td>
            </tr>
            <tr>
                <th>Birth Date</th>
                <td><?= htmlspecialchars($member['BirthDate']) ?></td>
            </tr>
            <tr>
                <th>Enrollment Date</th>
                <td><?= htmlspecialchars($member['EnrollmentDate']) ?></td>
            </tr>
            <tr>
                <th>Height</th>
                <td><?= htmlspecialchars($member['Height']) ?> cm</td>
            </tr>
            <tr>
                <th>Weight</th>
                <td><?= htmlspecialchars($member['Weight']) ?> kg</td>
            </tr>
            <tr>
                <th>Current Location</th>
                <td><?= htmlspecialchars($member['LocationName']) ?></td>
            </tr>
            <tr>
                <th>Telephone</th>
                <td><?= htmlspecialchars($member['Telephone'] ?? 'N/A') ?></td>
            </tr>
            <tr>
                <th>Address</th>
                <td>
                    <?= htmlspecialchars($member['Address'] ?? 'N/A') ?><br>
                    <?= htmlspecialchars($member['City'] ?? '') ?>, 
                    <?= htmlspecialchars($member['Province'] ?? '') ?> 
                    <?= htmlspecialchars($member['PostalCode'] ?? '') ?>
                </td>
            </tr>
        </table>
    </div>

    <!-- Primary Family Members Section -->
    <?php if (!empty($primaryFamily)): ?>
    <div class="family-section">
        <h3>Primary Family Members</h3>
        <table class="display-table">
            <tr>
                <th>Name</th>
                <th>Relationship</th>
                <th>Birth Date</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Relationship Start</th>
                <th>Relationship End</th>
            </tr>
            <?php foreach ($primaryFamily as $familyMember): ?>
            <tr>
                <td><?= htmlspecialchars($familyMember['FirstName'] . ' ' . $familyMember['LastName']) ?></td>
                <td><?= htmlspecialchars($familyMember['RelationshipType']) ?></td>
                <td><?= htmlspecialchars($familyMember['BirthDate']) ?></td>
                <td><?= htmlspecialchars($familyMember['Telephone'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($familyMember['Email']) ?></td>
                <td><?= htmlspecialchars($familyMember['RelationshipStartDate']) ?></td>
                <td><?= htmlspecialchars($familyMember['RelationshipEndDate'] ?? 'Current') ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php else: ?>
        <p>No primary family members found.</p>
    <?php endif; ?>

    <!-- Secondary Family Members Section -->
    <?php if (!empty($secondaryFamily)): ?>
    <div class="family-section">
        <h3>Secondary Family Members</h3>
        <table class="display-table">
            <tr>
                <th>Name</th>
                <th>Relationship to Primary</th>
                <th>Primary Member</th>
                <th>Phone</th>
                <th>Relationship Start</th>
            </tr>
            <?php foreach ($secondaryFamily as $secondaryMember): ?>
            <tr>
                <td><?= htmlspecialchars($secondaryMember['FirstName'] . ' ' . $secondaryMember['LastName']) ?></td>
                <td><?= htmlspecialchars($secondaryMember['SecondaryRelationship']) ?></td>
                <td><?= htmlspecialchars($secondaryMember['PrimaryFirstName'] . ' ' . $secondaryMember['PrimaryLastName']) ?></td>
                <td><?= htmlspecialchars($secondaryMember['Telephone'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($secondaryMember['RelationshipStartDate']) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php else: ?>
        <p>No secondary family members found.</p>
    <?php endif; ?>

        <br>
        <a href="index.php" class="return-button">Back to List</a>
</main>

</body>
</html>