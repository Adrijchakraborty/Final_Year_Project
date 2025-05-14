<?php
// Database connection
$host = "localhost";
$username = "root";
$password = "";
$database = "smart_notice_board";

$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch latest notices as JSON
if (isset($_GET['latest'])) {
    $sql = "SELECT content, importance_level 
FROM notices 
ORDER BY importance_level DESC, created_at DESC";
    $result = $conn->query($sql);

    $notices = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $notices[] = $row; // Collect each row into the array
        }
    }

    // Set response header to JSON
    header('Content-Type: application/json');
    echo json_encode($notices, JSON_PRETTY_PRINT);
    exit;
}

// Default behavior for HTML rendering
// Initialize messages
$message = "";
$error = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $content = $_POST['content'] ?? '';
    $importance_level = $_POST['importance_level'] ?? '';

    if (empty($content) || empty($importance_level)) {
        $error = "All fields are required.";
    } else {
        $sql = "INSERT INTO notices (content, importance_level) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $content, $importance_level);

        if ($stmt->execute()) {
            $message = "Notice submitted successfully!";
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch notices for HTML rendering
$sql = "SELECT id, content, importance_level 
FROM notices 
ORDER BY importance_level DESC, created_at DESC";
$result = $conn->query($sql);

$notices = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $notices[] = $row;
    }
}

// Handle deletion
if (isset($_GET['delete'])) {
    $idToDelete = intval($_GET['delete']);
    $deleteSql = "DELETE FROM notices WHERE id = ?";
    $stmt = $conn->prepare($deleteSql);
    $stmt->bind_param("i", $idToDelete);

    if ($stmt->execute()) {
        $message = "Notice deleted successfully!";
    } else {
        $error = "Delete failed: " . $stmt->error;
    }

    $stmt->close();
}


$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Smart Notice Board</title>
</head>
<body>
    <nav>
        <h1 class="font-inter">Smart Notice Board</h1>
    </nav>

    <!-- Display Section -->
    <section id="display">
        <?php if (count($notices) > 0): ?>
            <?php foreach ($notices as $notice): ?>
    <div>
        <p><?php echo htmlspecialchars($notice['content']); ?></p>
        <small>Importance: <?php echo htmlspecialchars($notice['importance_level']); ?></small><br>
        <a href="?delete=<?php echo $notice['id']; ?>" onclick="return confirm('Are you sure you want to delete this notice?');">Delete</a>
    </div>
<?php endforeach; ?>
        <?php else: ?>
            <p>No notices available.</p>
        <?php endif; ?>
    </section>

    <!-- Form Section -->
    <div class="add-notice-container">
        <button class="add-notice" onclick="document.getElementById('addNotice').showModal()">Add notice</button>
    </div>
    <dialog id="addNotice">
        <h3>Add notice</h3>
        <form method="POST" action="">
            <input type="text" name="content" placeholder="Enter the content" required>
            <input type="range" name="importance_level" min="1" max="10" oninput="this.nextElementSibling.value = this.value">
            <output>5</output>
            <button type="submit">Submit</button>
        </form>
        <button id="close-dialog" onclick="document.getElementById('addNotice').close()">Close</button>
    </dialog>

    <!-- Error/Message Display -->
    <?php if (!empty($message)): ?>
        <p class="success-message"><?php echo $message; ?></p>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <p class="error-message"><?php echo $error; ?></p>
    <?php endif; ?>
</body>
</html>