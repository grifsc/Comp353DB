<?php
require_once '../database.php';

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get all locations for the dropdown
$locationsQuery = "SELECT LocationID, Name FROM ClubLocation ORDER BY Name";
$locationsResult = $conn->query($locationsQuery);

// Initialize variables
$selectedLocation = isset($_GET['location_id']) ? intval($_GET['location_id']) : null;
$results = null;
$hasResults = false;

// If form is submitted, run the query
if ($selectedLocation) {
    $query = "
        SELECT DISTINCT
            fm.FirstName,
            fm.LastName,
            fm.Telephone
        FROM 
            FamilyMember fm
        JOIN 
            FamilyRelationship fr ON fm.FamilyMemberID = fr.FamilyMemberID
        JOIN 
            ClubMember cm ON fr.ChildMemberCMN = cm.CMN
        JOIN 
            TeamFormation tf ON cm.CMN = tf.CaptainCMN
        WHERE 
            tf.LocationID = ?
            AND cm.BirthDate <= DATE_SUB(CURDATE(), INTERVAL 11 YEAR)
            AND cm.BirthDate >= DATE_SUB(CURDATE(), INTERVAL 18 YEAR)
        ORDER BY 
            fm.LastName, fm.FirstName
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $selectedLocation);
    $stmt->execute();
    $results = $stmt->get_result();
    $hasResults = $results->num_rows > 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Query 15 - Captain Family Members | MYVC Montreal</title>
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
    <h2 class="query-title">Query 15: Captain Family Members</h2>
    
    <div class="form-container">
        <form method="get" action="query15.php">
            <div class="form-group">
                <label for="location_id">Select Location:</label>
                <select id="location_id" name="location_id" required>
                    <option value="">-- Select a Location --</option>
                    <?php while ($row = $locationsResult->fetch_assoc()): ?>
                        <option value="<?= $row['LocationID'] ?>" <?= $selectedLocation == $row['LocationID'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($row['Name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <button type="submit">Show Captains' Families</button>
        </form>
    </div>
    
    <?php if ($selectedLocation): ?>
        <div class="results-container">
            <?php 
            $locationName = '';
            $locationQuery = "SELECT Name FROM ClubLocation WHERE LocationID = ?";
            $stmtLoc = $conn->prepare($locationQuery);
            $stmtLoc->bind_param("i", $selectedLocation);
            $stmtLoc->execute();
            $locationResult = $stmtLoc->get_result();
            if ($locationResult->num_rows > 0) {
                $locationName = $locationResult->fetch_assoc()['Name'];
            }
            $stmtLoc->close();
            ?>
            
            <div class="location-info">
                <h3>Location: <?= htmlspecialchars($locationName) ?></h3>
            </div>
            
            <?php if ($hasResults): ?>
                <table>
                    <thead>
                        <tr>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Phone Number</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $results->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['FirstName']) ?></td>
                                <td><?= htmlspecialchars($row['LastName']) ?></td>
                                <td><?= htmlspecialchars($row['Telephone']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-results">No family members found with captain club members at this location.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <br>
    <a href="../otherQueries/index.php" class="back-link">Back to All Queries</a>
</main>

<?php
if (isset($stmt)) $stmt->close();
$conn->close();
?>
</body>
</html>