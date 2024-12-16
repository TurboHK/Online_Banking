<?php
session_start();
include 'db_connection.php';

// Verify that the user is logged on
if (!isset($_SESSION['username'])) {
    header("Location: index.html");
    exit();
}

// Get the currently logged in username
$username = $_SESSION['username'];

//Start timing
$start_time = microtime(true); 

// Query current user information
$user_info = null;
$stmt = $conn->prepare("SELECT id, name, address ,phone FROM users WHERE username = ?");
if ($stmt) {
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_info = $result->fetch_assoc();
    $stmt->close();
}

if (!$user_info) {
    echo "Error: Failed to retrieve user information.";
    exit();
}

$user_id = $user_info['id'];
$user_name = $user_info['name'];
$user_address = $user_info['address'];
$user_phone = $user_info['phone'];

// Processing form submissions
if (isset($_POST['submit'])) {
    $Name = $_POST['Name'] ?? null;
    $Address = $_POST['Address'] ?? null;
    $Phone = $_POST['Phone'] ?? null;

    // Check if the cell phone number is 11 digits
    
        // Insert data into the applycredit table
        $sql = "INSERT INTO applycredit (status, name, address, phone, user_id) VALUES ('waiting', ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("sssi", $Name, $Address, $Phone, $user_id);
            if ($stmt->execute()) {
                echo "<script>alert('Application submitted successfully!');</script>";
            } else {
                echo "Error: " . $stmt->error;
            }
            $stmt->close();
        } else {
            echo "Error in SQL: " . $conn->error;
        }
    
}

// Processing revocation requests
if (isset($_POST['cancel'])) {
    $apply_id = $_POST['apply_id'];

    $delete_query = "DELETE FROM applycredit WHERE apply_id = ? AND user_id = ?";
    $stmt = $conn->prepare($delete_query);

    if ($stmt) {
        $stmt->bind_param("ii", $apply_id, $user_id);
        if ($stmt->execute()) {
            echo "<script>alert('Application cancelled successfully!');</script>";
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Error in SQL: " . $conn->error;
    }
}

// Query all application records for the current user
$applications_query = "SELECT apply_id, name, address, phone, status FROM applycredit WHERE user_id = ?";
$applications_result = null;
$stmt = $conn->prepare($applications_query);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $applications_result = $stmt->get_result();
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
    <title>Credit Card Application | GBC Internet Banking</title>
</head>
<body>
    <header class="header">
        <div class="header__content">
            <div class="header__logo">
                <a href="dashboard.php" style="text-decoration: none;">
                    <img src="./assets/logo.png" alt="Bank Logo">
                </a>
            </div>
            <h1>Apply For Credit Cards</h1>
            <div class="header__right">
                Current User: <?php echo htmlspecialchars($_SESSION['username']); ?>
                <button class="logout-button" style="margin-left: 10px;" onclick="window.location.href='logout.php'">Logout</button>
            </div>
        </div>
    </header>

    <main class="dashboard">
        <div class="form-container">
            <h2>Application Form</h2>
            <form method="post" action="">
                <div class="form-group">
                    <label for="Name">Name:</label>
                    <input type="text" name="Name" placeholder="Full Name" value="<?php echo htmlspecialchars($user_name); ?>" required>
                </div>
                <div class="form-group">
                    <label for="Address">Address:</label>
                    <input type="text" name="Address" placeholder="Address" value="<?php echo htmlspecialchars($user_address); ?>" required>
                </div>
                <div class="form-group">
                    <label for="Phone">Phone:</label>
                    <input type="tel" name="Phone" placeholder="Phone number" pattern="^\d{8}$" minlength="8" maxlength="8" value="<?php echo htmlspecialchars($user_phone); ?>" required>
                </div>
                If you want to change the information above, please click <a href="./profile.php">here</a>.<br><br>
                <div class="form-group">
                    <input type="submit" name="submit" value="Submit">
                </div>
            </form>

            <!-- 显示用户申请记录 -->
            <h3>Your Current Applications</h3>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Address</th>
                    <th>Phone Number</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                <?php
                if ($applications_result && $applications_result->num_rows > 0) {
                    while ($row = $applications_result->fetch_assoc()) {
                        echo "<tr>
                                <td>{$row['apply_id']}</td>
                                <td>{$row['name']}</td>
                                <td>{$row['address']}</td>
                                <td>{$row['phone']}</td>
                                <td>{$row['status']}</td>
                                <td>";
                        // Check the status, if it's “Waiting”, show the cancel button. Otherwise show “Complete”
                        if ($row['status'] === 'waiting') {
                            echo "<form method='post' action='' style='margin: 0;'>
                                    <input type='hidden' name='apply_id' value='{$row['apply_id']}'>
                                    <input type='submit' name='cancel' value='Cancel' class='cancel-button'>
                                  </form>";
                        } else {
                            echo "<span style='color: gray;'>Complete</span>";
                        }
                        echo "</td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='6'>No current applications</td></tr>";
                }
                ?>
            </table>
        </div>
    </main>

    <div class="back-link">
        <p><a href="javascript:history.back()">Return to the previous page</a></p>
    </div>

    <footer class="footer">
        <?php if ($execution_time): ?>
            It took <?php echo $execution_time; ?> milliseconds to get data from the server.</p>
        <?php endif; ?>
        ©2024 Global Banking Corporation Limited. All rights reserved.
    </footer>
</body>
<style>
        .form-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 1px 6px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        .form-container h2 {
            text-align: center;
            font-size: 24px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            font-size: 16px;
            display: block;
            margin-bottom: 8px;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .form-group input[type="submit"] {
            background-color: #4ba247;
            color: white;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }

        .form-group input[type="submit"]:hover {
            background-color: #45a047;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 12px;
            text-align: left;
        }

        th {
            background-color: #f4f4f4;
        }

        .cancel-button {
            background-color: #d9534f;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            border-radius: 4px;
        }

        .cancel-button:hover {
            background-color: #c9302c;
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
</html>
