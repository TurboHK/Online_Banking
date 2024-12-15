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

// Query all information about a user
$stmt = $conn->prepare("SELECT id, username, name, phone, created_at, address, picture FROM users WHERE username=?");
$stmt->bind_param("s", $current_username);
$stmt->execute();
$result = $stmt->get_result();

// Getting user data
$user_data = $result->fetch_assoc();
$user_id = $user_data['id'] ?? 'N/A';
$username = $user_data['username'] ?? 'N/A';
$address = $user_data['address'] ?? 'N/A';
$phone = $user_data['phone'] ?? 'N/A';
$created_at = $user_data['created_at'] ?? 'N/A';
$name = $user_data['name'] ?? 'N/A';
$picture = $user_data['picture'] ?? null;

$stmt->close();

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
            <!-- Left card -->
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
    
            <!-- Right card -->
            <section class="account-summary">
                <h2>Profile Picture</h2>
                <div class="account">
                    <?php if ($picture): ?>
                        <!-- Image Display -->
                        <img src="data:image/jpeg;base64,<?php echo base64_encode($picture); ?>" alt="Profile Picture" class="profile-picture">
                    <?php else: ?>
                        <!-- If there is no image, show placeholder -->
                        No profile picture uploaded.<br><br>Consider uploading one to make your profile unique!<br><br>
                    <?php endif; ?>
                    
                    <!-- Upload button -->
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
        <?php if ($execution_time): ?>
            It took <?php echo $execution_time; ?> milliseconds to get data from the server.</p>
        <?php endif; ?>
        ©2024 Global Banking Corporation Limited. All rights reserved.
    </footer>

    <style>
        .update-button-container {
        text-align: center; /* center button */
        margin: 20px 0; /* Spacing between buttons and other content */
        }

        .update-button-container button {
            padding: 10px 20px;
            background-color: #4BA247; /* Button background color */
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
        }
        .info-container {
            display: flex; /* horizontal arrangement */
            gap: 20px; /* Spacing between cards */
        }

        .account-summary {
            background-color: #f7f7f7;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 80%; /* Default 60% width for left side cards */
        }

        .account-summary:nth-child(2) {
            width: 20%; /* The second card is 40% of the width */
        }

        .account {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .profile-picture {
            width: 100%; /* The image takes up the full width of the parent container */
            max-width: 200px; /* Setting the maximum width of an image */
            border-radius: 10px;
            margin-bottom: 15px; /* Spacing between images and buttons */
        }
        
        .upload-form {
            display: flex;
            flex-direction: column; /* Vertical Alignment Upload Component */
            align-items: center; /* center button */
        }
        
        .upload-form input[type="file"] {
            margin-bottom: 10px; /* Spacing between the upload file selection box and the button */
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
            background-color: #0a254a; /* Button Hover Effect */
        }
        
    </style>