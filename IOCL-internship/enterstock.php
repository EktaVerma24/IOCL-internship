<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true ) {
    header("location: admin.php");
    exit;
}
// Include the database connection file
include 'partials/connect.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $oem = $_POST['oem'];
    $model = $_POST['model'];
    $cartridge = $_POST['cartridge'];
    $quantity = $_POST['quantity'];

    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO current_stock (OEM, MODEL, CARTRIDGE, QUANTITY) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $oem, $model, $cartridge, $quantity);

    // Execute the statement
    if ($stmt->execute()) {
        $success_message = "New stock added successfully.";
    } else {
        $error_message = "Error adding new stock.";
    }

    // Close the statement
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Cartridge Stock</title>
    <style>
        body {
            font-family: Tahoma, sans-serif;
            background-color: #f0f0f0;
            margin: 0;
            padding: 0;
        }
        nav {
            background-color: #031854;
            color: #fff;
            padding: 10px;
            width: 100%;
            box-sizing: border-box;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        nav ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
            display: flex;
        }
        nav ul li {
            margin-right: 10px;
        }
        nav ul li a {
            color: #fff;
            text-decoration: none;
            padding: 10px 15px;
            display: block;
        }
        nav ul li a:hover {
            background-color: #FF4900;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        .container {
            width: 80%;
            margin: 20px auto;
            background-color: #fff;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
        }
        h1 {
            text-align: center;
            color: #031854;
        }
        .notification {
            background-color: #4CAF50;
            color: white;
            text-align: center;
            padding: 10px;
            margin-bottom: 15px;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            text-align: center;
            padding: 10px;
            margin-bottom: 15px;
        }
        form {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        input[type="text"], input[type="number"] {
            padding: 10px;
            margin: 10px 0;
            width: 80%;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
        }
        button {
            background-color: #031854;
            color: #fff;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 4px;
            transition: background-color 0.3s ease;
            font-size: 16px;
        }
        button:hover {
            background-color: #FF4900;
        }
    </style>
</head>
<body>
    <nav>
        <div><h2>Add New Cartridge Stock</h2></div>
        <ul>
            <li><a href="admin.php">Home</a></li>
           <li><a href="#" onclick="confirmLogout(event)">Logout</a></li>         </ul>
    </nav>

    <div class="container">
        <h1>Add New Cartridge Stock</h1>

        <?php if (isset($success_message)): ?>
            <div class="notification"><?= htmlspecialchars($success_message) ?></div>
        <?php elseif (isset($error_message)): ?>
            <div class="error"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <form action="enterstock.php" method="POST">
            <input type="text" name="oem" placeholder="OEM" required>
            <input type="text" name="model" placeholder="Model" required>
            <input type="text" name="cartridge" placeholder="Cartridge" required>
            <input type="number" name="quantity" placeholder="Quantity" required min="0">
            <button type="submit">Add Stock</button>
        </form>
    </div>
    <script src="partials/adminlogout.js"></script>

</body>
</html>
