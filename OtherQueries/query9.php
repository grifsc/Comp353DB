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
$selectedDateTime = isset($_GET['event_datetime']) ? $_GET['event_datetime'] : date('Y-m-d\TH:i');
$results = null;

// If form is submitted, run the query
if ($selectedLocation && $selectedDateTime) {
    $datetime = $conn->real_escape_string($selectedDateTime);
    $endDateTime = date('Y-m-d H:i:s', strtotime($datetime . ' + 7 days'));

    $query = "
        SELECT 
            e.EventDateTime AS SessionStartTime,
            e.Address AS SessionAddress,
            e.SessionType,
            t.TeamName,
            tf.Score,
            CONCAT(p.FirstName, ' ', p.LastName) AS HeadCoach,
            cm.FirstName AS PlayerFirstName,
            cm.LastName AS PlayerLastName,
            pa.PlayerRole,
            CASE 
                WHEN e.EventDateTime > NOW() THEN 'Upcoming'
                ELSE 'Completed'
            END AS SessionStatus
        FROM 
            TeamFormation tf
        JOIN 
            Event e ON tf.EventID = e.EventID
        JOIN 
            ClubLocation cl ON tf.LocationID = cl.LocationID
        JOIN 
            PlayerAssignment pa ON tf.FormationID = pa.FormationID
        JOIN 
            ClubMember cm ON pa.CMN = cm.CMN
        JOIN
            Team t ON tf.TeamName = t.TeamName AND tf.LocationID = t.LocationID
        LEFT JOIN
            Personnel p ON t.Coach = CONCAT(p.FirstName, ' ', p.LastName)
        WHERE 
            tf.LocationID = ?
            AND e.EventDateTime BETWEEN ? AND ?
        ORDER BY 
            e.EventDateTime ASC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("iss", $selectedLocation, $datetime, $endDateTime);
    $stmt->execute();
    $results = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Query 9 - Team Formations by Location and Time Range | MYVC Montreal</title>
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
    <h2 class="query-title">Query 9: Team Formations by Location and Time Range</h2>
    <p>View all team formations for a specific location within 7 days from the selected date and time.</p>
    
    <div class="form-container">
        <form method="get" action="query9.php">
            <div class="form-row">
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
                <div class="form-group">
                    <label for="event_datetime">Starting Date and Time:</label>
                    <input type="datetime-local" id="event_datetime" name="event_datetime" 
                           value="<?= htmlspecialchars($selectedDateTime) ?>" required>
                </div>
            </div>
            <button type="submit">Show Team Formations</button>
        </form>
    </div>
    
    <?php if ($selectedLocation && $selectedDateTime): ?>
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
            
            $startDate = date('F j, Y g:i A', strtotime($selectedDateTime));
            $endDate = date('F j, Y g:i A', strtotime($selectedDateTime . ' + 7 days'));
            ?>
            
            <div class="time-range-info">
                <h3>Results for <?= htmlspecialchars($locationName) ?></h3>
                <p>Showing sessions between <?= $startDate ?> and <?= $endDate ?></p>
            </div>
            
            <?php if ($results && $results->num_rows > 0): ?>
                <?php 
                $currentSession = null;
                while ($row = $results->fetch_assoc()): 
                    $sessionKey = $row['SessionStartTime'] . $row['TeamName'];
                    if ($currentSession !== $sessionKey): 
                        if ($currentSession !== null): ?>
                            </tbody>
                            </table>
                        <?php endif; ?>
                        
                        <table>
                            <thead>
                                <tr class="session-header">
                                    <th colspan="4">
                                        <?= date('l, F j, Y g:i A', strtotime($row['SessionStartTime'])) ?> - 
                                        <?= htmlspecialchars($row['SessionType']) ?> at <?= htmlspecialchars($row['SessionAddress']) ?>
                                    </th>
                                </tr>
                                <tr class="<?= strtolower($row['SessionStatus']) ?>">
                                    <th>Team</th>
                                    <th>Coach</th>
                                    <th>Score</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="<?= strtolower($row['SessionStatus']) ?>">
                                    <td><?= htmlspecialchars($row['TeamName']) ?></td>
                                    <td><?= htmlspecialchars($row['HeadCoach'] ?? 'Not assigned') ?></td>
                                    <td><?= $row['SessionStatus'] === 'Upcoming' ? '--' : htmlspecialchars($row['Score'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($row['SessionStatus']) ?></td>
                                </tr>
                                <tr>
                                    <th colspan="4">Players</th>
                                </tr>
                                <tr>
                                    <th>Name</th>
                                    <th>Role</th>
                                    <th colspan="2">Status</th>
                                </tr>
                    <?php 
                        $currentSession = $sessionKey;
                    endif; ?>
                    
                    <tr>
                        <td><?= htmlspecialchars($row['PlayerFirstName'] . ' ' . $row['PlayerLastName']) ?></td>
                        <td><?= htmlspecialchars($row['PlayerRole']) ?></td>
                        <td colspan="2"><?= htmlspecialchars($row['SessionStatus']) ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
                </table>
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