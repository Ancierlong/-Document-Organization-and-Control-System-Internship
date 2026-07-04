<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$conn = require '../db_connect.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$display = "";
$concepts = [];

// --- Fetch Current Balance Early for Display ---
$currentBalanceValue = getCurrentBalance($conn);
if ($currentBalanceValue === false) {
    // Handle error case where balance couldn't be fetched
    $currentBalanceDisplay = "Error";
    $display .= "<h2 style='color:orange;'>Could not retrieve current balance.</h2>"; // Add to feedback
    error_log("Failed to retrieve current balance for display.");
} else {
    // Format the balance as Philippine Peso
    $currentBalanceDisplay = '₱ ' . number_format($currentBalanceValue, 2);
}
// --- End Fetch Current Balance ---


// Fetch approved concept papers for the dropdown, excluding concept_id 0 as it's now 'Others'
$query_concepts = "SELECT id, concept_title FROM conceptpapers WHERE status = 'Approved' AND id != 0 ORDER BY concept_title ASC";
$result_concepts = mysqli_query($conn, $query_concepts);

if ($result_concepts) {
    while ($row = mysqli_fetch_assoc($result_concepts)) {
        $concepts[$row['id']] = $row['concept_title'];
    }
    mysqli_free_result($result_concepts);
} else {
    error_log("Error fetching concept papers: " . mysqli_error($conn));
    // Handle error appropriately, maybe display a message
}


function getCurrentBalance($conn) {
    // Make sure $conn is valid before using it
    if (!$conn) {
        error_log("Database connection is not valid in getCurrentBalance.");
        return false;
    }
    $prepared_stmt = $conn->prepare("SELECT balance FROM funds WHERE id = 1");
    if ($prepared_stmt === false) {
        error_log("Error preparing statement to get balance: " . $conn->error);
        return false;
    }
    if (!$prepared_stmt->execute()) {
        error_log("Error executing statement to get balance: " . $prepared_stmt->error);
        $prepared_stmt->close();
        return false;
    }
    $result = $prepared_stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $prepared_stmt->close(); // Close statement here
        return $row['balance'];
    } else {
        // If no row exists (e.g., table is empty), return 0 or handle as error
        $prepared_stmt->close();
        error_log("No balance found in funds table for id = 1.");
        // Return 0.00 as a default if no record found
        return 0.00;
    }
}

