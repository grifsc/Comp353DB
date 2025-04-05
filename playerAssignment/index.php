<?php 
require_once '../database.php';

// Query to get all player assignments with related information
$query = 'SELECT 
            pa.FormationID, 
            pa.CMN, 
            pa.PlayerRole, 
            pa.Note,
            CONCAT(cm.FirstName, " ", cm.LastName) AS MemberName,
            tf.TeamName,
            e.EventDateTime,
            e.SessionType
          FROM kqc353_4.PlayerAssignment pa
          JOIN kqc353_4.ClubMember cm ON pa.CMN = cm.CMN
          JOIN kqc353_4.TeamFormation tf ON pa.FormationID = tf.FormationID
          JOIN kqc353_4.Event e ON tf.EventID = e.EventID
          ORDER BY pa.FormationID, pa.CMN';

$statement = $conn->query($query);

// Check if query succeeded
if ($statement === false) {
    die("Query failed: " . $conn->error);
}

// Check if there are any results
$hasResults = false;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Player Assignments</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .index-table tr.even-formation {
            /* light gray for even FormationID */
            background-color: #f2f2f2;  
        }
        
        .index-table tr.odd-formation {
            /* white for odd FormationID */
            background-color: #ffffff;  
        }
        
        .index-table tr:hover {
            /* light blue highlight */
            background-color: #e6f7ff;  
        }
    </style>
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
    </ul>
</nav>

<main>
    <h2>Player Assignments</h2>
    <h3><a class="show-link" href="assign.php">Assign Player</a></h3>
    
    <div>
        <table class="index-table">
            <tr>
                <th>Formation ID</th>
                <th>Team Name</th>
                <th>Event Date/Time</th>
                <th>Session Type</th>
                <th>Member (CMN)</th>
                <th>Player Name</th>
                <th>Player Role</th>
                <th>Note</th>
            </tr>
            <?php 
            if ($statement->num_rows > 0) {
                while($row = $statement->fetch_assoc()) {
                    $hasResults = true;
                    // Determine if FormationID is even or odd
                    $rowClass = ($row['FormationID'] % 2 == 0) ? 'even-formation' : 'odd-formation';
            ?>
                    <tr class="<?= $rowClass ?>">
                        <td><?= htmlspecialchars($row['FormationID']) ?></td>
                        <td><?= htmlspecialchars($row['TeamName']) ?></td>
                        <td><?= htmlspecialchars(date('M j, Y H:i', strtotime($row['EventDateTime']))) ?></td>
                        <td><?= htmlspecialchars($row['SessionType']) ?></td>
                        <td><?= htmlspecialchars($row['CMN']) ?></td>
                        <td><?= htmlspecialchars($row['MemberName']) ?></td>
                        <td><?= htmlspecialchars($row['PlayerRole']) ?></td>
                        <td><?= htmlspecialchars($row['Note']) ?></td>
                        <td>
                            <a class="edit-link" href="edit.php?FormationID=<?= $row['FormationID'] ?>&CMN=<?= $row['CMN'] ?>&PlayerRole=<?= urlencode($row['PlayerRole']) ?>">Edit</a>
                            <a class="delete-link" href="delete.php?FormationID=<?= $row['FormationID'] ?>&CMN=<?= $row['CMN'] ?>&PlayerRole=<?= urlencode($row['PlayerRole']) ?>">Delete</a>
                        </td>
                    </tr>
            <?php 
                }
            } 
            
            if (!$hasResults) {
                echo '<tr><td colspan="9">No player assignments found</td></tr>';
            }
            ?>
        </table>
    </div>
</main>

</body>
</html>