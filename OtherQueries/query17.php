<?php
require_once '../database.php';

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$query = "
    SELECT 
        p.FirstName,
        p.LastName,
        pr.StartDate AS StartDateAsTreasurer,
        pr.EndDate AS EndDateAsTreasurer,
        CASE 
            WHEN pr.EndDate IS NULL THEN 'Current Treasurer'
            ELSE 'Former Treasurer'
        END AS Status
    FROM 
        Personnel p
    JOIN 
        Personnel_Role pr ON p.PersonnelID = pr.PersonnelID
    JOIN 
        Role r ON pr.RoleID = r.RoleID
    WHERE 
        r.RoleName = 'Treasurer'
    ORDER BY 
        p.FirstName ASC,
        p.LastName ASC,
        pr.StartDate ASC
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
    <title>Query 17 - Club Treasurers | MYVC Montreal</title>
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
    <h2 class="query-title">Query 17: Club Treasurers</h2>
    
    <div class="results-container">
        <?php if ($result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr class="<?= $row['Status'] === 'Current Treasurer' ? 'current-treasurer' : '' ?>">
                            <td><?= htmlspecialchars($row['FirstName']) ?></td>
                            <td><?= htmlspecialchars($row['LastName']) ?></td>
                            <td><?= date('M j, Y', strtotime($row['StartDateAsTreasurer'])) ?></td>
                            <td>
                                <?= $row['EndDateAsTreasurer'] ? date('M j, Y', strtotime($row['EndDateAsTreasurer'])) : 'Present' ?>
                            </td>
                            <td>
                                <span class="status-badge <?= $row['Status'] === 'Current Treasurer' ? 'current' : 'former' ?>">
                                    <?= htmlspecialchars($row['Status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-results">No treasurer records found.</p>
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