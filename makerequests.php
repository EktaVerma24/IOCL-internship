<?php
session_start();

// Check if user is not logged in or is an admin, redirect to welcome page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['USER_TYPE'] !== 'user') {
    header("location: welcome.php");
    exit;
}

include 'partials/connect.php'; // Include database connection file
$admin_email_query = "SELECT EMAIL_ID FROM admin LIMIT 1";
$admin_email_result = mysqli_query($conn, $admin_email_query);

if (!$admin_email_result || mysqli_num_rows($admin_email_result) == 0) {
    die('Error fetching admin email: ' . mysqli_error($conn));
}

$admin_email_row = mysqli_fetch_assoc($admin_email_result);
$from_email = $admin_email_row['EMAIL_ID'];
$message = '';

// Fetch user details from the database
$USER = $_SESSION['USER']; // Assuming USER is the username or identifier

$sql_select = "SELECT USER_ID, OEM, MODEL FROM user_printer_data WHERE USER = ?";
$stmt = $conn->prepare($sql_select);
$stmt->bind_param("s", $USER);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $USER_ID = $row['USER_ID'];
    $OEM = $row['OEM'];
    $MODEL = $row['MODEL'];
} else {
    // Handle case where user details are not found
    $OEM = '';
    $MODEL = '';
}

$stmt->close();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Insert request into requests table and send email

    // Validate and sanitize inputs if needed
    // Example: $someInput = mysqli_real_escape_string($conn, $_POST['someInput']);

    // Insert request into requests table
    $sql_insert = "INSERT INTO requests (USER_ID, USER, OEM, MODEL, DATE, TIME) 
                   VALUES (?, ?, ?, ?, NOW(), NOW())";

    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("ssss", $USER_ID, $USER, $OEM, $MODEL);

    if ($stmt_insert->execute()) {
        // Request successfully inserted, now send email

        // Fetch admin email addresses from the database
        $sql_admin_emails = "SELECT EMAIL_ID FROM user_details WHERE USER_TYPE='engineer'";
        $result_admin_emails = $conn->query($sql_admin_emails);

        if ($result_admin_emails->num_rows > 0) {
            $admin_emails = [];
            while ($row = $result_admin_emails->fetch_assoc()) {
                // Validate email address
                if (filter_var($row['EMAIL_ID'], FILTER_VALIDATE_EMAIL)) {
                    $admin_emails[] = $row['EMAIL_ID'];
                }
            }

            if (!empty($admin_emails)) {
                $html = "<p>Request From <strong>{$USER}</strong></p>";
                $html .= "<p><strong>OEM:</strong> {$OEM}</p>";
                $html .= "<p><strong>Model:</strong> {$MODEL}</p>";
                $html .= "<p>Please confirm this request by clicking the button below:</p>";
                $html .= "<p><a href='http://localhost/project/login.php?user={$USER}&timestamp=" . time() . "' style='padding: 10px 20px; background-color: #007bff; color: #fff; text-decoration: none; border-radius: 4px;'>Confirm Request</a></p>";

                // Include PHPMailer library and send email
                require_once 'smtp/PHPMailerAutoload.php';

                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = "smtp.gmail.com";
                $mail->Port = 587;
                $mail->SMTPSecure = 'tls';
                $mail->SMTPAuth = true;
                $mail->Username = "ekta24v@gmail.com";
                $mail->Password = "xfujamtssarffzlo";
                $mail->setFrom($from_email);
                
                // Add admin email addresses as recipients
                foreach ($admin_emails as $admin_email) {
                    $mail->addAddress($admin_email);
                }
                
                $mail->isHTML(true);
                $mail->Subject = "Request from $USER";
                $mail->Body = $html;

                try {
                    $mail->send();
                    $message = "Request submitted successfully!";
                    // Disable the submit button after successful submission
                    $disableSubmitButton = true;
                } catch (Exception $e) {
                    $message = "Error sending email: " . $mail->ErrorInfo;
                }
            } else {
                $message = "Error: No valid admin email addresses found.";
            }
        } else {
            $message = "Error: No admin email addresses found.";
        }

        $stmt_insert->close();
    } else {
        $message = "Error: " . $sql_insert . "<br>" . $conn->error;
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
    <title>Make Request</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        html, body {
            height: 100%;
            margin: 0;
            font-family: Tahoma, sans-serif;
            background-color: #f9f9f9;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .navbar {
            background-color: #031854;
            width: 100%;
            padding: 15px 10px; /* Adjusted padding for better positioning */
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #ffffff;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .navbar h2 {
            margin: 0;
            font-size: 24px;
        }

        .navbar a {
            color: #ffffff;
            text-decoration: none;
            font-size: 16px;
            padding: 10px 20px; /* Added padding for better visibility */
            border-radius: 8px;
            transition: background-color 0.3s ease;
            margin-right: 10px; /* Added margin to space out the buttons */
        }

        .navbar a:hover {
            background-color: #FF4900;
            border-radius: 5px;
        }

        .container {
            width: 100%;
            max-width: 600px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
            text-align: center;
            margin-top: 80px; /* Space for fixed navbar */
        }

        .form-container {
            margin-top: 20px;
        }

        .form-container h1 {
            color: #031854;
            margin-bottom: 20px;
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
            <?php if (isset($disableSubmitButton) && $disableSubmitButton) echo "display: none;"; ?> /* Hide button if disabled */
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
    </style>
</head>

<body>
    <div class="navbar">
        <h2>Make Request</h2>
        <div>
            <a href="welcome.php">Home</a>
            <a href="#" onclick="confirmLogout(event)">Logout</a>        </div>
    </div>

    <div class="container">
        <div class="form-container">
            <form method="post">
                <h1>Printer Not Working?</h1>
                <h1>Do Let the Engineer Know!</h1>
                <input type="submit" value="Submit Request">
            </form>
            <?php if (!empty($message)) : ?>
                <div class="message">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script src="partials/logout.js"></script>

</body>

</html>