// Added $custom_purpose parameter
function updateBalanceAndLog($conn, $concept_id, $type, $amount, $userid, $custom_purpose = null) {
    // Use global $display AND the balance display variable
    global $display, $currentBalanceDisplay; // Make sure to update display value if transaction succeeds
    date_default_timezone_set("Asia/Manila");
    $datetime = date("Y-m-d H:i:s");

    // Validate amount is positive
    if ($amount <= 0) {
        $display = "<h2 style='color:red;'>Amount must be a positive number.</h2>";
        return;
    }

    // Validate concept_id: must be a positive integer or 0 (for 'Others')
    if (!is_int($concept_id) || $concept_id < 0) {
        $display = "<h2 style='color:red;'>Invalid concept selection value.</h2>";
        return;
    }

    // If concept_id is 0 ('Others'), ensure custom_purpose is provided
    if ($concept_id === 0 && empty($custom_purpose)) {
        $display = "<h2 style='color:red;'>Please specify the purpose for 'Others'.</h2>";
        return;
    }

    // Sanitize custom_purpose for database storage if it's not null
    $custom_purpose_for_db = ($custom_purpose !== null) ? htmlspecialchars(trim($custom_purpose)) : null;


    // Start transaction for atomicity
    mysqli_begin_transaction($conn);

    try {
        // Fetch current balance within the transaction using FOR UPDATE for locking
        $stmt_get_balance = $conn->prepare("SELECT balance FROM funds WHERE id = 1 FOR UPDATE");
         if (!$stmt_get_balance) {
             throw new Exception("Error preparing get balance statement: " . $conn->error);
         }
         if (!$stmt_get_balance->execute()) {
            throw new Exception("Error executing get balance statement: " . $stmt_get_balance->error);
         }
         $result_balance = $stmt_get_balance->get_result();
         if (!($row_balance = $result_balance->fetch_assoc())) {
            throw new Exception("Funds record not found.");
         }
         $current_balance = $row_balance['balance'];


        // Calculate new balance
        // 'credit' reduces the balance (money going out), 'debit' increases it (money coming in)
        $new_balance = ($type === 'credit') ? $current_balance - $amount : $current_balance + $amount;

        // Prevent balance going below zero for CREDITS (subtractions)
        if ($type === 'credit' && $new_balance < 0) {
            throw new Exception("Insufficient funds for this credit transaction (balance would go below zero).");
        }

        // Update balance
        $stmt_update = $conn->prepare("UPDATE funds SET balance = ? WHERE id = 1");
        if (!$stmt_update) {
            throw new Exception("Error preparing UPDATE statement: " . $conn->error);
        }
        $stmt_update->bind_param("d", $new_balance);
        if (!$stmt_update->execute()) {
            throw new Exception("Error executing UPDATE statement: " . $stmt_update->error);
        }


        // Log transaction
        // *** NOTE: This INSERT query now includes 'custom_purpose' ***
        // *** Your 'logs_funds' table MUST have a 'custom_purpose' column for this to work ***
        $log_query = "INSERT INTO logs_funds (concept_id, custom_purpose, transaction_type, amount, transaction_date, user_id, previous_balance, new_balance) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_log = $conn->prepare($log_query);
        if (!$stmt_log) {
            throw new Exception("Error preparing log statement: " . $conn->error);
        }
        // Bind concept_id as integer (0 for Others, actual ID for concepts)
        $concept_id_for_db = (int)$concept_id;
        // Bind the custom purpose (will be null for non-others transactions)
        // Assuming custom_purpose is VARCHAR/TEXT, bind as 's'
        $stmt_log->bind_param("issdsidd", $concept_id_for_db, $custom_purpose_for_db, $type, $amount, $datetime, $userid, $current_balance, $new_balance);


        if (!$stmt_log->execute()) {
            throw new Exception("Error executing log statement: " . $stmt_log->error);
        }


        // If all successful, commit transaction
        mysqli_commit($conn);
        $display .= "<h2 style='color:green;'>Balance updated and logged successfully! New Balance: ₱ " . number_format($new_balance, 2) . "</h2>";

        // --- Update the display variable for the top balance display ---
        $currentBalanceDisplay = '₱ ' . number_format($new_balance, 2);
        // --- End Update Display Variable ---


        // Close all statements at the end of the successful transaction
        $stmt_get_balance->close();
        $stmt_update->close();
        $stmt_log->close();

    } catch (Exception $e) {
        // If any error occurred, roll back transaction
        mysqli_rollback($conn);
        error_log("Transaction failed: " . $e->getMessage());
        $display .= "<h2 style='color:red;'>Transaction failed: " . htmlspecialchars($e->getMessage()) . "</h2>";
        // Close any potentially open statements in catch block
         if (isset($stmt_get_balance) && $stmt_get_balance instanceof mysqli_stmt) $stmt_get_balance->close();
         if (isset($stmt_update) && $stmt_update instanceof mysqli_stmt) $stmt_update->close();
         if (isset($stmt_log) && $stmt_log instanceof mysqli_stmt) $stmt_log->close();

    }
}


// --- Process POST requests AFTER fetching initial balance ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Removed the general_fund_submit block entirely

    if (isset($_POST['credit_debit_submit'])) {
        // Use FILTER_VALIDATE_INT to get concept_id, which will be the integer value from the select option (0 or actual ID)
        $concept_id_for_log = filter_input(INPUT_POST, 'concept_title_select', FILTER_VALIDATE_INT);
        $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
        $type = filter_input(INPUT_POST, 'transaction_type');
        // Get custom purpose if 'Others' is selected, sanitize it slightly for display/storage
        $custom_purpose = isset($_POST['other_purpose']) ? trim($_POST['other_purpose']) : null;


        // Ensure user_id is set in session before using it
        $userid = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

        // Validate inputs
        if ($concept_id_for_log === false || $concept_id_for_log === null) {
            $display = "<h2 style='color:red;'>Please select a valid Concept Paper or 'Others'.</h2>";
        } elseif ($amount === false || $amount <= 0) {
            $display = "<h2 style='color:red;'>Please enter a valid positive amount.</h2>";
        } elseif (($type !== 'credit' && $type !== 'debit')) {
            $display = "<h2 style='color:red;'>Invalid transaction type selected.</h2>";
        } elseif ($userid === null) {
             $display = "<h2 style='color:red;'>Error: User session not found. Please log in again.</h2>";
        } elseif ($concept_id_for_log === 0 && empty($custom_purpose)) {
            // Specific check for 'Others' requiring custom purpose
             $display = "<h2 style='color:red;'>Please specify the purpose for 'Others'.</h2>";
        }
        else {
            // All inputs are valid, proceed with update and log
            // Pass custom_purpose to the update function
            updateBalanceAndLog($conn, $concept_id_for_log, $type, $amount, $userid, $custom_purpose);
        }
    }
}
// --- End Process POST requests ---


