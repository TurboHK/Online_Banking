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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="./css/dashboard.css" />
        <link rel="icon" href="assets/logo.png" type="image/png">
    <title>Transaction History | GBC Internet Banking</title>

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
        <h1>Transaction History</h1>
        <h2>Recent Transactions</h2>
        <table id="transaction-table">
            <thead>
                <tr>
                    <th>Transaction ID</th>
                    <th>Card Number</th>
                    <th>Transaction Date</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td>1234 5678 9876 5432</td>
                    <td>2024-11-01</td>
                    <td>$100</td>
                </tr>
                <tr>
                    <td>2</td>
                    <td>1111 2222 3333 4444</td>
                    <td>2024-10-28</td>
                    <td>$200</td>
                </tr>
                <tr>
                    <td>3</td>
                    <td>1234 5678 9876 5432</td>
                    <td>2024-11-02</td>
                    <td>$150</td>
                </tr>
                <tr>
                    <td>4</td>
                    <td>5555 6666 7777 8888</td>
                    <td>2024-11-05</td>
                    <td>$500</td>
                </tr>
                <tr>
                    <td>5</td>
                    <td>1234 5678 9876 5432</td>
                    <td>2024-11-06</td>
                    <td>$300</td>
                </tr>
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="pagination">
            <a href="#" class="active">1</a>
            <a href="#">2</a>
            <a href="#">3</a>
            <a href="#">4</a>
            <a href="#">5</a>
            <a href="#">...</a>
            <a href="#">107</a>
            <a href="#">»</a>
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
</body>
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
