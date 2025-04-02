<?php
require_once '../database.php';

// Initialize variables
$formData = [
    'firstName' => '',
    'lastName' => '',
    'birthDate' => '',
    'enrollmentDate' => date('Y-m-d'),
    'height' => '',
    'weight' => '',
    'sin' => '',
    'medicareCardNumber' => '',
    'telephone' => '',
    'address' => '',
    'city' => '',
    'province' => '',
    'postalCode' => '',
    'currentLocationID' => ''
];
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

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize all inputs
    foreach ($_POST as $key => $value) {
        $formData[$key] = trim($value);
    }

    // Validate required fields
    $requiredFields = ['firstName', 'lastName', 'birthDate', 'enrollmentDate', 
                      'height', 'weight', 'sin', 'medicareCardNumber', 'currentLocationID'];
    foreach ($requiredFields as $field) {
        if (empty($formData[$field])) {
            $errors[$field] = 'This field is required';
        }
    }

    // Validate age between 11-18
    if (empty($errors['birthDate'])) {
        $birthDate = new DateTime($formData['birthDate']);
        $today = new DateTime();
        $age = $today->diff($birthDate)->y;
        
        if ($age < 11 || $age > 18) {
            $errors['birthDate'] = 'Member must be between 11 and 18 years old';
        }
    }

    // Validate enrollment date is today or in the past
    if (empty($errors['enrollmentDate'])) {
        $enrollmentDate = new DateTime($formData['enrollmentDate']);
        if ($enrollmentDate > new DateTime()) {
            $errors['enrollmentDate'] = 'Enrollment date cannot be in the future';
        }
    }

    // Validate numeric fields
    $numericFields = ['height', 'weight'];
    foreach ($numericFields as $field) {
        if (!is_numeric($formData[$field])) {
            $errors[$field] = 'Must be a valid number';
        }
    }

    // Validate location exists
    if (empty($errors['currentLocationID']) && !array_key_exists($formData['currentLocationID'], $locations)) {
        $errors['currentLocationID'] = 'Invalid location selected';
    }

    // If no errors, insert into database
    if (empty($errors)) {
        $query = "INSERT INTO kqc353_4.ClubMember (
            FirstName, LastName, BirthDate, EnrollmentDate, 
            Height, Weight, SIN, MedicareCardNumber, 
            Telephone, Address, City, Province, PostalCode,
            CurrentLocationID
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($query);
        
        if ($stmt) {
            $stmt->bind_param(
                "ssssddsssssssi",
                $formData['firstName'],
                $formData['lastName'],
                $formData['birthDate'],
                $formData['enrollmentDate'],
                $formData['height'],
                $formData['weight'],
                $formData['sin'],
                $formData['medicareCardNumber'],
                $formData['telephone'],
                $formData['address'],
                $formData['city'],
                $formData['province'],
                $formData['postalCode'],
                $formData['currentLocationID']
            );
            
            if ($stmt->execute()) {
                header("Location: index.php?success=1");
                exit();
            } else {
                if (strpos($stmt->error, 'SIN') !== false) {
                    $errors['sin'] = 'This SIN is already registered';
                } elseif (strpos($stmt->error, 'MedicareCardNumber') !== false) {
                    $errors['medicareCardNumber'] = 'This Medicare card is already registered';
                } else {
                    $errors['database'] = "Error creating club member: " . $stmt->error;
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
    <title>Create Club Member</title>
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
    <h2>Create New Club Member</h2>
    
    <?php if (!empty($errors['database'])): ?>
        <div class="error-message"><?= htmlspecialchars($errors['database']) ?></div>
    <?php endif; ?>
    
    <form method="post" action="create.php">
        <div class="form-container">
            <!-- Column 1 -->
            <div>
                <div class="form-group">
                    <label for="firstName">First Name*:</label>
                    <input type="text" id="firstName" name="firstName" 
                           value="<?= htmlspecialchars($formData['firstName']) ?>" required>
                    <?php if (!empty($errors['firstName'])): ?>
                        <span class="error"><?= $errors['firstName'] ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="lastName">Last Name*:</label>
                    <input type="text" id="lastName" name="lastName" 
                           value="<?= htmlspecialchars($formData['lastName']) ?>" required>
                    <?php if (!empty($errors['lastName'])): ?>
                        <span class="error"><?= $errors['lastName'] ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="birthDate">Birth Date*:</label>
                    <input type="date" id="birthDate" name="birthDate" 
                           value="<?= htmlspecialchars($formData['birthDate']) ?>" required
                           max="<?= date('Y-m-d', strtotime('-11 years')) ?>"
                           min="<?= date('Y-m-d', strtotime('-18 years')) ?>">
                    <?php if (!empty($errors['birthDate'])): ?>
                        <span class="error"><?= $errors['birthDate'] ?></span>
                    <?php endif; ?>
                    <small>Must be between 11-18 years old</small>
                </div>
                
                <div class="form-group">
                    <label for="enrollmentDate">Enrollment Date*:</label>
                    <input type="date" id="enrollmentDate" name="enrollmentDate" 
                           value="<?= htmlspecialchars($formData['enrollmentDate']) ?>" required
                           max="<?= date('Y-m-d') ?>">
                    <?php if (!empty($errors['enrollmentDate'])): ?>
                        <span class="error"><?= $errors['enrollmentDate'] ?></span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Column 2 -->
            <div>
                <div class="form-group">
                    <label for="height">Height (cm)*:</label>
                    <input type="number" id="height" name="height"
                           value="<?= htmlspecialchars($formData['height']) ?>" required>
                    <?php if (!empty($errors['height'])): ?>
                        <span class="error"><?= $errors['height'] ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="weight">Weight (kg)*:</label>
                    <input type="number" id="weight" name="weight"
                           value="<?= htmlspecialchars($formData['weight']) ?>" required>
                    <?php if (!empty($errors['weight'])): ?>
                        <span class="error"><?= $errors['weight'] ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="sin">SIN*:</label>
                    <input type="text" id="sin" name="sin" 
                           value="<?= htmlspecialchars($formData['sin']) ?>" required>
                    <?php if (!empty($errors['sin'])): ?>
                        <span class="error"><?= $errors['sin'] ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="medicareCardNumber">Medicare Card Number*:</label>
                    <input type="text" id="medicareCardNumber" name="medicareCardNumber" 
                           value="<?= htmlspecialchars($formData['medicareCardNumber']) ?>" required>
                    <?php if (!empty($errors['medicareCardNumber'])): ?>
                        <span class="error"><?= $errors['medicareCardNumber'] ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="currentLocationID">Current Club Location*:</label>
                    <select id="currentLocationID" name="currentLocationID" required>
                        <option value="">-- Select Location --</option>
                        <?php foreach ($locations as $id => $name): ?>
                            <option value="<?= $id ?>" <?= $id == $formData['currentLocationID'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['currentLocationID'])): ?>
                        <span class="error"><?= $errors['currentLocationID'] ?></span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Full width fields -->
            <div class="form-group" style="grid-column: span 2">
                <label for="telephone">Telephone:</label>
                <input type="tel" id="telephone" name="telephone" 
                       value="<?= htmlspecialchars($formData['telephone']) ?>">
            </div>
            
            <div class="form-group" style="grid-column: span 2">
                <label for="address">Address:</label>
                <input type="text" id="address" name="address" 
                       value="<?= htmlspecialchars($formData['address']) ?>">
            </div>
            
            <div class="form-group" style="grid-column: span 2">
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                    <div>
                        <label for="city">City:</label>
                        <input type="text" id="city" name="city" 
                               value="<?= htmlspecialchars($formData['city']) ?>">
                    </div>
                    <div>
                        <label for="province">Province:</label>
                        <input type="text" id="province" name="province" 
                               value="<?= htmlspecialchars($formData['province']) ?>">
                    </div>
                    <div>
                        <label for="postalCode">Postal Code:</label>
                        <input type="text" id="postalCode" name="postalCode" 
                               value="<?= htmlspecialchars($formData['postalCode']) ?>">
                    </div>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="submit-button">Create Club Member</button>
            <a href="index.php" class="cancel-button">Cancel</a>
        </div>
    </form>
</main>

</body>
</html>