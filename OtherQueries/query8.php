<?php
require_once '../database.php';

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get all family members for the dropdown
$familyMembersQuery = "SELECT FamilyMemberID, FirstName, LastName FROM FamilyMember ORDER BY LastName, FirstName";
$familyMembersResult = $conn->query($familyMembersQuery);

// Initialize variables
$selectedFamilyMember = isset($_GET['family_member_id']) ? intval($_GET['family_member_id']) : null;
$results = null;

// If a family member is selected, run the main query
if ($selectedFamilyMember) {
    $query = "
        SELECT 
            fm.FirstName AS FamilyMemberFirstName,
            fm.LastName AS FamilyMemberLastName,
            cl.Name AS LocationName,
            sfm.FirstName AS SecondaryFirstName,
            sfm.LastName AS SecondaryLastName,
            sfm.Telephone AS SecondaryPhone,
            cm.CMN,
            cm.FirstName AS MemberFirstName,
            cm.LastName AS MemberLastName,
            cm.BirthDate,
            cm.SIN,
            cm.MedicareCardNumber,
            cm.Telephone,
            cm.Address,
            cm.City,
            cm.Province,
            cm.PostalCode,
            fr.RelationshipType AS RelationshipWithSecondary
        FROM 
            FamilyMember fm
        JOIN 
            FamilyRelationship fr ON fm.FamilyMemberID = fr.FamilyMemberID
        JOIN 
            ClubMember cm ON fr.ChildMemberCMN = cm.CMN
        LEFT JOIN 
            MemberLocation ml ON cm.CMN = ml.CMN
        LEFT JOIN 
            ClubLocation cl ON ml.LocationID = cl.LocationID
        LEFT JOIN 
            PrimarySecondaryRelationship psr ON fm.FamilyMemberID = psr.PrimaryFamilyMemberID
        LEFT JOIN 
            SecondaryFamilyMember sfm ON psr.SecondaryFamilyMemberID = sfm.SecFamilyMemberID
        WHERE 
            fm.FamilyMemberID = ?
        ORDER BY 
            cl.Name, cm.LastName, cm.FirstName
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $selectedFamilyMember);
    $stmt->execute();
    $results = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Query 8 - Family Member Associations | MYVC Montreal</title>
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
    <h2 class="query-title">Query 8: Family Member Associations</h2>
    
    <div class="form-container">
        <form method="get" action="query8.php">
            <div class="form-group">
                <label for="family_member_id">Select Family Member:</label>
                <select id="family_member_id" name="family_member_id" required>
                    <option value="">-- Select a Family Member --</option>
                    <?php while ($row = $familyMembersResult->fetch_assoc()): ?>
                        <option value="<?= $row['FamilyMemberID'] ?>" <?= $selectedFamilyMember == $row['FamilyMemberID'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($row['LastName'] . ', ' . $row['FirstName']) ?> (ID: <?= $row['FamilyMemberID'] ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <button type="submit">Show Associations</button>
        </form>
    </div>
    
    <?php if ($selectedFamilyMember): ?>
        <div class="results-container">
            <?php if ($results && $results->num_rows > 0): ?>
                <h3>Associations for <?= htmlspecialchars($results->fetch_assoc()['FamilyMemberFirstName'] . ' ' . $results->fetch_assoc()['FamilyMemberLastName']) ?></h3>
                <?php $results->data_seek(0); // Reset pointer ?>
                
                <table>
                    <thead>
                        <tr>
                            <th>Location</th>
                            <th>Secondary Family Member</th>
                            <th>Secondary Phone</th>
                            <th>Club Member</th>
                            <th>CMN</th>
                            <th>Birth Date</th>
                            <th>Address</th>
                            <th>Relationship</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $results->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['LocationName'] ?? 'N/A') ?></td>
                                <td>
                                    <?= !empty($row['SecondaryFirstName']) ? 
                                        htmlspecialchars($row['SecondaryFirstName'] . ' ' . $row['SecondaryLastName']) : 'N/A' ?>
                                </td>
                                <td><?= htmlspecialchars($row['SecondaryPhone'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($row['MemberFirstName'] . ' ' . $row['MemberLastName']) ?></td>
                                <td><?= htmlspecialchars($row['CMN']) ?></td>
                                <td><?= htmlspecialchars($row['BirthDate']) ?></td>
                                <td>
                                    <?= htmlspecialchars(
                                        $row['Address'] . ', ' . 
                                        $row['City'] . ', ' . 
                                        $row['Province'] . ' ' . 
                                        $row['PostalCode']
                                    ) ?>
                                </td>
                                <td><?= htmlspecialchars($row['RelationshipWithSecondary'] ?? 'N/A') ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-results">No associations found for the selected family member.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <a href="../otherQueries/index.php" class="back-link">Back to All Queries</a>
</main>

<?php
if (isset($stmt)) $stmt->close();
$conn->close();
?>
</body>
</html>