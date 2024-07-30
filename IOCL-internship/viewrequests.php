<?php
session_start();
include 'partials/connect.php'; // Include database connection

// Function to fetch requests with optional filters and sorting
function getFilteredRequests($filter_date = null, $filter_action = null, $sort_order = 'DESC') {
    global $conn;
    
    // Base query to select all requests
    $query = "SELECT * FROM requests WHERE 1";

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

// Update request action to 'CONFIRMED' and set confirmation status if provided
if (isset($_GET['REQUEST_NO'], $_GET['CONFIRMED'])) {
    $REQUEST_NO = $_GET['REQUEST_NO'];
    $CONFIRMED = $_GET['CONFIRMED'];

    // Check if request hasn't been confirmed and confirmation status isn't already set
    $check_query = "SELECT * FROM requests WHERE REQUEST_NO='$REQUEST_NO' AND ACTION != 'CONFIRMED' AND CONFIRMATION != 'CONFIRMED'";
    $check_result = mysqli_query($conn, $check_query);


    // Redirect to the same page after updates
    header("location: confirmrequests.php");
    exit;
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
    header("location: confirmrequests.php");
    exit;
}

// Fetch all requests or filtered requests
$filter_date = $_GET['filter_date'] ?? null;
$filter_action = $_GET['filter_action'] ?? null;

$requests = getFilteredRequests($filter_date, $filter_action, $sort_order);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Requests</title>
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
        nav {
            background-color: #031854;
            color: #fff;
            padding: 8px 10px;
            width: 100%;
            box-sizing: border-box;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
        }
        nav h1 {
            margin: 0;
            font-size: 20px;
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
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        nav ul li a:hover {
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
        .filter-section button:hover {
            background-color: #FF4900;
            transition: opacity 0.3s ease;
        }
    </style>
</head>
<body>
    <nav>
        <h3>View All Requests</h3>
        <ul>
            <li><a href="officer.php">Home</a></li>
        
           <li><a href="#" onclick="confirmLogout(event)">Logout</a> </li>        </ul>
    </nav>

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
            <div>
                <?php
                $current_sort = isset($_GET['sort']) ? $_GET['sort'] : 'DESC';
                $next_sort = ($current_sort === 'DESC') ? 'ASC' : 'DESC';
                ?>
                <a href="?filter_date=<?php echo urlencode($filter_date ?? ''); ?>&filter_action=<?php echo urlencode($filter_action ?? ''); ?>&sort=<?php echo $next_sort; ?>">
                    <button><?php echo ($current_sort === 'DESC') ? 'Sort Oldest First' : 'Sort Newest First'; ?></button>
                </a>
                
                <!-- Clear Filters button -->
                <a href="viewrequests.php">
                    <button style="background-color: #dc3545;">Clear Filters</button>
                </a>
            </div>
        </div>

        <h2>ALL REQUESTS FROM USERS</h2>

        <!-- Notification message -->
        <?php
        if (isset($_SESSION['notification'])) {
            echo '<div class="notification">' . $_SESSION['notification'] . '</div>';
            unset($_SESSION['notification']);
        }
        ?>

        <table>
            <thead>
                <tr>
                    <th>REQUEST NO</th>
                    <th>DATE</th>
                    <th>OEM</th>
                    <th>MODEL</th>
                    <th>CARTRIDGE</th>
                    <th>ACTION</th>
                    <th>CONFIRMATION</th>
                    
                </tr>
            </thead>
            <tbody>
                <?php
                if (empty($requests)) {
                    echo '<tr><td colspan="7">No requests found.</td></tr>';
                } else {
                    foreach ($requests as $row) {
                        $rowClass = ($row['ACTION'] === 'CONFIRMED') ? 'confirmed' : '';
                        echo '<tr class="' . $rowClass . '">';
                        echo '<td>' . $row['REQUEST_NO'] . '</td>';
                        echo '<td>' . $row['DATE'] . '</td>';
                        echo '<td>' . $row['OEM'] . '</td>';
                        echo '<td>' . $row['MODEL'] . '</td>';
                        echo '<td>' . $row['CARTRIDGE'] . '</td>';
                        echo '<td>' . $row['ACTION'] . '</td>';
                        echo '<td>' . $row['CONFIRMATION'] . '</td>';
                        echo '</tr>';
                    }
                }
                ?>
            </tbody>
        </table>
    </div>
    <script src="partials/logout.js"></script>

</body>
</html>
