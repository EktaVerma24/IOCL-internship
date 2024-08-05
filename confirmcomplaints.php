<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['USER_TYPE'] !== 'engineer') {
    header("location: login.php");
    exit;
}

include 'partials/connect.php';

// Get admin email
$admin_email_query = "SELECT EMAIL_ID FROM admin LIMIT 1";
$admin_email_result = mysqli_query($conn, $admin_email_query);

if (!$admin_email_result || mysqli_num_rows($admin_email_result) == 0) {
    die('Error fetching admin email: ' . mysqli_error($conn));
}
$admin_email_row = mysqli_fetch_assoc($admin_email_result);
$admin_email = $admin_email_row['EMAIL_ID'];

// Function to fetch complaints
function getComplaints($filter_date, $filter_action, $sort_order) {
    global $conn;
    $filter_date_sql = $filter_date ? "AND DATE = '$filter_date'" : '';
    $filter_action_sql = $filter_action ? "AND ACTION = '$filter_action'" : '';
    
    $query = "SELECT * FROM complaints WHERE 1=1 $filter_date_sql $filter_action_sql ORDER BY DATE $sort_order, TIME $sort_order";
    $query_run = mysqli_query($conn, $query);

    if (!$query_run) {
        die('Error fetching complaints: ' . mysqli_error($conn));
    }

    $complaints = array();
    while ($row = mysqli_fetch_assoc($query_run)) {
        $complaints[] = $row;
    }
    return $complaints;
}

// Fetch and filter complaints
$filter_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : '';
$filter_action = isset($_GET['filter_action']) ? $_GET['filter_action'] : '';
$sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'DESC';

$complaints = getComplaints($filter_date, $filter_action, $sort_order);

// Process confirmation request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_complaint'])) {
    $complaint_id = $_POST['complaint_id'];
    $remark = $_POST['remark'];

    if (empty($remark)) {
        $_SESSION['notification'] = "Remark is required for Complaint #$complaint_id. Please add a remark to confirm.";
    } else {
        $confirmation_time = date('Y-m-d H:i:s');

        // Fetch complaint date and time to calculate turnaround time
        $complaint_query = "SELECT DATE, TIME FROM complaints WHERE COMPLAINT_ID=?";
        $stmt = $conn->prepare($complaint_query);
        $stmt->bind_param('s', $complaint_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $complaint = $result->fetch_assoc();
        
        $complaint_date_time = $complaint['DATE'] . ' ' . $complaint['TIME'];
        
        // Calculate turnaround time in seconds
        $turnaround_seconds = strtotime($confirmation_time) - strtotime($complaint_date_time);
        
        // Calculate turnaround time in days, hours, and minutes
        $turnaround_days = floor($turnaround_seconds / (3600 * 24));
        $turnaround_hours = floor(($turnaround_seconds % (3600 * 24)) / 3600);
        $turnaround_minutes = floor(($turnaround_seconds % 3600) / 60);
        $turnaround_time = sprintf('%d days %02d hours %02d minutes', $turnaround_days, $turnaround_hours, $turnaround_minutes);

        // Update complaint action to 'CONFIRMED' and set remarks, confirmation time, and turnaround time
        $stmt = $conn->prepare("UPDATE complaints SET ACTION='CONFIRMED', REMARKS=?, CONFIRMATION_TIME=?, TURNAROUND_TIME=? WHERE COMPLAINT_ID=?");
        $stmt->bind_param('ssss', $remark, $confirmation_time, $turnaround_time, $complaint_id);
        $stmt->execute();

        // Fetch user details
        $sql = "SELECT user_details.EMAIL_ID, user_details.USER FROM complaints
                INNER JOIN user_details ON complaints.USER_ID = user_details.USER_ID
                WHERE complaints.COMPLAINT_ID = ?";
        $stmt->prepare($sql);
        $stmt->bind_param('s', $complaint_id);
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
            $mail->Subject = "Complaint Confirmation";
            $mail->Body = "<h1>Confirm Your Complaint Resolvation...</h1>
            <p><a href=\"http://localhost/project/userconfirmcomplaints.php?USER={$USER}&COMPLAINT_ID={$complaint_id}&timestamp=" . time() . "\" style=\"padding: 10px 20px; background-color: #007bff; color: #fff; text-decoration: none; border-radius: 4px;\">Confirm Complaint</a></p>";
        
            try {
                // Send email
                $mail->send();
                $_SESSION['notification'] = "Complaint #$complaint_id has been confirmed and notification emails have been sent.";
            } catch (Exception $e) {
                $_SESSION['notification'] = "Email sending failed. Error: {$mail->ErrorInfo}";
            }
        }
    }

    // Redirect to the same page after updates
    header("location: confirmcomplaints.php");
    exit;
}

