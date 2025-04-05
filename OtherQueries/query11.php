<?php
require_once '../database.php';

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$startDateTime = isset($_GET['start_datetime']) ? $_GET['start_datetime'] : date('Y-m-01\T00:00');
$endDateTime = isset($_GET['end_datetime']) ? $_GET['end_datetime'] : date('Y-m-t\T23:59');
$results = null;
$hasResults = false;

// If form is submitted, run the query
if (isset($_GET['start_datetime']) && isset($_GET['end_datetime'])) {
    $start = $conn->real_escape_string($startDateTime);
    $end = $conn->real_escape_string($endDateTime);

    $query = "
        SELECT 
            cl.Name AS LocationName,
            SUM(CASE WHEN e.SessionType = 'Training' THEN 1 ELSE 0 END) AS TotalTrainingSessions,
            COUNT(DISTINCT CASE WHEN e.SessionType = 'Training' THEN pa.CMN ELSE NULL END) AS TotalPlayersInTraining,
            SUM(CASE WHEN e.SessionType = 'Game' THEN 1 ELSE 0 END) AS TotalGameSessions,
            COUNT(DISTINCT CASE WHEN e.SessionType = 'Game' THEN pa.CMN ELSE NULL END) AS TotalPlayersInGames
        FROM 
            ClubLocation cl
        JOIN 
            TeamFormation tf ON cl.LocationID = tf.LocationID
        JOIN 
            Event e ON tf.EventID = e.EventID
        JOIN 
            PlayerAssignment pa ON tf.FormationID = pa.FormationID
        WHERE 
            e.EventDateTime BETWEEN ? AND ?
        GROUP BY 
            cl.LocationID, cl.Name
        HAVING 
            SUM(CASE WHEN e.SessionType = 'Game' THEN 1 ELSE 0 END) >= 2  
        ORDER BY 
            SUM(CASE WHEN e.SessionType = 'Game' THEN 1 ELSE 0 END) DESC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $start, $end);
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
    <title>Query 11 - Team Formation Report | MYVC Montreal</title>
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
    <h2 class="query-title">Query 11: Team Formation Report</h2>
    <p>Generate a report of team formations for all locations during a specific time period.</p>
    
    <div class="form-container">
        <form method="get" action="query11.php">
            <div class="form-row">
                <div class="form-group">
                    <label for="start_datetime">Start Date and Time:</label>
                    <input type="datetime-local" id="start_datetime" name="start_datetime" 
                           value="<?= htmlspecialchars($startDateTime) ?>" required>
                </div>
                <div class="form-group">
                    <label for="end_datetime">End Date and Time:</label>
                    <input type="datetime-local" id="end_datetime" name="end_datetime" 
                           value="<?= htmlspecialchars($endDateTime) ?>" required>
                </div>
            </div>
            <button type="submit">Generate Report</button>
        </form>
    </div>
    
    <?php if (isset($_GET['start_datetime']) && isset($_GET['end_datetime'])): ?>
        <div class="results-container">
            <div class="time-range-info">
                <h3>Report Period: <?= date('F j, Y g:i A', strtotime($startDateTime)) ?> to <?= date('F j, Y g:i A', strtotime($endDateTime)) ?></h3>
                <p>Showing only locations with at least 2 game sessions during this period.</p>
            </div>
            
            <?php if ($hasResults): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Location</th>
                            <th>Training Sessions</th>
                            <th>Players in Training</th>
                            <th>Game Sessions</th>
                            <th>Players in Games</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $results->fetch_assoc()): ?>
                            <tr>
                                <td class="highlight"><?= htmlspecialchars($row['LocationName']) ?></td>
                                <td><?= htmlspecialchars($row['TotalTrainingSessions']) ?></td>
                                <td><?= htmlspecialchars($row['TotalPlayersInTraining']) ?></td>
                                <td class="highlight"><?= htmlspecialchars($row['TotalGameSessions']) ?></td>
                                <td><?= htmlspecialchars($row['TotalPlayersInGames']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-results">No locations found with at least 2 game sessions during the selected time period.</p>
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