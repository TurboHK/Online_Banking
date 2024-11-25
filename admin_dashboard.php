<?php
session_start();
include 'db_connection.php';

// DO NOT CHANGE ANYTHING BELOW THIS LINE! DO NOT CHANGE ANYTHING BELOW THIS LINE! DO NOT CHANGE ANYTHING BELOW THIS LINE! DO NOT CHANGE ANYTHING BELOW THIS LINE!
// DO NOT CHANGE ANYTHING BELOW THIS LINE! DO NOT CHANGE ANYTHING BELOW THIS LINE! DO NOT CHANGE ANYTHING BELOW THIS LINE! DO NOT CHANGE ANYTHING BELOW THIS LINE!
// DO NOT CHANGE ANYTHING BELOW THIS LINE! DO NOT CHANGE ANYTHING BELOW THIS LINE! DO NOT CHANGE ANYTHING BELOW THIS LINE! DO NOT CHANGE ANYTHING BELOW THIS LINE!

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.html"); //If not logged in redirect to login page
    exit();
}

$search_result = '';
$username = '';
$execution_time = ''; //Add a new variable to store the execution time


$total_users = 0;
$users_role_count = 0; //User number
$admins_role_count = 0; //Admin number

//Query the total number of users
$total_users_query = "SELECT COUNT(*) AS total FROM users";
$total_result = $conn->query($total_users_query);
if ($total_result) {
    $total_row = $total_result->fetch_assoc();
    $total_users = $total_row['total'];
}

//Get the number of users with the identity 'user'
$users_role_query = "SELECT COUNT(*) AS user_count FROM users WHERE role = 'user'";
$users_role_result = $conn->query($users_role_query);
if ($users_role_result) {
    $users_role_row = $users_role_result->fetch_assoc();
    $users_role_count = $users_role_row['user_count'];
}

//Get the number of users with the identity 'admin'
$admins_role_query = "SELECT COUNT(*) AS admin_count FROM users WHERE role = 'admin'";
$admins_role_result = $conn->query($admins_role_query);
if ($admins_role_result) {
    $admins_role_row = $admins_role_result->fetch_assoc();
    $admins_role_count = $admins_role_row['admin_count'];
}

//Information about the user currently logged in to the database
$current_db_user = $conn->query("SELECT CURRENT_USER() AS db_user")->fetch_assoc();
$db_user_info = $current_db_user['db_user'];

$current_username = $_SESSION['username']; // 当前登录的用户名
// 查询当前用户名对应的用户 ID
$stmt = $conn->prepare("SELECT id FROM users WHERE username=?");
$stmt->bind_param("s", $current_username);
$stmt->execute();
$result = $stmt->get_result();
// 获取用户 ID
$user_data = $result->fetch_assoc();
$user_id = $user_data['id'] ?? 'N/A'; // 如果没有找到，默认显示 'N/A'
$stmt->close();

// DO NOT CHANGE ANYTHING ABOVE THIS LINE! DO NOT CHANGE ANYTHING ABOVE THIS LINE! DO NOT CHANGE ANYTHING ABOVE THIS LINE! DO NOT CHANGE ANYTHING ABOVE THIS LINE!
// DO NOT CHANGE ANYTHING ABOVE THIS LINE! DO NOT CHANGE ANYTHING ABOVE THIS LINE! DO NOT CHANGE ANYTHING ABOVE THIS LINE! DO NOT CHANGE ANYTHING ABOVE THIS LINE!
// DO NOT CHANGE ANYTHING ABOVE THIS LINE! DO NOT CHANGE ANYTHING ABOVE THIS LINE! DO NOT CHANGE ANYTHING ABOVE THIS LINE! DO NOT CHANGE ANYTHING ABOVE THIS LINE!

if (isset($_POST['search'])) { //Check if there is a search request
    $username = $_POST['username'];

    //Start timing
    $start_time = microtime(true); 

    //Prepare SQL statements to prevent SQL injection
    $stmt = $conn->prepare("SELECT * FROM users WHERE username LIKE ?");
    $search_param = "%" . $username . "%";
    $stmt->bind_param("s", $search_param);
    $stmt->execute();
    $result = $stmt->get_result();

    //Constructing search results
    if ($result->num_rows > 0) {
        $search_result .= "<table border='1'><tr><th>ID</th><th>Username</th><th>Role</th><th>Registration Time</th><th>Operations</th></tr>";
        while ($row = $result->fetch_assoc()) {
            $search_result .= "<tr>
                                <td>" . htmlspecialchars($row['id']) . "</td>
                                <td>" . htmlspecialchars($row['username']) . "</td>
                                <td>" . htmlspecialchars($row['role']) . "</td>
                                <td>" . htmlspecialchars($row['created_at']) . "</td>
                                <td>
                                    <form method='post' action='' style='display:inline;'>
                                        <input type='hidden' name='username' value='" . htmlspecialchars($row['username']) . "'>
                                        <input type='submit' name='reset_password' value='Reset password'>
                                    </form>
                                    <form method='post' action='' style='display:inline;'>
                                        <input type='hidden' name='username' value='" . htmlspecialchars($row['username']) . "'>
                                        <input type='submit' name='delete_user' value='Delete user' onclick=\"return confirm('Are you sure to delete the user?');\">
                                    </form>
                                </td>
                              </tr>";
        }
        $search_result .= "</table>";
    } else {
        $search_result = "No users found.";
    }
    $stmt->close();

    //End timing
    $end_time = microtime(true);
    $execution_time = round(($end_time - $start_time) * 1000, 2); //Convert to milliseconds
}

