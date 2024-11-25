<?php
session_start();
include 'db_user_connection.php';

if (!isset($_SESSION['username'])) {
    header("Location: index.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_username = $_SESSION['username'];

    // 获取上传的图片文件
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $image = file_get_contents($_FILES['profile_picture']['tmp_name']); // 获取图片内容

        // 更新数据库中的图片
        $stmt = $conn->prepare("UPDATE users SET picture = ? WHERE username = ?");
        $stmt->bind_param("bs", $image, $current_username);
        $stmt->send_long_data(0, $image);

        if ($stmt->execute()) {
            echo "Profile picture updated successfully.";
        } else {
            echo "Error updating profile picture: " . $stmt->error;
        }

        $stmt->close();
        $conn->close();
        header("Location: profile.php"); // 上传成功后重定向到个人信息页面
        exit();
    } else {
        echo "Error: Please upload a valid image file.";
    }
}
?>
