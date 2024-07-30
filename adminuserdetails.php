<?php
session_start();

// Check if the user is logged in and is an admin, otherwise redirect to the welcome page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true ) {
    header("location: admin.php");
    exit;
}

include 'partials/connect.php'; // Include database connection file

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['search'])) {
        // Search for user by ID or username
        $search_term = $_POST['search_term'];
        $sql_search = "SELECT * FROM user_details WHERE USER_ID = ? OR USER = ?";
        $stmt_search = $conn->prepare($sql_search);
        $stmt_search->bind_param("ss", $search_term, $search_term);
        $stmt_search->execute();
        $result_search = $stmt_search->get_result();
        
        if ($result_search->num_rows > 0) {
            $user = $result_search->fetch_assoc();
            // Fetch printer data for the user
            $user_id = $user['USER_ID'];
            $sql_printer_data = "SELECT * FROM user_printer_data WHERE USER_ID = ?";
            $stmt_printer_data = $conn->prepare($sql_printer_data);
            $stmt_printer_data->bind_param("s", $user_id);
            $stmt_printer_data->execute();
            $result_printer_data = $stmt_printer_data->get_result();
            
            if ($result_printer_data->num_rows > 0) {
                $printer_data = $result_printer_data->fetch_assoc();
            } else {
                $message = "Printer data not found.";
            }
            
            $stmt_printer_data->close();
        } else {
            $message = "User not found.";
        }
        
        $stmt_search->close();
    } elseif (isset($_POST['update_user_type'])) {
        // Update user type
        $user_id = $_POST['user_id'];
        $new_user_type = $_POST['user_type'];

        // Fetch current user type
        $sql_current = "SELECT USER_TYPE FROM user_details WHERE USER_ID = ?";
        $stmt_current = $conn->prepare($sql_current);
        $stmt_current->bind_param("s", $user_id);
        $stmt_current->execute();
        $result_current = $stmt_current->get_result();
        $current_data = $result_current->fetch_assoc();
        $current_user_type = $current_data['USER_TYPE'];

        if ($current_user_type === $new_user_type) {
            $message = "No changes made.";
        } else {
            $sql_update = "UPDATE user_details SET USER_TYPE = ? WHERE USER_ID = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("ss", $new_user_type, $user_id);
            
            if ($stmt_update->execute()) {
                $message = "User type updated successfully.";
            } else {
                $message = "Error updating user type: " . $conn->error;
            }
            
            $stmt_update->close();
        }

        $stmt_current->close();
    } elseif (isset($_POST['update_printer_data'])) {
        // Update printer data
        $user_id = $_POST['user_id'];
        $new_oem = $_POST['oem'];
        $new_model = $_POST['model'];

        // Fetch current printer data
        $sql_current_printer = "SELECT OEM, MODEL FROM user_printer_data WHERE USER_ID = ?";
        $stmt_current_printer = $conn->prepare($sql_current_printer);
        $stmt_current_printer->bind_param("s", $user_id);
        $stmt_current_printer->execute();
        $result_current_printer = $stmt_current_printer->get_result();
        $current_printer_data = $result_current_printer->fetch_assoc();
        $current_oem = $current_printer_data['OEM'];
        $current_model = $current_printer_data['MODEL'];

        if ($current_oem === $new_oem && $current_model === $new_model) {
            $message = "No changes made.";
        } else {
            $sql_update_printer = "UPDATE user_printer_data SET OEM = ?, MODEL = ? WHERE USER_ID = ?";
            $stmt_update_printer = $conn->prepare($sql_update_printer);
            $stmt_update_printer->bind_param("sss", $new_oem, $new_model, $user_id);
            
            if ($stmt_update_printer->execute()) {
                $message = "Printer data updated successfully.";
            } else {
                $message = "Error updating printer data: " . $conn->error;
            }
            
            $stmt_update_printer->close();
        }

        $stmt_current_printer->close();
    }
}