// DO NOT CHANGE ANYTHING BELOW THIS LINE! DO NOT CHANGE ANYTHING BELOW THIS LINE! DO NOT CHANGE ANYTHING BELOW THIS LINE! DO NOT CHANGE ANYTHING BELOW THIS LINE!
// DO NOT CHANGE ANYTHING BELOW THIS LINE! DO NOT CHANGE ANYTHING BELOW THIS LINE! DO NOT CHANGE ANYTHING BELOW THIS LINE! DO NOT CHANGE ANYTHING BELOW THIS LINE!
// DO NOT CHANGE ANYTHING BELOW THIS LINE! DO NOT CHANGE ANYTHING BELOW THIS LINE! DO NOT CHANGE ANYTHING BELOW THIS LINE! DO NOT CHANGE ANYTHING BELOW THIS LINE!

//Processing a request to clear results
if (isset($_POST['clear'])) {
    $username = ''; //Clear the input box
    $search_result = ''; //Clear search results
}

//Processing password reset requests
if (isset($_POST['reset_password'])) {
    $username = $_POST['username'];
    $new_password = password_hash('123456', PASSWORD_DEFAULT); //Storing passwords using hashes

    $stmt = $conn->prepare("UPDATE users SET password=? WHERE username=?");
    $stmt->bind_param("ss", $new_password, $username);
    if ($stmt->execute()) {
        echo "<script>alert('{$username} /'s password has been reset to 123456.');</script>";
    } else {
        echo "<script>alert('An error occurred while resetting the password!');</script>";
    }
    $stmt->close();
}

//Handling delete user requests
if (isset($_POST['delete_user'])) {
    $username = $_POST['username'];

    $stmt = $conn->prepare("DELETE FROM users WHERE username=?");
    $stmt->bind_param("s", $username);
    if ($stmt->execute()) {
        echo "<script>alert('{$username} has been deleted.');</script>";
    } else {
        echo "<script>alert('An error occurred while deleting the user!');</script>";
    }
    $stmt->close();
}

