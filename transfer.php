<?php
session_start();
include 'db_connection.php';

// 如果未登录，则重定向到登录页面
if (!isset($_SESSION['username'])) {
    header("Location: index.html");
    exit();
}

$current_username = $_SESSION['username']; // 当前登录用户名

// 检查并设置用户ID
if (!isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $current_username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
    } else {
        die("User not found. Please log in again.");
    }
}

// 从会话获取用户ID
$user_id = $_SESSION['user_id'];

// 获取当前用户的储蓄卡信息
$stmt = $conn->prepare("SELECT debitcards.debitcard_id, debitcards.balance, cards.id as card_id 
                        FROM debitcards
                        INNER JOIN cards ON debitcards.id = cards.id
                        INNER JOIN users ON cards.cardholder_id = users.id
                        WHERE users.id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$debit_cards = [];
while ($row = $result->fetch_assoc()) {
    $debit_cards[] = $row;
}
$stmt->close();

$transfer_error = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account_from = $_POST['account_from'] ?? '';
    $account_to = $_POST['account_to'] ?? '';
    $amount = $_POST['amount'] ?? 0;

    if (empty($account_from) || empty($account_to) || $amount <= 0) {
        $transfer_error = "All fields are required, and amount must be greater than 0.";
    } else {
        // 检查目标账户是否存在
        $stmt = $conn->prepare("SELECT cards.cardholder_id, debitcards.id, debitcards.balance 
                                FROM debitcards 
                                INNER JOIN cards ON debitcards.id = cards.id 
                                WHERE debitcards.debitcard_id = ?");
        $stmt->bind_param("s", $account_to);
        $stmt->execute();
        $result = $stmt->get_result();
        $recipient_card = $result->fetch_assoc();
        $stmt->close();

        if (!$recipient_card) {
            $transfer_error = "Recipient account not found.";
        } else {
            $sender_card = array_filter($debit_cards, fn($card) => $card['debitcard_id'] == $account_from);
            $sender_card = reset($sender_card);

            if (!$sender_card || $sender_card['balance'] < $amount) {
                $transfer_error = "Insufficient balance.";
            } else {
                $conn->begin_transaction();
                try {
                    // 更新发起人余额
                    $new_balance_sender = $sender_card['balance'] - $amount;
                    $stmt = $conn->prepare("UPDATE debitcards SET balance = ? WHERE debitcard_id = ?");
                    $stmt->bind_param("ds", $new_balance_sender, $account_from);
                    $stmt->execute();

                    // 更新接收人余额
                    $new_balance_recipient = $recipient_card['balance'] + $amount;
                    $stmt = $conn->prepare("UPDATE debitcards SET balance = ? WHERE debitcard_id = ?");
                    $stmt->bind_param("ds", $new_balance_recipient, $account_to);
                    $stmt->execute();

                    // 同步发起人储蓄卡余额到 account 表
                    $stmt = $conn->prepare("
                        UPDATE account 
                        INNER JOIN cards ON account.user_id = cards.cardholder_id 
                        INNER JOIN debitcards ON debitcards.id = cards.id 
                        SET account.local_currency_balance = debitcards.balance 
                        WHERE account.Account_type = 'card' AND debitcards.debitcard_id = ?
                    ");
                    $stmt->bind_param("s", $account_from);
                    $stmt->execute();

                    // 同步接收人储蓄卡余额到 account 表
                    $stmt->bind_param("s", $account_to);
                    $stmt->execute();

                    // 添加转账记录
                    $stmt = $conn->prepare("INSERT INTO transfer (payer_id, payee_id) VALUES (?, ?)");
                    $stmt->bind_param("ii", $_SESSION['user_id'], $recipient_card['cardholder_id']);
                    $stmt->execute();

                    // 添加交易记录
                    $stmt = $conn->prepare("INSERT INTO transactions (transaction_type, card_id, amount) VALUES ('transfer', ?, ?)");
                    $stmt->bind_param("id", $sender_card['card_id'], $amount);
                    $stmt->execute();

                    $conn->commit();
                    $success_message = "Transfer successful.";
                } catch (Exception $e) {
                    $conn->rollback();
                    $transfer_error = "Transfer failed. Please try again.";
                }
            }
        }
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
    <title>Send Transfer | GBC Internet Banking</title>
</head>
<body>
<header class="header">
    <div class="header__content">
        <div class="header__logo">
            <a href="./dashboard.php"><img src="./assets/logo.png" alt="Bank Logo"></a>
        </div>
        <h1>Send Transfer</h1>
        <div class="header__right">
            Current User: <?php echo htmlspecialchars($_SESSION['username']); ?>
            <button class="logout-button" style="margin-left: 10px;" onclick="window.location.href='logout.php'">Logout</button>
        </div>
    </div>
</header>

<main class="dashboard">
    <div class="info-container">
        <section class="account-summary">
            <h2>Transfer Funds</h2>
            <?php if ($transfer_error): ?>
                <p style="color: red;"> <?php echo $transfer_error; ?> </p>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <p style="color: green;"> <?php echo $success_message; ?> </p>
            <?php endif; ?>

            <form action="" method="POST" class="transfer-form">
                <div class="sub-accounts">
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
                    <div class="sub-account">
                        <label for="account_to">To Account</label>
                        <input type="text" id="account_to" name="account_to" placeholder="Recipient Account Number" required>
                    </div>
                    <div class="sub-account">
                        <label for="amount">Amount</label>
                        <input type="number" id="amount" name="amount" placeholder="Enter amount" step="0.01" min="0.01" required>
                        <p id="balance-info" style="color: green; margin-top: 5px;"></p>
                    </div>
                </div>
                <div class="submit-button-container">
                    <button type="submit" id="transferButton">Transfer</button>
                </div>
            </form>
        </section>

        <div class="back-link">
            <p><a href="javascript:history.back()">Return to the previous page</a></p>
        </div>
    </div>
</main>

<script>
function updateBalance() {
    const accountSelect = document.getElementById("account_from");
    const selectedOption = accountSelect.options[accountSelect.selectedIndex];
    const balance = selectedOption.getAttribute("data-balance");
    const balanceInfo = document.getElementById("balance-info");
    if (balance) {
        balanceInfo.textContent = `${parseFloat(balance).toFixed(2)} HKD available`;
    } else {
        balanceInfo.textContent = "";
    }
}

</script>

</body>
</html>


    <style>
        .dashboard {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            padding-top: 50px;
        }
        
        .info-container {
            width: 100%;
            max-width: 800px;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-top: 30px;
        }
        
        .transfer-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .sub-account label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        .sub-account input, .sub-account select {
            width: 100%;
            max-width: 400px;
            padding: 8px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        
        .submit-button-container {
            text-align: center;
            margin-top: 20px;
        }
        
        .submit-button-container button {
            padding: 10px 20px;
            background-color: #4BA247;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }
        
        .submit-button-container button:hover {
            background-color: #3D8C3A;
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
