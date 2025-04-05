<?php
require_once '../database.php';

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$query = "
    SELECT 
        cl.Name AS LocationName,
        cl.Address,
        cl.City,
        cl.Province,
        cl.PostalCode,
        GROUP_CONCAT(DISTINCT p.PhoneNumber SEPARATOR ', ') AS PhoneNumbers,
        cl.WebAddress,
        cl.LocationType,
        cl.MaxCapacity,
        (
            SELECT CONCAT(pers.FirstName, ' ', pers.LastName)
            FROM Personnel pers
            JOIN PersonnelLocation pl ON pers.PersonnelID = pl.PersonnelID
            JOIN Personnel_Role pr ON pers.PersonnelID = pr.PersonnelID
            JOIN Role r ON pr.RoleID = r.RoleID AND r.RoleName = 'General Manager'
            WHERE pl.LocationID = cl.LocationID 
            AND (pl.EndDate IS NULL OR pl.EndDate >= CURDATE())
            ORDER BY pl.StartDate DESC
            LIMIT 1
        ) AS GeneralManagerName,
        COUNT(DISTINCT cm.CMN) AS NumberOfMembers
    FROM 
        ClubLocation cl
    LEFT JOIN 
        Phone p ON cl.LocationID = p.LocationID
    LEFT JOIN 
        ClubMember cm ON cl.LocationID = cm.CurrentLocationID
    GROUP BY 
        cl.LocationID, cl.Name, cl.Address, cl.City, cl.Province, cl.PostalCode, 
        cl.WebAddress, cl.LocationType, cl.MaxCapacity
    ORDER BY 
        cl.Province ASC, cl.City ASC
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
    <title>Query 7 - Location Details | MYVC Montreal</title>
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
    <h2 class="query-title">Query 7: Complete Location Details</h2>
    
    <div class="results-container">
        <?php if ($result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Location Name</th>
                        <th>Address</th>
                        <th>City</th>
                        <th>Province</th>
                        <th>Postal Code</th>
                        <th>Phone Numbers</th>
                        <th>Website</th>
                        <th>Type</th>
                        <th>Capacity</th>
                        <th>General Manager</th>
                        <th>Members</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['LocationName']) ?></td>
                            <td><?= htmlspecialchars($row['Address']) ?></td>
                            <td><?= htmlspecialchars($row['City']) ?></td>
                            <td><?= htmlspecialchars($row['Province']) ?></td>
                            <td><?= htmlspecialchars($row['PostalCode']) ?></td>
                            <td><?= htmlspecialchars($row['PhoneNumbers']) ?></td>
                            <td>
                                <?php if (!empty($row['WebAddress'])): ?>
                                    <a href="<?= htmlspecialchars($row['WebAddress']) ?>" target="_blank">Visit</a>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($row['LocationType']) ?></td>
                            <td><?= htmlspecialchars($row['MaxCapacity']) ?></td>
                            <td><?= !empty($row['GeneralManagerName']) ? htmlspecialchars($row['GeneralManagerName']) : 'Vacant' ?></td>
                            <td><?= htmlspecialchars($row['NumberOfMembers']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-results">No location records found.</p>
        <?php endif; ?>
    </div>
    
    <a href="../otherQueries/index.php" class="back-link">Back to All Queries</a>
</main>

<?php
$conn->close();
?>
</body>
</html>