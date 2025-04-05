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
        cm.EnrollmentDate,
        DATEDIFF(CURDATE(), cm.EnrollmentDate) / 365 AS MembershipYears,
        (
            SELECT COUNT(DISTINCT LocationID)
            FROM MemberLocation
            WHERE CMN = cm.CMN
        ) AS LocationCount
    FROM 
        ClubMember cm
    JOIN (
        SELECT 
            CMN, 
            COUNT(DISTINCT LocationID) AS LocationCount
        FROM 
            MemberLocation
        GROUP BY 
            CMN
        HAVING 
            COUNT(DISTINCT LocationID) >= 3
    ) AS ml ON cm.CMN = ml.CMN
    WHERE 
        cm.BirthDate <= DATE_SUB(CURDATE(), INTERVAL 11 YEAR)
        AND cm.BirthDate >= DATE_SUB(CURDATE(), INTERVAL 18 YEAR)
        AND cm.EnrollmentDate >= DATE_SUB(CURDATE(), INTERVAL 3 YEAR)
    ORDER BY 
        cm.CMN ASC
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
    <title>Query 10 - Mobile Club Members | MYVC Montreal</title>
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
    <h2 class="query-title">Query 10: Mobile Club Members</h2>
    <p>Showing club members who have been associated with at least 3 locations and have been members for at most 3 years.</p>
    
    <div class="results-container">
        <?php if ($result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>CMN</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Enrollment Date</th>
                        <th>Membership Years</th>
                        <th>Locations Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['CMN']) ?></td>
                            <td><?= htmlspecialchars($row['FirstName']) ?></td>
                            <td><?= htmlspecialchars($row['LastName']) ?></td>
                            <td><?= htmlspecialchars($row['EnrollmentDate']) ?></td>
                            <td><?= number_format($row['MembershipYears'], 2) ?></td>
                            <td><?= htmlspecialchars($row['LocationCount']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-results">No club members found matching the criteria.</p>
        <?php endif; ?>
    </div>
    
    <a href="../otherQueries/index.php" class="back-link">Back to All Queries</a>
</main>

<?php
$conn->close();
?>
</body>
</html>