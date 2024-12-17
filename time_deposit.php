<?php
session_start();
include 'db_user_connection.php';

if (!isset($_SESSION['username'])) {
    header("Location: index.html");
    exit();
}

$current_username = $_SESSION['username'];
$user_id = $_SESSION['user_id'] ?? null;

// Fetch or set user ID in the session
if (!$user_id) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $current_username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $user_id = $user['id'];
    } else {
        die("User not found. Please log in again.");
    }
}

// Fetch user's debit cards
$stmt = $conn->prepare("SELECT debitcards.debitcard_id, debitcards.balance, cards.id as card_id 
                        FROM debitcards
                        INNER JOIN cards ON debitcards.id = cards.id
                        WHERE cards.cardholder_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$debit_cards = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch available time deposits with pagination
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

$stmt = $conn->prepare("SELECT * FROM local_currency_time_deposits LIMIT ?, ?");
$stmt->bind_param("ii", $offset, $records_per_page);
$stmt->execute();
$deposits = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$count_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM local_currency_time_deposits");
$count_stmt->execute();
$total_deposits = $count_stmt->get_result()->fetch_assoc()['total'];
$count_stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['select_deposit'])) {
    $debitcard_id = $_POST['account_from'] ?? '';
    $deposit_id = $_POST['deposit_id'] ?? '';

    // Input validation
    if (!ctype_digit($debitcard_id) || strlen($debitcard_id) != 16) {
        echo "<script>alert('Invalid Debit Card ID. Please select a valid card.');</script>";
        exit();
    }

    if (!ctype_digit($deposit_id)) {
        echo "<script>alert('Invalid Deposit ID. Please enter a valid number.');</script>";
        exit();
    }

    // Fetch deposit details
    $stmt = $conn->prepare("SELECT amount, maturity FROM local_currency_time_deposits WHERE id = ?");
    $stmt->bind_param("i", $deposit_id);
    $stmt->execute();
    $deposit_result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$deposit_result) {
        echo "<script>alert('Deposit ID not found.');window.location.href = 'time_deposit.php';</script>";
        exit();
    }

    $deposit_amount = $deposit_result['amount'];
    $maturity_days = $deposit_result['maturity'];

    // Calculate maturity date by adding maturity days to the current date
    $maturity_date = date('Y-m-d', strtotime("+$maturity_days days"));

    // Check card balance
    $stmt = $conn->prepare("SELECT balance FROM debitcards WHERE debitcard_id = ?");
    $stmt->bind_param("d", $debitcard_id);
    $stmt->execute();
    $balance_result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$balance_result) {
        echo "<script>alert('Card not found. Please select a valid card.');window.location.href = 'time_deposit.php';</script>";
        exit();
    }

    $balance = $balance_result['balance'];

    if ($balance >= $deposit_amount) {
        // Start transaction
        $conn->begin_transaction();
        try {
            $new_balance = $balance - $deposit_amount;

            // Update debit card balance
            $stmt = $conn->prepare("UPDATE debitcards SET balance = ? WHERE debitcard_id = ?");
            $stmt->bind_param("di", $new_balance, $debitcard_id);
            $stmt->execute();
            $stmt->close();

            // Insert into user_time_deposits with dynamic maturity_date
            $stmt = $conn->prepare("INSERT INTO user_time_deposits (user_id, deposit_id, deposit_amount, deposit_date, maturity_date)
                                    VALUES (?, ?, ?, NOW(), ?)");
            $stmt->bind_param("iiis", $user_id, $deposit_id, $deposit_amount, $maturity_date);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            echo "<script>alert('Deposit successful!'); window.location.href = 'time_deposit.php';</script>";
        } catch (Exception $e) {
            $conn->rollback();
            echo "<script>alert('Transaction failed. Please try again.');window.location.href = 'time_deposit.php';</script>";
        }
    } else {
        echo "<script>alert('Insufficient balance. Current balance: " . number_format($balance, 2) . " HKD');window.location.href = 'time_deposit.php';</script>";
    }
}
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
    <div class="sub-account">
                        <label for="account_from">From</label>
                        <select id="account_from" name="account_from" required onchange="updateBalance()">
                            <option value="" disabled selected>Select Account</option>
                            <?php foreach ($debit_cards as $card): ?>
                                <option value="<?php echo $card['debitcard_id']; ?>" data-balance="<?php echo $card['balance']; ?>">
                                    <?php echo $card['debitcard_id']; ?> - <?php echo number_format($card['balance'], 2); ?> HKD available
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

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
                        <td><?php echo htmlspecialchars($deposit['maturity']); ?> days</td>
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
