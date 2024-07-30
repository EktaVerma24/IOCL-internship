<?php
session_start();

// Check session and user type
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['USER_TYPE'] !== 'engineer') {
    header("location: login.php");
    exit;
}

// Set the target month for the reminder (1 for January, 2 for February, etc.)
$targetMonth = 11; // November

// Get the current month
$currentMonth = date('n');

// Check if the current month matches the target month (November)
if ($currentMonth == $targetMonth) {
    // Database connection parameters
    include 'partials/connect.php';

    // Query to fetch current stock information
    $sql = "SELECT * FROM stock_table";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // Fetch and format stock information
        $stockInformation = "Current Stock Information for November:\n";
        while ($row = $result->fetch_assoc()) {
            $stockInformation .= "- " . $row["OEM"]."-" .$row["MODEL"]. $row["CARTRDIGE"] . ": " . $row["QUANTITY"] . "\n";
        }

        // Officer's email address
        $officerEmail = 'officer@example.com';

        // Email subject and message
        $subject = 'Monthly Stock Reminder';
        $message = "Dear Officer,\n\nThis is a reminder to update the current stock levels for November.\n\n$stockInformation\n\nRegards,\nYour Company";

        // Send the email
        $headers = 'From: ektaverma241204@gmail.com' . "\r\n" .
                   'Reply-To: ektaverma241204@gmail.com' . "\r\n" .
                   'X-Mailer: PHP/' . phpversion();

        // Send the email using mail() function
        if (mail($officerEmail, $subject, $message, $headers)) {
            echo "Reminder email sent successfully.";
        } else {
            echo "Failed to send reminder email.";
        }
    } else {
        echo "No stock information found.";
    }

    // Close MySQL connection
    $conn->close();
}
?> 

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Stock Reminder</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        html, body {
            height: 100%;
            margin: 0;
            font-family: Tahoma, sans-serif;
            background-color: #f9f9f9;
        }

        body {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            height: 100%;
            padding: 10px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .navbar {
            background-color: #031854;
            padding: 15px 20px;
            border-top-right-radius: 12px;
            border-top-left-radius: 12px;
            margin-bottom: 0px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #ffffff;
        }

        .navbar h2 {
            margin: 0;
            font-size: 24px;
        }

        .navbar a {
            color: #ffffff;
            text-decoration: none;
            font-size: 16px;
            padding: 10px 15px;
            border-radius: 8px;
            transition: background-color 0.3s ease;
        }

        .navbar a:hover {
            background-color: #FF4900;
        }

        .user-greet {
            display: flex;
            align-items: center;
            /* justify-content: center; */
            gap: 10vw;
            background-color: #FF4900;
            padding: 10px;
            border-bottom-right-radius: 12px;
            border-bottom-left-radius: 12px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 18px;
            color: #ffffff;
        }

        .iug {
            display: flex;
            height: 2.5vw;
            width: 100vw;
            gap: 10vw;
            align-items: center;
            /* justify-content: center; */

        }

        .welcome {
            margin-left: 16vw;
        }

        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            flex-grow: 1;
        }

        .grid-item {
            background-color: #ffffff;
            color: orangered;
            padding: 100px;
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            transition: transform 0.3s ease, background-color 0.3s ease, border-radius 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border-color: #FF4900;
        }

        .grid-item i {
            font-size: 40px;
            margin-bottom: 10px;
        }

        .grid-item:hover {
            background-color: #FF4900;
            transform: translateY(-5px);
           
            border-radius: 5%;
            color: #ffff;
        
        }

        .grid-item:active {
            transform: translateY(0);
        }
        .navbar img {
            height: 100px;
            margin-right: 0px;
        }
    
    </style>
</head>

<body>
    <div class="container">
        <div>
            <div class="navbar">
                <img src="IOL.gif" alt="logo">
                <h1 style="font-family:'Franklin Gothic Medium', 'Arial Narrow', Arial, sans-serif;margin-right:0px;">CARTRIDGE STOCK MANAGEMENT</h1>
                <a href="#" onclick="confirmLogout(event)">LOGOUT</a>            </div>

            <div class="user-greet">

                <div class="iug">
                    <h2>User Dashboard</h2>

                    <div class="welcome"> Welcome, <?php echo $_SESSION['USER']; ?>!
                    </div>

                </div>




            </div>
        </div>

        <div class="grid-container">
            <div class="grid-item" onclick="location.href='confirmrequests.php';">
                <i class="fas fa-check-circle"></i>
                View Requests
            </div>
            <div class="grid-item" onclick="location.href='confirmcomplaints.php';">
                <i class="fas fa-exclamation-circle"></i>
                View Complaints
            </div>
            <div class="grid-item" onclick="location.href='updatestock.php';">
                <i class="fas fa-edit"></i>
                Update Stock
            </div>
            <div class="grid-item" onclick="location.href='access.php';">
            <i class="fas fa-user"></i>
                User Details
            </div>
            <div class="grid-item" onclick="location.href='editengineerdetails.php';">
            <i class="fas fa-edit"></i>
                Edit My Details
            </div>
        </div>
    </div>
    <script src="partials/logout.js"></script>

</body>
</html>
