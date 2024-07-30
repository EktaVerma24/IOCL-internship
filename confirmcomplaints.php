<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['USER_TYPE'] !== 'engineer') {
    header("location: login.php");
    exit;
}

include 'partials/connect.php';
include 'partials/connect.php';
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
if (isset($_GET['COMPLAINT_ID'], $_GET['CONFIRMED'])) {
    $COMPLAINT_ID = $_GET['COMPLAINT_ID'];
    $CONFIRMED = $_GET['CONFIRMED'];

    $sql = "SELECT user_details.EMAIL_ID, user_details.USER 
            FROM complaints
            INNER JOIN user_details ON complaints.USER_ID = user_details.USER_ID
            WHERE complaints.COMPLAINT_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $COMPLAINT_ID);
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
        <p><a href=\"http://localhost/project/userconfirmcomplaints.php?USER={$USER}&COMPLAINT_ID={$COMPLAINT_ID}&timestamp=" . time() . "\" style=\"padding: 10px 20px; background-color: #007bff; color: #fff; text-decoration: none; border-radius: 4px;\">Confirm Complaint</a></p>";
    
        try {
            // Send email
            $mail->send();

            // Update complaint action to 'CONFIRMED'
            $query_update = "UPDATE complaints SET ACTION='CONFIRMED' WHERE COMPLAINT_ID='$COMPLAINT_ID'";
            $result_update = mysqli_query($conn, $query_update);

            if ($result_update) {
                $_SESSION['notification'] = "Complaint #$COMPLAINT_ID has been confirmed and notification emails have been sent.";
            } else {
                $_SESSION['notification'] = "Error updating complaint: " . mysqli_error($conn);
            }
        } catch (Exception $e) {
            $_SESSION['notification'] = "Email sending failed. Error: {$mail->ErrorInfo}";
        }
    } else {
        $_SESSION['notification'] = "No confirmation required.";
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
    <title>CONFIRM COMPLAINTS PAGE</title>
    <style>
        body {
            font-family: Tahoma, sans-serif;
            background-color: #f0f0f0;
            margin: 0;
            padding: 0;
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
            padding: 20px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
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
        .welcome-message {
            color: #333;
            font-size: 1.2em;
            margin-bottom: 10px;
        }
        
        .pending {
            background-color: #f8d7da;
        }
        .notification {
            background-color: #4CAF50;
            color: white;
            text-align: center;
            padding: 10px;
            margin-bottom: 15px;
        }
        .filter-form {
            margin-bottom: 20px;
        }
        .filter-form select, .filter-form input {
            margin-right: 10px;
        }
        .clear-filters {
            margin-bottom: 20px;
        }
        .clear-filters button {
            background-color: #031854;
            color: #fff;
            border: none;
            padding: 8px 16px;
            cursor: pointer;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }
        .clear-filters button:hover {
            background-color: #FF4900;
        }
        .CR{ 
            background-color: #09ABBE;
            color: #fff;
            border: none;
            padding: 8px 16px;
            cursor: pointer;
            border-radius: 4px;
            transition: background-color 0.3s ease;
            width: 100%;
            height: 100%;
           
        }
        .CR:hover{
            border: 3px solid #09ABBE;
           background-color: #fff;
           color: #09ABBE;
        }
        #filter_date {
            padding: 5px;
            margin-right: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
        }
        #filter_action {
            padding: 5px;
            margin-right: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
        }
        #sort_order {
            padding: 5px;
            margin-right: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
        }
        .filter {
            background-color: #031854;
            color: #fff;
            border: none;
            padding: 8px 16px;
            cursor: pointer;
            border-radius: 4px;
            transition: background-color 0.3s ease;
            font-size: 16px;
        }
        .filter:hover {
            background-color: #FF4900;
        }
        .clear_filters {
            background-color: #031854;
            color: #fff;
            border: none;
            padding: 8px 16px;
            cursor: pointer;
            border-radius: 4px;
            transition: background-color 0.3s ease;
            font-size: 16px;
        }
        .clear_filters:hover {
            background-color: #FF4900;
        }
    </style>
</head>
<body>
    <nav>
        <div><h2>Confirm Requests</h2></div>
        <ul>
           <li><a href="engineer.php">Home</a></li>
           <li><a href="#" onclick="confirmLogout(event)">Logout</a>  </li>      </ul>
    </nav>

    <div class="container">
        <div class="welcome-message">Complaint Confirmation Page</div>

        <?php if (isset($_SESSION['notification'])): ?>
            <div class="notification"><?= $_SESSION['notification']; ?></div>
            <?php unset($_SESSION['notification']); ?>
        <?php endif; ?>

        <div class="filter-form">
            <form method="GET" action="confirmcomplaints.php">
                <label for="filter_date">DATE:</label>
                <input type="date" name="filter_date" id="filter_date" value="<?= htmlspecialchars($filter_date); ?>">
                <label for="filter_action">SELECT ACTION:</label>
                <select name="filter_action" id="filter_action">
                    <option value="">All Actions</option>
                    <option value="PENDING" <?= $filter_action == 'PENDING' ? 'selected' : ''; ?>>Pending</option>
                    <option value="CONFIRMED" <?= $filter_action == 'CONFIRMED' ? 'selected' : ''; ?>>Confirmed</option>
                </select>
                <label for="sort_order">SORT BY:</label>
                <select name="sort_order" id="sort_order">
                    <option value="ASC" <?= $sort_order == 'ASC' ? 'selected' : ''; ?>>Oldest First</option>
                    <option value="DESC" <?= $sort_order == 'DESC' ? 'selected' : ''; ?>>Newest First</option>
                 
                </select>
                <button type="submit" class="filter">Filter</button>
                <button type="submit" name="clear_filters" value="1" class="clear_filters">Clear Filters</button>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Complaint ID</th>
                    <th>User ID</th>
                    <th>Complaint</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Action</th>
                    <th>Confirm</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($complaints)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center;">No complaints found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($complaints as $complaint): ?>
                        <tr class="<?= $complaint['ACTION'] === 'CONFIRMED' ? 'confirmed' : 'pending'; ?>">
                            <td><?= htmlspecialchars($complaint['COMPLAINT_ID']); ?></td>
                            <td><?= htmlspecialchars($complaint['USER_ID']); ?></td>
                            <td><?= htmlspecialchars($complaint['COMPLAINTS']); ?></td>
                            <td><?= htmlspecialchars($complaint['DATE']); ?></td>
                            <td><?= htmlspecialchars($complaint['TIME']); ?></td>
                            <td><?= htmlspecialchars($complaint['ACTION']); ?></td>
                            <td>
                                <?php if ($complaint['ACTION'] !== 'CONFIRMED'): ?>
                                    <a href="confirmcomplaints.php?COMPLAINT_ID=<?= $complaint['COMPLAINT_ID']; ?>&CONFIRMED=1" class="CR">Confirm</a>
                                <?php else: ?>
                                    <span>N/A</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <script src="partials/logout.js"></script>

</body>
</html>
