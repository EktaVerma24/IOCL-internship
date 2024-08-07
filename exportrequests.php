<?php
require ('partials/connect.php');

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

// Fetch all requests or filtered requests based on query parameters
$filter_date = $_GET['filter_date'] ?? null;
$filter_action = $_GET['filter_action'] ?? null;
$sort_order = $_GET['sort'] ?? 'DESC';

$requests = getFilteredRequests($filter_date, $filter_action, $sort_order);

// Generate the Excel file
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="requests.xls"');

echo '<table>
        <thead>
            <tr>
                <th>REQUEST NO</th>
                <th>USER ID</th>
                <th>USER</th>
                <th>DATE</th>
                <th>OEM</th>
                <th>MODEL</th>
                <th>CARTRIDGE</th>
                <th>ACTION</th>
                <th>CONFIRMATION</th>
            </tr>
        </thead>
        <tbody>';

foreach ($requests as $row) {
    echo '<tr>
            <td>' . htmlspecialchars($row['REQUEST_NO']) . '</td>
            <td>' . htmlspecialchars($row['USER_ID']) . '</td>
            <td>' . htmlspecialchars($row['USER']) . '</td>
            <td>' . htmlspecialchars($row['DATE']) . '</td>
            <td>' . htmlspecialchars($row['OEM']) . '</td>
            <td>' . htmlspecialchars($row['MODEL']) . '</td>
            <td>' . htmlspecialchars($row['CARTRIDGE']) . '</td>
            <td>' . htmlspecialchars($row['ACTION']) . '</td>
            <td>' . htmlspecialchars($row['CONFIRMATION']) . '</td>
          </tr>';
}

echo '</tbody></table>';
?>
