<?php
session_start();
include 'partials/connect.php'; // Include database connection

// Ensure the user is logged in
if (!isset($_SESSION['USER'])) {
    header("Location: welcome.php");
    exit();
}

// Get the logged-in user's ID
$user = $_SESSION['USER'];

// Function to fetch requests for the logged-in user with optional filters and sorting
function getUserRequests($user, $filter_date = null, $filter_action = null, $sort_order = 'DESC') {
    global $conn;

    // Base query to select requests for the specific user
    $query = "SELECT * FROM requests WHERE USER = '$user'";

    // Add filters if provided
    if ($filter_date) {
        $query .= " AND DATE = '$filter_date'";
    }
    if ($filter_action) {
        $query .= " AND ACTION = '$filter_action'";
    }

    // Add sorting by date with dynamic order
    $query .= " ORDER BY DATE $sort_order";

    $query_run = mysqli_query($conn, $query);

    if (!$query_run) {
        die('Error fetching requests: ' . mysqli_error($conn));
    }

    $requests = array();
    while ($row = mysqli_fetch_assoc($query_run)) {
        $requests[] = $row;
    }
    return $requests;
}

// Handle sorting button action
if (isset($_GET['sort'])) {
    $sort_order = $_GET['sort'];
} else {
    $sort_order = 'DESC'; // Default sorting order (latest date first)
}

// Handle filter clearing button action
if (isset($_GET['clear_filters'])) {
    // Redirect to the page without filter parameters
    header("location: myrequests.php");
    exit();
}

// Fetch all requests or filtered requests for the logged-in user
$filter_date = $_GET['filter_date'] ?? null;
$filter_action = $_GET['filter_action'] ?? null;

$requests = getUserRequests($user, $filter_date, $filter_action, $sort_order);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Requests</title>
    <style>
        body {
            font-family: Tahoma, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 80%;
            margin: 20px auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
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
        .navbar {
            background-color: #031854;
            width: 100%;
            padding: 15px 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #ffffff;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 50px;
            height: 40px;
        }
        .navbar h2 {
            margin: 0;
            font-size: 24px;
        }
        .navbar a {
            color: #ffffff;
            text-decoration: none;
            font-size: 16px;
            padding: 10px 10px;
            border-radius: 8px;
            transition: background-color 0.3s ease;
            margin-right: 15px;
        }
        .navbar a:hover {
            background-color: #FF4900;
            border-radius: 5px;
        }
        .welcome-message {
            color: #333;
            font-size: 1.2em;
            margin-bottom: 10px;
        }
        .confirmed {
            background-color: #c3e6cb; /* Light green color for confirmed requests */
        }
        .notification {
            background-color: #c3e6cb;
            color: #155724;
            text-align: center;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .filter-section {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .filter-section label {
            margin-right: 10px;
        }
        .filter-section select, .filter-section input[type="date"] {
            padding: 10px;
            margin-right: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .filter-section button {
            padding: 10px 20px;
            background-color: #031854;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }
        .filter-section button:hover{
            background-color: #FF4900;
            transform: translateY(-5px);
        }
        .right {
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h2>My Requests</h2>
        <div>
            <a href="welcome.php">Home</a>
            <a href="#" onclick="confirmLogout(event)">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="welcome-message">
            Welcome, <?php echo $_SESSION['USER']; ?>
        </div>

        <div class="filter-section">
            <form action="" method="GET">
                <label for="filter_date">Filter by Date:</label>
                <input type="date" id="filter_date" name="filter_date" value="<?php echo isset($_GET['filter_date']) ? $_GET['filter_date'] : ''; ?>">

                <label for="filter_action">Filter by Action:</label>
                <select name="filter_action" id="filter_action">
                    <option value="">SELECT ACTION</option>
                    <option value="PENDING" <?php echo ($_GET['filter_action'] ?? '') === 'PENDING' ? 'selected' : ''; ?>>PENDING</option>
                    <option value="CONFIRMED" <?php echo ($_GET['filter_action'] ?? '') === 'CONFIRMED' ? 'selected' : ''; ?>>CONFIRMED</option>
                </select>

                <button type="submit">Filter</button>
            </form>

            <!-- Add sorting and filter clearing buttons -->
            <div class="right">
                <?php
                $current_sort = isset($_GET['sort']) ? $_GET['sort'] : 'DESC';
                $next_sort = ($current_sort === 'DESC') ? 'ASC' : 'DESC';
                ?>
                <a href="?filter_date=<?php echo urlencode($filter_date ?? ''); ?>&filter_action=<?php echo urlencode($filter_action ?? ''); ?>&sort=<?php echo $next_sort; ?>">
                    <button><?php echo ($current_sort === 'DESC') ? 'Sort Oldest First' : 'Sort Newest First'; ?></button>
                </a>

                <!-- Clear Filters button -->
                <a href="myrequests.php">
                    <button style="background-color: #dc3545;">Clear Filters</button>
                </a>
            </div>
        </div>

        <h2>MY REQUESTS</h2>

        <!-- Notification message -->
        <?php
        if (isset($_SESSION['notification'])) {
            echo '<div class="notification">' . $_SESSION['notification'] . '</div>';
            unset($_SESSION['notification']); // Clear the notification after displaying
        }
        ?>

        <table border="1">
            <thead>
                <tr>
                    <th>REQUEST NO.</th>
                    <th>OEM</th>
                    <th>MODEL</th>
                    <th>CARTRIDGE</th>
                    <th>DATE</th>
                    <th>TIME</th>
                    <th>ACTION</th>
                    <th>CONFIRMATION</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($requests)) {
                    foreach ($requests as $row) {
                        // Determine row class based on status for styling
                        $row_class = '';
                        if ($row['ACTION'] === 'CONFIRMED' && $row['CONFIRMATION'] === 'CONFIRMED') {
                            $row_class = 'confirmed';
                        }
                    ?>
                        <tr class="<?php echo $row_class; ?>">
                            <td><?php echo $row['REQUEST_NO']; ?></td>
                            <td><?php echo $row['OEM']; ?></td>
                            <td><?php echo $row['MODEL']; ?></td>
                            <td><?php echo $row['CARTRIDGE']; ?></td>
                            <td><?php echo $row['DATE']; ?></td>
                            <td><?php echo $row['TIME']; ?></td>
                            <td><?php echo $row['ACTION']; ?></td>
                            <td><?php echo $row['CONFIRMATION']; ?></td>
                        </tr>
                <?php }
                } else { ?>
                    <tr>
                        <td colspan="8" style="text-align: center;">No requests found</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <script>
        function confirmLogout(event) {
            event.preventDefault();
            if (confirm('Are you sure you want to log out?')) {
                window.location.href = 'logout.php'; // Redirect to logout page
            }
        }
    </script>
</body>
</html>