// Fetch ALL log data - DataTables will handle pagination/sorting client-side
$logs = [];
$log_query = "SELECT
                    lf.transaction_date,
                    u.username,
                    CASE
                        -- Modified CASE to show 'Others: [custom purpose]' if available
                        WHEN lf.concept_id = 0 THEN CONCAT('Others: ', COALESCE(lf.custom_purpose, 'N/A'))
                        ELSE c.concept_title
                    END AS concept_title_display, -- Use alias for clarity
                    c.concept_date,
                    lf.transaction_type,
                    lf.amount,
                    lf.previous_balance,
                    lf.new_balance
                    FROM logs_funds lf
                    JOIN users u ON lf.user_id = u.id
                    -- Left join conceptpapers only if concept_id is not 0 (prevents errors if a conceptpaper is deleted)
                    LEFT JOIN conceptpapers c ON lf.concept_id = c.id AND lf.concept_id != 0
                    ORDER BY lf.transaction_date DESC"; // Initial sort order from DB (DataTables can override)

$log_result = mysqli_query($conn, $log_query);
if ($log_result) {
    while ($row = mysqli_fetch_assoc($log_result)) {
        $logs[] = $row;
    }
    mysqli_free_result($log_result);
} else {
    error_log("Error fetching logs: " . mysqli_error($conn));
    $display .= "<h2 style='color:orange;'>Could not fetch transaction logs.</h2>";
}

