<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "smart_notice_board";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Mark expired notices and update importance_level
$sql = "UPDATE notices SET status = 'expired', importance_level = 1 
        WHERE status = 'active' AND delete_at IS NOT NULL AND delete_at <= NOW()";

if ($conn->query($sql) === TRUE) {
    echo "Expired notices updated successfully at " . date("Y-m-d H:i:s");
} else {
    echo "Error: " . $conn->error;
}
$conn->close();
?>
