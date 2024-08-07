<?php
require 'partials/connect.php';

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

// Fetch all complaints or filtered complaints based on query parameters
$filter_date = $_GET['filter_date'] ?? null;
$filter_action = $_GET['filter_action'] ?? null;
$sort_order = $_GET['sort'] ?? 'DESC';

$complaints = getFilteredComplaints($filter_date, $filter_action, $sort_order);

// Generate the Excel file
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="complaints.xls"');

echo '<table>
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
        <tbody>';

foreach ($complaints as $row) {
    echo '<tr>
            <td>' . htmlspecialchars($row['COMPLAINT_ID']) . '</td>
            <td>' . htmlspecialchars($row['USER_ID']) . '</td>
            <td>' . htmlspecialchars($row['USER']) . '</td>
            <td>' . htmlspecialchars($row['SUBJECT']) . '</td>
            <td>' . htmlspecialchars($row['COMPLAINTS']) . '</td>
            <td>' . htmlspecialchars($row['DATE']) . '</td>
            <td>' . htmlspecialchars($row['TIME']) . '</td>
            <td>' . htmlspecialchars($row['ACTION']) . '</td>
            <td>' . htmlspecialchars($row['STATUS']) . '</td>
          </tr>';
}

echo '</tbody></table>';
?>
