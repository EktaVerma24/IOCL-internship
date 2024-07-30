<?php
session_start();
include 'partials/connect.php'; // Include database connection

// Function to fetch complaints with optional filters and sorting
function getFilteredComplaints($filter_date = null, $filter_action = null, $sort_order = 'DESC') {
    global $conn;
    
    // Base query to select all complaints
    $query = "SELECT * FROM complaints WHERE 1";

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
        die('Error fetching complaints: ' . mysqli_error($conn));
    }

    $complaints = array();
    while ($row = mysqli_fetch_assoc($query_run)) {
        $complaints[] = $row;
    }
    return $complaints;
}

// Update complaint action to 'RESOLVED' if provided
if (isset($_GET['COMPLAINT_ID'], $_GET['ACTION'])) {
    $COMPLAINT_ID = $_GET['COMPLAINT_ID'];
    $ACTION = $_GET['ACTION'];

    // Check if complaint hasn't been resolved
    $check_query = "SELECT * FROM complaints WHERE COMPLAINT_ID='$COMPLAINT_ID' AND STATUS != 'RESOLVED'";
    $check_result = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($check_result) > 0) {
        // Update action to 'RESOLVED'
        $query = "UPDATE complaints SET ACTION='RESOLVED', STATUS='RESOLVED' WHERE COMPLAINT_ID='$COMPLAINT_ID'";
        $result = mysqli_query($conn, $query);

        if (!$result) {
            die('Error updating complaint: ' . mysqli_error($conn));
        }

        $_SESSION['notification'] = "Complaint #$COMPLAINT_ID has been resolved.";

    } else {
        $_SESSION['notification'] = "Complaint #$COMPLAINT_ID has already been resolved.";
    }

    // Redirect to the same page after updates
    header("location: viewcomplaints.php");
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
    header("location: viewcomplaints.php");
    exit;
}

// Fetch all complaints or filtered complaints
if (isset($_GET['filter_date'])) {
    $filter_date = $_GET['filter_date'];
} else {
    $filter_date = null;
}

if (isset($_GET['filter_action'])) {
    $filter_action = $_GET['filter_action'];
} else {
    $filter_action = null;
}

$complaints = getFilteredComplaints($filter_date, $filter_action, $sort_order);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Complaints</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: Tahoma, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .navbar {
            background-color: #031854;
            padding: 8px 20px;
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #ffffff;
        }
        .navbar h3 {
            margin-left: 20px;
        }
        .navbar a {
            color: #ffffff;
            text-decoration: none;
            font-size: 16px;
            padding: 10px 15px;
            border-radius: 8px;
            transition: background-color 0.3s ease;
        }
        .navbar a:hover {
            background-color: #FF4900;
        }
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 20px auto;
            background-color: #ffffff;
            padding: 20px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
        }
        h1, h2 {
            text-align: center;
            color: #031854;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
        }
        .confirmed {
            background-color: #d4edda; /* Light green */
        }
        .notification {
            background-color: #4CAF50;
            color: white;
            text-align: center;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
        .filter-section {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f2f2f2;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: space-between; /* Space between filter and sort controls */
        }
        .filter-section label {
            margin-right: 10px;
        }
        .filter-section select, .filter-section input[type="date"] {
            padding: 5px;
            margin-right: 10px;
            border: 1px solid #ccc;
            border-radius: 3px;
        }
        .filter-section button{
            padding: 8px 15px;
            background-color: #031854;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            text-decoration: none; /* Remove underline from anchor tags */
            display: inline-block;
            text-align: center;
        }
        .filter-section button:hover {
            background-color: #FF4900;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h3>View Complaints</h3>
        <ul>
            <a href="officer.php">HOME</a>
            <a href="#" onclick="confirmLogout(event)">LOGOUT</a>        </ul>
    </div>

    <div class="container">
        <h1>View Complaints from Users</h1>
        
        <!-- Notification message -->
        <?php
        if (isset($_SESSION['notification'])) {
            echo '<div class="notification">' . htmlspecialchars($_SESSION['notification']) . '</div>';
            unset($_SESSION['notification']); // Clear the notification after displaying
        }
        ?>

        <div class="filter-section">
            <form action="" method="GET">
                <label for="filter_date">Filter by Date:</label>
                <input type="date" id="filter_date" name="filter_date" value="<?php echo htmlspecialchars(isset($_GET['filter_date']) ? $_GET['filter_date'] : ''); ?>">
                
                <label for="filter_action">Filter by Action:</label>
                <select name="filter_action" id="filter_action">
                    <option value="">SELECT ACTION</option>
                    <option value="PENDING" <?php echo ($_GET['filter_action'] ?? '') === 'PENDING' ? 'selected' : ''; ?>>PENDING</option>
                    <option value="RESOLVED" <?php echo ($_GET['filter_action'] ?? '') === 'RESOLVED' ? 'selected' : ''; ?>>RESOLVED</option>
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
                <a href="viewcomplaints.php">
                    <button style="background-color: #dc3545;">Clear Filters</button>
                </a>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>COMPLAINT ID</th>
                    <th>USER ID</th>
                    <th>USER</th>
                    <th>SUBJECT</th>
                    <th>COMPLAINTS</th>
                    <th>DATE</th>
                    <th>TIME</th>
                    <th>ACTION</th>
                    <th>STATUS</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($complaints)) {
                    foreach ($complaints as $row) {
                        // Determine row class based on status for styling
                        $row_class = '';
                        if ($row['STATUS'] === 'RESOLVED') {
                            $row_class = 'confirmed';
                        }
                    ?>
                        <tr class="<?php echo htmlspecialchars($row_class); ?>">
                            <td><?php echo htmlspecialchars($row['COMPLAINT_ID']); ?></td>
                            <td><?php echo htmlspecialchars($row['USER_ID']); ?></td>
                            <td><?php echo htmlspecialchars($row['USER']); ?></td>
                            <td><?php echo htmlspecialchars($row['SUBJECT']); ?></td>
                            <td><?php echo htmlspecialchars($row['COMPLAINTS']); ?></td>
                            <td><?php echo htmlspecialchars($row['DATE']); ?></td>
                            <td><?php echo htmlspecialchars($row['TIME']); ?></td>
                            <td><?php echo htmlspecialchars($row['ACTION']); ?></td>
                            <td><?php echo htmlspecialchars($row['STATUS']); ?></td>
                        </tr>
                    <?php }
                } else { ?>
                    <tr>
                        <td colspan="9">No Complaints Received Yet!!</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <script src="partials/logout.js"></script>

</body>
</html>