// Close connection AFTER all DB operations for this page load are done
if ($conn) { // Check if connection is still valid before closing
     mysqli_close($conn);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS Department Database Management System | Manage Concept Funds</title>

    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

    <style>
        /* Your existing styles */
        body {
            font-family: Arial, sans-serif;
            background-color: #7F1416;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
        }

        .manage-balance-container {
            background-color: #fff;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
            padding: 25px;
            width: 100%;
            max-width: 900px; /* Added max-width for better layout on large screens */
            margin: 20px auto;
        }

        .manage-balance-container h2 {
            text-align: center;
            margin-bottom: 5px; /* Space between H2 and balance */
            color: #333;
        }

        /* --- Style for Current Balance Display --- */
        .current-balance-display {
           text-align: center;
           font-size: 1.5em; /* Slightly larger */
           font-weight: bold;
           color: #0056b3; /* Dark blue */
           margin-top: 5px; /* Space from header */
           margin-bottom: 20px; /* Space before feedback/hr */
           padding: 10px;
           background-color: #e7f3ff; /* Light blue background */
           border: 1px solid #b8daff; /* Light blue border */
           border-radius: 4px;
        }
        .current-balance-display strong {
             color: #004085; /* Even darker blue for the value */
             /* Optionally add padding if needed: padding-left: 5px; */
        }
        /* --- End Current Balance Style --- */


        .manage-balance-container hr {
            border: none;
            height: 1px;
            background-color: #ddd;
            margin-top: 20px;
            margin-bottom: 20px;
        }

        /* ... rest of your existing styles ... */
         .manage-balance-container label {
            font-size: 13px;
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
            color: #555;
        }
        .manage-balance-container input[type="number"],
        .manage-balance-container select,
        .manage-balance-container input[type="text"] { /* Added text input */
            width: 100%;
            padding: 9px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 14px;
        }
         .manage-balance-container input[type="submit"] {
            background-color: #007BFF;
            font-size: 16px;
            color: #fff;
            padding: 12px 25px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: auto;
            transition: background-color 0.3s ease;
            display: inline-block;
        }
        .manage-balance-container input[type="submit"]:hover {
            background-color: #0056b3;
        }
         .manage-balance-container .button.backtodash {
            display: block;
            width: fit-content;
            margin: 20px auto 0 auto;
            text-align: center;
            padding: 10px 20px;
            background-color: #ff851b;
            color: #fff;
            text-decoration: none;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .manage-balance-container .button.backtodash:hover {
             background-color: #d47716;
        }
         .form-section {
            margin-bottom: 25px;
            padding: 20px;
            border: 1px solid #eee;
            border-radius: 4px;
            background-color: #f9f9f9;
        }
        .form-section h3 {
            margin-top: 0;
            margin-bottom: 15px;
            text-align: center;
             color: #333;
            font-size: 1.2em;
        }
        .form-inline {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
            margin-bottom: 15px;
        }
        .form-inline label {
             flex-basis: 150px;
             text-align: right;
             margin-bottom: 0;
             padding-right: 10px;
        }
        .form-inline > input,
        .form-inline > select {
             flex-grow: 1;
             min-width: 200px;
        }
        .form-inline small {
             flex-basis: 100%;
             text-align: left;
             margin-left: 165px; /* Adjust to align with input */
             color: #666;
             font-size: 12px;
        }
         .log-list-section {
            margin-top: 30px;
            padding: 20px;
            border: 1px solid #eee;
            border-radius: 4px;
            background-color: #f9f9f9;
        }
        .log-list-section h3 {
            margin-top: 0;
            margin-bottom: 15px;
            text-align: center;
             color: #333;
            font-size: 1.2em;
        }
        .dataTables_wrapper {
            font-size: 14px;
        }
        .dataTables_filter label,
        .dataTables_length label {
            font-weight: normal;
            margin-bottom: 10px;
            display: inline-flex;
            align-items: center;
        }
         .dataTables_filter input {
             margin-left: 0.5em;
             padding: 6px;
             border: 1px solid #ccc;
             border-radius: 4px;
        }
         .dataTables_length select {
             margin-left: 0.5em;
             margin-right: 0.5em;
             padding: 6px;
             border: 1px solid #ccc;
             border-radius: 4px;
        }
        .log-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .log-table th, .log-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
            vertical-align: middle;
        }
        .log-table th {
            background-color: #f2f2f2;
            font-weight: bold;
            cursor: pointer;
        }
        .log-table tbody tr:nth-child(odd) {
             background-color: #fdfdfd;
        }
        .log-table tbody tr:hover {
            background-color: #e9e9e9;
        }
        .dataTables_paginate .paginate_button {
            padding: 6px 12px;
            margin: 0 2px;
            border: 1px solid #ddd;
            border-radius: 3px;
            cursor: pointer;
            background-color: #fff;
            color: #337ab7;
        }
        .dataTables_paginate .paginate_button:hover {
             background-color: #eee;
             border-color: #ddd;
             text-decoration: none;
        }
         .dataTables_paginate .paginate_button.current {
            background-color: #337ab7;
            color: #fff;
            border-color: #337ab7;
        }
         .dataTables_paginate .paginate_button.disabled,
         .dataTables_paginate .paginate_button.disabled:hover {
             color: #999;
             background-color: #fff;
             border-color: #ddd;
             cursor: default;
        }
        .form-section > form > div:last-child {
            text-align: center;
            margin-top: 10px;
        }

        /* Style for the 'Others' input field container */
        #otherPurposeContainer {
            display: none; /* Hidden by default */
            flex-wrap: wrap; /* Maintain flex layout */
            gap: 15px; /* Maintain gap */
            align-items: center; /* Align items */
            margin-bottom: 15px; /* Maintain margin */
        }
        #otherPurposeContainer label {
            flex-basis: 150px; /* Match other labels */
            text-align: right;
            margin-bottom: 0;
            padding-right: 10px;
        }
        #otherPurposeContainer input[type="text"] {
            flex-grow: 1;
            min-width: 200px;
        }


    </style>