// DO NOT CHANGE ANYTHING ABOVE THIS LINE! DO NOT CHANGE ANYTHING ABOVE THIS LINE! DO NOT CHANGE ANYTHING ABOVE THIS LINE! DO NOT CHANGE ANYTHING ABOVE THIS LINE!
// DO NOT CHANGE ANYTHING ABOVE THIS LINE! DO NOT CHANGE ANYTHING ABOVE THIS LINE! DO NOT CHANGE ANYTHING ABOVE THIS LINE! DO NOT CHANGE ANYTHING ABOVE THIS LINE!
// DO NOT CHANGE ANYTHING ABOVE THIS LINE! DO NOT CHANGE ANYTHING ABOVE THIS LINE! DO NOT CHANGE ANYTHING ABOVE THIS LINE! DO NOT CHANGE ANYTHING ABOVE THIS LINE!
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/dashboard.css" />
    <link rel="icon" href="assets/logo.png" type="image/png">
    <title>Dashboard | GBC Internet Banking</title>

    <!-- DO NOT CHANGE ANYTHING BELOW THIS LINE! DO NOT CHANGE ANYTHING BELOW THIS LINE! DO NOT CHANGE ANYTHING BELOW THIS LINE! DO NOT CHANGE ANYTHING BELOW THIS LINE! -->
    <!-- DO NOT CHANGE ANYTHING BELOW THIS LINE! DO NOT CHANGE ANYTHING BELOW THIS LINE! DO NOT CHANGE ANYTHING BELOW THIS LINE! DO NOT CHANGE ANYTHING BELOW THIS LINE! -->
    <!-- DO NOT CHANGE ANYTHING BELOW THIS LINE! DO NOT CHANGE ANYTHING BELOW THIS LINE! DO NOT CHANGE ANYTHING BELOW THIS LINE! DO NOT CHANGE ANYTHING BELOW THIS LINE! -->  

    <style>
        .flex-container {
            display: flex;
            justify-content: space-between;
        }

        .account-summary {
            flex: 0 0 49%; /* Each section takes up 49% of the width */
            margin-right: 2%; /* Add some spacing */
        }

        .account-summary:last-child {
            margin-right: 0; /* The last element does not need right spacing */
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header__content">
            <div class="header__logo">
                <a href="./admin_dashboard.php"><img src="./assets/logo.png" alt="Bank Logo"></a>
            </div>
            <h1>Welcome to GBC Internet Banking Control Panel</h1>
            <div class="header__right">
                Current Administrator: <?php echo htmlspecialchars($_SESSION['username']); ?>
                <button class="logout-button" style="margin-left: 10px;" onclick="window.location.href='logout.php'">Logout</button>
            </div>
        </div>
    </header>

    <!-- Main Dashboard Content -->
    <main class="dashboard">
        <div class="flex-container">
            <section class="account-summary">
                <h2>User Overview</h2>
                <div class="account">
                    <h3>Total User Number</h3>
                    <div class="account-balance"><?php echo $total_users; ?></div> <!-- The total number of all users in the current database -->
                </div>
                <div class="sub-accounts">
                    <div class="sub-account">
                        <span>Users</span>
                        <span class="balance"><?php echo $users_role_count; ?></span> <!-- The total number of users with the identity user -->
                    </div>
                    <div class="sub-account">
                        <span>Administrators</span>
                        <span class="balance"><?php echo $admins_role_count; ?></span> <!-- Total number of users with Admin identity -->
                    </div>
                </div>
            </section>

            <section class="account-summary">
                <h2>Database Server Overview</h2>
                <div class="account">
                    <h3>Current User</h3>
                    <span class="account-number"><?php echo htmlspecialchars($conn->host_info); ?></span>
                    <div class="account-balance"><?php echo htmlspecialchars($db_user_info); ?></div>
                </div>
                <div class="sub-accounts">
                <div class="sub-account">
                        <span>Server Type</span>
                        <span class="balance">MariaDB</span>
                    </div>
                    <div class="sub-account">
                        <span>Character Set</span>
                        <span class="balance"><?php echo htmlspecialchars($conn->character_set_name()); ?></span>
                    </div>
                </div>
            </section>
        </div>

    <!-- DO NOT CHANGE ANYTHING ABOVE THIS LINE! DO NOT CHANGE ANYTHING ABOVE THIS LINE! DO NOT CHANGE ANYTHING ABOVE THIS LINE! DO NOT CHANGE ANYTHING ABOVE THIS LINE! -->
    <!-- DO NOT CHANGE ANYTHING ABOVE THIS LINE! DO NOT CHANGE ANYTHING ABOVE THIS LINE! DO NOT CHANGE ANYTHING ABOVE THIS LINE! DO NOT CHANGE ANYTHING ABOVE THIS LINE! -->
    <!-- DO NOT CHANGE ANYTHING ABOVE THIS LINE! DO NOT CHANGE ANYTHING ABOVE THIS LINE! DO NOT CHANGE ANYTHING ABOVE THIS LINE! DO NOT CHANGE ANYTHING ABOVE THIS LINE! -->

        <!-- 用户搜索功能 -->
        <section class="user-search">
            <h2>User Management</h2>
            <form method="post" action="">
                <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($username); ?>" required>
                <input type="submit" name="search" value="Search">
                <input type="submit" name="clear" value="Clear">
            </form>

            <!-- DO NOT CHANGE ANYTHING BELOW THIS LINE! DO NOT CHANGE ANYTHING BELOW THIS LINE! DO NOT CHANGE ANYTHING BELOW THIS LINE! DO NOT CHANGE ANYTHING BELOW THIS LINE! --> 
            <div>
                <?php if ($execution_time): ?>
                    <p>Search executed in <span><?php echo $execution_time; ?></span> milliseconds.</p><br>
                <?php endif; ?>
                <?php
                //Output search results
                if ($search_result) {
                    echo $search_result;
                }
                ?>
            </div>
            <!-- DO NOT CHANGE ANYTHING ABOVE THIS LINE! DO NOT CHANGE ANYTHING ABOVE THIS LINE! DO NOT CHANGE ANYTHING ABOVE THIS LINE! DO NOT CHANGE ANYTHING ABOVE THIS LINE! -->

        </section>

        <!-- Quick Access Links -->
        <section class="quick-links">

            <div class="link">
                <img src="./assets/icons/card_management.png"/>
                <a href="./Admin_Card_Management.php" class="quick-link-links"><span>Card Management</span></a>
            </div>

            <div class="link">
                <img src="./assets/icons/application.png"/>
                <a href="./Admin_Card_Management.php" class="quick-link-links"><span>Credit Card Application Management</span></a>
            </div>

            <div class="link">
                <img src="./assets/icons/email.png"/>
                <a href="./Change_Email.php" class="quick-link-links"><span>Update email Address</span></a>
            </div>
            
            <div class="link">
                <img src="./assets/icons/password.png"/>
                <a href="./Change_Password.php" class="quick-link-links"><span>Update Password</span></a>
            </div>

        </section>
    </main>

<!-- DO NOT CHANGE ANYTHING BELOW THIS LINE! DO NOT CHANGE ANYTHING BELOW THIS LINE! DO NOT CHANGE ANYTHING BELOW THIS LINE! DO NOT CHANGE ANYTHING BELOW THIS LINE! --> 
    <footer class="footer">
        <span class="author">©2024 Global Banking Corporation Limited. All rights reserved.</span>
    </footer>
<!-- DO NOT CHANGE ANYTHING ABOVE THIS LINE! DO NOT CHANGE ANYTHING ABOVE THIS LINE! DO NOT CHANGE ANYTHING ABOVE THIS LINE! DO NOT CHANGE ANYTHING ABOVE THIS LINE! -->
</body>
</html>
