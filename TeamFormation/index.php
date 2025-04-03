<?php 
require_once '../database.php';
//FormationID, EventID, TeamName, CaptainCMN, LocationID, Score, Note //missing team ID check schema
$query = 'SELECT FormationID, EventID, TeamName, CaptainCMN, LocationID, Score, Note FROM kqc353_4.TeamFormation';
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
    <title>Team Formation</title>
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
    </ul>
</nav>

<main>
    <h2>Team Formation</h2>
    <h3><a class="show-link" href ="create.php">Create Team Formation</a></h3>
    

    <div>
        <table class="index-table">
            <tr>
                <th>FormationID</th>
                <th>EventID</th>
                <th>TeamName</th>
                <th>CaptainCMN</th>
                <th>LocationID</th>
                <th>Score</th>
                <th>Note</th>
            </tr>
            <?php 
            if ($statement->num_rows > 0) {
                while($row = $statement->fetch_assoc()) {
                    $hasResults = true;
            ?>
                    <tr>
                        <td><?= htmlspecialchars($row['FormationID']) ?></td>
                        <td><?= htmlspecialchars($row['EventID']) ?></td>
                        <td><?= htmlspecialchars($row['TeamName']) ?></td>
                        <td><?= htmlspecialchars($row['CaptainCMN']) ?></td>
                        <td><?= htmlspecialchars($row['LocationID']) ?></td>
                        <td><?= htmlspecialchars($row['Score']) ?></td>
                        <td><?= htmlspecialchars($row['Note']) ?></td>
                        <td>
                          <!-- pass pID to edit and delete php file-->
                          <a class="show-link" href="display.php?CMN=<?= $row['FormationID'] ?>" >Display</a>
                          <a class="edit-link" href="edit.php?CMN=<?= $row['FormationID'] ?>" >Edit</a>
                          <a class="delete-link" href="delete.php?CMN=<?= $row['FormationID'] ?>" >Delete</a>
                      </td>
                    </tr>
            <?php 
                }
            } 
            
            if (!$hasResults) {
                echo '<tr><td colspan="4">No club members found</td></tr>';
            }
            ?>
        </table>
    </div>
</main>

</body>
</html>