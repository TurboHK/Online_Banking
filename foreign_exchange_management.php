<?php
session_start();
include 'db_user_connection.php';

if (!isset($_SESSION['username'])) {
    header("Location: index.html");
    exit();
}
// Start timing
$start_time = microtime(true);

$current_username = $_SESSION['username'];
$user_id = $_SESSION['user_id'] ?? null;

// Get all debit card information
$stmt = $conn->prepare("SELECT debitcards.debitcard_id, debitcards.balance, cards.id as card_id, cards.blocked
                        FROM debitcards
                        INNER JOIN cards ON debitcards.id = cards.id
                        WHERE cards.cardholder_id = ? AND blocked = 0");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$debit_cards = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $baseCurrency = $_POST['baseCurrency'];
    $targetCurrency = $_POST['targetCurrency'];
    $amount = $_POST['amount'];
    $account_from = $_POST['account_from'];

    // Get the selected debit card's balance and card ID
    $stmt = $conn->prepare("SELECT balance, id FROM debitcards WHERE debitcard_id = ?");
    $stmt->bind_param("i", $account_from);
    $stmt->execute();
    $result = $stmt->get_result();
    $card = $result->fetch_assoc();
    $balance = $card['balance'];
    $card_id = $card['id'];
    $stmt->close();

    if ($baseCurrency == "HKD") {
        // Get the exchange rate from the exchange_rates table
        $stmt = $conn->prepare("SELECT exchange_rate FROM exchange_rates WHERE sell_currency = ? AND buy_currency = ?");
        $stmt->bind_param("ss", $baseCurrency, $targetCurrency);
        $stmt->execute();
        $result = $stmt->get_result();
        $exchange_rate_data = $result->fetch_assoc();
        $stmt->close();

        // Check if the balance is enough
        if ($balance < $amount) {
            $error_message = "Insufficient balance, unable to complete the exchange.";
        } else {
            if ($targetCurrency == $baseCurrency) {
                $exchange_rate_data['exchange_rate'] = 1;  // No exchange needed
            }

            if (!$exchange_rate_data || !isset($exchange_rate_data['exchange_rate'])) {
                $error_message = "Unable to find the exchange rate.";
            } else {
                $rate = $exchange_rate_data['exchange_rate'];
                $convertedAmount = $amount * $rate;

                // Insert transaction record into transactions table
                $stmt = $conn->prepare("INSERT INTO transactions (transaction_type, card_id, amount) 
                                        VALUES ('exchange_t', ?, ?)");
                $stmt->bind_param("ii", $card_id, $amount);
                $stmt->execute();
                $stmt->close();

                // Update the debit card balance
                $new_balance = $balance - $amount;
                $stmt = $conn->prepare("UPDATE debitcards SET balance = ? WHERE debitcard_id = ?");
                $stmt->bind_param("di", $new_balance, $account_from);
                $stmt->execute();
                $stmt->close();

                if ($targetCurrency != "HKD") {
                    $stmt = $conn->prepare("UPDATE account SET local_currency_balance = local_currency_balance + ? WHERE user_id = ? AND Account_type = 'foreign_exchange'");
                    $stmt->bind_param("di", $amount, $user_id);
                    $stmt->execute();
                    $stmt->close();
                    // Update the exchange transaction table if target currency is not HKD
                    $stmt = $conn->prepare("SELECT amount FROM exchange_transactions WHERE card_id = ? AND currency_type = ?");
                    $stmt->bind_param("is", $card_id, $targetCurrency);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $s_Amount = $result->fetch_assoc();
                    $stmt->close();

                    // Set default value if no record exists
                    $s_Amount = $s_Amount['amount'] ?? 0;
                    $s_Amount += $convertedAmount;
                    $stmt = $conn->prepare("UPDATE exchange_transactions SET amount = ? WHERE card_id = ? AND currency_type = ?");
                    $stmt->bind_param("dis", $s_Amount, $card_id, $targetCurrency);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    // Directly add the converted amount to the debit card's balance
                    $stmt = $conn->prepare("UPDATE debitcards SET balance = balance + ? WHERE debitcard_id = ?");
                    $stmt->bind_param("di", $convertedAmount, $account_from);
                    $stmt->execute();
                    $stmt->close();
                }

                $success_message = "Exchange successful! You have converted {$amount} {$baseCurrency} into {$convertedAmount} {$targetCurrency}.";
            }
        }
    } else {
        // Handle non-HKD case here (same logic as above with $baseCurrency != "HKD")
        $stmt = $conn->prepare("SELECT amount FROM exchange_transactions WHERE card_id = ? AND currency_type = ?");
        $stmt->bind_param("is", $card_id, $baseCurrency);
        $stmt->execute();
        $result = $stmt->get_result();
        $f_Amount = $result->fetch_assoc();
        $stmt->close();

        if ($f_Amount && $f_Amount['amount'] >= $amount) {
            // If there is enough balance, proceed with the exchange
            $new_amount = $f_Amount['amount'] - $amount;

            // Update the exchange_transactions table for baseCurrency balance
            $stmt = $conn->prepare("UPDATE exchange_transactions SET amount = ? WHERE card_id = ? AND currency_type = ?");
            $stmt->bind_param("dis", $new_amount, $card_id, $baseCurrency);
            $stmt->execute();
            $stmt->close();

            // Insert transaction record into transactions table
            $stmt = $conn->prepare("INSERT INTO transactions (transaction_type, card_id, amount) 
                                    VALUES ('exchange_t', ?, ?)");
            $stmt->bind_param("ii", $card_id, $amount);
            $stmt->execute();
            $stmt->close();

            // Get the exchange rate for the target currency
            $stmt = $conn->prepare("SELECT exchange_rate FROM exchange_rates WHERE sell_currency = ? AND buy_currency = ?");
            $stmt->bind_param("ss", $baseCurrency, $targetCurrency);
            $stmt->execute();
            $result = $stmt->get_result();
            $exchange_rate_data = $result->fetch_assoc();
            $stmt->close();

            if ($targetCurrency == $baseCurrency) {
                $exchange_rate_data['exchange_rate'] = 1;
            }

            if (!$exchange_rate_data || !isset($exchange_rate_data['exchange_rate'])) {
                $error_message = "Unable to find the exchange rate.";
            } else {
                $rate = $exchange_rate_data['exchange_rate'];
                $convertedAmount = $amount * $rate;

                if ($targetCurrency != "HKD") {
                    $stmt = $conn->prepare("SELECT amount FROM exchange_transactions WHERE card_id = ? AND currency_type = ?");
                    $stmt->bind_param("is", $card_id, $targetCurrency);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $s_Amount = $result->fetch_assoc();
                    $stmt->close();

                    // Set default value if no record exists
                    $s_Amount = $s_Amount['amount'] ?? 0;
                    $s_Amount += $convertedAmount;
                    $stmt = $conn->prepare("UPDATE exchange_transactions SET amount = ? WHERE card_id = ? AND currency_type = ?");
                    $stmt->bind_param("dis", $s_Amount, $card_id, $targetCurrency);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    $stmt = $conn->prepare("UPDATE account SET local_currency_balance = local_currency_balance - ? WHERE user_id = ? AND Account_type = 'foreign_exchange'");
                    $stmt->bind_param("di", $amount, $user_id);
                    $stmt->execute();
                    $stmt->close();
                    // Directly add the converted amount to the debit card's balance
                    $stmt = $conn->prepare("UPDATE debitcards SET balance = balance + ? WHERE debitcard_id = ?");
                    $stmt->bind_param("di", $convertedAmount, $account_from);
                    $stmt->execute();
                    $stmt->close();
                }

                $success_message = "Exchange successful! You have converted {$amount} {$baseCurrency} into {$convertedAmount} {$targetCurrency}.";
            }
        } else {
            $error_message = "Insufficient balance for the currency, unable to complete the exchange.";
        }
    }
}
// End timing
$end_time = microtime(true);
$execution_time = round(($end_time - $start_time) * 1000, 2); 
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <title>Foreign Exchange</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/dashboard.css" />
    <link rel="icon" href="assets/logo.png" type="image/png">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header__content">
            <div class="header__logo">
                <a href="./dashboard.php"><img src="./assets/logo.png" alt="Bank Logo"></a>
            </div>
            <h1>Foreign Exchange</h1>
            <div class="header__right">
                Current User: <?php echo htmlspecialchars($_SESSION['username']); ?>
                <button class="logout-button" style="margin-left: 10px;" onclick="window.location.href='logout.php'">Logout</button>
            </div>
        </div>
    </header>

    <div class="container" style="margin-top: 150px;">
        <h1 id="l">Currency Exchange</h1>

        <!-- Currency Exchange Section -->
        <div id="exchange-section">
            <form id="exchange-form" method="POST" action="" onsubmit="return validateForm(event)">
                <div class="form-group">
                    <label for="baseCurrency">Base Currency</label>
                    <select id="baseCurrency" name="baseCurrency" required>
                        <option value="USD">USD</option>
                        <option value="EUR">EUR</option>
                        <option value="HKD">HKD</option>
                        <option value="JPY">JPY</option>
                        <option value="CNY">CNY</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="targetCurrency">Target Currency</label>
                    <select id="targetCurrency" name="targetCurrency" required>
                        <option value="USD">USD</option>
                        <option value="EUR">EUR</option>
                        <option value="HKD">HKD</option>
                        <option value="JPY">JPY</option>
                        <option value="CNY">CNY</option>
                    </select>
                </div>
                <div class="sub-account">
                    <select id="account_from" name="account_from" required onchange="updateBalance()">
                        <option value="" disabled selected>Select Account</option>
                        <?php foreach ($debit_cards as $card): ?>
                            <option value="<?php echo $card['debitcard_id']; ?>" data-balance="<?php echo $card['balance']; ?>">
                                <?php echo $card['debitcard_id']; ?> 
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="amount">Amount</label>
                    <input type="number" id="amount" name="amount" required min="0.01" step="any" />
                </div>
                <div class="form-group">
                    <label>Exchange Rate</label>
                    <p id="exchangeRate">1.000</p>
                </div>
                <button type="submit" class="btn">Exchange</button>
            </form>
            
            <?php if (isset($error_message)): ?>
                <div class="error"><?php echo $error_message; ?></div>
            <?php elseif (isset($success_message)): ?>
                <div class="success"><?php echo $success_message; ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="back-link">
        <p><a href="javascript:history.back()">Return to the previous page</a></p>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <?php if ($execution_time): ?>
            It took <?php echo $execution_time; ?> milliseconds to get data from the server.</p>
        <?php endif; ?>
        ©2024 Global Banking Corporation Limited. All rights reserved.
    </footer>

    <script>
        // Simulated exchange rates
        const exchangeRates = {
            "USD": { "USD": 1, "EUR": 0.91, "HKD": 7.82, "JPY": 134.5, "CNY": 7.1 },
            "EUR": { "USD": 1.1, "EUR": 1, "HKD": 8.6, "JPY": 148, "CNY": 7.8 },
            "HKD": { "USD": 0.13, "EUR": 0.12, "HKD": 1, "JPY": 17.5, "CNY": 0.91 },
            "JPY": { "USD": 0.0074, "EUR": 0.0068, "HKD": 0.058, "JPY": 1, "CNY": 0.052 },
            "CNY": { "USD": 0.14, "EUR": 0.13, "HKD": 1.1, "JPY": 17.5, "CNY": 1 }
        };

        document.getElementById("baseCurrency").addEventListener("change", updateExchangeRate);
        document.getElementById("targetCurrency").addEventListener("change", updateExchangeRate);

        function updateExchangeRate() {
            const base = document.getElementById("baseCurrency").value;
            const target = document.getElementById("targetCurrency").value;
            const rate = exchangeRates[base][target];
            document.getElementById("exchangeRate").textContent = rate.toFixed(3);
        }

        function validateForm(event) {
            const amount = document.getElementById("amount").value;
            if (amount <= 0) {
                alert("Please enter a valid amount.");
                event.preventDefault();
                return false;
            }
            return true;
        }

        updateExchangeRate();
    </script>
</body>
<style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        #l{
            text-align: center;
            color: black;
        }
         h2 {
            text-align: center;
            color: #333;
        }

        #exchange-section {
            margin-top: 30px;
        }

        #exchange-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        label {
            font-weight: bold;
            color: #555;
        }
        h1{
            text-align: center;
            color: white;
        }

        input[type="number"], select {
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 5px;
            width: 100%;
            box-sizing: border-box;
        }

        #exchangeRate {
            font-weight: bold;
            color: #4BA247;
            font-size: 18px;
        }

        .btn {
            padding: 12px 20px;
            background-color: #4BA247;
            color: white;
            font-size: 16px;
            font-weight: bold;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            width: 100%;
            text-align: center;
        }

        .btn:hover {
            background-color: #3D8C3A;
        }

        @media (max-width: 600px) {
            .container {
                padding: 15px;
            }

            h1, h2 {
                font-size: 20px;
            }

            .btn {
                font-size: 14px;
            }
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
