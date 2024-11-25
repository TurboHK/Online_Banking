<?php
// 设置HTTP响应代码为404，表示页面未找到
http_response_code(404);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/dashboard.css" />
    <link rel="icon" href="assets/logo.png" type="image/png">
    <title>Page Not Found | GBC Internet Banking</title>
    <style>
        .error-container {
            text-align: center;
            padding: 50px;
            background-color: #fff;
            box-shadow: 0 1px 6px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            max-width: 600px;
            margin: 100px auto;
        }

        .error-container h1 {
            font-size: 60px;
            color: #ff4e4e;
        }

        .error-container p {
            font-size: 18px;
            color: #333;
            margin-bottom: 20px;
        }

        .back-link {
            font-size: 16px;
            color: #0064d2;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
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
            <h1>404 - Page Not Found</h1>
        </div>
    </header>

    <!-- Main Content -->
    <main class="dashboard">
        <div class="error-container">
            <h1>Oops!</h1>
            <p>Sorry, the page you are looking for could not be found.</p>
            <p><a href="javascript:history.back()" class="back-link">Return to the previous page</a></p>
        </div>
    </main>

    <footer class="footer">
        <span class="author">©2024 Global Banking Corporation Limited. All rights reserved.</span>
    </footer>
</body>
</html>
