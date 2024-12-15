<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['username'])) {
    header("Location: index.html");
    exit();
}

$cardNumber = $_GET['card'] ?? null;

if (!$cardNumber) {
    echo "Invalid card number.";
    exit();
}

// Start timing
$start_time = microtime(true);

// 查询储蓄卡详情
$stmt = $conn->prepare("SELECT * FROM debit_cards WHERE card_number=?");
$stmt->bind_param("s", $cardNumber);
$stmt->execute();
$result = $stmt->get_result();
$cardData = $result->fetch_assoc();

if (!$cardData) {
    echo "Card details not found.";
    exit();
}

$stmt->close();

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
    <title>Debit Card Details | GBC Internet Banking</title>
</head>
<body>
        <!-- Header -->
        <header class="header">
            <div class="header__content">
                <div class="header__logo">
                    <a href="./dashboard.html"><img src="./assets/logo.png" alt="Bank Logo"></a>
                </div>
                <h1>Card Details</h1>
                <div class="header__right">
                    User: Sengokuran
                    <button class="logout-button" style="margin-left: 10px;" onclick="window.location.href='index.html'">Logout</button>
                </div>
            </div>
        </header>
    <div class="container">
        <h1>Debit Card Details</h1>
        <div class="details">
            <p><strong>Card Number:</strong> <?php echo htmlspecialchars($cardData['card_number']); ?></p>
            <p><strong>Last Transaction:</strong> <?php echo htmlspecialchars($cardData['last_transaction']); ?></p>
            <p><strong>Spending Limit:</strong> <?php echo htmlspecialchars($cardData['spending_limit']); ?></p>
            <p><strong>Available Balance:</strong> <?php echo htmlspecialchars($cardData['available_balance']); ?></p>
        </div>

        <div class="button-group">
            <button class="freeze-button" onclick="handleCardAction('freeze')">Freeze Card</button>
            <button class="unlock-button" onclick="handleCardAction('unlock')">Unlock Card</button>
        </div>

        <div style="text-align: center;">
            <a href="./user_card_management.html" style="text-decoration: none; color: #007BFF;">Back to Card Management</a>
        </div>
    </div>

    <footer class="footer">
        <?php if ($execution_time): ?>
            It took <?php echo $execution_time; ?> milliseconds to get data from the server.</p>
        <?php endif; ?>
        ©2024 Global Banking Corporation Limited. All rights reserved.
    </footer>
    
    <script>
        function handleCardAction(action) {
            const cardNumber = "<?php echo htmlspecialchars($cardData['card_number']); ?>";
            const confirmation = confirm(`Are you sure you want to ${action} the card ending in ${cardNumber.slice(-4)}?`);
            if (confirmation) {
                alert(`Card ending in ${cardNumber.slice(-4)} has been ${action}ed.`);
            }
        }
    </script>

<style>
    .container {
        max-width: 600px;
        margin: 50px auto;
        background-color: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 1px 6px rgba(0, 0, 0, 0.1);
    }

    .details {
        margin: 20px 0;
    }
    .details p {
        font-size: 16px;
        margin-bottom: 10px;
    }
    .button-group {
        text-align: center;
        margin-top: 20px;
    }
    .button-group button {
        padding: 10px 20px;
        margin: 10px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
    }
    .freeze-button {
        background-color: #FF4C4C;
        color: white;
    }
    .freeze-button:hover {
        background-color: #E53935;
    }
    .unlock-button {
        background-color: #4CAF50;
        color: white;
    }
    .unlock-button:hover {
        background-color: #388E3C;
    }
</style>
</body>
</html>