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

$sql = "SELECT content, importance_level FROM notices ORDER BY created_at DESC";
$result = $conn->query($sql);

$notices = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $notices[] = $row;
    }
}

$conn->close();
echo json_encode($notices);
?>
