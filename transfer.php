<?php
session_start();
include 'db_user_connection.php';

if (!isset($_SESSION['username'])) {
    header("Location: index.html"); //If not logged in redirect to login page
    exit();
}

$current_username = $_SESSION['username']; // 当前登录的用户名

//Start timing
$start_time = microtime(true); 

//End timing
$end_time = microtime(true);
$execution_time = round(($end_time - $start_time) * 1000, 2); //Convert to milliseconds
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/dashboard.css" />
    <link rel="icon" href="assets/logo.png" type="image/png">
    <title>Funds Transfer | GBC Internet Banking</title>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header__content">
            <div class="header__logo">
                <a href="./dashboard.html"><img src="./assets/logo.png" alt="Bank Logo"></a>
            </div>
            <h1>Funds Transfer</h1>
            <div class="header__right">
                Current User: Sengokuran
                <button class="logout-button" style="margin-left: 10px;" onclick="window.location.href='index.html'">Logout</button>
            </div>
        </div>
    </header>

    <main class="dashboard">
        <div class="info-container">
            <!-- Transfer Form -->
            <section class="account-summary">
                <h2>Transfer Funds</h2>
                <form action="" method="POST" class="transfer-form">
                    <div class="sub-accounts">
                        <div class="sub-account">
                            <label for="account_from">From Account:</label>
                            <select id="account_from" name="account_from" required>
                                <option value="" disabled selected>Select Account</option>
                                <option value="1234567890">1234567890 - Savings</option>
                                <option value="0987654321">0987654321 - Checking</option>
                            </select>
                        </div>
                        <div class="sub-account">
                            <label for="account_to">To Account: </label>
                            <input type="text" id="account_to" name="account_to" placeholder="Recipient Account Number" required>
                        </div>
                        <div class="sub-account">
                            <label for="amount">Amount: </label>
                            <input type="number" id="amount" name="amount" placeholder="Enter amount" step="0.01" min="0.01" required>
                        </div>
                        <div class="sub-account">
                            <label for="note">Note (Optional): </label>
                            <input type="text" id="note" name="note" placeholder="Add a note">
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

    <footer class="footer">
        <?php if ($execution_time): ?>
            It took <?php echo $execution_time; ?> milliseconds to get data from the server.</p>
        <?php endif; ?>
        ©2024 Global Banking Corporation Limited. All rights reserved.
    </footer>

    <style>
         /* 使表单居中并设置适当的宽度 */
        .dashboard {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            background-color: #f7f7f7;
            padding-top: 50px;
        }
        
        .info-container {
            width: 100%;
            max-width: 500px;
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
</body>
</html>
<script>
  const transferButton = document.getElementById("transferButton");
  transferButton.addEventListener("click", function (event){
    event.preventDefault(); // Prevent default form submission
    alert("Funds transferred successfully.");
    window.location.href = "dashboard.html";  
  });
</script>