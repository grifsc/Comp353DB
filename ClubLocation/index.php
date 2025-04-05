<?php 
require_once '../database.php';

$query = 'SELECT LocationID, LocationType, `Name`, `Address`, City, Province, PostalCode, WebAddress, MaxCapacity FROM kqc353_4.ClubLocation';
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
    <title>Club Location</title>
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
    <h2>Club Location</h2>
    <h3><a class="show-link" href ="create.php">Create Club Location</a></h3>
    

    <div>
        <table class="index-table">
            <tr>
                <th>LocationID</th>
                <th>LocationType</th>
                <th>Name</th>
                <th>Address</th>
                <th>City</th>
                <th>Province</th>
                <th>PostalCode</th>
                <th>WebAddress</th>
                <th>MaxCapacity</th>

            </tr>
            <?php 
            if ($statement->num_rows > 0) {
                while($row = $statement->fetch_assoc()) {
                    $hasResults = true;
            ?>
                    <tr>
                        <td><?= htmlspecialchars($row['LocationID']) ?></td>
                        <td><?= htmlspecialchars($row['LocationType']) ?></td>
                        <td><?= htmlspecialchars($row['Name']) ?></td>
                        <td><?= htmlspecialchars($row['Address']) ?></td>
                        <td><?= htmlspecialchars($row['City']) ?></td>
                        <td><?= htmlspecialchars($row['Province']) ?></td>
                        <td><?= htmlspecialchars($row['PostalCode']) ?></td>
                        <td><?= htmlspecialchars($row['WebAddress']) ?></td>
                        <td><?= htmlspecialchars($row['MaxCapacity']) ?></td>
                        <td>
                          <!-- pass pID to edit and delete php file-->
                          <a class="show-link" href="display.php?LocationID=<?= $row['LocationID'] ?>" >Display</a>
                          <a class="edit-link" href="edit.php?LocationID=<?= $row['LocationID'] ?>" >Edit</a>
                          <a class="delete-link" href="delete.php?LocationID=<?= $row['LocationID'] ?>" >Delete</a>
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