<?php
session_start();

// Check if user is logged in and is an officer
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['USER_TYPE'] !== 'engineer') {
    header("location: login.php");
    exit;}

// Include the database connection file
include 'partials/connect.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_quantities = $_POST['new_quantities'];

    foreach ($new_quantities as $key => $new_quantity) {
        list($oem, $model, $cartridge) = explode('_', $key);

        // Retrieve the current quantity
        $stmt = $conn->prepare("SELECT QUANTITY FROM current_stock WHERE OEM = ? AND MODEL = ? AND CARTRIDGE = ?");
        $stmt->bind_param("sss", $oem, $model, $cartridge);
        $stmt->execute();
        $stmt->bind_result($current_quantity);
        $stmt->fetch();
        $stmt->close();

        // Calculate the new quantity
        $total_quantity = $current_quantity + $new_quantity;

        // Update the quantity in the database
        $stmt = $conn->prepare("UPDATE current_stock SET QUANTITY = ? WHERE OEM = ? AND MODEL = ? AND CARTRIDGE = ?");
        $stmt->bind_param("isss", $total_quantity, $oem, $model, $cartridge);

        // Execute the statement
        if ($stmt->execute()) {
            $success_message = "Stock updated successfully.";
        } else {
            $error_message = "Error updating stock.";
        }

        // Close the statement
        $stmt->close();
    }
}

// Fetch data from current_stock table
$sql = "SELECT OEM, MODEL, CARTRIDGE, QUANTITY FROM current_stock";
$result = $conn->query($sql);

$cartridges = [];
if ($result->num_rows > 0) {
    // Output data of each row
    while ($row = $result->fetch_assoc()) {
        $cartridges[] = [
            'OEM' => $row['OEM'],
            'MODEL' => $row['MODEL'],
            'CARTRIDGE' => $row['CARTRIDGE'],
            'QUANTITY' => $row['QUANTITY']
        ];
    }
} else {
    echo "0 results";
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Cartridge Stock</title>
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
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
        input[type="number"] {
            padding: 5px;
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
        <div><h2>Update Current stock</h2></div>
        <ul>
            <li><a href="engineer.php">Home</a></li>
            <li><a href="#" onclick="confirmLogout(event)">Logout</a></li>        </ul>
    </nav>

    <div class="container">
        <h1>Update Cartridge Stock</h1>

        <?php if (isset($success_message)): ?>
            <div class="notification"><?= htmlspecialchars($success_message) ?></div>
        <?php elseif (isset($error_message)): ?>
            <div class="error"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <form action="updatestock.php" method="POST">
            <table>
                <tr>
                    <th>OEM</th>
                    <th>MODEL</th>
                    <th>CARTRIDGE</th>
                    <th>QUANTITY</th>
                    <th>NEW QUANTITY</th>
                </tr>
                <?php foreach ($cartridges as $cartridge): ?>
                    <tr>
                        <td><?= htmlspecialchars($cartridge['OEM']) ?></td>
                        <td><?= htmlspecialchars($cartridge['MODEL']) ?></td>
                        <td><?= htmlspecialchars($cartridge['CARTRIDGE']) ?></td>
                        <td><?= htmlspecialchars($cartridge['QUANTITY']) ?></td>
                        <td>
                            <input type="number" name="new_quantities[<?= htmlspecialchars($cartridge['OEM'].'_'.$cartridge['MODEL'].'_'.$cartridge['CARTRIDGE']) ?>]" value="0" min="0">
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <div style="text-align: center; margin: 20px;">
                <button type="submit">Update Stock</button>
            </div>
        </form>
    </div>
    <script src="partials/logout.js"></script>

</body>
</html>
