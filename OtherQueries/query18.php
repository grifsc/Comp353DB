<?php
require_once '../database.php';

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$query = "
    SELECT 
        cm.FirstName,
        cm.LastName,
        cm.Telephone,
        fm.Email,
        DATE_ADD(cm.BirthDate, INTERVAL 18 YEAR) AS DeactivationDate,
        cl.Name AS LastLocationName,
        (
            SELECT pa.PlayerRole 
            FROM PlayerAssignment pa
            JOIN TeamFormation tf ON pa.FormationID = tf.FormationID
            JOIN Event e ON tf.EventID = e.EventID
            WHERE pa.CMN = cm.CMN
            ORDER BY e.EventDateTime DESC
            LIMIT 1
        ) AS LastRole
    FROM 
        ClubMember cm
    JOIN 
        ClubLocation cl ON cm.CurrentLocationID = cl.LocationID
    JOIN
        FamilyRelationship fr ON cm.CMN = fr.ChildMemberCMN
    JOIN
        FamilyMember fm ON fr.FamilyMemberID = fm.FamilyMemberID
    WHERE 
        cm.BirthDate <= DATE_SUB(CURDATE(), INTERVAL 18 YEAR)
        AND fr.RelationshipEndDate IS NULL  -- Only current family relationships
    ORDER BY 
        cl.Name ASC,
        LastRole ASC,
        cm.FirstName ASC,
        cm.LastName ASC
";

$result = $conn->query($query);

if (!$result) {
    die("Query failed: " . $conn->error);
}

// Group results by location first
$groupedResults = [];
while ($row = $result->fetch_assoc()) {
    $location = $row['LastLocationName'];
    if (!isset($groupedResults[$location])) {
        $groupedResults[$location] = [];
    }
    $groupedResults[$location][] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Query 18 - Aged Out Members | MYVC Montreal</title>
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
    <h2 class="query-title">Query 18: Aged Out Members</h2>
    
    <div class="results-container">
        <?php if (!empty($groupedResults)): ?>
            <?php foreach ($groupedResults as $location => $members): ?>
                <br>
                <table>
                    <thead>
                        <tr class="location-header">
                            <th colspan="7"><?= htmlspecialchars($location) ?></th>
                        </tr>
                        <tr>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Deactivated On</th>
                            <th>Last Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($members as $member): ?>
                            <tr>
                                <td><?= htmlspecialchars($member['FirstName'] . ' ' . $member['LastName']) ?></td>
                                <td><?= htmlspecialchars($member['Telephone'] ?? 'N/A') ?></td>
                                <td>
                                    <?php if (!empty($member['Email'])): ?>
                                        <a href="mailto:<?= htmlspecialchars($member['Email']) ?>">
                                            <?= htmlspecialchars($member['Email']) ?>
                                        </a>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td class="deactivated"><?= date('M j, Y', strtotime($member['DeactivationDate'])) ?></td>
                                <td>
                                    <?php if (!empty($member['LastRole'])): ?>
                                        <span class="role-badge"><?= htmlspecialchars($member['LastRole']) ?></span>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="no-results">No aged out club members found in the system.</p>
        <?php endif; ?>
    </div>
    <br>
    <a href="../otherQueries/index.php" class="back-link">Back to All Queries</a>
</main>

<?php
$conn->close();
?>
</body>
</html>