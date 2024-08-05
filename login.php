<?php
ob_start(); // Start output buffering
session_start();

$login = false;
$showError = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include 'partials/connect.php'; // Include database connection file

    $USER = $_POST["username"];
    $password = $_POST["password"];

    // Using prepared statement to prevent SQL injection
    $sql = "SELECT * FROM user_details WHERE USER=? AND PASSWORD=?";
    $stmt = mysqli_stmt_init($conn);
    if (mysqli_stmt_prepare($stmt, $sql)) {
        mysqli_stmt_bind_param($stmt, "ss", $USER, $password);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            $_SESSION['loggedin'] = true;
            $_SESSION['USER'] = $USER;
            $_SESSION['USER_TYPE'] = $row['USER_TYPE'];

            if ($row['USER_TYPE'] == "engineer") {
                header("Location: engineer.php");
                exit;
            } elseif ($row['USER_TYPE'] == "user") {
                header("Location: welcome.php");
                exit;
            } else {
                header("Location: officer.php");
                exit;
            }
        } else {
            $showError = "Invalid Credentials";
        }
    } else {
        $showError = "Database query failed.";
    }
}
ob_end_flush(); // Flush the output buffer and turn off output buffering
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login Portal</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap');
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #ffffff, #d4e9fd);
        }
        .login-box {
            width: 400px;
            padding: 40px;
            background: #f9f9f9;
            border-radius: 10px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.8);
        }
        .login-box form {
            width: 100%;
        }
        h2 {
            font-size: 2em;
            color: #ff4500; /* IOCL orange */
            text-align: center;
            margin-bottom: 20px;
        }
        .input-box {
            position: relative;
            margin: 25px 0;
        }
        .input-box input {
            width: 100%;
            height: 50px;
            background: transparent;
            border: 2px solid #020130; /* IOCL blue */
            outline: none;
            border-radius: 40px;
            font-size: 1em;
            color: #2c3e50; /* IOCL blue */
            padding: 0 20px;
            transition: .5s ease;
        }
        .input-box input:focus,
        .input-box input:valid {
            border-color: #ff4500; /* IOCL orange */
        }
        .input-box label {
            position: absolute;
            top: 50%;
            left: 20px;
            transform: translateY(-50%);
            font-size: 1em;
            color: #2c3e50; /* IOCL blue */
            pointer-events: none;
            transition: .5s ease;
        }
        .input-box input:focus ~ label,
        .input-box input:valid ~ label {
            top: 1px;
            font-size: .8em;
            padding: 0 6px;
            color: #ff4500; /* IOCL orange */
            background-color: #f9f9f9;
        }
        .btn {
            width: 100%;
            height: 45px;
            background: #ff4500; /* IOCL orange */
            border: none;
            outline: none;
            border-radius: 40px;
            cursor: pointer;
            font-size: 1em;
            color: #fff;
            font-weight: 600;
            margin-top: 20px;
            transition: background-color 0.3s ease;
        }
        .btn:hover {
            background: #020130; /* Darker shade of IOCL orange */
        }
        .alert {
            padding: 15px;
            margin-top: 20px;
            border-radius: 4px;
            width: 100%;
            text-align: center;
            display: block;
        }
        .alert-success {
            background-color: #c3e6cb;
            border-color: #a4d2a5;
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
    <div class="login-box">
        <h2>USER LOGIN PORTAL</h2>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
            <div class="input-box">
                <input type="text" name="username" required>
                <label>Username</label>
            </div>
            <div class="input-box">
                <input type="password" name="password" required>
                <label>Password</label>
            </div>
            <button type="submit" class="btn">Login</button>
            <?php if ($showError): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($showError) ?></div>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>
