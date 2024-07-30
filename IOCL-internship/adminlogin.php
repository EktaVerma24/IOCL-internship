<?php
// Initialize variables
$showError = "";

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include 'partials/connect.php'; // Include database connection file

    $username = $_POST["username"];
    $password = $_POST["password"];

    // Using prepared statement to prevent SQL injection
    $sql = "SELECT * FROM admin WHERE USERNAME=?";
    $stmt = mysqli_stmt_init($conn);

    if (mysqli_stmt_prepare($stmt, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        // Check if a record is found
        if ($row = mysqli_fetch_assoc($result)) {
            // Debug: Print the hashed password from the database
            // echo 'Stored Hashed Password: ' . $row['PASSWORD'] . '<br>';

            // Verify the password
            if ($password === $row['PASSWORD']) {
                session_start();
                $_SESSION['loggedin'] = true;
                $_SESSION['USERNAME'] = $username;

                header("Location: admin.php"); // Redirect to admin.php
                exit;
            } else {
                $showError = "Invalid Credentials: Incorrect password";
            }
        } else {
            $showError = "Invalid Credentials: Username not found";
        }
    } else {
        $showError = "SQL error: " . mysqli_stmt_error($stmt);
    }

    // Close connections
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <style>
        /* Basic styling */
        body {
            font-family: Tahoma, sans-serif;
            background-color: #f0f0f0;
            margin: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        .container {
            width: 400px;
            padding: 40px;
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        h1 {
            color: #031854;
            margin-top: 10px;
            margin-bottom: 20px;
        }
        form {
            display: flex;
            flex-direction: column;
        }
        .form-group {
            margin-bottom: 15px;
            text-align: left;
        }
        label {
            font-weight: bold;
            color: #031854;
            display: block;
            margin-bottom: 5px;
            margin-left: 10px;
        }
        input[type="text"],
        input[type="password"] {
            width: calc(100% - 22px);
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 20px;
            margin-top: 5px;
        }
        button {
            background-color: #031854;
            color: #fff;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 30px;
            transition: background-color 0.3s ease;
            margin-top: 10px;
        }
        button:hover {
            background-color: #FF4900;
        }
        /* Alert styling */
        .alert {
            padding: 15px;
            margin-top: 20px;
            border-radius: 4px;
            width: 100%;
            text-align: center;
            display: block; /* Changed from none to block to show alerts */
        }
        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Admin Login</h1>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit">Login</button>
        <?php if ($showError): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($showError) ?></div>
        <?php endif; ?>
    </form>
</div>

</body>
</html>
