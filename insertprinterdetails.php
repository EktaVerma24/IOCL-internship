<?php
session_start();

// Check if the user is logged in and is an admin, otherwise redirect to the welcome page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: admin.php");
    exit;
}

include 'partials/connect.php'; // Include database connection file

$message = '';
$user = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['search'])) {
        // Search for user by ID or username
        $search_type = $_POST['search_type'];
        $search_term = $_POST['search_term'];
        
        if ($search_type == 'id') {
            $sql_search = "SELECT * FROM user_details WHERE USER_ID = ?";
        } else {
            $sql_search = "SELECT * FROM user_details WHERE USER = ?";
        }
        
        $stmt_search = $conn->prepare($sql_search);
        $stmt_search->bind_param("s", $search_term);
        $stmt_search->execute();
        $result_search = $stmt_search->get_result();
        
        if ($result_search->num_rows > 0) {
            $user = $result_search->fetch_assoc();
        } else {
            $message = "User not found.";
        }
        
        $stmt_search->close();
    } elseif (isset($_POST['add_printer_data'])) {
        // Add printer data
        $user_id = $_POST['user_id'];
        $user_name = $_POST['user']; // Renamed variable to avoid confusion
        $oem = $_POST['oem'];
        $model = $_POST['model'];
        
        // Check if printer data already exists for the user
        $sql_check_printer = "SELECT * FROM user_printer_data WHERE USER_ID = ?";
        $stmt_check_printer = $conn->prepare($sql_check_printer);
        $stmt_check_printer->bind_param("s", $user_id);
        $stmt_check_printer->execute();
        $result_check_printer = $stmt_check_printer->get_result();
        
        if ($result_check_printer->num_rows > 0) {
            $message = "Printer data already exists for this user.";
        } else {
            // Insert new printer data
            $sql_insert_printer = "INSERT INTO user_printer_data (USER_ID, USER, OEM, MODEL) VALUES (?, ?, ?, ?)";
            $stmt_insert_printer = $conn->prepare($sql_insert_printer);
            $stmt_insert_printer->bind_param("ssss", $user_id, $user_name, $oem, $model);
            
            if ($stmt_insert_printer->execute()) {
                $message = "Printer data added successfully.";
            } else {
                $message = "Error adding printer data: " . $conn->error;
            }
            
            $stmt_insert_printer->close();
        }
        
        $stmt_check_printer->close();
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
    <title>Add Printer Data</title>
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
            padding: 10px 20px;
            border-radius: 8px;
            transition: background-color 0.3s ease;
            margin-right: 10px;
        }

        .navbar a:hover {
            background-color: #FF4900;
            border-radius: 5px;
        }

        .container {
            min-height: 70vh;
            width: 100%;
            max-width: 600px;
            border-radius: 12px;
            /* box-shadow: 0 0 15px rgba(0, 0, 0, 0.1); */
            padding: 20px;
            text-align: center;
            margin-top: 80px;
        }

        .form-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            background-color: #ffffff;

            justify-content: center;
            /* margin-top: 90px; */
            min-height: 80vh;
        }

        .form-container h1 {
            color: #031854;
            margin-bottom: 20px;
            font-size: 28px;
        }

        .form-container input[type="text"], .form-container select {
            width: 80%;
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
            width: 35vw;
            min-height: 50vh;
            margin-top: 2vh;
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

        .user-details input[type="text"], .user-details select {
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
        .in{
            width: 60px;
        }

        /* Dropdown styling */
        .form-container select {
            background-color: #ffffff;
            color: #333;
            font-size: 16px;
            cursor: pointer;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .form-container select:focus {
            border-color: #031854;
            box-shadow: 0 0 5px rgba(0, 50, 100, 0.3);
            outline: none;
        }

        .form-container option {
            padding: 10px;
            height: 20px;
        }

        .form-container select {
            background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxOCIgaGVpZ2h0PSIxOCI+PHBhdGggZD0iTTEwLjUgMy4yNWw2LTIuMDFsLTYuMTMgNi4xMCIgc3Ryb2tlLXdpZHRoPSIxIiBzdHJva2UtY29sb3I9IiMwMzE4NTYiLz48L3N2Zz4=');
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 1rem auto;
            padding-right: 2.5rem;
        }

        .form-container select::-ms-expand {
            display: none; /* Hides default dropdown arrow in IE */
        }

        .formbear{
            width: full;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h2>Add Printer Data</h2>
        <div>
            <a href="admin.php">Home</a>
            <a href="#" onclick="confirmLogout(event)">Logout</a>        </div>
    </div>

    <div class="container">
        <div class="form-container">
            <h1>Search User</h1>
            <form method="post">
                <select name="search_type" required>
                    <option value="id">Search by User ID</option>
                    <option value="username">Search by Username</option>
                </select>
                <input type="text" name="search_term" placeholder="Enter User ID or Username" required>
                <input type="submit" name="search" value="Search">
            </form>

            <?php if (!empty($message)) : ?>
                <div class="message">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($user)) : ?>
                <div class="user-details">
                    <h2>User Details</h2>
                    <p><strong>ID:</strong> <?php echo $user['USER_ID']; ?></p>
                    <p><strong>Username:</strong> <?php echo $user['USER']; ?></p>

                    <h2>Add Printer Data</h2>
                    <form class="formbear" method="post">
                        <input type="hidden" name="user_id" value="<?php echo $user['USER_ID']; ?>">
                        <input type="hidden" name="user" value="<?php echo $user['USER']; ?>">
                        <input class="in" type="text" name="oem" placeholder="Enter OEM" required>
                        <input class="in" type="text" name="model" placeholder="Enter Model" required>
                        <input type="submit" name="add_printer_data" value="Add Printer Data">
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script src="partials/adminlogout.js"></script>

</body>
</html>
