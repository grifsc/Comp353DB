<?php
require_once '../database.php';

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$query = "
    SELECT 
        cm.CMN,
        cm.FirstName,
        cm.LastName,
        TIMESTAMPDIFF(YEAR, cm.BirthDate, CURDATE()) AS Age,
        cm.Telephone,
        fm.Email AS FamilyMemberEmail,
        cl.Name AS CurrentLocationName,
        COUNT(pa.FormationID) AS TotalAssignments
    FROM 
        ClubMember cm
    JOIN 
        ClubLocation cl ON cm.CurrentLocationID = cl.LocationID
    JOIN 
        PlayerAssignment pa ON cm.CMN = pa.CMN
    JOIN
        FamilyRelationship fr ON cm.CMN = fr.ChildMemberCMN
    JOIN
        FamilyMember fm ON fr.FamilyMemberID = fm.FamilyMemberID
    WHERE 
        cm.BirthDate <= DATE_SUB(CURDATE(), INTERVAL 11 YEAR)
        AND cm.BirthDate >= DATE_SUB(CURDATE(), INTERVAL 18 YEAR)
        AND NOT EXISTS (
            SELECT 1 
            FROM PlayerAssignment pa2 
            WHERE pa2.CMN = cm.CMN AND pa2.PlayerRole != 'outside hitter'
        )
        AND EXISTS (
            SELECT 1 
            FROM PlayerAssignment pa3 
            WHERE pa3.CMN = cm.CMN AND pa3.PlayerRole = 'outside hitter'
        )
    GROUP BY 
        cm.CMN, cm.FirstName, cm.LastName, cm.BirthDate, cm.Telephone, fm.Email, cl.Name
    ORDER BY 
        cl.Name ASC, cm.CMN ASC
";

$result = $conn->query($query);

if (!$result) {
    die("Query failed: " . $conn->error);
}

// Group results by location first
$groupedResults = [];
while ($row = $result->fetch_assoc()) {
    $location = $row['CurrentLocationName'];
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
    <title>Query 13 - Exclusive Outside Hitters | MYVC Montreal</title>
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
    <h2 class="query-title">Query 13: Exclusive Outside Hitters</h2>

    
    <div class="results-container">
        <?php if (!empty($groupedResults)): ?>
            <?php foreach ($groupedResults as $location => $members): ?>
                <br>
                <table>
                    <thead>
                        <tr class="location-header">
                            <th colspan="8"><?= htmlspecialchars($location) ?></th>
                        </tr>
                        <tr>
                            <th>CMN</th>
                            <th>Name</th>
                            <th>Age</th>
                            <th>Phone</th>
                            <th>Family Email</th>
                            <th>Assignments</th>
                            <th>Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($members as $member): ?>
                            <tr>
                                <td><?= htmlspecialchars($member['CMN']) ?></td>
                                <td><?= htmlspecialchars($member['FirstName'] . ' ' . $member['LastName']) ?></td>
                                <td><?= htmlspecialchars($member['Age']) ?></td>
                                <td><?= htmlspecialchars($member['Telephone'] ?? 'N/A') ?></td>
                                <td>
                                    <?php if (!empty($member['FamilyMemberEmail'])): ?>
                                        <a href="mailto:<?= htmlspecialchars($member['FamilyMemberEmail']) ?>">
                                            <?= htmlspecialchars($member['FamilyMemberEmail']) ?>
                                        </a>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($member['TotalAssignments']) ?></td>
                                <td><span class="role-badge">Outside Hitter</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="no-results">No club members found who have only been assigned as outside hitters.</p>
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