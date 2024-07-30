<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database connection file
include 'partials/connect.php';

// Function to fetch cartridges based on filters
function getFilteredCartridges($filter_oem = null, $filter_model = null) {
    global $conn;
    $threshold = 10;
    $conditions = [];

    // Fetch current stock from the current_stock table
    $sql = "SELECT OEM, MODEL, CARTRIDGE, QUANTITY, alert_sent FROM current_stock";
    
    if ($filter_oem || $filter_model) {
        if ($filter_oem) {
            $conditions[] = "OEM LIKE ?";
        }
        if ($filter_model) {
            $conditions[] = "MODEL LIKE ?";
        }
        $sql .= " WHERE " . implode(' AND ', $conditions);
    }
    
    $stmt = $conn->prepare($sql);
    
    if ($filter_oem && $filter_model) {
        $filter_oem = '%' . $filter_oem . '%';
        $filter_model = '%' . $filter_model . '%';
        $stmt->bind_param('ss', $filter_oem, $filter_model);
    } elseif ($filter_oem) {
        $filter_oem = '%' . $filter_oem . '%';
        $stmt->bind_param('s', $filter_oem);
    } elseif ($filter_model) {
        $filter_model = '%' . $filter_model . '%';
        $stmt->bind_param('s', $filter_model);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $cartridges = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $cartridges[] = $row;
        }
    }
    return $cartridges;
}

// Handle filtering actions
$filter_oem = $_GET['filter_oem'] ?? null;
$filter_model = $_GET['filter_model'] ?? null;

// Fetch cartridges based on filters
$cartridges = getFilteredCartridges($filter_oem, $filter_model);

// Define the threshold value
$threshold = 10;

// Update alert_sent if quantity is above the threshold
foreach ($cartridges as $cartridge) {
    if ($cartridge['QUANTITY'] >= $threshold && $cartridge['alert_sent'] == 1) {
        $updateSql = "UPDATE current_stock SET alert_sent = 0 WHERE OEM = ? AND MODEL = ? AND CARTRIDGE = ?";
        $stmt = $conn->prepare($updateSql);
        $stmt->bind_param('sss', $cartridge['OEM'], $cartridge['MODEL'], $cartridge['CARTRIDGE']);
        $stmt->execute();
    }
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cartridge Stock</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: Tahoma, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .navbar {
            background-color: #031854;
            padding: 15px 20px;
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #ffffff;
        }
        .navbar h3 {
            margin-left: 20px;
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
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 20px auto;
            background-color: #ffffff;
            padding: 20px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
        }
        h1 {
            text-align: center;
            color: #031854;
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
        .low-stock {
            background-color: #f8d7da; /* Light red */
        }
        .filter-section {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .filter-section label {
            margin-right: 10px;
        }
        .filter-section input[type="text"] {
            padding: 10px;
            margin-right: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .filter-section button {
            padding: 10px 20px;
            background-color: #031854;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: background-color 0.3s ease;
        }
        .filter-section button:hover {
            background-color: #FF4900;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h3>Current Cartridge Stock</h3>    
        <ul>
            <a href="officer.php">Home</a>
            <a href="#" onclick="confirmLogout(event)">Logout</a>        </ul>
    </div>
    <div class="container">
        <h1>Cartridge Stock</h1>

        <!-- Filter Section -->
        <div class="filter-section">
            <form action="" method="GET">
                <label for="filter_oem">Filter by OEM:</label>
                <input type="text" id="filter_oem" name="filter_oem" value="<?php echo htmlspecialchars($filter_oem ?? '', ENT_QUOTES, 'UTF-8'); ?>">

                <label for="filter_model">Filter by Model:</label>
                <input type="text" id="filter_model" name="filter_model" value="<?php echo htmlspecialchars($filter_model ?? '', ENT_QUOTES, 'UTF-8'); ?>">

                <button type="submit">Filter</button>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>OEM</th>
                    <th>MODEL</th>
                    <th>CARTRIDGE</th>
                    <th>QUANTITY</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($cartridges)) {
                    foreach ($cartridges as $cartridge) {
                        $row_class = $cartridge['QUANTITY'] < $threshold ? 'low-stock' : '';
                    ?>
                        <tr class="<?php echo $row_class; ?>">
                            <td><?php echo htmlspecialchars($cartridge['OEM']); ?></td>
                            <td><?php echo htmlspecialchars($cartridge['MODEL']); ?></td>
                            <td><?php echo htmlspecialchars($cartridge['CARTRIDGE']); ?></td>
                            <td><?php echo htmlspecialchars($cartridge['QUANTITY']); ?></td>
                        </tr>
                    <?php }
                } else { ?>
                    <tr>
                        <td colspan="4">No Cartridges Found</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <script src="partials/logout.js"></script>

</body>
</html>
