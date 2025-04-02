<?php
require_once '../database.php';

// Initialize variables
$formData = [
    'firstName' => '',
    'lastName' => '',
    'birthDate' => '',
    'sin' => '',
    'medicareCardNumber' => '',
    'telephone' => '',
    'address' => '',
    'city' => '',
    'province' => '',
    'postalCode' => '',
    'email' => ''
];
$errors = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize all inputs
    foreach ($_POST as $key => $value) {
        $formData[$key] = trim($value);
    }

    // Validate required fields
    $requiredFields = ['firstName', 'lastName', 'birthDate', 'sin', 
                      'medicareCardNumber', 'telephone', 'address', 
                      'city', 'province', 'postalCode', 'email'];
    
    foreach ($requiredFields as $field) {
        if (empty($formData[$field])) {
            $errors[$field] = 'This field is required';
        }
    }

    // Validate email format
    if (empty($errors['email']) && !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }

    // If no errors, insert into database
    if (empty($errors)) {
        $query = "INSERT INTO kqc353_4.Personnel (
            FirstName, LastName, BirthDate, SIN, MedicareCardNumber,
            Telephone, Address, City, Province, PostalCode, Email
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($query);
        
        if ($stmt) {
            $stmt->bind_param(
                "sssssssssss",
                $formData['firstName'],
                $formData['lastName'],
                $formData['birthDate'],
                $formData['sin'],
                $formData['medicareCardNumber'],
                $formData['telephone'],
                $formData['address'],
                $formData['city'],
                $formData['province'],
                $formData['postalCode'],
                $formData['email']
            );
            
            if ($stmt->execute()) {
                header("Location: index.php?success=1");
                exit();
            } else {
                // Check for duplicate SIN or Medicare card
                if (strpos($stmt->error, 'SIN') !== false) {
                    $errors['sin'] = 'This SIN is already registered';
                } elseif (strpos($stmt->error, 'MedicareCardNumber') !== false) {
                    $errors['medicareCardNumber'] = 'This Medicare card is already registered';
                } else {
                    $errors['database'] = "Error creating personnel: " . $stmt->error;
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
    <title>Create Personnel</title>
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
    <h2>Create New Personnel</h2>
    
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
                           value="<?= htmlspecialchars($formData['birthDate']) ?>" required>
                    <?php if (!empty($errors['birthDate'])): ?>
                        <span class="error"><?= $errors['birthDate'] ?></span>
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
            </div>
            
            <!-- Column 2 -->
            <div>
                <div class="form-group">
                    <label for="telephone">Telephone*:</label>
                    <input type="tel" id="telephone" name="telephone" 
                           value="<?= htmlspecialchars($formData['telephone']) ?>" required>
                    <?php if (!empty($errors['telephone'])): ?>
                        <span class="error"><?= $errors['telephone'] ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="address">Address*:</label>
                    <input type="text" id="address" name="address" 
                           value="<?= htmlspecialchars($formData['address']) ?>" required>
                    <?php if (!empty($errors['address'])): ?>
                        <span class="error"><?= $errors['address'] ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="city">City*:</label>
                    <input type="text" id="city" name="city" 
                           value="<?= htmlspecialchars($formData['city']) ?>" required>
                    <?php if (!empty($errors['city'])): ?>
                        <span class="error"><?= $errors['city'] ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="province">Province*:</label>
                    <input type="text" id="province" name="province" 
                           value="<?= htmlspecialchars($formData['province']) ?>" required>
                    <?php if (!empty($errors['province'])): ?>
                        <span class="error"><?= $errors['province'] ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="postalCode">Postal Code*:</label>
                    <input type="text" id="postalCode" name="postalCode" 
                           value="<?= htmlspecialchars($formData['postalCode']) ?>" required>
                    <?php if (!empty($errors['postalCode'])): ?>
                        <span class="error"><?= $errors['postalCode'] ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="email">Email*:</label>
                    <input type="email" id="email" name="email" 
                           value="<?= htmlspecialchars($formData['email']) ?>" required>
                    <?php if (!empty($errors['email'])): ?>
                        <span class="error"><?= $errors['email'] ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="submit-button">Create Personnel</button>
            <a href="index.php" class="cancel-button">Cancel</a>
        </div>
    </form>
</main>

</body>
</html>