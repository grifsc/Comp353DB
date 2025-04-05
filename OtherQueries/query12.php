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
        cm.EnrollmentDate,
        cm.Telephone,
        fm.Email AS FamilyMemberEmail,
        cl.Name AS CurrentLocationName
    FROM 
        (SELECT * FROM ClubMember 
         WHERE BirthDate <= DATE_SUB(CURDATE(), INTERVAL 11 YEAR)
         AND BirthDate >= DATE_SUB(CURDATE(), INTERVAL 18 YEAR)
         AND CMN NOT IN (SELECT DISTINCT CMN FROM PlayerAssignment)
        ) cm
    JOIN 
        ClubLocation cl ON cm.CurrentLocationID = cl.LocationID
    LEFT JOIN
        FamilyRelationship fr ON cm.CMN = fr.ChildMemberCMN
    LEFT JOIN
        FamilyMember fm ON fr.FamilyMemberID = fm.FamilyMemberID
    ORDER BY 
        cl.Name ASC, cm.CMN ASC
";

$result = $conn->query($query);

if (!$result) {
    die("Query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Query 12 - Unassigned Club Members | MYVC Montreal</title>
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
    <h2 class="query-title">Query 12: Unassigned Club Members</h2>
    
    <div class="results-container">
        <?php if ($result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Location</th>
                        <th>CMN</th>
                        <th>Name</th>
                        <th>Age</th>
                        <th>Joined</th>
                        <th>Phone</th>
                        <th>Family Email</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $currentLocation = null;
                    while ($row = $result->fetch_assoc()): 
                        if ($currentLocation !== $row['CurrentLocationName']):
                            $currentLocation = $row['CurrentLocationName'];
                    ?>
                            <tr class="highlight">
                                <td colspan="7"><?= htmlspecialchars($currentLocation) ?></td>
                            </tr>
                        <?php endif; ?>
                        <tr>
                            <td><?= htmlspecialchars($row['CurrentLocationName']) ?></td>
                            <td><?= htmlspecialchars($row['CMN']) ?></td>
                            <td><?= htmlspecialchars($row['FirstName'] . ' ' . $row['LastName']) ?></td>
                            <td><?= htmlspecialchars($row['Age']) ?></td>
                            <td><?= date('M j, Y', strtotime($row['EnrollmentDate'])) ?></td>
                            <td><?= htmlspecialchars($row['Telephone'] ?? 'N/A') ?></td>
                            <td>
                                <?php if (!empty($row['FamilyMemberEmail'])): ?>
                                    <a href="mailto:<?= htmlspecialchars($row['FamilyMemberEmail']) ?>">
                                        <?= htmlspecialchars($row['FamilyMemberEmail']) ?>
                                    </a>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-results">No unassigned club members found matching the criteria.</p>
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