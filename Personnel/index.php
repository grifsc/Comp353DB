<?php 
require_once '../database.php';

$query = 'SELECT PersonnelID, FirstName, LastName, BirthDate, SIN, MedicareCardNumber, Telephone, `Address`, City, Province, PostalCode, Email FROM kqc353_4.Personnel';
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
    <title>Personnel</title>
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
    <h2>Personnel</h2>
    <h3><a class="show-link" href ="create.php">Create Club Member</a></h3>
    

    <div>
        <table class="index-table">
            <tr>
                <th>PersonnelID</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Birth Date</th>
                <th>SIN</th>
                <th>MedicareCardNumber</th>
                <th>Telephone</th>
                <th>Address</th>
                <th>City</th>
                <th>Province</th>
                <th>PostalCode</th>
                <th>Email</th>
            </tr>
            <?php 
            if ($statement->num_rows > 0) {
                while($row = $statement->fetch_assoc()) {
                    $hasResults = true;
            ?>
                    <tr>
                        <td><?= htmlspecialchars($row['PersonnelID']) ?></td>
                        <td><?= htmlspecialchars($row['FirstName']) ?></td>
                        <td><?= htmlspecialchars($row['LastName']) ?></td>
                        <td><?= htmlspecialchars($row['BirthDate']) ?></td>
                        <td><?= htmlspecialchars($row['SIN']) ?></td>
                        <td><?= htmlspecialchars($row['MedicareCardNumber']) ?></td>
                        <td><?= htmlspecialchars($row['Telephone']) ?></td>
                        <td><?= htmlspecialchars($row['Address']) ?></td>
                        <td><?= htmlspecialchars($row['City']) ?></td>
                        <td><?= htmlspecialchars($row['Province']) ?></td>
                        <td><?= htmlspecialchars($row['PostalCode']) ?></td>
                        <td><?= htmlspecialchars($row['Email']) ?></td>
                        <td>
                          <!-- pass pID to edit and delete php file-->
                          <a class="show-link" href="display.php?PersonnelID=<?= $row['PersonnelID'] ?>" >Display</a>
                          <a class="edit-link" href="edit.php?PersonnelID=<?= $row['PersonnelID'] ?>" >Edit</a>
                          <a class="delete-link" href="delete.php?PersonnelID=<?= $row['PersonnelID'] ?>" >Delete</a>
                      </td>
                    </tr>
            <?php 
                }
            } 
            
            if (!$hasResults) {
                echo '<tr><td colspan="4">No Personnel found</td></tr>';
            }
            ?>
        </table>
    </div>
</main>

</body>
</html>