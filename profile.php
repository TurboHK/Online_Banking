<?php
session_start();
include 'db_user_connection.php';

if (!isset($_SESSION['username'])) {
    header("Location: index.html");
    exit();
}

$current_username = $_SESSION['username'];

// 查询用户的全部信息
$stmt = $conn->prepare("SELECT id, username, name, phone, created_at, address, picture FROM users WHERE username=?");
$stmt->bind_param("s", $current_username);
$stmt->execute();
$result = $stmt->get_result();

// 获取用户数据
$user_data = $result->fetch_assoc();
$user_id = $user_data['id'] ?? 'N/A';
$username = $user_data['username'] ?? 'N/A';
$address = $user_data['address'] ?? 'N/A';
$phone = $user_data['phone'] ?? 'N/A';
$created_at = $user_data['created_at'] ?? 'N/A';
$name = $user_data['name'] ?? 'N/A';
$picture = $user_data['picture'] ?? null;

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/dashboard.css" />
    <link rel="icon" href="assets/logo.png" type="image/png">
    <title>Personal Information | GBC Internet Banking</title>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header__content">
            <div class="header__logo">
                <a href="./dashboard.php"><img src="./assets/logo.png" alt="Bank Logo"></a>
            </div>
            <h1>Personal Information</h1>
            <div class="header__right">
                Current User: <?php echo htmlspecialchars($_SESSION['username']); ?>
                <button class="logout-button" style="margin-left: 10px;" onclick="window.location.href='logout.php'">Logout</button>
            </div>

        </div>
    </header>

    <main class="dashboard">
        <div class="info-container">
            <!-- 左侧卡片 -->
            <section class="account-summary">
                <h2>Overview</h2>
                <div class="sub-accounts">
                    <div class="sub-account">
                        <span>Username</span>
                        <span class="balance"><?php echo htmlspecialchars($username); ?></span>
                    </div>
                    <div class="sub-account">
                        <span>User ID</span>
                        <span class="balance"><?php echo htmlspecialchars($user_id); ?></span>
                    </div>
                    <div class="sub-account">
                        <span>Valued customer since</span>
                        <span class="balance"><?php echo htmlspecialchars(date("F j, Y", strtotime($created_at))); ?></span>
                    </div>
                    <div class="sub-account">
                        <span>Name</span>
                        <span class="balance"><?php echo htmlspecialchars($name); ?></span>
                    </div>
                    <div class="sub-account">
                        <span>Address</span>
                        <span class="balance"><?php echo htmlspecialchars($address); ?></span>
                    </div>
                    <div class="sub-account">
                        <span>Phone Number</span>
                        <span class="balance"><?php echo htmlspecialchars($phone); ?></span>
                    </div>
                </div>
            </section>
    
            <!-- 右侧卡片 -->
            <!-- 右侧卡片 -->
            <section class="account-summary">
                <h2>Profile Picture</h2>
                <div class="account">
                    <?php if ($picture): ?>
                        <!-- 图片显示 -->
                        <img src="data:image/jpeg;base64,<?php echo base64_encode($picture); ?>" alt="Profile Picture" class="profile-picture">
                    <?php else: ?>
                        <!-- 如果没有图片，显示占位符 -->
                        No profile picture uploaded.<br><br>Consider uploading one to make your profile unique!<br><br>
                    <?php endif; ?>
                    
                    <!-- 上传按钮 -->
                    <form action="upload_picture.php" method="POST" enctype="multipart/form-data" class="upload-form">
                        <input type="file" name="profile_picture" accept="image/*" required>
                        <button type="submit">Update Picture</button>
                    </form>
                </div>
            </section>

        </div>
        <div class="update-button-container">
        <button onclick="window.location.href='update.php'">Update My Personal Information</button><br><br><br>
        <button onclick="window.location.href='Change_Email.php'">Change My Email</button><br><br>
        <button onclick="window.location.href='Change_Password.php'">Change My Password</button>
    </div>

    </main>

    <footer class="footer">
        <span class="author">©2024 Global Banking Corporation Limited. All rights reserved.</span>
    </footer>

    <style>
        .update-button-container {
        text-align: center; /* 居中按钮 */
        margin: 20px 0; /* 按钮与其他内容的间距 */
        }

    .update-button-container button {
        padding: 10px 20px;
        background-color: #4BA247; /* 按钮背景颜色 */
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
        font-weight: bold;
    }
        .info-container {
            display: flex; /* 水平排列 */
            gap: 20px; /* 卡片之间的间距 */
        }

        .account-summary {
            background-color: #f7f7f7;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 80%; /* 默认60%宽度，用于左侧卡片 */
        }

        .account-summary:nth-child(2) {
            width: 20%; /* 第二张卡片占40%宽度 */
        }

        .account {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .profile-picture {
            width: 100%; /* 图片占满父容器宽度 */
            max-width: 200px; /* 可选：设置图片最大宽度 */
            border-radius: 10px;
            margin-bottom: 15px; /* 图片与按钮之间的间距 */
        }
        
        .upload-form {
            display: flex;
            flex-direction: column; /* 垂直排列上传组件 */
            align-items: center; /* 居中按钮 */
        }
        
        .upload-form input[type="file"] {
            margin-bottom: 10px; /* 上传文件选择框与按钮之间的间距 */
        }
        
        .upload-form button {
            padding: 10px 20px;
            background-color: #123362;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .upload-form button:hover {
            background-color: #0a254a; /* 按钮悬停效果 */
        }
        
    </style>