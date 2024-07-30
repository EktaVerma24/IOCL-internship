<?php
session_start();
include 'partials/connect.php'; // Include database connection file

// Initialize variables
$showMessage = "";
$showError = "";

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['USER_TYPE'] !== 'engineer') {
    header("location: engineer.php");
    exit;
}

$username = $_SESSION['USER'];
$email = $_SESSION['EMAIL_ID']; // Assuming the email is stored in session

// Handle form submission for email update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_email'])) {
    $current_password = $_POST['current_password'];
    $new_email = $_POST['new_email'];

    // Prepare and bind statement for fetching user details
    $stmt = $conn->prepare("SELECT PASSWORD FROM user_details WHERE USER = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    // Verify the current password directly
    if ($current_password === $user['PASSWORD']) {
        // Prepare and bind statement for updating email
        $stmt = $conn->prepare("UPDATE user_details SET EMAIL_ID = ? WHERE USER = ?");
        $stmt->bind_param("ss", $new_email, $username);

        if ($stmt->execute()) {
            $showMessage = "Email updated successfully!";
            $_SESSION['EMAIL_ID'] = $new_email; // Update session email
        } else {
            $showError = "Error updating email: " . $stmt->error;
        }

        $stmt->close();
    } else {
        $showError = "Incorrect current password!";
    }
}

// Handle form submission for password update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Check if new passwords match
    if ($new_password !== $confirm_password) {
        $showError = "Passwords do not match!";
    } else {
        // Prepare and bind statement for fetching user details
        $stmt = $conn->prepare("SELECT PASSWORD FROM user_details WHERE USER = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        // Verify the current password directly
        if ($current_password === $user['PASSWORD']) {
            // Prepare and bind statement for updating password
            $stmt = $conn->prepare("UPDATE user_details SET PASSWORD = ? WHERE USER = ?");
            $stmt->bind_param("ss", $new_password, $username);

            if ($stmt->execute()) {
                $showMessage = "Password changed successfully!";
            } else {
                $showError = "Error changing password: " . $stmt->error;
            }

            $stmt->close();
        } else {
            $showError = "Incorrect current password!";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        html, body {
            height: 100%;
            margin: 0;
            font-family: Tahoma, sans-serif;
            background-color: #f4f6f8;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .navbar {
            background-color: #031854;
            width: 100%;
            padding: 10px 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #ffffff;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 10px;
            height: 60px;
        }

        .navbar h2 {
            margin: 0;
            font-size: 24px;
        }

        .navbar a {
            color: #ffffff;
            text-decoration: none;
            font-size: 16px;
            padding: 10px 10px;
            border-radius: 8px;
            transition: background-color 0.3s ease;
            margin-right: 20px;
            border-radius: 5px;
         }

        .navbar a:hover {
            background-color: #FF4900;
            border-radius: 5px;
        }

        .container {
            width: 100%;
            max-width: 600px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
            text-align: center;
            margin-top: 80px; /* Adjust based on navbar height */
        }

        .form-container {
            margin-top: 10px;
        }

        .form-container h1 {
            color: #031854;
            margin-bottom: 20px;
            font-size: 28px;
        }

        .form-container input[type="email"],
        .form-container input[type="password"] {
            width: calc(100% - 22px);
            padding: 12px;
            border-radius: 5px;
            border: 1px solid #ccc;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .form-container button {
            background-color: #031854;
            color: #ffffff;
            border: none;
            padding: 12px 20px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.3s ease;
            margin-top: 10px;
        }

        .form-container button:hover {
            background-color: #FF4900;
            transform: translateY(-5px);
        }

        .message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
            padding: 10px;
            margin-top: 20px;
            text-align: center;
            font-size: 16px;
            transition: opacity 0.3s ease;
        }

        .message-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h2>Profile Management</h2>
        <div>
            <a href="engineer.php">Home</a>
            <a href="#" onclick="confirmLogout(event)">Logout</a>        </div>
    </div>

    <div class="container">
        <div class="form-container">
            <!-- Change Email Form -->
            <h1>Change Email</h1>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                <input type="email" id="new_email" name="new_email" placeholder="New Email" required>
                <input type="password" id="current_password" name="current_password" placeholder="Current Password" required>
                <button type="submit" name="update_email">Update Email</button>
                <?php if ($showMessage && !isset($_GET['action'])): ?>
                    <div class="message"><?= htmlspecialchars($showMessage) ?></div>
                <?php endif; ?>
                <?php if ($showError && !isset($_GET['action'])): ?>
                    <div class="message message-error"><?= htmlspecialchars($showError) ?></div>
                <?php endif; ?>
            </form>

            <!-- Change Password Form -->
            <h1>Change Password</h1>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                <input type="password" id="new_password" name="new_password" placeholder="New Password" required>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm New Password" required>
                <input type="password" id="current_password" name="current_password" placeholder="Current Password" required>
                <button type="submit" name="update_password">Update Password</button>
                <?php if ($showMessage && !isset($_GET['action'])): ?>
                    <div class="message"><?= htmlspecialchars($showMessage) ?></div>
                <?php endif; ?>
                <?php if ($showError && !isset($_GET['action'])): ?>
                    <div class="message message-error"><?= htmlspecialchars($showError) ?></div>
                <?php endif; ?>
            </form>
        </div>
    </div>
    <script src="partials/logout.js"></script>

</body>
</html>
