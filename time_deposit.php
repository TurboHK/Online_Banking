<?php
session_start();
include 'db_user_connection.php';

if (!isset($_SESSION['username'])) {
    header("Location: index.html"); // Redirect to login page if not logged in
    exit();
}

$current_username = $_SESSION['username'];
// Start timing
$start_time = microtime(true);

// Pagination logic for listing time deposits
$records_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Fetch all available time deposit options
$deposits = [];
$stmt = $conn->prepare("SELECT * FROM local_currency_time_deposits LIMIT ?, ?");
$stmt->bind_param("ii", $offset, $records_per_page);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $deposits[] = $row;
}

// Fetch total time deposit count for pagination
$count_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM local_currency_time_deposits");
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_deposits = $count_result->fetch_assoc()['total'];
$count_stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['select_deposit'])) {
    $debitcard_id = trim($_POST['debitcard_id']); // Trim to remove whitespace
    $deposit_id = trim($_POST['deposit_id']);

    // Validate input data
    if (empty($deposit_id) || !ctype_digit($deposit_id)) {
        echo "<script>alert('Invalid Deposit ID. Please enter a valid number.');</script>";
        exit();
    }
    if (empty($debitcard_id) || !ctype_digit($debitcard_id) || strlen($debitcard_id) != 16) {
        echo "<script>alert('Invalid Debit Card ID. Please enter a valid 16-digit number.');</script>";
        exit();
    }

    $deposit_id = (int)$deposit_id;
    $debitcard_id = (int)$debitcard_id;

    // Get the amount from the selected time deposit
    $stmt = $conn->prepare("SELECT amount FROM local_currency_time_deposits WHERE id = ?");
    $stmt->bind_param("i", $deposit_id);
    $stmt->execute();
    $deposit_result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$deposit_result) {
        echo "<script>alert('Deposit ID not found. Please check and try again.');</script>";
        exit();
    }

    $deposit_amount = $deposit_result['amount'];

    // Check if debit card has sufficient balance
    $balance_stmt = $conn->prepare("SELECT balance FROM debitcards WHERE debitcard_id = ?");
    $balance_stmt->bind_param("d", $debitcard_id); // Using double (d) for BIGINT
    $balance_stmt->execute();
    $balance_result = $balance_stmt->get_result()->fetch_assoc();
    $balance_stmt->close();
    if (!$balance_result) {
        echo "<script>alert('Debit Card ID not found. Please check and try again.');</script>";
        exit();
    }

    $balance = $balance_result['balance'];

    if ($balance >= $deposit_amount) {
        // Start transaction to ensure atomicity
        $conn->begin_transaction();
        
        try {
            // Update balance by deducting the deposit amount
            $new_balance = $balance - $deposit_amount;
            $update_balance_stmt = $conn->prepare("UPDATE debitcards SET balance = ? WHERE debitcard_id = ?");
            $update_balance_stmt->bind_param("di", $new_balance, $debitcard_id);
            $update_balance_stmt->execute();
            $update_balance_stmt->close();

            // Get the user ID based on the current session's username
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->bind_param("s", $current_username);
            $stmt->execute();
            $user_result = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$user_result) {
                echo "<script>alert('User not found. Please try again later.');</script>";
                exit();
            }

            $user_id = $user_result['id'];

            // Insert into user_time_deposits table
            $insert_stmt = $conn->prepare("INSERT INTO user_time_deposits (user_id, deposit_id, deposit_amount, deposit_date, maturity_date) VALUES (?, ?, ?, NOW(), ADDDATE(NOW(), INTERVAL 1 YEAR))");
            $insert_stmt->bind_param("iii", $user_id, $deposit_id, $deposit_amount);
            $insert_stmt->execute();
            $insert_stmt->close();

            // Commit the transaction
            $conn->commit();

            echo "<script>alert('Deposit successful.'); window.location.href = 'local_currency_time_deposits.php';</script>";
        } catch (Exception $e) {
            // Rollback transaction in case of error
            $conn->rollback();
            echo "<script>alert('An error occurred while processing the deposit. Please try again.');</script>";
        }
    } else {
        echo "<script>alert('Insufficient balance in the debit card. Current balance: " . number_format($balance, 2) . " HKD.');</script>";
    }
}

// End timing
$end_time = microtime(true);
$execution_time = round(($end_time - $start_time) * 1000, 2); // Convert to milliseconds
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/dashboard.css" />
    <link rel="icon" href="assets/logo.png" type="image/png">
    <title>Local Currency Time Deposits | GBC Internet Banking</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 8px;
            text-align: center;
        }

        th {
            background-color: #f4f4f4;
        }

        .pagination {
            margin-top: 20px;
            text-align: center;
        }

        .pagination a {
            padding: 8px 16px;
            margin: 0 4px;
            text-decoration: none;
            color: #007bff;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .pagination a:hover {
            background-color: #e9ecef;
        }

        .pagination .active {
            background-color: #007bff;
            color: white;
            border: 1px solid #007bff;
        }

        .container {
            width: 80%;
            margin: 120px auto 20px;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .container h1 {
            text-align: center;
            color: #333;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            text-decoration: none;
            color: #0064d2;
        }

        .input-section {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .input-section label {
            font-size: 14px;
        }

        .input-section input {
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .input-section button {
            padding: 5px 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .input-section button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header__content">
            <div class="header__logo">
                <a href="./dashboard.php"><img src="./assets/logo.png" alt="Bank Logo"></a>
            </div>
            <h1>Local Currency Time Deposits</h1>
            <div class="header__right">
                Current User: <?php echo htmlspecialchars($_SESSION['username']); ?>
                <button class="logout-button" style="margin-left: 10px;" onclick="window.location.href='logout.php'">Logout</button>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="input-section">
        <form id="deposit-form" method="POST" action="">
    <div class="input-section">
    <label for="debitcard_id">Please enter card number:</label>
<input type="number" id="debitcard_id" name="debitcard_id" required>

<label for="deposit_id">Please enter deposit ID:</label>
<input type="number" id="deposit_id" name="deposit_id" required>

<button type="submit" name="select_deposit">Submit</button>

    </div>
        </form>

        </div>

        <h2>Available Time Deposits</h2>
        <table id="transaction-table">
            <thead>
                <tr>
                    <th>Deposit ID</th>
                    <th>Interest Rate</th>
                    <th>Maturity Period</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($deposits as $deposit): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($deposit['id']); ?></td>
                        <td><?php echo htmlspecialchars($deposit['interest_rate']); ?>%</td>
                        <td><?php echo htmlspecialchars($deposit['maturity']); ?> months</td>
                        <td><?php echo htmlspecialchars(number_format($deposit['amount'], 2)); ?> HKD</td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($deposits)): ?>
                    <tr>
                        <td colspan="4">No available time deposit options at the moment.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="pagination">
            <?php
            $total_pages = ceil($total_deposits / $records_per_page);
            for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>" class="<?php echo $i === $page ? 'active' : ''; ?>"> <?php echo $i; ?> </a>
            <?php endfor; ?>
        </div>
    </div>

    <div class="back-link">
        <p><a href="./dashboard.php">Return to dashboard</a></p>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <?php if ($execution_time): ?>
            It took <?php echo $execution_time; ?> milliseconds to get data from the server. </p>
        <?php endif; ?>
        ©2024 Global Banking Corporation Limited. All rights reserved.
    </footer>
</body>
</html>