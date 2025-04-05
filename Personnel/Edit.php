<?php
require_once '../database.php';

// Check if PersonnelID is provided
if (!isset($_GET['PersonnelID'])) {
    header("Location: index.php");
    exit();
}

$personnelID = $_GET['PersonnelID'];

// Initialize variables
$formData = [];
$errors = [];

// Fetch existing personnel data
$personnelQuery = "SELECT * FROM kqc353_4.Personnel WHERE PersonnelID = ?";
$stmt = $conn->prepare($personnelQuery);
$stmt->bind_param("i", $personnelID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php");
    exit();
}

$formData = $result->fetch_assoc();
$stmt->close();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize all inputs
    foreach ($_POST as $key => $value) {
        $formData[$key] = trim($value);
    }

    // Validate required fields
    $requiredFields = ['FirstName', 'LastName', 'BirthDate', 'SIN', 
                      'MedicareCardNumber', 'Telephone', 'Address', 
                      'City', 'Province', 'PostalCode', 'Email'];
    
    foreach ($requiredFields as $field) {
        if (empty($formData[$field])) {
            $errors[$field] = 'This field is required';
        }
    }

    // Validate email format
    if (empty($errors['Email']) && !filter_var($formData['Email'], FILTER_VALIDATE_EMAIL)) {
        $errors['Email'] = 'Invalid email format';
    }

    // If no errors, update database
    if (empty($errors)) {
        $query = "UPDATE kqc353_4.Personnel SET
            FirstName = ?,
            LastName = ?,
            BirthDate = ?,
            SIN = ?,
            MedicareCardNumber = ?,
            Telephone = ?,
            Address = ?,
            City = ?,
            Province = ?,
            PostalCode = ?,
            Email = ?
            WHERE PersonnelID = ?";
        
        $stmt = $conn->prepare($query);
        
        if ($stmt) {
            $stmt->bind_param(
                "sssssssssssi",
                $formData['FirstName'],
                $formData['LastName'],
                $formData['BirthDate'],
                $formData['SIN'],
                $formData['MedicareCardNumber'],
                $formData['Telephone'],
                $formData['Address'],
                $formData['City'],
                $formData['Province'],
                $formData['PostalCode'],
                $formData['Email'],
                $personnelID
            );
            
            if ($stmt->execute()) {
                header("Location: index.php?success=2");
                exit();
            } else {
                // Check for duplicate SIN or Medicare card
                if (strpos($stmt->error, 'SIN') !== false) {
                    $errors['SIN'] = 'This SIN is already registered';
                } elseif (strpos($stmt->error, 'MedicareCardNumber') !== false) {
                    $errors['MedicareCardNumber'] = 'This Medicare card is already registered';
                } else {
                    $errors['database'] = "Error updating personnel: " . $stmt->error;
                }
            }
            
            $stmt->close();
        } else {
            $errors['database'] = "Database error: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Personnel</title>
    <link rel="stylesheet" href="../style.css">
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
    <h2>Edit Personnel (ID: <?= htmlspecialchars($personnelID) ?>)</h2>
    
    <?php if (!empty($errors['database'])): ?>
        <div class="error-message"><?= htmlspecialchars($errors['database']) ?></div>
    <?php endif; ?>
    
    <form method="post" action="edit.php?PersonnelID=<?= $personnelID ?>">
        <div class="form-container">
            <!-- Column 1 -->
            <div>
                <div class="form-group">
                    <label for="FirstName">First Name*:</label>
                    <input type="text" id="FirstName" name="FirstName" 
                           value="<?= htmlspecialchars($formData['FirstName']) ?>" required>
                    <?php if (!empty($errors['FirstName'])): ?>
                        <span class="error"><?= $errors['FirstName'] ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="LastName">Last Name*:</label>
                    <input type="text" id="LastName" name="LastName" 
                           value="<?= htmlspecialchars($formData['LastName']) ?>" required>
                    <?php if (!empty($errors['LastName'])): ?>
                        <span class="error"><?= $errors['LastName'] ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="BirthDate">Birth Date*:</label>
                    <input type="date" id="BirthDate" name="BirthDate" 
                           value="<?= htmlspecialchars($formData['BirthDate']) ?>" required>
                    <?php if (!empty($errors['BirthDate'])): ?>
                        <span class="error"><?= $errors['BirthDate'] ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="SIN">SIN*:</label>
                    <input type="text" id="SIN" name="SIN" 
                           value="<?= htmlspecialchars($formData['SIN']) ?>" required>
                    <?php if (!empty($errors['SIN'])): ?>
                        <span class="error"><?= $errors['SIN'] ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="MedicareCardNumber">Medicare Card Number*:</label>
                    <input type="text" id="MedicareCardNumber" name="MedicareCardNumber" 
                           value="<?= htmlspecialchars($formData['MedicareCardNumber']) ?>" required>
                    <?php if (!empty($errors['MedicareCardNumber'])): ?>
                        <span class="error"><?= $errors['MedicareCardNumber'] ?></span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Column 2 -->
            <div>
                <div class="form-group">
                    <label for="Telephone">Telephone*:</label>
                    <input type="tel" id="Telephone" name="Telephone" 
                           value="<?= htmlspecialchars($formData['Telephone']) ?>" required>
                    <?php if (!empty($errors['Telephone'])): ?>
                        <span class="error"><?= $errors['Telephone'] ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="Address">Address*:</label>
                    <input type="text" id="Address" name="Address" 
                           value="<?= htmlspecialchars($formData['Address']) ?>" required>
                    <?php if (!empty($errors['Address'])): ?>
                        <span class="error"><?= $errors['Address'] ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="City">City*:</label>
                    <input type="text" id="City" name="City" 
                           value="<?= htmlspecialchars($formData['City']) ?>" required>
                    <?php if (!empty($errors['City'])): ?>
                        <span class="error"><?= $errors['City'] ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="Province">Province*:</label>
                    <input type="text" id="Province" name="Province" 
                           value="<?= htmlspecialchars($formData['Province']) ?>" required>
                    <?php if (!empty($errors['Province'])): ?>
                        <span class="error"><?= $errors['Province'] ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="PostalCode">Postal Code*:</label>
                    <input type="text" id="PostalCode" name="PostalCode" 
                           value="<?= htmlspecialchars($formData['PostalCode']) ?>" required>
                    <?php if (!empty($errors['PostalCode'])): ?>
                        <span class="error"><?= $errors['PostalCode'] ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="Email">Email*:</label>
                    <input type="email" id="Email" name="Email" 
                           value="<?= htmlspecialchars($formData['Email']) ?>" required>
                    <?php if (!empty($errors['Email'])): ?>
                        <span class="error"><?= $errors['Email'] ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="submit-button">Update Personnel</button>
            <a href="index.php" class="cancel-button">Cancel</a>
        </div>
    </form>
</main>

</body>
</html>