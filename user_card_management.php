<?php
session_start();
include 'db_user_connection.php';

if (!isset($_SESSION['username'])) {
    header("Location: index.html"); // 如果没有登录则跳转到登录页面
    exit();
}

$current_username = $_SESSION['username'];

//Start timing
$start_time = microtime(true);

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
    <title>My Cards | GBC Internet Banking</title>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header__content">
            <div class="header__logo">
                <a href="./dashboard.php"><img src="./assets/logo.png" alt="Bank Logo"></a>
            </div>
            <h1>Welcome to GBC Internet Banking</h1>
            <div class="header__right">
                Current User: <?php echo htmlspecialchars($_SESSION['username']); ?>
                <button class="logout-button" style="margin-left: 10px;" onclick="window.location.href='logout.php'">Logout</button>
            </div>
        </div>
    </header>

    <div class="container" style="margin-top: 150px;">

        <!-- Search Box -->
        <div class="search-container">
            <input type="text" id="searchBox" placeholder="Search by card number..." oninput="filterBySearch()">
        </div>

        <!-- Filter Buttons -->
        <div class="filter-buttons">
            <button onclick="filterCards('all')">Show All</button>
            <button onclick="filterCards('Credit Card')">Credit Cards</button>
            <button onclick="filterCards('Debit Card')">Debit Cards</button>
        </div>

        <!-- Card List -->
        <div class="card-list" id="cardList">
            <!-- Cards will be displayed here dynamically -->
        </div>
    </div>

    <div class="back-link">
        <p><a href="./dashboard.php">Return to the Dashboard</a></p>
    </div>
    
    <!-- Footer -->
    <footer class="footer">
        <?php if ($execution_time): ?>
            It took <?php echo $execution_time; ?> milliseconds to get data from the server.</p>
        <?php endif; ?>
        ©2024 Global Banking Corporation Limited. All rights reserved.
    </footer>

    <script>
        // Example data fetched from backend
        const userCards = [
            { cardNumber: '1234 5678 9876 5432', type: 'Credit Card', detailsPage: 'credit_card_details.php?card=1234' },
            { cardNumber: '1111 2222 3333 4444', type: 'Debit Card', detailsPage: 'debit_card_details.php' },
        ];

        // Function to load and filter cards
        function filterCards(filter) {
            const cardList = document.getElementById('cardList');
            cardList.innerHTML = ''; // Clear the list
            const filteredCards = filter === 'all' ? userCards : userCards.filter(card => card.type === filter);
            renderCards(filteredCards);
        }

        // Function to filter by search
        function filterBySearch() {
            const searchValue = document.getElementById('searchBox').value.toLowerCase();
            const filteredCards = userCards.filter(card => card.cardNumber.toLowerCase().includes(searchValue));
            renderCards(filteredCards);
        }

        // Function to render cards
        function renderCards(cards) {
            const cardList = document.getElementById('cardList');
            cardList.innerHTML = '';
            if (cards.length === 0) {
                cardList.innerHTML = '<p>No cards found.</p>';
                return;
            }
            cards.forEach(card => {
                const cardItem = document.createElement('div');
                cardItem.classList.add('card-item');
                cardItem.innerHTML = `
                    <span>${card.cardNumber} (${card.type})</span>
                    <button class="details-button" onclick="window.location.href='${card.detailsPage}'">View Details</button>
                `;
                cardList.appendChild(cardItem);
            });
        }

        // Load all cards on page load
        window.onload = () => filterCards('all');
    </script>

<style>
    .container {
        width: 80%;
        margin: 120px auto 20px;
        background-color: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .filter-buttons {
        text-align: center;
        margin-bottom: 20px;
    }
    .filter-buttons button {
        padding: 10px 20px;
        background-color: #4CAF50;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        margin: 0 10px;
    }
    .filter-buttons button:hover {
        background-color: #45a049;
    }
    .search-container {
        text-align: center;
        margin: 20px auto;
    }
    .search-container input {
        width: 60%;
        padding: 10px;
        font-size: 16px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }
    .card-list {
        max-width: 800px;
        margin: 20px auto;
        text-align: left;
    }
    .card-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 4px;
        background-color: #fff;
    }
    .card-item .details-button {
        padding: 5px 10px;
        background-color: #007BFF;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }
    .card-item .details-button:hover {
        background-color: #0056b3;
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
