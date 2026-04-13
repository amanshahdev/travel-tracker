<?php
session_start();
require_once 'config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    // Server-side validation
    $errors = [];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    if (empty($password)) {
        $errors[] = "Password is required.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            setcookie('user_id', $user['user_id'], time() + (86400 * 30), "/"); // 30-day cookie
            header("Location: dashboard.php");
            exit;
        } else {
            $errors[] = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Travel Tracker</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body class="bg-cover bg-center h-screen" style="background-image: url('images/travel8.jpg'); font-family: 'Inter', Arial, sans-serif;">
    <div class="flex items-center justify-center h-full bg-black bg-opacity-50">
        <div class="w-full max-w-md">
            <div class="flex justify-center mb-6">
                <img src="images/logo.png" alt="Travel Tracker Logo" class="h-12 w-auto">
            </div>
            <div class="bg-white bg-opacity-60 p-8 rounded-lg shadow-lg">
                <div class="flex flex-col items-center mb-4">
                    <h2 class="text-2xl font-bold mb-2 text-center">Login</h2>
                </div>
                <?php if (!empty($errors)): ?>
                    <div class="bg-red-100 text-red-700 p-4 mb-4 rounded">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="bg-green-100 text-green-700 p-4 mb-4 rounded">
                        <p><?php echo htmlspecialchars($_SESSION['success']); ?></p>
                        <?php unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>
                <form method="POST" class="space-y-4" id="loginForm">
                    <div>
                        <label for="email" class="block text-sm font-medium mb-2">Email</label>
                        <input type="email" name="email" id="email" required class="w-full p-2 border rounded">
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium mb-2">Password</label>
                        <input type="password" name="password" id="password" required class="w-full p-2 border rounded">
                    </div>
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 rounded">Login</button>
                </form>
                <p class="mt-4 text-center">Don't have an account? <a href="register.php" class="text-blue-600">Register</a></p>
            </div>
        </div>
    </div>
    <script src="js/script.js"></script>
</body>
</html>