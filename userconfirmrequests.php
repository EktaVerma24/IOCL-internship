<?php
session_start();

// Initialize notification variable
$confirmation_message = '';

// Define the threshold value
$threshold = 10;

// Ensure the request parameters are set and match the session values
if (!isset($_GET['USER'], $_GET['REQUEST_NO'], $_GET['timestamp'])) {
    header("location: confirmrequests.php");
    exit;
}

// Directly handle confirmation
include 'partials/connect.php'; // Ensure your database connection is included

$admin_email_query = "SELECT EMAIL_ID FROM admin LIMIT 1";
$admin_email_result = mysqli_query($conn, $admin_email_query);

if (!$admin_email_result || mysqli_num_rows($admin_email_result) == 0) {
    die('Error fetching admin email: ' . mysqli_error($conn));
}

$admin_email_row = mysqli_fetch_assoc($admin_email_result);
$from_email = $admin_email_row['EMAIL_ID'];

// Include PHPMailer
require_once 'smtp/PHPMailerAutoload.php';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $REQUEST_NO = $_GET['REQUEST_NO'];

    // Start transaction
    $conn->begin_transaction();

    try {
        // Check if the request has already been confirmed
        $sql_check_confirmation = "SELECT CONFIRMATION FROM requests WHERE REQUEST_NO = ?";
        $stmt_check_confirmation = $conn->prepare($sql_check_confirmation);
        if (!$stmt_check_confirmation) {
            throw new Exception("Error preparing confirmation check statement: " . $conn->error);
        }
        $stmt_check_confirmation->bind_param("i", $REQUEST_NO);
        $stmt_check_confirmation->execute();
        $stmt_check_confirmation->bind_result($CONFIRMATION);
        if (!$stmt_check_confirmation->fetch()) {
            throw new Exception("Failed to fetch confirmation status.");
        }
        $stmt_check_confirmation->close();

        if ($CONFIRMATION === 'CONFIRMED') {
            throw new Exception("This request has already been confirmed.");
        }

        // SQL to update the 'CONFIRMATION' column in your 'requests' table to 'CONFIRMED'
        $sql_update_request = "UPDATE requests SET CONFIRMATION='CONFIRMED' WHERE REQUEST_NO = ?";
        $stmt_request = $conn->prepare($sql_update_request);
        if (!$stmt_request) {
            throw new Exception("Error preparing request update statement: " . $conn->error);
        }
        $stmt_request->bind_param("i", $REQUEST_NO);
        if (!$stmt_request->execute()) {
            throw new Exception("Error executing request update statement: " . $stmt_request->error);
        }
        $stmt_request->close();

        // Get the OEM model and cartridge associated with the request
        $sql_get_request_details = "SELECT OEM, MODEL, CARTRIDGE FROM requests WHERE REQUEST_NO = ?";
        $stmt_details = $conn->prepare($sql_get_request_details);
        if (!$stmt_details) {
            throw new Exception("Error preparing request details statement: " . $conn->error);
        }
        $stmt_details->bind_param("i", $REQUEST_NO);
        $stmt_details->execute();
        $stmt_details->bind_result($OEM, $MODEL, $CARTRIDGE);
        if (!$stmt_details->fetch()) {
            throw new Exception("Failed to fetch request details.");
        }
        $stmt_details->close();

        // SQL to check if the current_stock entry exists
        $sql_check_stock = "SELECT QUANTITY FROM current_stock WHERE OEM = ? AND MODEL = ? AND CARTRIDGE = ?";
        $stmt_check_stock = $conn->prepare($sql_check_stock);
        if (!$stmt_check_stock) {
            throw new Exception("Error preparing stock check statement: " . $conn->error);
        }
        $stmt_check_stock->bind_param("sss", $OEM, $MODEL, $CARTRIDGE);
        $stmt_check_stock->execute();
        $stmt_check_stock->store_result();
        if ($stmt_check_stock->num_rows === 0) {
            throw new Exception("No matching stock entry found for the provided OEM, MODEL, and CARTRIDGE.");
        }
        $stmt_check_stock->bind_result($QUANTITY);
        $stmt_check_stock->fetch();
        $stmt_check_stock->close();

        // SQL to update the 'current_stock' table to decrement the quantity
        $sql_update_stock = "UPDATE current_stock SET QUANTITY = QUANTITY - 1 WHERE OEM = ? AND MODEL = ? AND CARTRIDGE = ?";
        $stmt_stock = $conn->prepare($sql_update_stock);
        if (!$stmt_stock) {
            throw new Exception("Error preparing stock update statement: " . $conn->error);
        }
        $stmt_stock->bind_param("sss", $OEM, $MODEL, $CARTRIDGE);
        if (!$stmt_stock->execute()) {
            throw new Exception("Error executing stock update statement: " . $stmt_stock->error);
        }
        if ($stmt_stock->affected_rows === 0) {
            throw new Exception("No rows updated in current_stock. Check if the request details match.");
        }
        $stmt_stock->close();

        // Check the updated quantity
        $sql_check_stock_after_update = "SELECT QUANTITY FROM current_stock WHERE OEM = ? AND MODEL = ? AND CARTRIDGE = ?";
        $stmt_check_stock_after_update = $conn->prepare($sql_check_stock_after_update);
        if (!$stmt_check_stock_after_update) {
            throw new Exception("Error preparing stock check after update statement: " . $conn->error);
        }
        $stmt_check_stock_after_update->bind_param("sss", $OEM, $MODEL, $CARTRIDGE);
        $stmt_check_stock_after_update->execute();
        $stmt_check_stock_after_update->store_result();
        $stmt_check_stock_after_update->bind_result($QUANTITY);
        $stmt_check_stock_after_update->fetch();
        $stmt_check_stock_after_update->close();

        // Send an email notification if quantity is below the threshold
        if ($QUANTITY < $threshold) {
            $mail = new PHPMailer(true);
            try {
                $sql_admin_emails = "SELECT EMAIL_ID FROM user_details WHERE USER_TYPE='officer'";
                $result_admin_emails = $conn->query($sql_admin_emails);

                if ($result_admin_emails->num_rows > 0) {
                    $admin_emails = [];
                    while ($row = $result_admin_emails->fetch_assoc()) {
                        if (filter_var($row['EMAIL_ID'], FILTER_VALIDATE_EMAIL)) {
                            $admin_emails[] = $row['EMAIL_ID'];
                        }
                    }
                }

                $mail->SMTPDebug = 0;
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'ekta24v@gmail.com';
                $mail->Password   = 'xfujamtssarffzlo';
                $mail->SMTPSecure = 'tls';
                $mail->Port       = 587;

                $mail->setFrom($from_email, 'Mailer');
                foreach ($admin_emails as $admin_email) {
                    $mail->addAddress($admin_email);
                }

                $mail->isHTML(true);
                $mail->Subject = 'Low Stock Alert';
                $mail->Body    = '<p>The following cartridge is below the threshold quantity of ' . $threshold . ':</p>';
                $mail->Body   .= '<p>OEM: ' . htmlspecialchars($OEM) . '<br>';
                $mail->Body   .= 'MODEL: ' . htmlspecialchars($MODEL) . '<br>';
                $mail->Body   .= 'CARTRIDGE: ' . htmlspecialchars($CARTRIDGE) . '<br>';
                $mail->Body   .= 'Quantity: ' . htmlspecialchars($QUANTITY) . '</p>';

                $mail->send();
            } catch (Exception $e) {
                $confirmation_message .= "Mailer Error: " . $mail->ErrorInfo;
            }
        }

        $conn->commit();
        $confirmation_message = "Request confirmed and stock updated successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $confirmation_message = "Error: " . $e->getMessage();
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confirmation Box</title>
<style>
    body {
        font-family: Tahoma, sans-serif;
        background-color: #f0f0f0;
        margin:0;
        width: 100%;
        height: 100vh;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    .header {
        background-color: #4CAF50;
        display: <?php echo !empty($confirmation_message) ? 'flex' : 'none'; ?>;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 50px;
        color: white;
        text-align: center;
        padding: 20px 0;
    }

    .confirmation-box {
        background-color: #fff;
        border: 1px solid #ccc;
        padding: 20px;
        border-radius: 5px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        max-width: 400px;
        text-align: center;
    }

    .confirmation-box h2 {
        margin-top: 0;
    }

    .btn-confirm {
        background-color: #4CAF50;
        color: white;
        border: none;
        padding: 10px 20px;
        text-align: center;
        text-decoration: none;
        display: inline-block;
        font-size: 16px;
        margin-top: 10px;
        cursor: pointer;
        border-radius: 5px;
    }
    .btn-confirm:hover {
        background-color: #45a049;
    }
</style>
</head>
<body>

<div class="header">
    <?php if (!empty($confirmation_message)) : ?>
        <?php echo $confirmation_message; ?>
    <?php endif; ?>
</div>

<div class="confirmation-box">
    <h2>Confirm Request</h2>
    <p>Are you sure you want to confirm this request?</p>
    <form method="post" action="userconfirmrequests.php?USER=<?php echo $_GET['USER']; ?>&REQUEST_NO=<?php echo $_GET['REQUEST_NO']; ?>&timestamp=<?php echo $_GET['timestamp']; ?>">
        <button type="submit" class="btn-confirm" <?php echo !empty($confirmation_message) ? 'disabled' : ''; ?>>Confirm</button>
    </form>
</div>

</body>
</html>
