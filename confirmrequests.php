<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['USER_TYPE'] !== 'engineer') {
    header("location: login.php");
    exit;
}

include 'partials/connect.php';

// Fetch admin email
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

// Fetch cartridge information from current_stock
$cartridge_query = "SELECT OEM, MODEL, CARTRIDGE FROM current_stock";
$cartridge_result = mysqli_query($conn, $cartridge_query);

$cartridge_options = array();
$fixed_cartridges = array(); // To store fixed cartridge values

while ($row = mysqli_fetch_assoc($cartridge_result)) {
    $key = $row['OEM'] . '-' . $row['MODEL'];
    if (!isset($cartridge_options[$key])) {
        $cartridge_options[$key] = array();
    }
    $cartridge_options[$key][] = $row['CARTRIDGE'];
}

// Determine fixed cartridges
foreach ($cartridge_options as $key => $cartridges) {
    if (count($cartridges) === 1) {
        list($oem, $model) = explode('-', $key);
        $fixed_cartridges[$oem][$model] = $cartridges[0];
    }
}

// Process individual confirmation request
if (isset($_GET['REQUEST_NO'], $_GET['CONFIRMED'], $_GET['OEM'])) {
    $REQUEST_NO = $_GET['REQUEST_NO'];
    $CONFIRMED = $_GET['CONFIRMED'];
    $CARTRIDGE = isset($_GET['CARTRIDGE']) ? mysqli_real_escape_string($conn, $_GET['CARTRIDGE']) : '';
    $OEM = $_GET['OEM'];

    $sql = "SELECT user_details.EMAIL_ID, user_details.USER, requests.DATE, requests.TIME
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
        $REQUEST_DATE = $row['DATE'];
        $REQUEST_TIME = $row['TIME'];

        $CONFIRMATION_TIME = date('Y-m-d H:i:s');
        $query_update = "UPDATE requests SET ACTION='CONFIRMED', CONFIRMATION_TIME='$CONFIRMATION_TIME'";
        if (!empty($CARTRIDGE)) {
            $query_update .= ", CARTRIDGE='$CARTRIDGE'";
        }
        $query_update .= " WHERE REQUEST_NO='$REQUEST_NO'";
        $result_update = mysqli_query($conn, $query_update);

        if ($result_update) {
            $turnaround_time_seconds = strtotime($CONFIRMATION_TIME) - strtotime("$REQUEST_DATE $REQUEST_TIME");

            // Convert seconds to days, hours, and minutes
            $days = floor($turnaround_time_seconds / 86400); // 86400 seconds in a day
            $hours = floor(($turnaround_time_seconds % 86400) / 3600); // 3600 seconds in an hour
            $minutes = floor(($turnaround_time_seconds % 3600) / 60); // 60 seconds in a minute

            $turnaround_time = '';
            if ($days > 0) {
                $turnaround_time .= "{$days}d ";
            }
            if ($hours > 0) {
                $turnaround_time .= "{$hours}h ";
            }
            if ($minutes > 0) {
                $turnaround_time .= "{$minutes}m";
            }

            // Send confirmation email
            require "smtp/PHPMailerAutoload.php";
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = "smtp.gmail.com";
            $mail->Port = 587;
            $mail->SMTPSecure = 'tls';
            $mail->SMTPAuth = true;
            $mail->Username = "ekta24v@gmail.com"; // Replace with your email address
            $mail->Password = "xfujamtssarffzlo"; // Replace with your email password

            $mail->setFrom($admin_email);
            $mail->addAddress($EMAIL);
            $mail->isHTML(true);
            $mail->Subject = "Request Confirmation";
            $mail->Body = "<h1>Confirm Your Request</h1>
            <p><a href=\"http://localhost/project/userconfirmrequests.php?USER={$USER}&REQUEST_NO={$REQUEST_NO}&timestamp=" . time() . "\" style=\"padding: 10px 20px; background-color: #007bff; color: #fff; text-decoration: none; border-radius: 4px;\">Confirm Request</a></p>";

            try {
                $mail->send();
                $_SESSION['notification'] = "Request #$REQUEST_NO has been confirmed and notification emails have been sent.";
            } catch (Exception $e) {
                $_SESSION['notification'] = "Email sending failed. Error: {$mail->ErrorInfo}";
            }
        } else {
            $_SESSION['notification'] = "Error updating request: " . mysqli_error($conn);
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
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = "smtp.gmail.com";
    $mail->Port = 587;
    $mail->SMTPSecure = 'tls';
    $mail->SMTPAuth = true;
    $mail->Username = "ekta24v@gmail.com"; // Replace with your email address
    $mail->Password = "xfujamtssarffzlo"; // Replace with your email password

    $mail->setFrom("ekta24v@gmail.com");

    foreach ($selected_requests as $request_no) {
        $cartridge = isset($_POST["CARTRIDGE_$request_no"]) ? mysqli_real_escape_string($conn, $_POST["CARTRIDGE_$request_no"]) : '';

        $sql = "SELECT requests.DATE, requests.TIME, user_details.EMAIL_ID, user_details.USER 
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
            $REQUEST_DATE = $row['DATE'];
            $REQUEST_TIME = $row['TIME'];

            $CONFIRMATION_TIME = date('Y-m-d H:i:s');
            $query_update = "UPDATE requests SET ACTION='CONFIRMED', CONFIRMATION_TIME='$CONFIRMATION_TIME'";
            if (!empty($cartridge)) {
                $query_update .= ", CARTRIDGE='$cartridge'";
            }
            $query_update .= " WHERE REQUEST_NO='$request_no'";
            mysqli_query($conn, $query_update);

            $turnaround_time_seconds = strtotime($CONFIRMATION_TIME) - strtotime("$REQUEST_DATE $REQUEST_TIME");

            // Convert seconds to days, hours, and minutes
            $days = floor($turnaround_time_seconds / 86400); // 86400 seconds in a day
            $hours = floor(($turnaround_time_seconds % 86400) / 3600); // 3600 seconds in an hour
            $minutes = floor(($turnaround_time_seconds % 3600) / 60); // 60 seconds in a minute

            $turnaround_time = '';
            if ($days > 0) {
                $turnaround_time .= "{$days}d ";
            }
            if ($hours > 0) {
                $turnaround_time .= "{$hours}h ";
            }
            if ($minutes > 0) {
                $turnaround_time .= "{$minutes}m";
            }

            $mail->addAddress($EMAIL);
            $mail->isHTML(true);
            $mail->Subject = "CONFIRM YOUR REQUEST";
            $mail->Body = "<h1>Your Work Done???</h1>
            <p><a href=\"http://localhost/project/userconfirmrequests.php?USER={$USER}&REQUEST_NO={$request_no}&timestamp=" . time() . "\" style=\"padding: 10px 20px; background-color: #007bff; color: #fff; text-decoration: none; border-radius: 4px;\">Confirm Request</a></p>";

            try {
                $mail->send();
            } catch (Exception $e) {
                $_SESSION['notification'] = "Email sending failed. Error: {$mail->ErrorInfo}";
                break; // Exit loop on email sending failure
            }
        }
    }

    $_SESSION['notification'] = "Selected requests have been confirmed and notification emails have been sent.";

    // Redirect to the same page after updates
    header("location: confirmrequests.php");
    exit;
}

