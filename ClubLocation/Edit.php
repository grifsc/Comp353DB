<?php
require_once '../database.php';

// Check if LocationID is provided
if (!isset($_GET['LocationID'])) {
    header("Location: index.php");
    exit();
}

$locationID = $_GET['LocationID'];

// Initialize variables
$formData = [];
$errors = [];

// Location type options
$locationTypes = ['Head', 'Branch'];

// Fetch existing location data
$locationQuery = "SELECT * FROM kqc353_4.ClubLocation WHERE LocationID = ?";
$stmt = $conn->prepare($locationQuery);
$stmt->bind_param("i", $locationID);
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
    $requiredFields = ['LocationType', 'Name', 'Address', 'City', 'Province', 'PostalCode', 'MaxCapacity'];
    
    foreach ($requiredFields as $field) {
        if (empty($formData[$field])) {
            $errors[$field] = 'This field is required';
        }
    }

    // Validate numeric fields
    if (empty($errors['MaxCapacity']) && !is_numeric($formData['MaxCapacity'])) {
        $errors['MaxCapacity'] = 'Must be a valid number';
    }

    // If no errors, update database
    if (empty($errors)) {
        $query = "UPDATE kqc353_4.ClubLocation SET
            LocationType = ?,
            Name = ?,
            Address = ?,
            City = ?,
            Province = ?,
            PostalCode = ?,
            WebAddress = ?,
            MaxCapacity = ?
            WHERE LocationID = ?";
        
        $stmt = $conn->prepare($query);
        
        if ($stmt) {
            $stmt->bind_param(
                "sssssssii",
                $formData['LocationType'],
                $formData['Name'],
                $formData['Address'],
                $formData['City'],
                $formData['Province'],
                $formData['PostalCode'],
                $formData['WebAddress'],
                $formData['MaxCapacity'],
                $locationID
            );
            
            if ($stmt->execute()) {
                header("Location: index.php?success=2");
                exit();
            } else {
                $errors['database'] = "Error updating club location: " . $stmt->error;
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
    <title>Edit Club Location</title>
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
        <li><a href="../otherQueries/index.php">Other Queries</a></li>
    </ul>
</nav>

<main>
    <h2>Edit Club Location (ID: <?= htmlspecialchars($locationID) ?>)</h2>
    
    <?php if (!empty($errors['database'])): ?>
        <div class="error-message"><?= htmlspecialchars($errors['database']) ?></div>
    <?php endif; ?>
    
    <form method="post" action="edit.php?LocationID=<?= $locationID ?>">
        <div class="form-container">
            <!-- Column 1 -->
            <div>
                <div class="form-group">
                    <label for="LocationType">Location Type*:</label>
                    <select id="LocationType" name="LocationType" required>
                        <option value="">-- Select Type --</option>
                        <?php foreach ($locationTypes as $type): ?>
                            <option value="<?= $type ?>" <?= $type == $formData['LocationType'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($type) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['LocationType'])): ?>
                        <span class="error"><?= $errors['LocationType'] ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="Name">Name*:</label>
                    <input type="text" id="Name" name="Name" 
                           value="<?= htmlspecialchars($formData['Name']) ?>" required>
                    <?php if (!empty($errors['Name'])): ?>
                        <span class="error"><?= $errors['Name'] ?></span>
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
            </div>
            
            <!-- Column 2 -->
            <div>
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
                    <label for="WebAddress">Web Address:</label>
                    <input type="url" id="WebAddress" name="WebAddress" 
                           value="<?= htmlspecialchars($formData['WebAddress']) ?>">
                </div>
                
                <div class="form-group">
                    <label for="MaxCapacity">Max Capacity*:</label>
                    <input type="number" id="MaxCapacity" name="MaxCapacity" min="1"
                           value="<?= htmlspecialchars($formData['MaxCapacity']) ?>" required>
                    <?php if (!empty($errors['MaxCapacity'])): ?>
                        <span class="error"><?= $errors['MaxCapacity'] ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="submit-button">Update Location</button>
            <a href="index.php" class="cancel-button">Cancel</a>
        </div>
    </form>
</main>

</body>
</html>