</head>
<body>
<div class="manage-balance-container">
    <h2>Manage Concept Funds</h2>

    <div class="current-balance-display">
        Current Balance: <strong><?php echo htmlspecialchars($currentBalanceDisplay); ?></strong>
    </div>

    <?php echo $display; // Display feedback messages (like success/error H2s) ?>
    <hr>

    <div class="form-section">
        <h3>Record Fund Transaction (Related to Concept Paper or Other Purpose)</h3>
        <form method="post" action="">
            <div class="form-inline">
                <label for="amount">Enter Amount:</label>
                <input type="number" id="amount" name="amount" step="0.01" min="0.01" required>
            </div>
            <div class="form-inline">
                <label for="transaction_type">Transaction Type:</label>
                <select id="transaction_type" name="transaction_type" required>
                    <option value="credit">Credit (Subtract from balance)</option>
                    <option value="debit">Debit (Add to balance)</option>
                </select>
            </div>
            <div class="form-inline">
                <label for="concept_title_select">Related To:</label>
                <select id="concept_title_select" name="concept_title_select" required>
                    <option value="" disabled selected>-- Select a Concept Paper or Other --</option>
                    <?php if (!empty($concepts)): ?>
                        <?php foreach ($concepts as $id => $title): ?>
                            <option value="<?php echo htmlspecialchars($id); ?>">
                                <?php echo htmlspecialchars($title); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <option value="0">Others (Specify)</option> </select>
                <small>Select the concept paper related to this transaction, or select 'Others'.</small>
            </div>

            <div class="form-inline" id="otherPurposeContainer">
                <label for="other_purpose">Specify Purpose:</label>
                <input type="text" id="other_purpose" name="other_purpose" placeholder="e.g., Department Supplies, Event X">
            </div>

            <div>
                <input type="submit" name="credit_debit_submit" value="Update Balance">
            </div>
        </form>
    </div>

    <div class="log-list-section">
        <h3>Transaction Logs</h3>
        <?php if (!empty($logs)): ?>
            <table id="logTable" class="log-table display">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>User</th>
                        <th>Concept Paper / Purpose</th> <th>Concept Date</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Previous Balance</th>
                        <th>New Balance</th>
                    </tr>
                </thead>
                <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($log['transaction_date']); ?></td>
                                <td><?php echo htmlspecialchars($log['username']); ?></td>
                                <td><?php echo htmlspecialchars($log['concept_title_display'] ?: 'N/A'); ?></td>
                                <td><?php echo $log['concept_date'] ? htmlspecialchars(date('Y-m-d', strtotime($log['concept_date']))) : 'N/A'; ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($log['transaction_type'])); ?></td>

                                <?php
                                // Determine the color for the amount based on transaction type
                                // credit = red (subtraction), debit = blue (addition)
                                $amount_color = ($log['transaction_type'] === 'credit') ? 'red' : 'blue';
                                ?>
                                <td style="color: <?php echo $amount_color; ?>;" data-sort="<?php echo $log['amount']; ?>">
                                    <?php echo '₱ ' . htmlspecialchars(number_format($log['amount'], 2)); ?>
                                </td>

                                <td style="color: #ff851b;" data-sort="<?php echo $log['previous_balance']; ?>">
                                    <?php echo '₱ ' . htmlspecialchars(number_format($log['previous_balance'], 2)); ?>
                                </td>

                                <td style="color: green;" data-sort="<?php echo $log['new_balance']; ?>">
                                    <?php echo '₱ ' . htmlspecialchars(number_format($log['new_balance'], 2)); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
            </table>
        <?php else: ?>
            <p>No transaction logs found.</p>
        <?php endif; ?>
    </div>

    <hr>
    <a href="../dashboard.php" class="button backtodash">Back to Dashboard</a>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<script>
$(document).ready(function() {
    $('#logTable').DataTable({
        "paging": true,
        "pageLength": 5,
        "lengthChange": true,
        "lengthMenu": [ [5, 10, 25, 50, -1], [5, 10, 25, 50, "All"] ],
        "ordering": true,
        "info": true,
        "searching": true,
        "order": [[ 0, "asc" ]], // Default sort by Date descending
        "columnDefs": [
            // 'Concept Paper / Purpose' (index 2) can be 'Others' or a concept title.
            // If it's 'Others', the 'Concept Date' (index 3) will be 'N/A', so keep index 3 not sortable.
            { "orderable": false, "targets": 3 }, // Make 'Concept Date' not sortable
            // Make currency columns sort numerically but display with formatting (DataTables usually handles this well with num-fmt)
            { "type": "num-fmt", "targets": [5, 6, 7] } // Amount, Previous, New Balance
        ]
    });

    // JavaScript to show/hide the "Specify Purpose" input for "Others"
    const conceptSelect = $('#concept_title_select');
    const otherPurposeContainer = $('#otherPurposeContainer');
    const otherPurposeInput = $('#other_purpose');

    conceptSelect.on('change', function() {
        if (this.value === '0') { // Check if 'Others' (value 0) is selected
            otherPurposeContainer.css('display', 'flex'); // Use flex to match form-inline styling
            otherPurposeInput.attr('required', true); // Make required when visible
        } else {
            otherPurposeContainer.hide();
            otherPurposeInput.removeAttr('required'); // Not required when hidden
            otherPurposeInput.val(''); // Clear the input when hiding
        }
    });

    // Trigger change on page load to ensure correct initial state
    conceptSelect.trigger('change');
});
</script>

</body>
</html>