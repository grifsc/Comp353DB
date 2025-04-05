<?php
require_once '../database.php';

// Check if CMN is provided
if (!isset($_GET['CMN'])) {
    header("Location: index.php");
    exit();
}

$cmn = $_GET['CMN'];

// Initialize variables
$formData = [];
$errors = [];

// Fetch available club locations for dropdown
$locations = [];
$locationQuery = "SELECT LocationID, Name FROM kqc353_4.ClubLocation";
$locationResult = $conn->query($locationQuery);
if ($locationResult) {
    while ($row = $locationResult->fetch_assoc()) {
        $locations[$row['LocationID']] = $row['Name'];
    }
}

// Fetch existing member data
$memberQuery = "SELECT * FROM kqc353_4.ClubMember WHERE CMN = ?";
$stmt = $conn->prepare($memberQuery);
$stmt->bind_param("i", $cmn);
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
    $requiredFields = ['FirstName', 'LastName', 'BirthDate', 'EnrollmentDate', 
                      'Height', 'Weight', 'SIN', 'MedicareCardNumber', 'CurrentLocationID'];
    foreach ($requiredFields as $field) {
        if (empty($formData[$field])) {
            $errors[$field] = 'This field is required';
        }
    }

    // Validate age between 11-18
    if (empty($errors['BirthDate'])) {
        $birthDate = new DateTime($formData['BirthDate']);
        $today = new DateTime();
        $age = $today->diff($birthDate)->y;
        
        if ($age < 11 || $age > 18) {
            $errors['BirthDate'] = 'Member must be between 11 and 18 years old';
        }
    }

    // Validate enrollment date is today or in the past
    if (empty($errors['EnrollmentDate'])) {
        $enrollmentDate = new DateTime($formData['EnrollmentDate']);
        if ($enrollmentDate > new DateTime()) {
            $errors['EnrollmentDate'] = 'Enrollment date cannot be in the future';
        }
    }

    // Validate numeric fields
    $numericFields = ['Height', 'Weight'];
    foreach ($numericFields as $field) {
        if (!is_numeric($formData[$field])) {
            $errors[$field] = 'Must be a valid number';
        }
    }

    // Validate location exists
    if (empty($errors['CurrentLocationID']) && !array_key_exists($formData['CurrentLocationID'], $locations)) {
        $errors['CurrentLocationID'] = 'Invalid location selected';
    }

    // If no errors, update database
    if (empty($errors)) {
        $query = "UPDATE kqc353_4.ClubMember SET
            FirstName = ?,
            LastName = ?,
            BirthDate = ?,
            EnrollmentDate = ?,
            Height = ?,
            Weight = ?,
            SIN = ?,
            MedicareCardNumber = ?,
            Telephone = ?,
            Address = ?,
            City = ?,
            Province = ?,
            PostalCode = ?,
            CurrentLocationID = ?
            WHERE CMN = ?";
        
        $stmt = $conn->prepare($query);
        
        if ($stmt) {
            $stmt->bind_param(
                $formData['FirstName'],
                $formData['LastName'],
                $formData['BirthDate'],
                $formData['EnrollmentDate'],
                $formData['Height'],
                $formData['Weight'],
                $formData['SIN'],
                $formData['MedicareCardNumber'],
                $formData['Telephone'],
                $formData['Address'],
                $formData['City'],
                $formData['Province'],
                $formData['PostalCode'],
                $formData['CurrentLocationID'],
                $cmn
            );
            
            if ($stmt->execute()) {
                header("Location: index.php?success=2");
                exit();
            } else {
                if (strpos($stmt->error, 'SIN') !== false) {
                    $errors['SIN'] = 'This SIN is already registered';
                } elseif (strpos($stmt->error, 'MedicareCardNumber') !== false) {
                    $errors['MedicareCardNumber'] = 'This Medicare card is already registered';
                } else {
                    $errors['database'] = "Error updating club member: " . $stmt->error;
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
    <title>Edit Club Member</title>
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
    <h2>Edit Club Member (CMN: <?= htmlspecialchars($cmn) ?>)</h2>
    
    <?php if (!empty($errors['database'])): ?>
        <div class="error-message"><?= htmlspecialchars($errors['database']) ?></div>
    <?php endif; ?>
    
    <form method="post" action="edit.php?CMN=<?= $cmn ?>">
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
                           value="<?= htmlspecialchars($formData['BirthDate']) ?>" required
                           max="<?= date('Y-m-d', strtotime('-11 years')) ?>"
                           min="<?= date('Y-m-d', strtotime('-18 years')) ?>">
                    <?php if (!empty($errors['BirthDate'])): ?>
                        <span class="error"><?= $errors['BirthDate'] ?></span>
                    <?php endif; ?>
                    <small>Must be between 11-18 years old</small>
                </div>
                
                <div class="form-group">
                    <label for="EnrollmentDate">Enrollment Date*:</label>
                    <input type="date" id="EnrollmentDate" name="EnrollmentDate" 
                           value="<?= htmlspecialchars($formData['EnrollmentDate']) ?>" required
                           max="<?= date('Y-m-d') ?>">
                    <?php if (!empty($errors['EnrollmentDate'])): ?>
                        <span class="error"><?= $errors['EnrollmentDate'] ?></span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Column 2 -->
            <div>
                <div class="form-group">
                    <label for="Height">Height (cm)*:</label>
                    <input type="number" id="Height" name="Height"
                           value="<?= htmlspecialchars($formData['Height']) ?>" required>
                    <?php if (!empty($errors['Height'])): ?>
                        <span class="error"><?= $errors['Height'] ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="Weight">Weight (kg)*:</label>
                    <input type="number" id="Weight" name="Weight"
                           value="<?= htmlspecialchars($formData['Weight']) ?>" required>
                    <?php if (!empty($errors['Weight'])): ?>
                        <span class="error"><?= $errors['Weight'] ?></span>
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
                
                <div class="form-group">
                    <label for="CurrentLocationID">Current Club Location*:</label>
                    <select id="CurrentLocationID" name="CurrentLocationID" required>
                        <option value="">-- Select Location --</option>
                        <?php foreach ($locations as $id => $name): ?>
                            <option value="<?= $id ?>" <?= $id == $formData['CurrentLocationID'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['CurrentLocationID'])): ?>
                        <span class="error"><?= $errors['CurrentLocationID'] ?></span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Full width fields -->
            <div class="form-group" style="grid-column: span 2">
                <label for="Telephone">Telephone:</label>
                <input type="tel" id="Telephone" name="Telephone" 
                       value="<?= htmlspecialchars($formData['Telephone']) ?>">
            </div>
            
            <div class="form-group" style="grid-column: span 2">
                <label for="Address">Address:</label>
                <input type="text" id="Address" name="Address" 
                       value="<?= htmlspecialchars($formData['Address']) ?>">
            </div>
            
            <div class="form-group" style="grid-column: span 2">
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                    <div>
                        <label for="City">City:</label>
                        <input type="text" id="City" name="City" 
                               value="<?= htmlspecialchars($formData['City']) ?>">
                    </div>
                    <div>
                        <label for="Province">Province:</label>
                        <input type="text" id="Province" name="Province" 
                               value="<?= htmlspecialchars($formData['Province']) ?>">
                    </div>
                    <div>
                        <label for="PostalCode">Postal Code:</label>
                        <input type="text" id="PostalCode" name="PostalCode" 
                               value="<?= htmlspecialchars($formData['PostalCode']) ?>">
                    </div>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="submit-button">Update Member</button>
            <a href="index.php" class="cancel-button">Cancel</a>
        </div>
    </form>
</main>

</body>
</html>