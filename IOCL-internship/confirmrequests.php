<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['USER_TYPE'] !== 'engineer') {
    header("location: login.php");
    exit;
}

include 'partials/connect.php';
$admin_email_query = "SELECT EMAIL_ID FROM admin LIMIT 1";
$admin_email_result = mysqli_query($conn, $admin_email_query);

if (!$admin_email_result || mysqli_num_rows($admin_email_result) == 0) {
    die('Error fetching admin email: ' . mysqli_error($conn));
}

$admin_email_row = mysqli_fetch_assoc($admin_email_result);
$admin_email = $admin_email_row['EMAIL_ID'];

// Fetch and filter requests
$filter_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : '';
$filter_action = isset($_GET['filter_action']) ? $_GET['filter_action'] : '';
$sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'DESC';

$filter_date_sql = $filter_date ? "AND DATE = '$filter_date'" : '';
$filter_action_sql = $filter_action ? "AND ACTION = '$filter_action'" : '';

$query = "SELECT * FROM requests WHERE 1=1 $filter_date_sql $filter_action_sql ORDER BY DATE $sort_order, TIME $sort_order";
$query_run = mysqli_query($conn, $query);

if (!$query_run) {
    die('Error fetching orders: ' . mysqli_error($conn));
}

$orders = array();
while ($row = mysqli_fetch_assoc($query_run)) {
    $orders[] = $row;
}

// Process individual confirmation request
if (isset($_GET['REQUEST_NO'], $_GET['CONFIRMED'], $_GET['OEM'])) {
    $REQUEST_NO = $_GET['REQUEST_NO'];
    $CONFIRMED = $_GET['CONFIRMED'];
    $CARTRIDGE = isset($_GET['CARTRIDGE']) ? mysqli_real_escape_string($conn, $_GET['CARTRIDGE']) : '';
    $OEM = $_GET['OEM'];

    $sql = "SELECT user_details.EMAIL_ID, user_details.USER 
            FROM requests
            INNER JOIN user_details ON requests.USER_ID = user_details.USER_ID
            WHERE requests.REQUEST_NO = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $REQUEST_NO);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $USER = $row['USER'];
        $EMAIL = $row['EMAIL_ID'];

        // Send confirmation email
        require "smtp/PHPMailerAutoload.php";
        
        // Initialize PHPMailer object
        $mail = new PHPMailer(true);
        
        // SMTP configuration
        $mail->isSMTP();
        $mail->Host = "smtp.gmail.com";
        $mail->Port = 587;
        $mail->SMTPSecure = 'tls';
        $mail->SMTPAuth = true;
        $mail->Username = "ekta24v@gmail.com"; // Replace with your email address
        $mail->Password = "xfujamtssarffzlo"; // Replace with your email password
        
        // Sender email address
        $mail->setFrom($admin_email);
        
        // Add recipient and body content
        $mail->addAddress($EMAIL);
        $mail->isHTML(true);
        $mail->Subject = "Request Confirmation";
        $mail->Body = "<h1>Confirm Your Request</h1>
        <p><a href=\"http://localhost/project/userconfirmrequests.php?USER={$USER}&REQUEST_NO={$REQUEST_NO}&timestamp=" . time() . "\" style=\"padding: 10px 20px; background-color: #007bff; color: #fff; text-decoration: none; border-radius: 4px;\">Confirm Request</a></p>";
    
        try {
            // Send email
            $mail->send();

            // Update request action to 'CONFIRMED' and CARTRIDGE if provided
            $query_update = "UPDATE requests SET ACTION='CONFIRMED'";
            if (!empty($CARTRIDGE)) {
                $query_update .= ", CARTRIDGE='$CARTRIDGE'";
            }
            $query_update .= " WHERE REQUEST_NO='$REQUEST_NO'";
            $result_update = mysqli_query($conn, $query_update);

            if ($result_update) {
                $_SESSION['notification'] = "Request #$REQUEST_NO has been confirmed and notification emails have been sent.";
            } else {
                $_SESSION['notification'] = "Error updating request: " . mysqli_error($conn);
            }
        } catch (Exception $e) {
            $_SESSION['notification'] = "Email sending failed. Error: {$mail->ErrorInfo}";
        }
    } else {
        $_SESSION['notification'] = "No confirmation required.";
    }

    // Redirect to the same page after updates
    header("location: confirmrequests.php");
    exit;
}

