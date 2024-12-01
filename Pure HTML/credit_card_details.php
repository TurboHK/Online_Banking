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

// 查询信用卡详情
$stmt = $conn->prepare("SELECT * FROM credit_cards WHERE card_number=?");
$stmt->bind_param("s", $cardNumber);
$stmt->execute();
$result = $stmt->get_result();
$cardData = $result->fetch_assoc();

if (!$cardData) {
    echo "Card details not found.";
    exit();
}

$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/dashboard.css" />
    <link rel="icon" href="assets/logo.png" type="image/png">
    <title>Credit Card Details | GBC Internet Banking</title>
    <style>
        .container {
            max-width: 600px;
            margin: 50px auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 6px rgba(0, 0, 0, 0.1);
        }
        h1, p {
            text-align: center;
            margin-bottom: 20px;
        }
        .details {
            margin: 20px 0;
        }
        .details p {
            font-size: 16px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Credit Card Details</h1>
        <div class="details">
            <p><strong>Card Number:</strong> <?php echo htmlspecialchars($cardData['card_number']); ?></p>
            <p><strong>Last Transaction:</strong> <?php echo htmlspecialchars($cardData['last_transaction']); ?></p>
            <p><strong>Repayment Date:</strong> <?php echo htmlspecialchars($cardData['repayment_date']); ?></p>
            <p><strong>Credit Limit:</strong> <?php echo htmlspecialchars($cardData['credit_limit']); ?> HKD</p>
            <p><strong>Available Balance:</strong> <?php echo htmlspecialchars($cardData['available_balance']); ?> HKD</p>
        </div>
        <div style="text-align: center;">
            <a href="manage_cards.php" style="text-decoration: none; color: #007BFF;">Back to Card Management</a>
        </div>
    </div>
</body>
</html>
