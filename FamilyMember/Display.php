<?php
require_once '../database.php';

// Get the FamilyMemberID from the URL
$familyMemberID = $_GET['FamilyMemberID'] ?? null;

if (!$familyMemberID) {
    header("Location: index.php");
    exit();
}

// Fetch primary family member details
$familyQuery = "SELECT * FROM kqc353_4.FamilyMember WHERE FamilyMemberID = ?";
$familyStmt = $conn->prepare($familyQuery);
$familyStmt->bind_param("i", $familyMemberID);
$familyStmt->execute();
$familyResult = $familyStmt->get_result();
$familyMember = $familyResult->fetch_assoc();

if (!$familyMember) {
    header("Location: index.php?error=notfound");
    exit();
}

// Fetch secondary family members associated with this primary
$secondaryQuery = "SELECT sf.* 
                  FROM kqc353_4.SecondaryFamilyMember sf
                  JOIN kqc353_4.PrimarySecondaryRelationship psr ON sf.SecFamilyMemberID = psr.SecondaryFamilyMemberID
                  WHERE psr.PrimaryFamilyMemberID = ?";
$secondaryStmt = $conn->prepare($secondaryQuery);
$secondaryStmt->bind_param("i", $familyMemberID);
$secondaryStmt->execute();
$secondaryResult = $secondaryStmt->get_result();
$secondaryMembers = $secondaryResult->fetch_all(MYSQLI_ASSOC);

// Fetch club members associated with this family member
$childrenQuery = "SELECT cm.*, fr.RelationshipType 
                 FROM kqc353_4.ClubMember cm
                 JOIN kqc353_4.FamilyRelationship fr ON cm.CMN = fr.ChildMemberCMN
                 WHERE fr.FamilyMemberID = ? AND (fr.RelationshipEndDate IS NULL OR fr.RelationshipEndDate > NOW())";
$childrenStmt = $conn->prepare($childrenQuery);
$childrenStmt->bind_param("i", $familyMemberID);
$childrenStmt->execute();
$childrenResult = $childrenStmt->get_result();
$children = $childrenResult->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Display Family Member</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .horizontal-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .horizontal-table th, .horizontal-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .horizontal-table th {
            background-color: #f2f2f2;
            width: 20%;
        }
        .details-section {
            margin-bottom: 30px;
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
    <h2>Family Member Details</h2>
    
    <div class="action-links">
        <a href="index.php" class="back-link">Back to List</a>
    </div>
    
    <!-- Primary Family Member Details - Horizontal Table -->
    <div class="details-section">
        <h3>Primary Family Member Information</h3>
        <table class="horizontal-table">
            <tr>
                <th>First Name</th>
                <td><?= htmlspecialchars($familyMember['FirstName']) ?></td>
                <th>Last Name</th>
                <td><?= htmlspecialchars($familyMember['LastName']) ?></td>
            </tr>
            <tr>
                <th>Birth Date</th>
                <td><?= htmlspecialchars($familyMember['BirthDate']) ?></td>
                <th>SIN</th>
                <td><?= htmlspecialchars($familyMember['SIN']) ?></td>
            </tr>
            <tr>
                <th>Medicare Card</th>
                <td><?= htmlspecialchars($familyMember['MedicareCardNumber']) ?></td>
                <th>Telephone</th>
                <td><?= htmlspecialchars($familyMember['Telephone'] ?? 'N/A') ?></td>
            </tr>
            <tr>
                <th>Address</th>
                <td><?= htmlspecialchars($familyMember['Address'] ?? 'N/A') ?></td>
                <th>City</th>
                <td><?= htmlspecialchars($familyMember['City'] ?? 'N/A') ?></td>
            </tr>
            <tr>
                <th>Province</th>
                <td><?= htmlspecialchars($familyMember['Province'] ?? 'N/A') ?></td>
                <th>Postal Code</th>
                <td><?= htmlspecialchars($familyMember['PostalCode'] ?? 'N/A') ?></td>
            </tr>
            <tr>
                <th>Email</th>
                <td colspan="3"><?= htmlspecialchars($familyMember['Email']) ?></td>
            </tr>
        </table>
    </div>
    
    <!-- Secondary Family Members -->
    <div class="details-section">
        <h3>Secondary Family Members</h3>
        <?php if (count($secondaryMembers) > 0): ?>
            <table class="display-table">
                <tr>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Telephone</th>
                    <th>Relationship Type</th>
                </tr>
                <?php foreach ($secondaryMembers as $secondary): ?>
                    <tr>
                        <td><?= htmlspecialchars($secondary['FirstName']) ?></td>
                        <td><?= htmlspecialchars($secondary['LastName']) ?></td>
                        <td><?= htmlspecialchars($secondary['Telephone'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($secondary['RelationshipType']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>No secondary family members found.</p>
        <?php endif; ?>
    </div>
    
    <!-- Associated Club Members (Children) -->
    <div class="details-section">
        <h3>Associated Club Members</h3>
        <?php if (count($children) > 0): ?>
            <table class="display-table">
                <tr>
                    <th>CMN</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Birth Date</th>
                    <th>Relationship</th>
                </tr>
                <?php foreach ($children as $child): ?>
                    <tr>
                        <td><?= htmlspecialchars($child['CMN']) ?></td>
                        <td><?= htmlspecialchars($child['FirstName']) ?></td>
                        <td><?= htmlspecialchars($child['LastName']) ?></td>
                        <td><?= htmlspecialchars($child['BirthDate']) ?></td>
                        <td><?= htmlspecialchars($child['RelationshipType']) ?></td>
                        <td>
                            <a href="../ClubMember/display.php?CMN=<?= $child['CMN'] ?>" class="show-link">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>No associated club members found.</p>
        <?php endif; ?>
    </div>
</main>

</body>
</html>