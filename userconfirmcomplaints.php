<?php
session_start();

// Initialize notification variable
$confirmation_message = '';

// Ensure the request parameters are set and match the session values
if (!isset($_GET['USER'], $_GET['COMPLAINT_ID'], $_GET['timestamp'])) {
    header("location: userconfirmcomplaints.php");
    exit;
}

// Directly handle confirmation
include 'partials/connect.php'; // Ensure your database connection is included

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $COMPLAINT_ID = $_GET['COMPLAINT_ID'];
    
    // SQL to update the 'STATUS' column in your 'complaints' table to 'RESOLVED'
    $sql = "UPDATE complaints SET STATUS='RESOLVED' WHERE COMPLAINT_ID = ?";
    
    // Prepare statement
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $COMPLAINT_ID); // 'i' for integer
    
    // Execute the statement
    if ($stmt->execute()) {
        $confirmation_message = '<span style="background-color: #4CAF50; color: white; border-radius: 5px; padding: 20px; text-align: center; font-size: 24px;">Complaint Resolved</span>';
    } else {
        $confirmation_message = "Error updating record: " . $conn->error;
    }
    
    // Close statement and database connection
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confirmation Box - Resolve Complaint</title>
<style>
    body {
        font-family: Tahoma, sans-serif;
        background-color: #f0f0f0;
        margin: 0;
        align-items: center;
        height: 100vh;
    }
    .header {
        background-color: #4CAF50;
        color: white;
        text-align: center;
        padding: 20px 0;
        margin-bottom: 20px;
        display: <?php echo !empty($confirmation_message) ? 'block' : 'none'; ?>;
    }
    .confirmation-box {
        background-color: #fff;
        border: 1px solid #ccc;
        padding: 20px;
        border-radius: 5px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        max-width: 400px;
        text-align: center;
        margin: 0 auto;
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
    .confirmation-message {
        margin-top: 20px;
    }
</style>
</head>
<body>

<div class="header">
    <?php if (!empty($confirmation_message)) : ?>
        <div>
            <?php echo $confirmation_message; ?>
        </div>
    <?php endif; ?>
</div>

<div class="confirmation-box">
    <h2>Resolve Complaint</h2>
    <p>Are you sure you want to resolve this complaint?</p>
    <form method="post" action="userconfirmcomplaints.php?USER=<?php echo $_GET['USER']; ?>&COMPLAINT_ID=<?php echo $_GET['COMPLAINT_ID']; ?>&timestamp=<?php echo $_GET['timestamp']; ?>">
        <button type="submit" class="btn-confirm" <?php echo !empty($confirmation_message) ? 'disabled' : ''; ?>>Resolve</button>
    </form>
</div>

</body>
</html>
