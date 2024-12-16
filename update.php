<?php
session_start();
include 'db_user_connection.php';

if (!isset($_SESSION['username'])) {
    header("Location: index.html");
    exit();
}

$current_username = $_SESSION['username'];

//Start timing
$start_time = microtime(true);

// Query all information about the user

$stmt = $conn->prepare("SELECT id, username, name, phone, created_at, address, picture FROM users WHERE username=?");
$stmt->bind_param("s", $current_username);
$stmt->execute();
$result = $stmt->get_result();

// Get user data
$user_data = $result->fetch_assoc();
$user_id = $user_data['id'] ?? 'N/A';
$username = $user_data['username'] ?? 'N/A';
$address = $user_data['address'] ?? 'N/A';
$phone = $user_data['phone'] ?? 'N/A';
$created_at = $user_data['created_at'] ?? 'N/A';
$name = $user_data['name'] ?? 'N/A';
$picture = $user_data['picture'] ?? null;

$stmt->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get user data
    $name = $_POST['name'] ?? '';
    $address = $_POST['address'] ?? '';
    $phone = $_POST['phone'] ?? '';

    // Update the database
    $stmt = $conn->prepare("UPDATE users SET name = ?, address = ?, phone = ? WHERE username = ?");
    $stmt->bind_param("ssss", $name, $address, $phone, $current_username);

    if ($stmt->execute()) {
        // When the modification is successful, display the alert box and jump to the
        echo '<script>
            alert("Information updated successfully.");
            window.location.href = "profile.php"; // 跳转到 profile.php
        </script>';
    } else {
        echo '<script>
            alert("Error updating information.");
        </script>';
    }

    $stmt->close();
}

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
    <title>Personal Information Update| GBC Internet Banking</title>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header__content">
            <div class="header__logo">
                <a href="./dashboard.php"><img src="./assets/logo.png" alt="Bank Logo"></a>
            </div>
            <h1>Personal Information Update</h1>
            <div class="header__right">
                Current User: <?php echo htmlspecialchars($_SESSION['username']); ?>
                <button class="logout-button" style="margin-left: 10px;" onclick="window.location.href='logout.php'">Logout</button>
            </div>
        </div>
    </header>

    <main class="dashboard">
        <div class="info-container">

            <section class="account-summary">
                <h2>Overview</h2>
                <form action="" method="POST" class="update-form">
                    <div class="sub-accounts">
                        <div class="sub-account">
                            <label for="username">Username: <br></label>
                            <span class="balance"><?php echo htmlspecialchars($username); ?></span>
                        </div>
                        <div class="sub-account">
                            <label for="user_id">User ID: </label>
                            <span class="balance"><?php echo htmlspecialchars($user_id); ?></span>
                        </div>
                        <div class="sub-account">
                            <label for="created_at">Valued customer since: </label>
                            <span class="balance"><?php echo htmlspecialchars(date("F j, Y", strtotime($created_at))); ?></span>
                        </div>
                        <div class="sub-account">
                            <label for="name">Name</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name ?: 'null'); ?>" required>
                        </div>
                        <div class="sub-account">
                            <label for="address">Address</label>
                            <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($address ?: 'null'); ?>" required>
                        </div>
                        <div class="sub-account">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone ?: 'null'); ?>" required>
                        </div>
                    </div>
                    <div class="submit-button-container">
                        <button type="submit">Update</button>
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
        
        .update-form {
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
        
        .sub-account input {
            width: 50%; 
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