<?php
session_start();
include('db_connection.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // 查询时加入角色检查
    $sql = "SELECT * FROM users WHERE username = ? AND role = 'admin'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();

    // 检查是否找到用户
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        // 验证密码
        if (password_verify($password, $user['password'])) {
            $_SESSION['username'] = $username;
            $_SESSION['role'] = 'admin'; // 设置用户角色
            echo json_encode(['success' => true, 'message' => 'Login successful', 'redirect' => 'admin_dashboard.php']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Incorrect password']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found or not an admin']);
    }
}
?>