// Close database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign User Type</title>
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
            padding: 15px 10px;
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
            height: 60px; /* Ensure navbar has a consistent height */
        }

        .navbar h2 {
            margin: 0;
            font-size: 24px;
        }

        .navbar a {
            color: #ffffff;
            text-decoration: none;
            font-size: 16px;
            padding: 10px 20px;
            border-radius: 8px;
            transition: background-color 0.3s ease;
            margin-right: 20px;
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

        .form-container input[type="text"] {
            width: 100%;
            padding: 12px;
            border-radius: 5px;
            border: 1px solid #ccc;
            margin-bottom: 10px;
        }

        .form-container input[type="submit"] {
            background-color: #031854;
            color: #ffffff;
            border: none;
            padding: 12px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.3s ease;
            margin-top: 10px;
        }

        .form-container input[type="submit"]:hover {
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

        .user-details {
            margin-top: 0px;
            text-align: left;
            padding: 20px;
            background-color: #f4f6f8;
            border-radius: 8px;
        }

        .user-details h2 {
            color: #031854;
            font-size: 24px;
            margin-bottom: 10px;
        }

        .user-details p {
            font-size: 16px;
            margin: 5px 0;
        }

        .user-details label {
            display: block;
            margin-top: 10px;
            font-size: 16px;
        }

        .user-details select, .user-details input[type="text"] {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 16px;
        }

        .user-details button {
            background-color: #031854;
            color: #ffffff;
            border: none;
            padding: 10px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.3s ease;
            margin-top: 20px;
        }

        .user-details button:hover {
            background-color: #FF4900;
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h2>User Details</h2>
        <div>
            <a href="admin.php">Home</a>
            <a href="#" onclick="confirmLogout(event)">Logout</a>        </div>
    </div>

    <div class="container">
        <div class="form-container">
            <h1>Search User</h1>
            <div class="search">
                <form method="post">
                    <input style="width: 30vh;" type="text" name="search_term" placeholder="Enter User ID or Username" required>
                    <input type="submit" name="search" value="Search">
                </form>
            </div>

            <?php if (!empty($message)) : ?>
                <div class="message">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($user)) : ?>
                <div class="user-details">
                    <h2>User Details</h2>
                    <form method="post">
                        <p><strong>ID:</strong> <?php echo $user['USER_ID']; ?></p>
                        <p><strong>Username:</strong> <?php echo $user['USER']; ?></p>
                        <p><strong>Current User Type:</strong> <?php echo $user['USER_TYPE']; ?></p>
                        <input style="width: 30vh;" type="hidden" name="user_id" value="<?php echo $user['USER_ID']; ?>">
                        <label for="user_type">Change User Type:</label>
                        <select style="width: 34vh;" name="user_type" id="user_type" required>
                            <option value="engineer" <?php echo $user['USER_TYPE'] === 'engineer' ? 'selected' : ''; ?>>Engineer</option>
                            <option value="officer" <?php echo $user['USER_TYPE'] === 'officer' ? 'selected' : ''; ?>>Officer</option>
                            <option value="user" <?php echo $user['USER_TYPE'] === 'user' ? 'selected' : ''; ?>>User</option>
                        </select>
                        <button style="margin-left: 20px;" type="submit" name="update_user_type">Update User Type</button>
                    </form>

                    <?php if (isset($printer_data)) : ?>
                        <h2>Printer Details</h2>
                        <form method="post">
                            <input style="width: 30vh;" type="hidden" name="user_id" value="<?php echo $user['USER_ID']; ?>">
                            <label for="oem">OEM:</label>
                            <input style="width: 30vh;" type="text" id="oem" name="oem" value="<?php echo $printer_data['OEM']; ?>" required>
                            <label for="model">Model:</label>
                            <input style="width: 30vh;" type="text" id="model" name="model" value="<?php echo $printer_data['MODEL']; ?>" required>
                            <button style="margin-left: 20px;" type="submit" name="update_printer_data">Update Printer Data</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script src="partials/adminlogout.js"></script>

</body>
</html>
