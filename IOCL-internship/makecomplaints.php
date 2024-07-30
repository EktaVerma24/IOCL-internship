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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize inputs if needed
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $complaint = mysqli_real_escape_string($conn, $_POST['complaint']);
    $USER = $_SESSION['USER']; // Assuming USER is the username or identifier

    // Fetch user details from the database
    $sql_select_user = "SELECT USER_ID FROM user_details WHERE USER = ?";
    $stmt = $conn->prepare($sql_select_user);
    $stmt->bind_param("s", $USER);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $USER_ID = $row['USER_ID'];
    } else {
        // Handle case where user details are not found
        $USER_ID = null;
    }

    $stmt->close();

    // Insert complaint into complaints table
    $sql_insert = "INSERT INTO complaints (USER_ID, USER, SUBJECT, COMPLAINTS, DATE, TIME) 
                   VALUES (?, ?, ?, ?, NOW(), NOW())";

    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("ssss", $USER_ID, $USER, $subject, $complaint);

    if ($stmt_insert->execute()) {
        // Complaint successfully inserted, now fetch the email of the engineer
        $sql_select_engineer = "SELECT EMAIL_ID FROM user_details WHERE USER_TYPE = 'engineer'";
        $result_engineer = $conn->query($sql_select_engineer);

        if ($result_engineer->num_rows > 0) {
            $row_engineer = $result_engineer->fetch_assoc();
            $engineer_email = $row_engineer['EMAIL_ID'];
        } else {
            $engineer_email = null; // Handle case where engineer email is not found
        }

        if ($engineer_email) {
            // Prepare the email content
            $html = "<p>Complaint From <strong>{$USER}</strong></p>";
            $html .= "<p><strong>Subject:</strong> {$subject}</p>";
            $html .= "<p><strong>Complaint:</strong> {$complaint}</p>";
            $html .= "<p>Please address this complaint promptly.</p>";

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
            $mail->addAddress($engineer_email);
            $mail->isHTML(true);
            $mail->Subject = "Complaint from $USER";
            $mail->Body = $html;

            try {
                $mail->send();
                $message = "Complaint submitted and email sent successfully!";
            } catch (Exception $e) {
                $message = "Error sending email: " . $mail->ErrorInfo;
            }
        } else {
            $message = "Complaint submitted, but no engineer email found.";
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
    <title>Complaint Page</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Basic styling */
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
            border-radius: 8px;
        }

        nav ul li a:hover {
            background-color: #FF4900;
            border-radius: 8px;
        }

        .container {
            width: 100%;
            max-width: 600px;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            margin-top: 80px; /* Space for fixed navbar */
            text-align: center;
        }

        h1 {
            font-size: 24px;
            margin-bottom: 20px;
            color: #031854;
        }

        .form-group {
            margin-bottom: 15px;
            text-align: left;
        }

        label {
            font-weight: bold;
            color: #495057;
            
        }

        input[type="text"],
        textarea {
            margin-top:6px ;
            width: 100%;
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
        }

        textarea {
            height: 150px;
        }

        button {
            background-color: #031854;
            color: #fff;
            border: none;
            padding: 12px 20px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 8px;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }

        button:hover {
            background-color: #FF4900;
            transform: translateY(-5px);
        }

        /* Alert styling */
        .alert {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
            padding: 10px;
            margin-top: 20px;
            border-radius: 8px;
            text-align: center;
            font-size: 16px;
            transition: opacity 0.3s ease;
        }

        .alert-success {
            background-color: #c3e6cb; /* Green color for success */
            border-color: #a4d2a5;
            color: #155724;
        }
    </style>
</head>
<body>
    <nav>
        <div class="navbar-heading">Add a Complaint</div>
        <ul>
            <li><a href="welcome.php">Home</a></li>
           <li><a href="#" onclick="confirmLogout(event)">Logout</a>        </li> </ul>
    </nav>

    <div class="container">
        <h1>Have Any Complaints? Let Us Know!</h1>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
            <div class="form-group">
                <label for="subject">Subject</label>
                <input type="text" id="subject" name="subject" placeholder="Enter Subject" required>
            </div>
            <div class="form-group">
                <label for="complaint">Complaint</label>
                <textarea id="complaint" name="complaint" placeholder="Write Your Complaint Here!" required></textarea>
            </div>
            <button type="submit">SEND</button>
        </form>
        <?php
        if (!empty($message)) {
            echo '<div class="alert alert-success">
                    <strong>' . $message . '</strong>
                  </div>';
        }
        ?>
    </div>
    <script src="partials/logout.js"></script>

</body>
</html>
