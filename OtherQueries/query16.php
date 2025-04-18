<?php
require_once '../database.php';

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$query = "
    WITH GameScores AS (
        SELECT 
            e.EventID,
            MIN(tf.Score) AS MinScore,
            MAX(tf.Score) AS MaxScore
        FROM 
            Event e
        JOIN 
            TeamFormation tf ON e.EventID = tf.EventID
        WHERE 
            e.SessionType = 'Game'
        GROUP BY 
            e.EventID
        HAVING 
            COUNT(*) = 2
    ),

    LosingFormations AS (
        SELECT DISTINCT
            pa.CMN
        FROM 
            PlayerAssignment pa
        JOIN 
            TeamFormation tf ON pa.FormationID = tf.FormationID
        JOIN 
            GameScores gs ON tf.EventID = gs.EventID
        WHERE 
            tf.Score = gs.MinScore 
            AND gs.MinScore != gs.MaxScore
    )

    SELECT 
        cm.CMN,
        cm.FirstName,
        cm.LastName,
        TIMESTAMPDIFF(YEAR, cm.BirthDate, CURDATE()) AS Age,
        cm.Telephone,
        fm.Email AS FamilyMemberEmail,
        cl.Name AS CurrentLocationName,
        COUNT(DISTINCT tf.FormationID) AS GamesPlayed
    FROM 
        ClubMember cm
    JOIN 
        ClubLocation cl ON cm.CurrentLocationID = cl.LocationID
    JOIN
        FamilyRelationship fr ON cm.CMN = fr.ChildMemberCMN
    JOIN
        FamilyMember fm ON fr.FamilyMemberID = fm.FamilyMemberID
    JOIN 
        PlayerAssignment pa ON cm.CMN = pa.CMN
    JOIN 
        TeamFormation tf ON pa.FormationID = tf.FormationID
    JOIN 
        Event e ON tf.EventID = e.EventID AND e.SessionType = 'Game'
    WHERE 
        cm.BirthDate <= DATE_SUB(CURDATE(), INTERVAL 11 YEAR)
        AND cm.BirthDate >= DATE_SUB(CURDATE(), INTERVAL 18 YEAR)
        AND cm.CMN NOT IN (SELECT CMN FROM LosingFormations)
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
    <title>Query 16 - Undefeated Players | MYVC Montreal</title>
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
    <h2 class="query-title">Query 16: Undefeated Players</h2>
    
    <div class="results-container">
        <?php if (!empty($groupedResults)): ?>
            <?php foreach ($groupedResults as $location => $members): ?>
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
                            <th>Email</th>
                            <th>Games Played</th>
                            <th>Status</th>
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
                                <td><?= htmlspecialchars($member['GamesPlayed']) ?></td>
                                <td><span class="win-badge">Undefeated</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="no-results">No undefeated club members found matching the criteria.</p>
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