// Process batch confirmation request
if (isset($_POST['confirm_batch'])) {
    $selected_requests = isset($_POST['selected_requests']) ? $_POST['selected_requests'] : [];

    require "smtp/PHPMailerAutoload.php";
    
    // Initialize PHPMailer object
    $mail = new PHPMailer(true);
    
    // SMTP configuration
    $mail->isSMTP();
    $mail->Host = "smtp.gmail.com";
    $mail->Port = 587;
    $mail->SMTPSecure = 'tls';
    $mail->SMTPAuth = true;
    $mail->Username = "ekta24v@gmail.com"; // Replace with your email address
    $mail->Password = "xfujamtssarffzlo"; // Replace with your email password
    
    // Sender email address
    $mail->setFrom("ekta24v@gmail.com");

    foreach ($selected_requests as $request_no) {
        $cartridge = isset($_POST["CARTRIDGE_$request_no"]) ? mysqli_real_escape_string($conn, $_POST["CARTRIDGE_$request_no"]) : '';

        // Update request action to 'CONFIRMED'
        $query_update = "UPDATE requests SET ACTION='CONFIRMED'";
        if (!empty($cartridge)) {
            $query_update .= ", CARTRIDGE='$cartridge'";
        }
        $query_update .= " WHERE REQUEST_NO='$request_no'";
        mysqli_query($conn, $query_update);

        // Fetch user details for sending email
        $sql = "SELECT user_details.EMAIL_ID, user_details.USER 
                FROM requests
                INNER JOIN user_details ON requests.USER_ID = user_details.USER_ID
                WHERE requests.REQUEST_NO = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $request_no);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $USER = $row['USER'];
            $EMAIL = $row['EMAIL_ID'];

            // Add recipient and body content for each request
            $mail->addAddress($EMAIL);
            $mail->isHTML(true);
            $mail->Subject = "CONFIRM YOUR REQUEST";
            $mail->Body = "<h1>Your Work Done???</h1>
            <p><a href=\"http://localhost/project/userconfirmrequests.php?USER={$USER}&REQUEST_NO={$request_no}&timestamp=" . time() . "\" style=\"padding: 10px 20px; background-color: #007bff; color: #fff; text-decoration: none; border-radius: 4px;\">Confirm Request</a></p>";

            try {
                // Send email
                $mail->send();
            } catch (Exception $e) {
                $_SESSION['notification'] = "Email sending failed for request #$request_no. Error: {$mail->ErrorInfo}";
            }

            // Clear the recipient address for the next loop
            $mail->clearAddresses();
        }
    }

    $_SESSION['notification'] = "Selected requests have been confirmed and notification emails have been sent.";
    header("location: confirmrequests.php");
    exit;
}

// Clear filters
if (isset($_GET['clear_filters'])) {
    header("location: confirmrequests.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Requests</title>
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
            padding: 15px 20px;
            width: 100%;
            box-sizing: border-box;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        nav .navbar-heading {
            font-size: 24px;
            font-weight: 700;
            color: #ffffff;
        }

        nav ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
            display: flex;
            gap: 20px;
        }

        nav ul li a {
            color: #fff;
            text-decoration: none;
            padding: 10px 15px;
            display: block;
            transition: background-color 0.3s ease;
        }

        nav ul li a:hover {
            background-color: #FF4900;
            border-radius: 8px;
        }

        .req {
            width: 10vw;
        }
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 80px auto 20px auto; 
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            margin: 0;
            font-size: 24px;
            color: #031854;
        }
        .notification, .error {
            margin-bottom: 20px;
            padding: 10px;
            border-radius: 5px;
            background-color: #e0e0e0;
        }
        .notification {
            color: green;
            background-color: #d4edda;
        }
        .error {
            color: red;
            background-color: #f8d7da;
        }
        button, .button {
            padding: 10px 20px;
            background-color: #031854; /* Deep blue */
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }
        button:hover, .button:hover {
            background-color: #FF4900; /* Darker blue */
        }
        .filter-form, .batch-confirm-form {
            margin-bottom: 20px;

        }
        .batch-confirm-form button{
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            height: 30px;
            padding: 10px;
            text-align: left;
            text-decoration: none;
        }
        th {
            background-color: #f4f4f4;
        }
        tr.pending {
            background-color: #f8d7da; /* Light red */
        }
        input[type="text"], input[type="date"], select {
            padding: 5px;
            margin: 5px 0;
            border-radius: 4px;
            border: 1px solid #ccc;
        }
        .checkbox-column {
            width: 5%;
        }
        .dropdown {
            padding: 5px;
            border-radius: 4px;
            border: 1px solid #ccc;
            background-color: #fff;
        }
    </style>
</head>
<body>
<nav>
        <div class="navbar-heading">View All Requests</div>
        <ul>
            <li><a href="engineer.php">Home</a></li>
           <li><a href="#" onclick="confirmLogout(event)">Logout</a> </li>        </ul>
    </nav>
