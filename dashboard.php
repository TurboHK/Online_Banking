<?php
session_start();
include 'db_user_connection.php';

if (!isset($_SESSION['username'])) {
    header("Location: index.html"); // If not logged in, redirect to login page
    exit();
}

$current_username = $_SESSION['username']; // Currently logged-in user's username

// Start timing
$start_time = microtime(true);

// Query the user ID corresponding to the current username
$stmt = $conn->prepare("SELECT id FROM users WHERE username=?");
$stmt->bind_param("s", $current_username);
$stmt->execute();
$result = $stmt->get_result();

// Get user ID
$user_data = $result->fetch_assoc();
$user_id = $user_data['id'] ?? 'N/A'; // If not found, default display is 'N/A'

$stmt->close();

// Initialize account balances
$savings_account_balance = 0.00;
$foreign_exchange_balance = 0.00;
$time_deposit_balance = 0.00;

// Query savings account balance (from debitcards table)
$stmt = $conn->prepare("
    SELECT SUM(balance) AS total_balance
    FROM debitcards
    INNER JOIN cards ON debitcards.id = cards.id
    WHERE cards.cardholder_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$savings_account_data = $result->fetch_assoc();
$savings_account_balance = $savings_account_data['total_balance'] ?? 0.00;
$stmt->close();

// Query foreign exchange account balance (from Account table)
$stmt = $conn->prepare("
    SELECT local_currency_balance
    FROM Account
    WHERE user_id = ? AND Account_type = 'foreign_exchange'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$foreign_exchange_data = $result->fetch_assoc();
$foreign_exchange_balance = $foreign_exchange_data['local_currency_balance'] ?? 0.00;
$stmt->close();

// Query time deposit account balance (from Account table)
$stmt = $conn->prepare("
    SELECT local_currency_balance
    FROM Account
    WHERE user_id = ? AND Account_type = 'time_deposit'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$time_deposit_data = $result->fetch_assoc();
$time_deposit_balance = $time_deposit_data['local_currency_balance'] ?? 0.00;
$stmt->close();

// Calculate the total balance
$total_balance = $savings_account_balance + $foreign_exchange_balance + $time_deposit_balance;

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
    <title>Dashboard | GBC Internet Banking</title>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header__content">
            <div class="header__logo">
                <a href="./dashboard.php"><img src="./assets/logo.png" alt="Bank Logo"></a>
            </div>
            <h1>Welcome to GBC Internet Banking</h1>
            <div class="header__right">
                Current User: <?php echo htmlspecialchars($_SESSION['username']); ?>
                <button class="logout-button" style="margin-left: 10px;" onclick="window.location.href='logout.php'">Logout</button>
            </div>
        </div>
    </header>

    <!-- Main Dashboard Content -->
    <main class="dashboard">
        <section class="account-summary">
            <h2>Account</h2>
            <div class="account">
                <h3>Total</h3>
                <span class="account-number">User ID: <?php echo htmlspecialchars($user_id); ?></span>
                <div class="account-balance"><?php echo number_format($total_balance, 2); ?> HKD</div>
            </div>
            <div class="sub-accounts">
                <div class="sub-account">
                    <span>Savings Account</span>
                    <span class="balance"><?php echo number_format($savings_account_balance, 2); ?> HKD</span>
                </div>
                <div class="sub-account">
                    <span>Foreign Exchange Account</span>
                    <span class="balance"><?php echo number_format($foreign_exchange_balance, 2); ?> HKD</span>
                </div>
                <div class="sub-account">
                    <span>Time Deposits</span>
                    <span class="balance"><?php echo number_format($time_deposit_balance, 2); ?> HKD</span>
                </div>
            </div>
        </section>

        <!-- Quick Access Links -->
        <section class="quick-links">
            <div class="link">
                <img src="./assets/icons/profile.png"/>
                <a href="./profile.php" class="quick-link-links"><span>My Personal Information</span></a>
            </div>
            <div class="link">
                <img src="./assets/icons/transaction.png"/>
                <a href="./transaction.php" class="quick-link-links"><span>Transactions</span></a>
            </div>
            <div class="link">
                <img src="./assets/icons/transfer.png"/>
                <a href="./transfer.php" class="quick-link-links"><span>Transfer</span></a>
            </div>
            <div class="link">
                <img src="./assets/icons/annuities.png"/>
                <a href="./time_deposit.php" class="quick-link-links"><span>Time Deposit</span></a>
            </div>
            <div class="link">
                <img src="./assets/icons/foreign-exchange.png"/>
                <a href="./foreign_exchange_management.php" class="quick-link-links"><span>Foreign Exchange</span></a>
            </div>
            <div class="link">
                <img src="./assets/icons/card.png"/>
                <a href="./user_card_management.php" class="quick-link-links"><span>Cards</span></a>
            </div>
            <div class="link">
                <img src="./assets/icons/credit.png"/>
                <a href="./applycredit.php" class="quick-link-links"><span>Apply for Credit Cards</span></a>
            </div>
        </section>
    </main>

    <footer class="footer">
        <?php if ($execution_time): ?>
            It took <?php echo $execution_time; ?> milliseconds to get data from the server. </p>
        <?php endif; ?>
        ©2024 Global Banking Corporation Limited. All rights reserved.
    </footer>
</body>
</html>
