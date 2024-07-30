<?php
session_start();

// Check session and user type
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['USER_TYPE'] !== 'officer') {
    header("location: login.php");
    exit;
}

$message = '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Officer Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        html, body {
            height: 100%;
            margin: 0;
            font-family: 'Roboto', sans-serif;
            background-color: #f9f9f9;
        }

        body {
            font-family: Tahoma, sans-serif;
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
                <a href="#" onclick="confirmLogout(event)">LOGOUT</a>            </div>

            <div class="user-greet">
                <div class="iug">
                    <h2>Officer Dashboard</h2>
                    <div class="welcome"> Welcome, <?php echo $_SESSION['USER']; ?>!
                    </div>
                </div>
            </div>
        </div>

        <div class="grid-container">
            
            <div class="grid-item" onclick="location.href='viewemptystock.php';">
                <i class="fas fa-box-open"></i>
                View Current Cartridge Stock
            </div>
          
            <div class="grid-item" onclick="location.href='viewrequests.php';">
                <i class="fas fa-edit"></i>
             View All Requests
            </div>
            <div class="grid-item" onclick="location.href='viewcomplaints.php';">
                <i class="fas fa-exclamation-circle"></i>
                View All Complaints
            </div>
            <div class="grid-item" onclick="location.href='editofficer.php';">
                <i class="fas fa-edit"></i>
                Edit My Details
            </div>
        </div>
    </div>
    <script src="partials/logout.js"></script>

</body>

</html>
