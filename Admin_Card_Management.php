<?php
session_start();
include 'db_connection.php';

// Check if the user is an administrator
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.html"); // Redirect to login page if not logged in or non-administrator
    exit();
}

$search_result = '';
$execution_time = ''; // Used to store execution time
$cards = []; // Used to store searched card information
$user_id = ''; // Initialize user ID to null

// Handling Search User IDs
if (isset($_POST['search_user'])) {
    $user_id = $_POST['user_id'];  // Get the user ID entered by the user

    // start counting
    $start_time = microtime(true);

    // Query all card information under this user ID
    $stmt = $conn->prepare("SELECT * FROM cards WHERE cardholder_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // If the card information is queried, it is stored in an array
    if ($result->num_rows > 0) {
        $cards = [];
        while ($row = $result->fetch_assoc()) {
            $cards[] = $row;  // Storing card information into an array
        }
        $search_result = "<table border='1'><tr><th>Card Type</th><th>Card Number</th><th>Status</th><th>Actions</th></tr>";
        foreach ($cards as $card) {
            $search_result .= "<tr>
                                    <td>" . htmlspecialchars($card['type']) . "</td>
                                    <td>" . htmlspecialchars($card['card_number']) . "</td> <!-- 直接显示卡号 -->
                                    <td>" . ($card['blocked'] == 0 ? 'Active' : 'Blocked') . "</td>
                                    <td>
                                        " . ($card['blocked'] == 0 ? 
                                            "<button onclick=\"location.href='block_card.php?card_id=" . $card['id'] . "'\">Block Card</button>" : 
                                            "<button onclick=\"location.href='unblock_card.php?card_id=" . $card['id'] . "'\">Unblock Card</button>") . "
                                    </td>
                                  </tr>";
        }
        $search_result .= "</table>";
    } else {
        $search_result = "No cards found for this user.";
    }

    $stmt->close();

    //End timing
    $end_time = microtime(true);
    $execution_time = round(($end_time - $start_time) * 1000, 2); // Convert to milliseconds
}

// Processing clear search results
if (isset($_POST['clear'])) {
    $search_result = '';  // Clear Search Results
    $execution_time = '';  // Clear execution time
    $user_id = ''; // Clear User ID
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/dashboard.css" />
    <link rel="icon" href="assets/logo.png" type="image/png">
    <title>Card Management | GBC Internet Banking</title>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header__content">
            <div class="header__logo">
                <a href="./admin_dashboard.php"><img src="./assets/logo.png" alt="Bank Logo"></a>
            </div>
            <h1>Card Management</h1>
            <div class="header__right">
                Current Administrator: <?php echo htmlspecialchars($_SESSION['username']); ?>
                <button class="logout-button" style="margin-left: 10px;" onclick="window.location.href='logout.php'">Logout</button>
            </div>
        </div>
    </header>

    <!-- Main Dashboard Content -->
    <main class="dashboard">
        <!-- Search User ID Function -->
        <section class="user-search">
            <h2>Search User's Cards</h2>
            <form method="post" action="">
                <label for="user_id">User ID:</label>
                <input type="text" name="user_id" id="user_id" value="<?php echo htmlspecialchars($user_id); ?>" required>
                <input type="submit" name="search_user" value="Search">
                <input type="submit" name="clear" value="Clear"> <!-- Clear Search Results button -->
            </form>

            <!-- Show search results -->
            <div>
                <?php if ($execution_time): ?>
                    <p>Search executed in <span><?php echo $execution_time; ?></span> milliseconds.</p><br>
                <?php endif; ?>
                <?php
                // Output search results
                if ($search_result) {
                    echo $search_result;
                }
                ?>
            </div>
        </section>

        <!-- Return to Admin Panel -->
        <div class="back-link">
                <p><a href="javascript:history.back()">Return to the previous page</a></p>
        </div>
    </main>

    <footer class="footer">
        <?php if ($execution_time): ?>
            It took <?php echo $execution_time; ?> milliseconds to get data from the server.</p>
        <?php endif; ?>
        ©2024 Global Banking Corporation Limited. All rights reserved.
    </footer>
</body>
</html>

<style>
        .back-link {
        text-align: center;
        margin-top: 20px;
    }
    .back-link a {
        text-decoration: none;
        color: #0064d2;
    }
</style>