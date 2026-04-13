<?php
session_start();
if (!isset($_SESSION['user_id']) && isset($_COOKIE['user_id'])) {
    require_once 'config/db_connect.php';
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$_COOKIE['user_id']]);
    $user = $stmt->fetch();
    if ($user) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
    }
}
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travel Tracker App</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body class="bg-cover bg-center h-screen" style="background-image: url('images/travel8.jpg'); font-family: 'Inter', Arial, sans-serif;">
    <div class="absolute top-0 left-0 m-6">
        <img src="images/logo.png" alt="Travel Tracker Logo" class="h-12 w-auto">
    </div>
    <div class="absolute top-0 right-0 m-6 flex space-x-4">
        <a href="login.php" class="text-white font-bold py-2 px-4 border border-white rounded hover:opacity-80 transition">Login</a>
    </div>
    <div class="flex items-center justify-center h-full bg-black bg-opacity-50">
        <div class="text-center text-white">
            <h1 class="text-4xl font-bold mb-4">Welcome to Travel Tracker</h1>
            <p class="text-lg mb-6">Your adventures await — click below to begin your journey!</p>
            <div class="space-x-4 mt-8">
                <a href="register.php" class="text-white font-bold py-2 px-4 border border-white rounded hover:opacity-80 transition">Get Started</a>
            </div>
        </div>
    </div>
    <footer class="w-full bg-black bg-opacity-60 text-white text-center py-4 text-sm fixed bottom-0 left-0">
        &copy; <?php echo date('Y'); ?> Travel Tracker &mdash; Log and relive your journeys.
    </footer>
</body>
</html>