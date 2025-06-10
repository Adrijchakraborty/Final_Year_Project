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

// Auto-update expired notices
$conn->query("UPDATE notices SET status = 'expired', importance_level = 1 WHERE status = 'active' AND delete_at IS NOT NULL AND delete_at <= NOW()");

// Fetch latest notices as JSON (only active ones)
if (isset($_GET['latest'])) {
    $sql = "SELECT content, importance_level, url FROM notices WHERE status = 'active' ORDER BY importance_level DESC, created_at DESC";
    $result = $conn->query($sql);

    $notices = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $notices[] = $row;
        }
    }

    header('Content-Type: application/json');
    echo json_encode($notices, JSON_PRETTY_PRINT);
    exit;
}

session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit;
}

$message = "";
$error = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $content = $_POST['content'] ?? '';
    $importance_level = $_POST['importance_level'] ?? '';
    $url = $_POST['url'] ?? null; // Added URL field
    $schedule = isset($_POST['schedule']) ? 1 : 0;
    $delete_at = $schedule ? ($_POST['delete_at'] ?? null) : null;

    if (empty($content) || empty($importance_level)) {
        $error = "All required fields are required.";
    } else {
        // Updated SQL query and bind_param to include 'url'
        $sql = "INSERT INTO notices (content, importance_level, url, status, created_at, delete_at) VALUES (?, ?, ?, 'active', NOW(), ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("siss", $content, $importance_level, $url, $delete_at);

        if ($stmt->execute()) {
            $message = "Notice submitted successfully!";
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle deletion by marking as expired
if (isset($_GET['delete'])) {
    $idToDelete = intval($_GET['delete']);
    $deleteSql = "UPDATE notices SET status = 'expired', importance_level = 1 WHERE id = ?";
    $stmt = $conn->prepare($deleteSql);
    $stmt->bind_param("i", $idToDelete);

    if ($stmt->execute()) {
        $message = "Notice marked as expired successfully!";
    } else {
        $error = "Delete failed: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch all notices for display
$sql = "SELECT id, content, importance_level, status, url FROM notices ORDER BY importance_level DESC, created_at DESC";
$result = $conn->query($sql);

$notices = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $notices[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <style>
        nav {
            background-color: #00bcd4;
            padding: 20px;
            text-align: center;
            display: flex;
            flex-direction: column;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        nav h1 {
            margin: 0;
            font-size: 32px;
            font-family: 'Inter', sans-serif;
        }
        .nav-user {
            margin-top: 10px;
            display: flex;
            gap: 10px;
            justify-content: space-between;
            align-items: center;
        }
        .nav-user h2 {
            margin: 0;
            font-size: 18px;
        }
        .logout {
            text-decoration: none;
            color: white;
            background-color: #e63946;
            padding: 8px 16px;
            border-radius: 5px;
            font-size: 14px;
        }
        .notice {
            padding: 10px;
            margin: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .status-active {
            color: green;
        }
        .status-expired {
            color: red;
        }
    </style>
    <title>Smart Notice Board</title>
</head>
<body>
    <nav>
        <h1 class="font-inter">Smart Notice Board</h1>
        <div class="nav-user">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h2>
            <a class="logout" href="logout.php">Logout</a>
        </div>
    </nav>

    <section id="display">
        <?php if (count($notices) > 0): ?>
            <?php foreach ($notices as $notice): ?>
                <div class="notice">
                    <p><?php echo htmlspecialchars($notice['content']); ?></p>
                    <?php if (!empty($notice['url'])): ?>
                        <p><a href="<?php echo htmlspecialchars($notice['url']); ?>" target="_blank">View More</a></p>
                    <?php endif; ?>
                    <small>Importance: <?php echo htmlspecialchars($notice['importance_level']); ?></small><br>
                    <small>Status:
                        <span class="status-<?php echo htmlspecialchars($notice['status']); ?>">
                            <?php echo htmlspecialchars($notice['status']); ?>
                        </span>
                    </small><br>
                    <?php if ($notice['status'] === 'active'): ?>
                        <a href="?delete=<?php echo $notice['id']; ?>" onclick="return confirm('Are you sure you want to mark this notice as expired?');">Mark as Expired</a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No notices available.</p>
        <?php endif; ?>
    </section>

    <div class="add-notice-container">
        <button class="add-notice" onclick="document.getElementById('addNotice').showModal()">Add notice</button>
    </div>
    <dialog id="addNotice">
        <h3>Add notice</h3>
        <form method="POST" action="">
            <input type="text" name="content" placeholder="Enter the content" required>
            <input type="url" name="url" placeholder="Optional: Enter a URL (e.g., https://example.com)">
            <input type="range" name="importance_level" min="1" max="10" oninput="this.nextElementSibling.value = this.value">
            <output>5</output><br><br>
            <label><input type="checkbox" id="scheduleToggle" name="schedule" onchange="document.getElementById('scheduleFields').style.display = this.checked ? 'block' : 'none';"> Schedule Deletion</label>
            <div id="scheduleFields" style="display:none;">
                <label for="delete_at">Delete At (Date & Time):</label>
                <input type="datetime-local" name="delete_at">
            </div>
            <button type="submit">Submit</button>
        </form>
        <button id="close-dialog" onclick="document.getElementById('addNotice').close()">Close</button>
    </dialog>

    <?php if (!empty($message)): ?>
        <p class="success-message"><?php echo $message; ?></p>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <p class="error-message"><?php echo $error; ?></p>
    <?php endif; ?>
</body>
</html>