// Clear filters
if (isset($_GET['clear_filters'])) {
    header("Location: confirmrequests.php");
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
        }
        nav ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
            display: flex;
            gap: 10px;
        }
        nav ul li a {
            color: #fff;
            text-decoration: none;
            padding: 10px 10px;
            display: block;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        nav ul li a:hover {
            background-color: #FF4900;
            border-radius: 5px;
        }
        nav a {
            color: #fff;
            text-decoration: none;
            margin-left: 20px;
        }
        nav a.btn {
            background-color: #007bff;
            padding: 10px 20px;
            border-radius: 4px;
        }
        .container {
            /* width: 0vw; */
            padding: 20px;
            max-width: 1200px;
            margin: 20px auto;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .form-container {
            width: 90vw;
        }
        h1 {
            text-align: center;
            margin-bottom: 20px;
        }
        form {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        form input[type="date"],
        form select {
            margin: 0 10px;
            padding: 5px;
            font-size: 14px;
            border-radius: 4px;
            border: 1px solid #ddd;
            width: 200px;
        }
        form button,
        .btn {
            padding: 10px 20px;
            font-size: 16px;
            background-color: #031854;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-left: 10px;
            text-decoration: none;
        }
        form button:hover,
        .btn:hover {
            background-color: #1a3cb1;
        }
        .table-container {
            overflow-x: auto;
            width: 80vw;
        }
        table {
            /* width: 1vw; */
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table th,
        table td {
            padding: 5px;
            border: 1px solid #ddd;
            text-align: left;
            font-size: 14px;
        }
        table th {
            background-color: #f8f8f8;
        }
        table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .btn-container {
            text-align: center;  
            padding: 2px;
        }
        .btn2{
            padding: 5px 10px;
            font-size: 16px;
            background-color: #031854;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-left: 5px;
            margin-right: 10px;
           
        }
        .notification {
            padding: 1px;
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<nav>
        <h1>Confirm Requests</h1>
        <ul>
            <li><a href="engineer.php">Home</a></li>
            <li> <a href="#" onclick="confirmLogout(event)">Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <h1>Confirm Requests</h1>

        <form action="confirmrequests.php" method="get">
            <input type="date" name="filter_date" value="<?php echo htmlspecialchars($filter_date); ?>">
            <select name="filter_action">
                <option value="">Select Action</option>
                <option value="PENDING" <?php if ($filter_action === 'PENDING') echo 'selected'; ?>>Pending</option>
                <option value="CONFIRMED" <?php if ($filter_action === 'CONFIRMED') echo 'selected'; ?>>Confirmed</option>
            </select>
            <button type="submit">Filter</button>
            <a href="confirmrequests.php?clear_filters=1" class="btn">Clear Filters</a>
        </form>

        <?php if (isset($_SESSION['notification'])): ?>
            <div class="notification">
                <?php echo $_SESSION['notification']; unset($_SESSION['notification']); ?>
            </div>
        <?php endif; ?>

        <form class="form-container" action="confirmrequests.php" method="post">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Select</th>
                            <th>Request No</th>
                            <th>User</th>
                            <th>User ID</th>
                            <th>OEM</th>
                            <th>Model</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Action</th>
                            <th>Cartridge</th>
                            <th>Confirmation Time</th>
                            <th>Turnaround Time</th>
                            <th>Confirm</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr class="table">
                                <td><input type="checkbox" name="selected_requests[]" value="<?php echo htmlspecialchars($order['REQUEST_NO']); ?>"></td>
                                <td><?php echo htmlspecialchars($order['REQUEST_NO']); ?></td>
                                <td><?php echo htmlspecialchars($order['USER']); ?></td>
                                <td><?php echo htmlspecialchars($order['USER_ID']); ?></td>
                                <td><?php echo htmlspecialchars($order['OEM']); ?></td>
                                <td><?php echo htmlspecialchars($order['MODEL']); ?></td>
                                <td><?php echo htmlspecialchars($order['DATE']); ?></td>
                                <td><?php echo htmlspecialchars($order['TIME']); ?></td>
                                <td><?php echo htmlspecialchars($order['ACTION']); ?></td>
                                <td>
                                    <?php
                                    $oem = $order['OEM'];
                                    $model = $order['MODEL'];

                                    if (isset($fixed_cartridges[$oem][$model])) {
                                        // Fixed cartridge value
                                        $fixed_cartridge = $fixed_cartridges[$oem][$model];
                                        echo '<input type="hidden" name="CARTRIDGE_' . htmlspecialchars($order['REQUEST_NO']) . '" value="' . htmlspecialchars($fixed_cartridge) . '">';
                                        echo htmlspecialchars($fixed_cartridge);
                                    } else {
                                        // Display options for cartridges
                                        echo '<select name="CARTRIDGE_' . htmlspecialchars($order['REQUEST_NO']) . '">';
                                        echo '<option value="">Select Cartridge</option>';
                                        if (isset($cartridge_options[$oem . '-' . $model])) {
                                            foreach ($cartridge_options[$oem . '-' . $model] as $option) {
                                                echo '<option value="' . htmlspecialchars($option) . '">' . htmlspecialchars($option) . '</option>';
                                            }
                                        }
                                        echo '</select>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    echo $order['CONFIRMATION_TIME'] ? htmlspecialchars($order['CONFIRMATION_TIME']) : 'Not Confirmed';
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    if ($order['CONFIRMATION_TIME']) {
                                        $request_time = strtotime($order['DATE'] . ' ' . $order['TIME']);
                                        $confirmation_time = strtotime($order['CONFIRMATION_TIME']);
                                        $turnaround_time_seconds = $confirmation_time - $request_time;

                                        // Convert seconds to days, hours, and minutes
                                        $days = floor($turnaround_time_seconds / 86400); // 86400 seconds in a day
                                        $hours = floor(($turnaround_time_seconds % 86400) / 3600); // 3600 seconds in an hour
                                        $minutes = floor(($turnaround_time_seconds % 3600) / 60); // 60 seconds in a minute

                                        $turnaround_time = '';
                                        if ($days > 0) {
                                            $turnaround_time .= "{$days}d ";
                                        }
                                        if ($hours > 0) {
                                            $turnaround_time .= "{$hours}h ";
                                        }
                                        if ($minutes > 0) {
                                            $turnaround_time .= "{$minutes}m";
                                        }

                                        echo htmlspecialchars($turnaround_time);
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                                <td class="cnfrm button">
                                    <?php if ($order['ACTION'] != 'CONFIRMED'): ?>
                                        <a href="confirmrequests.php?REQUEST_NO=<?php echo htmlspecialchars($order['REQUEST_NO']); ?>&CONFIRMED=true&OEM=<?php echo htmlspecialchars($order['OEM']); ?>&CARTRIDGE=<?php echo htmlspecialchars(isset($_POST["CARTRIDGE_{$order['REQUEST_NO']}"]) ? $_POST["CARTRIDGE_{$order['REQUEST_NO']}"] : ''); ?>" class="btn">Confirm</a>
                                    <?php else: ?>
                                        CONFIRMED
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="btn-container">
                <button class="btn2" type="submit" name="confirm_batch">Confirm Selected</button>
            </div>
        </form>
    </div>
    <script src="partials/logout.js"></script>

</body>
</html>
