<?php
// Database Configuration (move to separate file in production)
require_once 'config.php';

// Start session securely
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => false, // Set to false for local testing without HTTPS
    'cookie_samesite' => 'Strict'
]);

// Validate session only for non-API requests
if (!isset($_GET['latest']) && (!isset($_SESSION['username']) || empty($_SESSION['username']))) {
    header("HTTP/1.1 401 Unauthorized");
    exit("Unauthorized access");
}

// Database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    die("Database connection error");
}

// Auto-update expired notices
$conn->query("UPDATE notices SET status = 'expired', importance_level = 1 
             WHERE status = 'active' AND delete_at IS NOT NULL AND delete_at <= NOW()");

// API endpoint for fetching notices
if (isset($_GET['latest'])) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *'); // Allow CORS for testing; restrict in production
    
    $sql = "SELECT content, importance_level, url 
            FROM notices 
            WHERE status = 'active' 
            ORDER BY importance_level DESC, created_at DESC
            LIMIT 20";
            
    $result = $conn->query($sql);

    $notices = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $notices[] = $row;
        }
        echo json_encode($notices, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database query failed']);
    }
    exit;
}

// Handle form submissions
$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid CSRF token";
    } else {
        $content = trim($_POST['content'] ?? '');
        $importance_level = (int)($_POST['importance_level'] ?? 5);
        $url = filter_var(trim($_POST['url'] ?? ''), FILTER_SANITIZE_URL);
        $schedule = isset($_POST['schedule']);
        $delete_at = $schedule ? ($_POST['delete_at'] ?? null) : null;

        // Validate inputs
        if (empty($content)) {
            $error = "Notice content is required";
        } elseif (strlen($content) > 500) {
            $error = "Content exceeds 500 character limit";
        } elseif ($importance_level < 1 || $importance_level > 10) {
            $error = "Invalid importance level";
        } elseif (!empty($url) && !filter_var($url, FILTER_VALIDATE_URL)) {
            $error = "Invalid URL format";
        } elseif ($schedule && empty($delete_at)) {
            $error = "Deletion time is required when scheduling";
        } else {
            // Prepare statement
            $sql = "INSERT INTO notices (content, importance_level, url, status, created_at, delete_at) 
                    VALUES (?, ?, ?, 'active', NOW(), ?)";
                    
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("siss", $content, $importance_level, $url, $delete_at);

            if ($stmt->execute()) {
                $message = "Notice submitted successfully!";
            } else {
                $error = "Database error: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Handle notice expiration
if (isset($_GET['delete'])) {
    $idToDelete = (int)$_GET['delete'];
    
    $deleteSql = "UPDATE notices SET status = 'expired', importance_level = 1 
                 WHERE id = ? AND status = 'active'";
                 
    $stmt = $conn->prepare($deleteSql);
    $stmt->bind_param("i", $idToDelete);

    if ($stmt->execute()) {
        $message = "Notice marked as expired!";
    } else {
        $error = "Deletion failed: " . $stmt->error;
    }
    $stmt->close();
}

// Generate CSRF token
$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;

// Fetch notices for display
$sql = "SELECT id, content, importance_level, status, url, created_at
        FROM notices 
        ORDER BY importance_level DESC, created_at DESC
        LIMIT 50";
        
$result = $conn->query($sql);
$notices = $result->num_rows > 0 ? $result->fetch_all(MYSQLI_ASSOC) : [];

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Smart Notice Board Management">
    <title>Smart Notice Board</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #00bcd4;
            --secondary: #0288d1;
            --danger: #e63946;
            --success: #4caf50;
            --warning: #ff9800;
            --dark: #333;
            --light: #f8f9fa;
            --gray: #6c757d;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f0f2f5;
            color: #333;
            line-height: 1.6;
        }
        
        nav {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            padding: 1rem 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .logo i {
            font-size: 2rem;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        .welcome {
            color: white;
            font-weight: 500;
            font-size: 1.1rem;
        }
        
        .logout {
            background-color: var(--danger);
            color: white;
            padding: 0.5rem 1.2rem;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .logout:hover {
            background-color: #c1121f;
            transform: translateY(-2px);
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }
        
        .notices-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .notice-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        
        .notice-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            padding: 1.2rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }
        
        .card-body {
            padding: 1.5rem;
            flex-grow: 1;
        }
        
        .card-footer {
            padding: 1rem 1.5rem;
            background-color: #f8f9fa;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .notice-content {
            margin-bottom: 1rem;
            line-height: 1.5;
        }
        
        .notice-url {
            display: inline-block;
            margin-top: 0.5rem;
            color: var(--secondary);
            text-decoration: none;
            font-weight: 500;
        }
        
        .notice-url:hover {
            text-decoration: underline;
        }
        
        .badge {
            padding: 0.35rem 0.75rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .badge-importance {
            background-color: #e3f2fd;
            color: var(--secondary);
        }
        
        .badge-active {
            background-color: #e8f5e9;
            color: var(--success);
        }
        
        .badge-expired {
            background-color: #ffebee;
            color: var(--danger);
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-danger {
            background-color: var(--danger);
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #c1121f;
        }
        
        .btn-add {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 0.8rem 1.5rem;
            font-size: 1.1rem;
            border-radius: 50px;
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            box-shadow: 0 6px 15px rgba(0, 188, 212, 0.4);
            z-index: 10;
        }
        
        .btn-add:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 8px 20px rgba(0, 188, 212, 0.6);
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            animation: modalOpen 0.3s ease;
        }
        
        @keyframes modalOpen {
            from { opacity: 0; transform: translateY(-50px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .modal-header {
            padding: 1.5rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .close-modal {
            background: none;
            border: none;
            color: white;
            font-size: 1.8rem;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .close-modal:hover {
            transform: rotate(90deg);
        }
        
        .modal-body {
            padding: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark);
        }
        
        .form-control {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            transition: border 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 188, 212, 0.2);
        }
        
        .form-range-container {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-top: 0.5rem;
        }
        
        .form-range {
            flex-grow: 1;
            height: 8px;
            border-radius: 4px;
            background: #e9ecef;
            outline: none;
            -webkit-appearance: none;
        }
        
        .form-range::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--primary);
            cursor: pointer;
        }
        
        .form-check {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .form-check-input {
            width: 18px;
            height: 18px;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 0.8rem 1.5rem;
            font-size: 1.1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .btn-submit:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin: 1.5rem 0;
            position: fixed;
            bottom: 1rem;
            right: 1rem;
            max-width: 400px;
            z-index: 1000;
            animation: fadeIn 0.3s ease;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .alert i {
            font-size: 1.5rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            grid-column: 1 / -1;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #ced4da;
            margin-bottom: 1.5rem;
        }
        
        .empty-state h3 {
            color: #6c757d;
            margin-bottom: 1rem;
        }
        
        .timestamp {
            font-size: 0.85rem;
            color: var(--gray);
        }
        
        @media (max-width: 768px) {
            .nav-container {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .user-info {
                width: 100%;
                justify-content: center;
            }
            
            .notices-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav>
        <div class="nav-container">
            <a href="#" class="logo">
                <i class="fas fa-chalkboard"></i>
                <span>Smart Notice Board</span>
            </a>
            <div class="user-info">
                <span class="welcome">
                    <i class="fas fa-user-circle"></i>
                    <?= htmlspecialchars($_SESSION['username']) ?>
                </span>
                <a href="logout.php" class="logout">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <h2>Manage Notices</h2>
        
        <?php if (count($notices) > 0): ?>
            <div class="notices-grid">
                <?php foreach ($notices as $notice): ?>
                    <div class="notice-card">
                        <div class="card-header">
                            <h3>Notice #<?= $notice['id'] ?></h3>
                        </div>
                        <div class="card-body">
                            <p class="notice-content"><?= htmlspecialchars($notice['content']) ?></p>
                            <?php if (!empty($notice['url'])): ?>
                                <a href="<?= htmlspecialchars($notice['url']) ?>" 
                                   class="notice-url" 
                                   target="_blank">
                                    <i class="fas fa-link"></i> More information
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer">
                            <div class="meta">
                                <span class="badge badge-importance">
                                    <i class="fas fa-star"></i> 
                                    Importance: <?= $notice['importance_level'] ?>
                                </span>
                                <span class="badge badge-<?= $notice['status'] ?>">
                                    <?= $notice['status'] === 'active' ? 
                                        '<i class="fas fa-check-circle"></i> Active' : 
                                        '<i class="fas fa-ban"></i> Expired' ?>
                                </span>
                                <div class="timestamp">
                                    <i class="far fa-clock"></i>
                                    <?= date('M d, Y H:i', strtotime($notice['created_at'])) ?>
                                </div>
                            </div>
                            <?php if ($notice['status'] === 'active'): ?>
                                <a href="?delete=<?= $notice['id'] ?>" 
                                   class="btn btn-danger"
                                   onclick="return confirm('Are you sure you want to expire this notice?');">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="far fa-clipboard"></i>
                <h3>No Notices Available</h3>
                <p>Get started by adding your first notice</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Add Notice Button -->
    <button class="btn btn-add" id="openModal">
        <i class="fas fa-plus"></i> Add Notice
    </button>

    <!-- Add Notice Modal -->
    <div class="modal" id="addModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add New Notice</h3>
                <button class="close-modal" id="closeModal">×</button>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    
                    <div class="form-group">
                        <label class="form-label" for="content">Notice Content *</label>
                        <textarea class="form-control" id="content" name="content" 
                                  rows="4" maxlength="500" required
                                  placeholder="Enter notice content..."></textarea>
                        <small class="text-muted">Max 500 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="url">Related URL (Optional)</label>
                        <input type="url" class="form-control" id="url" name="url" 
                               placeholder="https://example.com">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="importance_level">
                            Importance Level: <span id="levelValue">5</span>
                        </label>
                        <div class="form-range-container">
                            <i class="fas fa-star" style="color: #ffc107;"></i>
                            <input type="range" class="form-range" id="importance_level" 
                                   name="importance_level" min="1" max="10" value="5"
                                   oninput="document.getElementById('levelValue').textContent = this.value">
                            <i class="fas fa-star" style="color: #ffc107;"></i>
                        </div>
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="schedule" 
                               name="schedule" onchange="toggleSchedule(this)">
                        <label class="form-label" for="schedule">Schedule Automatic Expiration</label>
                    </div>
                    
                    <div class="form-group" id="scheduleFields" style="display: none;">
                        <label class="form-label" for="delete_at">Expiration Date & Time *</label>
                        <input type="datetime-local" class="form-control" id="delete_at" name="delete_at">
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane"></i> Submit Notice
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Status Messages -->
    <?php if (!empty($message)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <div><?= htmlspecialchars($message) ?></div>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <div><?= htmlspecialchars($error) ?></div>
        </div>
    <?php endif; ?>

    <script>
        // Modal functionality
        const modal = document.getElementById('addModal');
        const openBtn = document.getElementById('openModal');
        const closeBtn = document.getElementById('closeModal');
        
        openBtn.addEventListener('click', () => {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        });
        
        closeBtn.addEventListener('click', () => {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        });
        
        window.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }
        });
        
        // Toggle schedule fields
        function toggleSchedule(checkbox) {
            const scheduleFields = document.getElementById('scheduleFields');
            scheduleFields.style.display = checkbox.checked ? 'block' : 'none';
        }
        
        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.display = 'none';
            });
        }, 5000);
    </script>
</body>
</html>