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
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once 'config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Server-side validation
    $errors = [];
    if (empty($username) || strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters long.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    // Password change logic
    $update_password = false;
    if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $errors[] = "All password fields are required to change password.";
        } else if (strlen($new_password) < 6) {
            $errors[] = "New password must be at least 6 characters long.";
        } else if ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match.";
        } else {
            // Fetch current password hash
            $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user_row = $stmt->fetch();
            if (!$user_row || !password_verify($current_password, $user_row['password'])) {
                $errors[] = "Current password is incorrect.";
            } else {
                $update_password = true;
            }
        }
    }

    if (empty($errors)) {
        if ($update_password) {
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE user_id = ?");
            $success = $stmt->execute([$username, $email, $hashed_password, $_SESSION['user_id']]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE user_id = ?");
            $success = $stmt->execute([$username, $email, $_SESSION['user_id']]);
        }
        if ($success) {
            $_SESSION['username'] = $username;
            $_SESSION['success'] = $update_password ? "Settings and password updated successfully." : "Settings updated successfully.";
            header("Location: settings.php");
            exit;
        } else {
            $errors[] = "Failed to update settings.";
        }
    }
}

$stmt = $pdo->prepare("SELECT username, email FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Travel Tracker</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body class="bg-cover bg-center bg-fixed h-screen" style="background-image: url('images/travel8.jpg'); font-family: 'Inter', Arial, sans-serif;">
    <div class="flex flex-col min-h-screen bg-black bg-opacity-50">
        <?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
        <nav class="bg-transparent text-gray-900 p-4 px-10 shadow-none">
            <div class="container mx-auto flex justify-between items-center">
                <img src="images/logo.png" alt="Travel Tracker Logo" class="h-10 w-auto mr-4">
                <div class="space-x-4">
                    <a href="dashboard.php" class="text-white hover:text-blue-200 transition inline-flex items-center align-middle <?php if($currentPage==='dashboard.php') echo 'underline underline-offset-4'; ?>">
                        <svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5 mr-1 align-middle' fill='none' viewBox='0 0 24 24' stroke='currentColor'>
                            <rect x='3' y='3' width='7' height='7' rx='2' />
                            <rect x='14' y='3' width='7' height='7' rx='2' />
                            <rect x='14' y='14' width='7' height='7' rx='2' />
                            <rect x='3' y='14' width='7' height='7' rx='2' />
                        </svg>
                        <span class="align-middle">Dashboard</span>
                    </a>
                    <a href="add_trip.php" class="text-white hover:text-blue-200 transition inline-flex items-center align-middle <?php if($currentPage==='add_trip.php') echo 'underline underline-offset-4'; ?>">
                        <svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5 mr-1 align-middle' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 4v16m8-8H4' /></svg>
                        <span class="align-middle">Add Trip</span>
                    </a>
                    <a href="settings.php" class="text-white hover:text-blue-200 transition inline-flex items-center align-middle <?php if($currentPage==='settings.php') echo 'underline underline-offset-4'; ?>">
                        <svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5 mr-1 align-middle flex-shrink-0 self-center' fill='none' viewBox='0 0 24 24' stroke='currentColor' style='display:inline-block;vertical-align:middle;'>
                            <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.527-.99 3.362.845 2.372 2.372a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.99 1.527-.845 3.362-2.372 2.372a1.724 1.724 0 00-2.573 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.527.99-3.362-.845-2.372-2.372a1.724 1.724 0 00-1.065-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.99-1.527.845-3.362 2.372-2.372.996.646 2.326.07 2.573-1.065z' />
                            <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M15 12a3 3 0 11-6 0 3 3 0 016 0z' />
                        </svg>
                        <span class="align-middle">Settings</span>
                    </a>
                    <button onclick="confirmLogout()" class="text-white hover:text-blue-200 transition inline-flex items-center align-middle <?php if($currentPage==='logout.php') echo 'underline underline-offset-4'; ?>">
                        <svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5 mr-1 align-middle' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H7a2 2 0 01-2-2V7a2 2 0 012-2h4a2 2 0 012 2v1' /></svg>
                        <span class="align-middle">Logout</span>
                    </button>
                </div>
            </div>
        </nav>
        <div class="flex flex-1 items-center justify-center mb-20">
            <div class="bg-white bg-opacity-60 rounded-lg shadow-lg p-8 w-full max-w-md">
                <h2 class="text-2xl font-bold mb-6 text-center text-blue-900">Settings</h2>
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
                <form method="POST" class="space-y-4 max-w-lg mx-auto">
                    <div>
                        <label for="username" class="block text-sm font-medium text-black mb-2">Username</label>
                        <input type="text" name="username" id="username" required minlength="3" value="<?php echo htmlspecialchars($user['username']); ?>" class="w-full p-2 border rounded">
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-black mb-2">Email</label>
                        <input type="email" name="email" id="email" required value="<?php echo htmlspecialchars($user['email']); ?>" class="w-full p-2 border rounded">
                    </div>
                    <hr class="my-4 border-gray-300">
                    <div>
                        <label class="block text-sm font-medium text-black mb-2">Change Password</label>
                        <input type="password" name="current_password" placeholder="Current Password" class="w-full p-2 border rounded mb-2">
                        <input type="password" name="new_password" placeholder="New Password (min 6 chars)" class="w-full p-2 border rounded mb-2">
                        <input type="password" name="confirm_password" placeholder="Confirm New Password" class="w-full p-2 border rounded">
                    </div>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Update Settings</button>
                </form>
            </div>
        </div>
    </div>
    <footer class="w-full bg-black bg-opacity-60 text-white text-center py-4 mt-auto text-sm">
        &copy; <?php echo date('Y'); ?> Travel Tracker &mdash; Log and relive your journeys.
    </footer>
    
    <!-- Custom Confirmation Popup -->
    <div id="confirmationPopup" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl shadow-lg p-6 max-w-sm w-full mx-4">
            <h3 id="popupTitle" class="text-lg font-semibold text-gray-900 mb-4"></h3>
            <div class="flex space-x-3 justify-center">
                <button id="popupCancel" class="px-4 py-2 text-gray-600 bg-gray-200 hover:bg-gray-300 rounded-md transition">
                    Cancel
                </button>
                <button id="popupConfirm" class="px-4 py-2 text-white bg-red-600 hover:bg-red-700 rounded-md transition">
                    Confirm
                </button>
            </div>
        </div>
    </div>
    
    <script src="js/script.js"></script>
    <script>
        function confirmLogout() {
            showConfirmationPopup(
                'Are you sure you want to logout?',
                'Stay logged in',
                'Logout',
                () => {
                    window.location.href = 'logout.php';
                },
                'red'
            );
        }

        function showConfirmationPopup(title, cancelText, confirmText, onConfirm, confirmColor = 'red') {
            const popup = document.getElementById('confirmationPopup');
            const popupTitle = document.getElementById('popupTitle');
            const cancelBtn = document.getElementById('popupCancel');
            const confirmBtn = document.getElementById('popupConfirm');

            popupTitle.textContent = title;
            cancelBtn.textContent = cancelText;
            confirmBtn.textContent = confirmText;

            // Reset button colors
            cancelBtn.className = 'px-4 py-2 text-gray-600 bg-gray-200 hover:bg-gray-300 rounded-md transition';
            confirmBtn.className = 'px-4 py-2 text-white bg-red-600 hover:bg-red-700 rounded-md transition';

            // Apply custom color if specified
            if (confirmColor === 'red') {
                confirmBtn.className = 'px-4 py-2 text-white bg-red-600 hover:bg-red-700 rounded-md transition';
            }

            popup.classList.remove('hidden');

            const hidePopup = () => {
                popup.classList.add('hidden');
            };

            cancelBtn.onclick = hidePopup;
            confirmBtn.onclick = () => {
                hidePopup();
                onConfirm();
            };

            // Close popup when clicking outside
            popup.onclick = (e) => {
                if (e.target === popup) {
                    hidePopup();
                }
            };
        }
    </script>
</body>
</html>