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
    <h2>Other Queries</h2>
    <br>
    <h1><a href="query7.php">Query 7</a></h1>
    <br>
    <h1><a href="query8.php">Query 8</a></h1>
    <br>
    <h1><a href="query9.php">Query 9</a></h1>
    <br>
    <h1><a href="query10.php">Query 10</a></h1>
    <br>
    <h1><a href="query11.php">Query 11</a></h1>
    <br>
    <h1><a href="query12.php">Query 12</a></h1>
    <br>
    <h1><a href="query13.php">Query 13</a></h1>
    <br>
    <h1><a href="query14.php">Query 14</a></h1>
    <br>
    <h1><a href="query15.php">Query 15</a></h1>
    <br>
    <h1><a href="query16.php">Query 16</a></h1>
    <br>
    <h1><a href="query17.php">Query 17</a></h1>
    <br>
    <h1><a href="query18.php">Query 18</a></h1>    
</main>

</body>
</html>