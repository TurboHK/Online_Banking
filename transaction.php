<?php
session_start();
include 'db_user_connection.php';

if (!isset($_SESSION['username'])) {
    header("Location: index.html"); // Redirect to login page if not logged in
    exit();
}

$current_username = $_SESSION['username'];

// Pagination logic
$records_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Fetch filter options from the request
$filter_types = isset($_GET['transaction_type']) ? $_GET['transaction_type'] : [];
$filter_query = '';
$filter_values = [$current_username, $offset, $records_per_page];

if (!empty($filter_types)) {
    $placeholders = implode(',', array_fill(0, count($filter_types), '?'));
    $filter_query = " AND transactions.transaction_type IN ($placeholders)";
    $filter_values = array_merge([$current_username], $filter_types, [$offset, $records_per_page]);
}

// Start timing
$start_time = microtime(true);

// Fetch transactions for the current user with filters
$stmt = $conn->prepare("
    SELECT transactions.id AS transaction_id, cards.card_number, transactions.time AS transaction_date, transactions.amount, transactions.transaction_type
    FROM transactions
    INNER JOIN cards ON transactions.card_id = cards.id
    INNER JOIN users ON cards.cardholder_id = users.id
    WHERE users.username = ? $filter_query
    ORDER BY transactions.time DESC
    LIMIT ?, ?
");

$stmt->bind_param(str_repeat('s', count($filter_values)), ...$filter_values);
$stmt->execute();
$result = $stmt->get_result();

// Fetch total transaction count for pagination
$count_stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM transactions
    INNER JOIN cards ON transactions.card_id = cards.id
    INNER JOIN users ON cards.cardholder_id = users.id
    WHERE users.username = ? $filter_query
");
$count_stmt->bind_param(str_repeat('s', count($filter_values) - 2), ...array_slice($filter_values, 0, -2));
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_transactions = $count_result->fetch_assoc()['total'];
$count_stmt->close();

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
    <title>Transactions | GBC Internet Banking</title>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header__content">
            <div class="header__logo">
                <a href="./dashboard.php"><img src="./assets/logo.png" alt="Bank Logo"></a>
            </div>
            <h1>Transaction History</h1>
            <div class="header__right">
                Current User: <?php echo htmlspecialchars($_SESSION['username']); ?>
                <button class="logout-button" style="margin-left: 10px;" onclick="window.location.href='logout.php'">Logout</button>
            </div>
        </div>
    </header>

    <div class="container" style="margin-top: 150px;">
        <h2>Recent Transactions</h2>
        
        <!-- Filter Section -->
        <form method="GET" id="filter-form">
            <div class="filter-section">
                <label><input type="checkbox" name="transaction_type[]" value="exchange_t" <?php echo in_array('exchange_t', $filter_types) ? 'checked' : ''; ?>> Exchange</label>
                <label><input type="checkbox" name="transaction_type[]" value="swipe_t" <?php echo in_array('swipe_t', $filter_types) ? 'checked' : ''; ?>> Swipe</label>
                <label><input type="checkbox" name="transaction_type[]" value="transfer" <?php echo in_array('transfer', $filter_types) ? 'checked' : ''; ?>> Transfer</label>
                <label><input type="checkbox" name="transaction_type[]" value="local_currency_cash_deposit" <?php echo in_array('local_currency_cash_deposit', $filter_types) ? 'checked' : ''; ?>> Local Deposit</label>
                <label><input type="checkbox" name="transaction_type[]" value="local_currency_cash_withdrawal" <?php echo in_array('local_currency_cash_withdrawal', $filter_types) ? 'checked' : ''; ?>> Local Withdrawal</label>
                <button type="submit">Apply Filter</button>
            </div>
        </form>

        <table id="transaction-table">
            <thead>
                <tr>
                    <th>Transaction ID</th>
                    <th>Card Number</th>
                    <th>Transaction Type</th>
                    <th>Transaction Date</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['transaction_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['card_number']); ?></td>
                        <td><?php echo htmlspecialchars($row['transaction_type']); ?></td>
                        <td><?php echo htmlspecialchars($row['transaction_date']); ?></td>
                        <td><?php echo htmlspecialchars(number_format($row['amount'], 2)); ?> HKD</td>
                    </tr>
                <?php endwhile; ?>
                <?php if ($result->num_rows === 0): ?>
                    <tr>
                        <td colspan="5">No transactions found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="pagination">
            <?php
            $total_pages = ceil($total_transactions / $records_per_page);
            for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&<?php echo http_build_query(['transaction_type' => $filter_types]); ?>" class="<?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
        </div>
    </div>

    <div class="back-link">
        <p><a href="./dashboard.php">Return to dashboard</a></p>
    </div>
    
    <!-- Footer -->
    <footer class="footer">
        <?php if ($execution_time): ?>
            It took <?php echo $execution_time; ?> milliseconds to get data from the server.</p>
        <?php endif; ?>
        ©2024 Global Banking Corporation Limited. All rights reserved.
    </footer>
</body>
<style>
        .filter-section {
            margin-bottom: 20px;
            display: flex;
            justify-content: flex-start;
            gap: 20px;
        }
        .filter-section label {
            font-size: 14px;
        }
        .filter-section button {
            padding: 5px 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .filter-section button:hover {
            background-color: #0056b3;
        }
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
        .container h1{
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
    </style>
</html>
