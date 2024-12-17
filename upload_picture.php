<?php
session_start();
include 'db_user_connection.php';

if (!isset($_SESSION['username'])) {
    header("Location: index.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_username = $_SESSION['username'];

    // Get the uploaded image file
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $image = file_get_contents($_FILES['profile_picture']['tmp_name']); // Get image content

        // Updating images in the database
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
        header("Location: profile.php"); // Redirect to personal information page after successful upload
        exit();
    }
    else {
        echo "Error: Please upload a valid image file.";
    }
}
?>
