<?php 
require_once '../database.php';

$query = 'SELECT CMN, FirstName, LastName, BirthDate, EnrollmentDate, Height, `Weight`, SIN, MedicareCardNumber, Telephone, `Address`, City, Province, PostalCode, CurrentLocationID FROM kqc353_4.ClubMember';
$statement = $conn->query($query);

// Check if query succeeded
if ($statement === false) {
    die("Query failed: " . $conn->error);
}

// Check if there are any results
$hasResults = false;
?>

<?php

$successMessage = '';
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $successMessage = 'Club member created successfully!';
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Members</title>
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
    <h2>Club Member</h2>

    <?php if (!empty($successMessage)): ?>
    <div class="success-message"><?= htmlspecialchars($successMessage) ?></div>
    <?php endif; ?>

    <h3><a class="show-link" href ="create.php">Create Club Member</a></h3>
    

    <div>
        <table class="index-table">
            <tr>
                <th>Member Number</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Birth Date</th>
            </tr>
            <?php 
            if ($statement->num_rows > 0) {
                while($row = $statement->fetch_assoc()) {
                    $hasResults = true;
            ?>
                    <tr>
                        <td><?= htmlspecialchars($row['CMN']) ?></td>
                        <td><?= htmlspecialchars($row['FirstName']) ?></td>
                        <td><?= htmlspecialchars($row['LastName']) ?></td>
                        <td><?= htmlspecialchars($row['BirthDate']) ?></td>
                        <td>
                          <!-- pass pID to edit and delete php file-->
                          <a class="show-link" href="display.php?CMN=<?= $row["CMN"] ?>" >Display</a>
                          <a class="edit-link" href="edit.php?CMN=<?= $row["CMN"] ?>" >Edit</a>
                          <a class="delete-link" href="delete.php?CMN=<?= $row["CMN"] ?>" >Delete</a>
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