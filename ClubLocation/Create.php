<?php
require_once '../database.php';

// Initialize variables
$formData = [
    'locationType' => '',
    'name' => '',
    'address' => '',
    'city' => '',
    'province' => '',
    'postalCode' => '',
    'webAddress' => '',
    'maxCapacity' => ''
];
$errors = [];

// Location type options
$locationTypes = ['Head', 'Branch'];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize all inputs
    foreach ($_POST as $key => $value) {
        $formData[$key] = trim($value);
    }

    // Validate required fields
    $requiredFields = ['locationType', 'name', 'address', 'city', 'province', 'postalCode', 'maxCapacity'];
    
    foreach ($requiredFields as $field) {
        if (empty($formData[$field])) {
            $errors[$field] = 'This field is required';
        }
    }

    // Validate numeric fields
    if (empty($errors['maxCapacity']) && !is_numeric($formData['maxCapacity'])) {
        $errors['maxCapacity'] = 'Must be a valid number';
    }

    //if no errors, insert into database
    if (empty($errors)) {
        $query = "INSERT INTO kqc353_4.ClubLocation (
            LocationType, Name, Address, City, Province, PostalCode, WebAddress, MaxCapacity
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($query);
        
        if ($stmt) {
            $stmt->bind_param(
                "sssssssi",
                $formData['locationType'],
                $formData['name'],
                $formData['address'],
                $formData['city'],
                $formData['province'],
                $formData['postalCode'],
                $formData['webAddress'],
                $formData['maxCapacity']
            );
            
            if ($stmt->execute()) {
                header("Location: index.php?success=1");
                exit();
            } else {
                $errors['database'] = "Error creating club location: " . $stmt->error;
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
    <title>Create Club Location</title>
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
    <h2>Create New Club Location</h2>
    
    <?php if (!empty($errors['database'])): ?>
        <div class="error-message"><?= htmlspecialchars($errors['database']) ?></div>
    <?php endif; ?>
    
    <form method="post" action="create.php">
        <div class="form-container">
            <!-- Column 1 -->
            <div>
                <div class="form-group">
                    <label for="locationType">Location Type*:</label>
                    <select id="locationType" name="locationType" required>
                        <option value="">-- Select Type --</option>
                        <?php foreach ($locationTypes as $type): ?>
                            <option value="<?= $type ?>" <?= $type == $formData['locationType'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($type) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['locationType'])): ?>
                        <span class="error"><?= $errors['locationType'] ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="name">Name*:</label>
                    <input type="text" id="name" name="name" 
                           value="<?= htmlspecialchars($formData['name']) ?>" required>
                    <?php if (!empty($errors['name'])): ?>
                        <span class="error"><?= $errors['name'] ?></span>
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
            </div>
            
            <!-- Column 2 -->
            <div>
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
                    <label for="webAddress">Web Address:</label>
                    <input type="url" id="webAddress" name="webAddress" 
                           value="<?= htmlspecialchars($formData['webAddress']) ?>">
                </div>
                
                <div class="form-group">
                    <label for="maxCapacity">Max Capacity*:</label>
                    <input type="number" id="maxCapacity" name="maxCapacity" min="1"
                           value="<?= htmlspecialchars($formData['maxCapacity']) ?>" required>
                    <?php if (!empty($errors['maxCapacity'])): ?>
                        <span class="error"><?= $errors['maxCapacity'] ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="submit-button">Create Location</button>
            <a href="index.php" class="cancel-button">Cancel</a>
        </div>
    </form>
</main>

</body>
</html>