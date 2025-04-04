<?php 
require_once '../database.php';

$query = 'SELECT FamilyMemberID, FirstName, LastName, BirthDate, SIN, MedicareCardNumber FROM kqc353_4.FamilyMember';
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
    <title>Family Member</title>
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
    <h2>Family Member</h2>
    <h3><a class="show-link" href ="create.php">Create Family Member</a></h3>
    

    <div>
        <table class="index-table">
            <tr>
                <th>FamilyMemberID</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Birth Date</th>
                <th>SIN</th>
                <th>MedicareCardNumber</th>
            </tr>
            <?php 
            if ($statement->num_rows > 0) {
                while($row = $statement->fetch_assoc()) {
                    $hasResults = true;
            ?>
                    <tr>
                        <td><?= htmlspecialchars($row['FamilyMemberID']) ?></td>
                        <td><?= htmlspecialchars($row['FirstName']) ?></td>
                        <td><?= htmlspecialchars($row['LastName']) ?></td>
                        <td><?= htmlspecialchars($row['BirthDate']) ?></td>
                        <td><?= htmlspecialchars($row['SIN']) ?></td>
                        <td><?= htmlspecialchars($row['MedicareCardNumber']) ?></td>
                        <td>
                          <!-- pass pID to edit and delete php file-->
                          <a class="show-link" href="display.php?FamilyMemberID=<?= $row['FamilyMemberID'] ?>" >Display</a>
                          <a class="edit-link" href="edit.php?FamilyMemberID=<?= $row['FamilyMemberID'] ?>" >Edit</a>
                          <a class="delete-link" href="delete.php?FamilyMemberID=<?= $row['FamilyMemberID'] ?>" >Delete</a>
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