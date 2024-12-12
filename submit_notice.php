<?php
// Database connection
$host = "localhost";
$username = "root";
$password = "";
$database = "smart_notice_board";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]));
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $content = $_POST['content'] ?? '';
    $importance_level = $_POST['importance_level'] ?? '';

    if (empty($content) || empty($importance_level)) {
        echo json_encode(["status" => "error", "message" => "All fields are required."]);
        exit;
    }

    $sql = "INSERT INTO notices (content, importance_level) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $content, $importance_level);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Notice submitted successfully!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error: " . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
    exit;
}

echo json_encode(["status" => "error", "message" => "Invalid request method."]);
exit;
?>
