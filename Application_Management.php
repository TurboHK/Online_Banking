<?php
session_start();
include 'db_connection.php';

// Check if the user is an administrator
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.html"); // Redirect to login page if not logged in or non-administrator
    exit();
}

$search_result = '';
$execution_time = ''; // Used to store execution time
$applycredits = []; // Used to store the information of the queried applications
$user_id = ''; // Initialize user ID to null

// Query all records or query by user ID
if (isset($_POST['search_user']) && !empty($_POST['user_id'])) {
    $user_id = $_POST['user_id'];  // Get the user ID entered by the user

    // start counting
    $start_time = microtime(true);

    // Query all credit card applications under this user ID
    $stmt = $conn->prepare("SELECT * FROM applycredit WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // If the application information is queried, it is stored in an array
    if ($result->num_rows > 0) {
        $applycredits = [];
        while ($row = $result->fetch_assoc()) {
            $applycredits[] = $row;  // Storing application information in an array
        }
        $search_result = "<table class='result-table'><tr><th>Apply ID</th><th>User ID</th><th>Name</th><th>Phone</th><th>Address</th><th>Status</th><th>Actions</th></tr>";
        foreach ($applycredits as $applycredit) {
            // If status is WAITING, the REFUSE and SUCCESS buttons are displayed.
            if ($applycredit['status'] == 'waiting') {
                $actions = "<form method='POST' id='form_" . $applycredit['apply_id'] . "'>
                    <button type='button' onclick='confirmAction(\"refuse\", " . $applycredit['apply_id'] . ")'>Reject</button>
                    <button type='button' onclick='confirmAction(\"success\", " . $applycredit['apply_id'] . ")'>Approve</button>
                </form>";
            } else {
                $actions = "<span>Complete</span>";
            }
            
            $search_result .= "<tr>
                <td>" . htmlspecialchars($applycredit['apply_id']) . "</td>
                <td>" . htmlspecialchars($applycredit['user_id']) . "</td>
                <td>" . htmlspecialchars($applycredit['name']) . "</td>
                <td>" . htmlspecialchars($applycredit['phone']) . "</td>
                <td>" . htmlspecialchars($applycredit['address']) . "</td>
                <td>" . htmlspecialchars($applycredit['status']) . "</td>
                <td>" . $actions . "</td>
            </tr>";
        }
        $search_result .= "</table>";
    } else {
        $search_result = "No applycredit records found for this user.";
    }

    $stmt->close();

    // End timing
    $end_time = microtime(true);
    $execution_time = round(($end_time - $start_time) * 1000, 2); // Convert to milliseconds
} else {
    // If there are no search criteria, check all application records
    $start_time = microtime(true);

    // Search all applycredit records
    $stmt = $conn->prepare("SELECT * FROM applycredit");
    $stmt->execute();
    $result = $stmt->get_result();

    // If the application information is queried, it is stored in an array
    if ($result->num_rows > 0) {
        $applycredits = [];
        while ($row = $result->fetch_assoc()) {
            $applycredits[] = $row;  // Storing application information in an array
        }
        $search_result = "<table class='result-table'><tr><th>Apply ID</th><th>User ID</th><th>Name</th><th>Phone</th><th>Address</th><th>Status</th><th>Actions</th></tr>";
        foreach ($applycredits as $applycredit) {
            // If status is WAITING, the REFUSE and SUCCESS buttons are displayed.
            if ($applycredit['status'] == 'waiting') {
                $actions = "<form method='POST' id='form_" . $applycredit['apply_id'] . "'>
                    <button type='button' onclick='confirmAction(\"refuse\", " . $applycredit['apply_id'] . ")'>Reject</button>
                    <button type='button' onclick='confirmAction(\"success\", " . $applycredit['apply_id'] . ")'>Approve</button>
                </form>";
            } else {
                $actions = "<span>Complete</span>";
            }
            
            $search_result .= "<tr>
                <td>" . htmlspecialchars($applycredit['apply_id']) . "</td>
                <td>" . htmlspecialchars($applycredit['user_id']) . "</td>
                <td>" . htmlspecialchars($applycredit['name']) . "</td>
                <td>" . htmlspecialchars($applycredit['phone']) . "</td>
                <td>" . htmlspecialchars($applycredit['address']) . "</td>
                <td>" . htmlspecialchars($applycredit['status']) . "</td>
                <td>" . $actions . "</td>
            </tr>";
        }
        $search_result .= "</table>";
    } else {
        $search_result = "No applycredit records found.";
    }

    $stmt->close();
}

// Handling of rejected operations
if (isset($_POST['refuse'])) {
    $apply_id = $_POST['refuse'];

    // Update the status of the applycredit table to refuse
    $stmt = $conn->prepare("UPDATE applycredit SET status = 'refuse' WHERE apply_id = ?");
    $stmt->bind_param("i", $apply_id);
    $stmt->execute();
    $stmt->close();

    // Refresh the page to reflect the update
    header("Location: Application_Management.php");
    exit();
}

// Synchronize card numbers logic
if (isset($_POST['synchronize'])) {
    $stmt = $conn->prepare("
        UPDATE cards 
        INNER JOIN creditcards ON cards.id = creditcards.id 
        SET cards.card_number = creditcards.creditcard_id
        WHERE cards.card_number IS NULL
    ");
    if ($stmt->execute()) {
        $sync_message = "Card numbers synchronized successfully.";
    } else {
        $sync_message = "Failed to synchronize card numbers.";
    }
    $stmt->close();
}

// Processing Successful Operations
if (isset($_POST['success'])) {
    $apply_id = $_POST['success'];

    // Update the status of the applycredit table to SUCCESS
    $stmt = $conn->prepare("UPDATE applycredit SET status = 'success' WHERE apply_id = ?");
    $stmt->bind_param("i", $apply_id);
    $stmt->execute();

    // Get information about this application
    $stmt = $conn->prepare("SELECT * FROM applycredit WHERE apply_id = ?");
    $stmt->bind_param("i", $apply_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $applycredit = $result->fetch_assoc();
    $stmt->close();

    if ($applycredit) {
        // Step 1: Insert a new record in the cards table
        $stmt = $conn->prepare("INSERT INTO cards (card_type, card_number, cardholder_id, type, blocked) VALUES ('credit', NULL, ?, 'credit', 0)");
        $stmt->bind_param("i", $applycredit['user_id']);
        $stmt->execute();
        $card_id = $conn->insert_id; // Get the inserted card's ID
        $stmt->close();

        // Step 2: Insert a new record in the creditcards table
        $stmt = $conn->prepare("INSERT INTO creditcards (id, application_id, quota, remaining_quota, repayment_date) VALUES (?, ?, ?, ?, ?)");
        $quota = 50000; // Initial credit limit
        $remaining_quota = 50000; // Remaining quota
        $repayment_date = date("Y-m-d", strtotime("+30 days")); // Repayment date: 30 days from today
        $stmt->bind_param("iiids", $card_id, $apply_id, $quota, $remaining_quota, $repayment_date);
        $stmt->execute();
        $stmt->close();
    }

    // Refresh the page to reflect the update
    header("Location: Application_Management.php");
    exit();
}

if (isset($_POST['clear'])) {
    $search_result = '';  // Clear Search Results
    $execution_time = '';  // Clear execution time
    $user_id = ''; // Clear User ID
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/dashboard.css" />
    <link rel="icon" href="assets/logo.png" type="image/png">
    <title>Credit Card Application Management | GBC Internet Banking</title>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header__content">
            <div class="header__logo">
                <a href="./admin_dashboard.php"><img src="./assets/logo.png" alt="Bank Logo"></a>
            </div>
            <h1>Credit Card Application Management</h1>
            <div class="header__right">
                Current Administrator: <?php echo htmlspecialchars($_SESSION['username']); ?>
                <button class="logout-button" style="margin-left: 10px;" onclick="window.location.href='logout.php'">Logout</button>
            </div>
        </div>
    </header>

    <!-- Main Dashboard Content -->
    <main class="dashboard">
        <!-- Search User ID Function -->
        <section class="user-search">
            <h2>Search User's Cards</h2>
            <form method="post" action="">
                <label for="user_id">User ID:</label>
                <input type="text" name="user_id" id="user_id" value="<?php echo htmlspecialchars($user_id); ?>" required>
                <input type="submit" name="search_user" value="Search">
                <input type="submit" name="clear" value="Clear"> <!-- Clear Search Results button -->
            </form>

            <!-- Show search results -->
            <div>
                <?php if ($execution_time): ?>
                    <p>Search executed in <span><?php echo $execution_time; ?></span> milliseconds.</p><br>
                <?php endif; ?>
                <?php
                // Output search results
                if ($search_result) {
                    echo $search_result;
                }
                ?>
            </div>
        </section>
        <p style="text-align: center; color: red;">Warning: Please press the button below to synchronize data after approving or rejecting any request!</p>

        <!-- Synchronize Button -->
        <div style="text-align: center; margin-top: 20px;">
            <form method="post" action="">
                <input type="submit" name="synchronize" value="Synchronize Card Numbers" style="padding: 10px 20px; font-size: 16px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;">
            </form>
        </div>

        <!-- Return to Admin Panel -->
        <div class="back-link">
            <p><a href="./admin_dashboard.php">Return to the dashboard</a></p>
        </div>
    </main>

    <footer class="footer">
        <?php if ($execution_time): ?>
            It took <?php echo $execution_time; ?> milliseconds to get data from the server.</p>
        <?php endif; ?>
        ©2024 Global Banking Corporation Limited. All rights reserved.
    </footer>

    <script>
    function confirmAction(action, applyId) {
        var confirmationMessage = "Are you sure you want to " + action + " this application?";
        if (confirm(confirmationMessage)) {
            var form = document.getElementById('form_' + applyId);
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = action;
            input.value = applyId;
            form.appendChild(input);
            form.submit();
        }
    }
    </script>

    <style>
        .result-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .result-table th, .result-table td {
            border: 1px solid #ddd;
            padding: 6px 10px;
            text-align: left;
        }
        .result-table th {
            background-color: #f4f4f4;
        }
        .result-table td button {
            padding: 5px 10px;
            margin: 2px;
            cursor: pointer;
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
            text-decoration: none;
            color: #0064d2;
        }
    </style>
</body>
</html>
