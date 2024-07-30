<?php
// Initialize variables
$showMessage = "";
$showError = "";

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include 'partials/connect.php'; // Include database connection file

    $username = $_POST["username"];
    $email = $_POST["email"];
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    // Check if passwords match
    if ($password !== $confirm_password) {
        $showError = "Passwords do not match!";
    } else {
        // Check if username or email already exists
        $sql = "SELECT * FROM admin WHERE USERNAME=? OR EMAIL_ID=?";
        $stmt = mysqli_stmt_init($conn);

        if (mysqli_stmt_prepare($stmt, $sql)) {
            mysqli_stmt_bind_param($stmt, "ss", $username, $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($result->num_rows > 0) {
                $showError = "Username or Email already exists!";
            } else {
                // Prepare and bind
                $sql = "INSERT INTO admin (USERNAME, EMAIL_ID, PASSWORD) VALUES (?, ?, ?)";
                $stmt = mysqli_stmt_init($conn);

                if (mysqli_stmt_prepare($stmt, $sql)) {
                    mysqli_stmt_bind_param($stmt, "sss", $username, $email, $password);

                    // Execute the query
                    if (mysqli_stmt_execute($stmt)) {
                        $showMessage = "New record created successfully";
                    } else {
                        $showError = "Error: " . mysqli_stmt_error($stmt);
                    }
                }
            }
        }
        // Close connections
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
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
        h2 {
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
        input[type="email"],
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
            display: block;
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
        .login-button {
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
        .login-button:hover {
            background-color: #FF4900;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Sign Up</h2>
    <form action="adminsignup.php" method="post">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" placeholder="Username" required>
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="Email" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Password" required>
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
        </div>
        <button type="submit">Sign Up</button>
        <?php if ($showMessage): ?>
            <div class="alert alert-success"><?= htmlspecialchars($showMessage) ?></div>
            <a href="adminlogin.php"><button class="login-button">Login</button></a>
        <?php endif; ?>
        <?php if ($showError): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($showError) ?></div>
        <?php endif; ?>
    </form>
</div>

</body>
</html>
