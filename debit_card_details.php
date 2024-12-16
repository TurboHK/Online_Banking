<?php
session_start();
include 'db_user_connection.php';

if (!isset($_SESSION['username'])) {
    header("Location: index.html"); // If not logged in, redirect to login page.
    exit();
}

$current_username = $_SESSION['username']; // Currently logged-in username

// Handle POST request to freeze or unlock the card
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['card_number'])) {
    $action = $_POST['action'];
    $card_number = $_POST['card_number'];

    // Validate action
    if (!in_array($action, ['freeze', 'unlock'])) {
        $error_message = "Invalid action.";
    } else {
        // Check if the card belongs to the logged-in user
        $stmt = $conn->prepare("
            SELECT cards.id, cards.blocked
            FROM cards
            INNER JOIN users ON cards.cardholder_id = users.id
            WHERE cards.card_number = ? AND users.username = ?
        ");
        $stmt->bind_param("ss", $card_number, $current_username);
        $stmt->execute();
        $result = $stmt->get_result();
        $card_data = $result->fetch_assoc();
        $stmt->close();

        if (!$card_data) {
            $error_message = "Card not found or access denied.";
        } else {
            // Update card's blocked status
            $new_blocked_status = ($action === 'freeze') ? 1 : 0;
            $stmt = $conn->prepare("UPDATE cards SET blocked = ? WHERE card_number = ?");
            $stmt->bind_param("is", $new_blocked_status, $card_number);
            if ($stmt->execute()) {
                $success_message = "Card status updated successfully.";
            } else {
                $error_message = "Failed to update card status.";
            }
            $stmt->close();
        }
    }
}

// Get the card number from the query string
$card_number = isset($_GET['card']) ? $_GET['card'] : null;

if (!$card_number) {
    header("Location: 404.php"); // If no card number is provided, redirect to 404 page.
    exit();
}

// Start timing
$start_time = microtime(true);

// Query to check if the debit card exists and belongs to the current user
$stmt = $conn->prepare("
    SELECT debitcards.*, cards.card_number, cards.blocked, users.username
    FROM debitcards
    INNER JOIN cards ON debitcards.id = cards.id
    INNER JOIN users ON cards.cardholder_id = users.id
    WHERE cards.card_number = ? AND users.username = ?
");
$stmt->bind_param("ss", $card_number, $current_username);
$stmt->execute();
$result = $stmt->get_result();

// Fetch card details
$card_data = $result->fetch_assoc();
$stmt->close();

// If no card is found or it does not belong to the current user, redirect to 404
if (!$card_data) {
    header("Location: 404.php");
    exit();
}

// End timing
$end_time = microtime(true);
$execution_time = round(($end_time - $start_time) * 1000, 2); // Convert to milliseconds
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/dashboard.css" />
    <link rel="icon" href="assets/logo.png" type="image/png">
    <title>Debit Card Details | GBC Internet Banking</title>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header__content">
            <div class="header__logo">
                <a href="./dashboard.php"><img src="./assets/logo.png" alt="Bank Logo"></a>
            </div>
            <h1>Debit Card Details</h1>
            <div class="header__right">
                Current User: <?php echo htmlspecialchars($_SESSION['username']); ?>
                <button class="logout-button" style="margin-left: 10px;" onclick="window.location.href='logout.php'">Logout</button>
            </div>
        </div>
    </header>

    <div class="container">
    <h1>Debit Card Details</h1>
    <div class="details">
        <p><strong>Card Number:</strong> <?php echo htmlspecialchars($card_data['card_number']); ?></p>
        <p><strong>Last Transaction:</strong> <?php echo htmlspecialchars($card_data['last_transaction'] ?? 'N/A'); ?></p>
        <p><strong>Spending Limit:</strong> <?php echo number_format($card_data['spending_limit'] ?? 0, 2); ?> HKD</p>
        <p><strong>Available Balance:</strong> <?php echo number_format($card_data['balance'], 2); ?> HKD</p>
        <p><strong>Card Status:</strong> <?php echo $card_data['blocked'] == 0 ? 'Active' : 'Frozen'; ?></p>
    </div>

    <!-- Display messages -->
    <?php if (isset($success_message)): ?>
        <p style="color: green;"><?php echo $success_message; ?></p>
    <?php elseif (isset($error_message)): ?>
        <p style="color: red;"><?php echo $error_message; ?></p>
    <?php endif; ?>

    <div class="button-group">
    <!-- Freeze button -->
    <form method="POST" style="display: inline;">
        <input type="hidden" name="card_number" value="<?php echo htmlspecialchars($card_data['card_number']); ?>">
        <input type="hidden" name="action" value="freeze">
        <button 
            class="freeze-button" 
            <?php echo $card_data['blocked'] == 1 ? 'disabled' : ''; ?>
            onmouseover="handleMouseOver(this, <?php echo $card_data['blocked'] == 1 ? 'true' : 'false'; ?>)"
            onmouseout="handleMouseOut(this)"
        >
            Freeze Card
        </button>
    </form>

    <!-- Unlock button -->
    <form method="POST" style="display: inline;">
        <input type="hidden" name="card_number" value="<?php echo htmlspecialchars($card_data['card_number']); ?>">
        <input type="hidden" name="action" value="unlock">
        <button 
            class="unlock-button" 
            <?php echo $card_data['blocked'] == 0 ? 'disabled' : ''; ?>
            onmouseover="handleMouseOver(this, <?php echo $card_data['blocked'] == 0 ? 'true' : 'false'; ?>)"
            onmouseout="handleMouseOut(this)"
        >
            Unlock Card
        </button>
    </form>
</div>

        <div style="text-align: center;">
            <a href="./user_card_management.php" style="text-decoration: none; color: #007BFF;">Back to Card Management</a>
        </div>
    </div>

    <footer class="footer">
        <?php if ($execution_time): ?>
            It took <?php echo $execution_time; ?> milliseconds to get data from the server.</p>
        <?php endif; ?>
        ©2024 Global Banking Corporation Limited. All rights reserved.
    </footer>

    <script>
    function handleCardAction(action) {
        const cardNumber = "<?php echo htmlspecialchars($card_data['card_number']); ?>";

        // Send the action directly to the server
        fetch('card_action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ card_number: cardNumber, action: action }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.blocked !== undefined) {
                alert(data.message); // Display server response
                location.reload();  // Reload the page to reflect changes
            } else {
                alert('Failed to update card status. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    }

    function handleMouseOver(button, isDisabled) {
        if (isDisabled) {
            button.classList.add('hover-disabled');
            button.title = "This action is currently disabled";
        }
    }

    function handleMouseOut(button) {
        button.classList.remove('hover-disabled');
        button.title = "";
    }
</script>

    <style>
        .container {
            max-width: 600px;
            margin: 50px auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 6px rgba(0, 0, 0, 0.1);
        }

        .details {
            margin: 20px 0;
        }
        .details p {
            font-size: 16px;
            margin-bottom: 10px;
        }
        .button-group {
            text-align: center;
            margin-top: 20px;
        }
        .button-group button {
            padding: 10px 20px;
            margin: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .freeze-button {
            background-color: #FF4C4C;
            color: white;
        }
        .freeze-button:hover {
            background-color: #E53935;
        }
        .unlock-button {
            background-color: #4CAF50;
            color: white;
        }
        .unlock-button:hover {
            background-color: #388E3C;
        }

        .freeze-button.hover-disabled,
        .unlock-button.hover-disabled {
            background-color: #cccccc !important;
            cursor: not-allowed !important;
            color: #666666 !important;
        }

        .freeze-button:hover:not(.hover-disabled) {
            background-color: #E53935;
        }

        .unlock-button:hover:not(.hover-disabled) {
            background-color: #388E3C;
        }

    </style>
</body>
</html>