// Clear filters
if (isset($_GET['clear_filters'])) {
    header("location: confirmcomplaints.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Complaints</title>
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 80%;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            margin-top: 80px;
            margin-left: 120px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        nav {
            background-color: #031854;
            color: #fff;
            padding: 15px 30px;
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
            height: 60px;
        }
        nav h1 {
            margin: 0;
            font-size: 24px;
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
            padding: 10px 10px;
            display: block;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        nav ul li a:hover {
            background-color: #FF4900;
            border-radius: 5px;
        }
        .confirmed {
            background-color: #c3e6cb; /* Light green color for confirmed requests */
        }
        .notification {
            background-color: #c3e6cb;
            color: #155724;
            text-align: center;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            border: 1px solid #155724;
        }
        .filter-section {
            margin-bottom: 20px;
        }
        .filter-section form {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .filter-section select, .filter-section input[type="date"] {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .filter-section button {
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            background-color: #007bff;
            color: #fff;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .filter-section button:hover {
            background-color: #0056b3;
        }
        .filter-section a {
            color: #007bff;
            text-decoration: none;
            font-size: 14px;
        }
        .filter-section a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <nav>
        <h1>Complaint System</h1>
        <ul>
            <li><a href="engineerdashboard.php">Dashboard</a></li>
            <li><a href="complaintlist.php">Complaints List</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>
    <div class="container">
        <div class="filter-section">
            <form action="confirmcomplaints.php" method="GET">
                <input type="date" name="filter_date" value="<?php echo htmlspecialchars($filter_date); ?>">
                <select name="filter_action">
                    <option value="">All Actions</option>
                    <option value="CONFIRMED" <?php echo $filter_action == 'CONFIRMED' ? 'selected' : ''; ?>>Confirmed</option>
                    <option value="PENDING" <?php echo $filter_action == 'PENDING' ? 'selected' : ''; ?>>Pending</option>
                </select>
                <select name="sort_order">
                    <option value="DESC" <?php echo $sort_order == 'DESC' ? 'selected' : ''; ?>>Newest First</option>
                    <option value="ASC" <?php echo $sort_order == 'ASC' ? 'selected' : ''; ?>>Oldest First</option>
                </select>
                <button type="submit">Apply Filters</button>
                <a href="confirmcomplaints.php?clear_filters=true">Clear Filters</a>
            </form>
        </div>

        <?php if (isset($_SESSION['notification'])): ?>
            <div class="notification">
                <?php echo $_SESSION['notification']; unset($_SESSION['notification']); ?>
            </div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Complaint ID</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Action</th>
                    <th>Remarks</th>
                    <th>Confirmation Time</th>
                    <th>Turnaround Time</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($complaints as $complaint): ?>
                    <tr class="<?php echo $complaint['ACTION'] === 'CONFIRMED' ? 'confirmed' : ''; ?>">
                        <td><?php echo htmlspecialchars($complaint['COMPLAINT_ID']); ?></td>
                        <td><?php echo htmlspecialchars($complaint['DATE']); ?></td>
                        <td><?php echo htmlspecialchars($complaint['TIME']); ?></td>
                        <td><?php echo htmlspecialchars($complaint['ACTION']); ?></td>
                        <td><?php echo htmlspecialchars($complaint['REMARKS']); ?></td>
                        <td><?php echo htmlspecialchars($complaint['CONFIRMATION_TIME']); ?></td>
                        <td><?php echo htmlspecialchars($complaint['TURNAROUND_TIME']) ?: 'N/A'; ?></td>
                        <td>
                            <?php if ($complaint['ACTION'] !== 'CONFIRMED'): ?>
                                <form action="confirmcomplaints.php" method="POST">
                                    <input type="hidden" name="complaint_id" value="<?php echo htmlspecialchars($complaint['COMPLAINT_ID']); ?>">
                                    <input type="text" name="remark" placeholder="Add remark" required>
                                    <button type="submit" name="confirm_complaint">Confirm</button>
                                </form>
                            <?php else: ?>
                                Confirmed
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