<div class="container">
    <?php if (isset($_SESSION['notification'])): ?>
        <div class="notification">
            <?php 
            echo $_SESSION['notification']; 
            unset($_SESSION['notification']);
            ?>
        </div>
    <?php endif; ?>
    
    <form action="confirmrequests.php" method="get" class="filter-form">
        <label for="filter_date">Filter by Date:</label>
        <input type="date" id="filter_date" name="filter_date" value="<?php echo htmlspecialchars($filter_date); ?>">
        <label for="filter_action">Filter by Action:</label>
        <select id="filter_action" name="filter_action">
            <option value="">All</option>
            <option value="PENDING" <?php echo $filter_action == 'PENDING' ? 'selected' : ''; ?>>Pending</option>
            <option value="CONFIRMED" <?php echo $filter_action == 'CONFIRMED' ? 'selected' : ''; ?>>Confirmed</option>
        </select>
        <label for="sort_order">Sort by Date:</label>
        <select id="sort_order" name="sort_order">
            <option value="DESC" <?php echo $sort_order == 'DESC' ? 'selected' : ''; ?>>Descending</option>
            <option value="ASC" <?php echo $sort_order == 'ASC' ? 'selected' : ''; ?>>Ascending</option>
        </select>
        <button type="submit">Apply Filters</button>
        <a href="confirmrequests.php?clear_filters=true" class="button" style="background-color:#f3452a; color: #ffff;text-decoration:none;">Clear Filters</a>
    </form>

    <form action="confirmrequests.php" method="post" class="batch-confirm-form">
        <button type="submit" name="confirm_batch">Confirm Selected Requests</button>
        <table>
            <thead>
                <tr>
                    <th class="checkbox-column">Select</th>
                    <th>Request No</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Action</th>
                    <th>OEM</th>
                    <th>Model</th>
                    <th>Cartridge</th>
                    <th>Confirm</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr class="<?php echo $order['ACTION'] == 'PENDING' ? 'pending' : ''; ?>">
                        <?php if ($order['ACTION'] != 'CONFIRMED'): ?>
                            <td class="checkbox-column"><input type="checkbox" name="selected_requests[]" value="<?php echo htmlspecialchars($order['REQUEST_NO']); ?>"></td>
                        <?php else: ?>
                            <td class="checkbox-column"></td>
                        <?php endif; ?>
                        <td><?php echo htmlspecialchars($order['REQUEST_NO']); ?></td>
                        <td><?php echo htmlspecialchars($order['DATE']); ?></td>
                        <td><?php echo htmlspecialchars($order['TIME']); ?></td>
                        <td><?php echo htmlspecialchars($order['ACTION']); ?></td>
                        <td><?php echo htmlspecialchars($order['OEM']); ?></td>
                        <td><?php echo htmlspecialchars($order['MODEL']); ?></td>
                        <td>
                            <?php if ($order['OEM'] == 'HP'&& $order['ACTION'] != 'CONFIRMED'): ?>
                                <select name="CARTRIDGE_<?php echo htmlspecialchars($order['REQUEST_NO']); ?>" class="dropdown">
                                    <!-- Add options here -->
                                    <option value="Cartridge1" <?php echo $order['CARTRIDGE'] == 'BLACK' ? 'selected' : ''; ?>>BLACK</option>
                                    <option value="Cartridge2" <?php echo $order['CARTRIDGE'] == 'MAGENTA' ? 'selected' : ''; ?>>MAGENTA</option>
                                    <option value="Cartridge2" <?php echo $order['CARTRIDGE'] == 'CYAN' ? 'selected' : ''; ?>>CYAN</option>
                                    <option value="Cartridge2" <?php echo $order['CARTRIDGE'] == 'YELLOW' ? 'selected' : ''; ?>>YELLOW</option>
                                </select>
                            <?php else: ?>
                                <?php echo htmlspecialchars($order['CARTRIDGE']); ?></td>                            <?php endif; ?>
                        
                        <?php if ($order['ACTION'] != 'CONFIRMED'): ?>
                            <td><a href="confirmrequests.php?REQUEST_NO=<?php echo htmlspecialchars($order['REQUEST_NO']); ?>&CONFIRMED=YES&CARTRIDGE=<?php echo htmlspecialchars($order['CARTRIDGE']); ?>&OEM=<?php echo htmlspecialchars($order['OEM']); ?>" class="button">Confirm</a></td>
                        <?php else: ?>
                            <td></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </form>
</div>
<script src="partials/logout.js"></script>

</body>
</html>
