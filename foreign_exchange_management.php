<?php
session_start();
include 'db_user_connection.php';

if (!isset($_SESSION['username'])) {
    header("Location: index.html"); // 如果没有登录则跳转到登录页面
    exit();
}

$current_username = $_SESSION['username'];

//Start timing
$start_time = microtime(true);

//End timing
$end_time = microtime(true);
$execution_time = round(($end_time - $start_time) * 1000, 2); //Convert to milliseconds
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Foreign Exchange Management</title>
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
            <h1>Welcome to GBC Internet Banking</h1>
            <div class="header__right">
                Current User: <?php echo htmlspecialchars($_SESSION['username']); ?>
                <button class="logout-button" style="margin-left: 10px;" onclick="window.location.href='logout.php'">Logout</button>
            </div>
        </div>
    </header>

    <div class="container" style="margin-top: 150px;">
        <h1 id="l">Foreign Exchange Management</h1>

        <!-- Currency Exchange Section -->
        <div id="exchange-section">
            <h2>Currency Exchange</h2>
            <form id="exchange-form" onsubmit="processExchange(event)">
                <div class="form-group">
                    <label for="baseCurrency">Base Currency</label>
                    <select id="baseCurrency" name="baseCurrency" required>
                        <option value="USD">USD - US Dollar</option>
                        <option value="EUR">EUR - Euro</option>
                        <option value="GBP">GBP - British Pound</option>
                        <option value="JPY">JPY - Japanese Yen</option>
                        <option value="CNY">CNY - Chinese Yuan</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="targetCurrency">Target Currency</label>
                    <select id="targetCurrency" name="targetCurrency" required>
                        <option value="USD">USD - US Dollar</option>
                        <option value="EUR">EUR - Euro</option>
                        <option value="GBP">GBP - British Pound</option>
                        <option value="JPY">JPY - Japanese Yen</option>
                        <option value="CNY">CNY - Chinese Yuan</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="amount">Amount</label>
                    <input type="number" id="amount" name="amount" placeholder="Enter Amount" required>
                </div>
                <div class="form-group">
                    <label>Exchange Rate</label>
                    <p id="exchangeRate">1.000</p>
                </div>
                <button type="submit" class="btn">Exchange</button>
            </form>
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
            "USD": { "USD": 1, "EUR": 0.85, "GBP": 0.75, "JPY": 110, "CNY": 6.5 },
            "EUR": { "USD": 1.18, "EUR": 1, "GBP": 0.88, "JPY": 129, "CNY": 7.6 },
            "GBP": { "USD": 1.33, "EUR": 1.14, "GBP": 1, "JPY": 146, "CNY": 8.7 },
            "JPY": { "USD": 0.0091, "EUR": 0.0078, "GBP": 0.0069, "JPY": 1, "CNY": 0.059 },
            "CNY": { "USD": 0.15, "EUR": 0.13, "GBP": 0.12, "JPY": 17, "CNY": 1 }
        };

        document.getElementById("baseCurrency").addEventListener("change", updateExchangeRate);
        document.getElementById("targetCurrency").addEventListener("change", updateExchangeRate);

        function updateExchangeRate() {
            const base = document.getElementById("baseCurrency").value;
            const target = document.getElementById("targetCurrency").value;
            const rate = exchangeRates[base][target];
            document.getElementById("exchangeRate").textContent = rate.toFixed(3);
        }

        function processExchange(event) {
            event.preventDefault();
            const base = document.getElementById("baseCurrency").value;
            const target = document.getElementById("targetCurrency").value;
            const amount = parseFloat(document.getElementById("amount").value);
            const rate = exchangeRates[base][target];
            const convertedAmount = (amount * rate).toFixed(2);

            alert(`Exchanged ${amount} ${base} to ${convertedAmount} ${target}.`);
        }

        // Initialize exchange rate on page load
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