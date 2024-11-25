<?php
session_start();
include 'db_user_connection.php';

if (!isset($_SESSION['username'])) {
    header("Location: index.html"); // 如果没有登录则跳转到登录页面
    exit();
}

$current_username = $_SESSION['username']; // 当前登录的用户名

// 获取当前用户的卡片信息
$stmt = $conn->prepare("SELECT * FROM cards WHERE cardholder_id = (SELECT id FROM users WHERE username = ?)"); // 使用用户的 username 查询用户的卡片信息
$stmt->bind_param("s", $current_username);
$stmt->execute();
$result = $stmt->get_result();
$cards = [];
while ($row = $result->fetch_assoc()) {
    $cards[] = $row;  // 将卡片信息存入数组
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
    <title>Manage Cards | GBC Internet Banking</title>
    <style>
        .card-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
        }

        .card-item {
            width: 250px;
            padding: 20px;
            background-color: #eee;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 1px 6px rgba(0, 0, 0, 0.1);
        }

        .card-item img {
            width: 100%;
            /* height: 40px; */
            margin-bottom: 10px;
        }

        .card-item .card-info {
            margin-bottom: 15px;
        }

        .card-item .card-info span {
            display: block;
            font-size: 16px;
            font-weight: bold;
        }

        .card-item .card-actions {
            margin-top: 10px;
        }

        .card-item .card-actions button {
            background-color: #4ba247;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }

        .card-item .card-actions button:hover {
            background-color: #45a047;
        }

        .back-link {
            text-align: center;
            margin-top: 30px;
        }

        .back-link a {
            text-decoration: none;
            color: #0064d2;
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
            <h1>Manage Your Cards</h1>
            <div class="header__right">
                Current User: <?php echo htmlspecialchars($_SESSION['username']); ?>
                <button class="logout-button" style="margin-left: 10px;" onclick="window.location.href='logout.php'">Logout</button>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="dashboard">
        <div class="card-container">
        <?php foreach ($cards as $card): ?>
            <div class="card-item">
                <?php 
                    // 根据卡片类型显示不同的图片
                    $card_image = ($card['type'] === 'credit') ? './assets/credit.png' : './assets/debit.png'; 
                ?>
                <img src="<?php echo $card_image; ?>" alt="Card Icon">
                <div class="card-info">
                    <span>Card Type: <?php echo htmlspecialchars($card['type']); ?></span>
                    <span>Card Number: <?php echo htmlspecialchars($card['card_number']); ?></span>
                </div>
                <div class="card-actions">
                    <?php if ($card['blocked'] === '0'): ?>
                        <button onclick="location.href='lock_card.php?card_id=<?php echo $card['id']; ?>'">Lock Card</button>
                    <?php else: ?>
                        <button onclick="location.href='unlock_card.php?card_id=<?php echo $card['id']; ?>'">Unlock Card</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
</div>


        <div class="back-link">
            <p><a href="dashboard.php">Back to Dashboard</a></p>
        </div>
    </main>

    <footer class="footer">
        <span class="author">©2024 Global Banking Corporation Limited. All rights reserved.</span>
    </footer>
</body>
</html>
