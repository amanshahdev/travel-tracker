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

$trip_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
$stmt = $pdo->prepare("SELECT * FROM trips WHERE trip_id = ? AND user_id = ?");
$stmt->execute([$trip_id, $_SESSION['user_id']]);
$trip = $stmt->fetch();

if (!$trip) {
    header("Location: dashboard.php");
    exit;
}

// Get existing additional images
$stmt = $pdo->prepare("SELECT image_id, image_filename FROM trip_images WHERE trip_id = ? ORDER BY upload_date ASC");
$stmt->execute([$trip_id]);
$existing_images = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
    $destination = filter_input(INPUT_POST, 'destination', FILTER_SANITIZE_STRING);
    $location = filter_input(INPUT_POST, 'location', FILTER_SANITIZE_STRING);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING);
    $expenses = filter_input(INPUT_POST, 'expenses', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $image = $trip['image'];
    $visibility = $_POST['visibility'] ?? $trip['visibility'] ?? 'private';
    $uploaded_images = [];

    // Handle main image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($_FILES['image']['type'], $allowed_types)) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('trip_', true) . '.' . $ext;
            $destination_path = 'uploads/' . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $destination_path)) {
                $image = $filename;
            } else {
                $errors[] = "Failed to upload main image.";
            }
        } else {
            $errors[] = "Only JPG, PNG, and GIF images are allowed.";
        }
    }

    // Handle additional images upload
    if (isset($_FILES['additional_images']) && is_array($_FILES['additional_images']['name'])) {
        $file_count = count($_FILES['additional_images']['name']);
        
        for ($i = 0; $i < $file_count; $i++) {
            if ($_FILES['additional_images']['error'][$i] === UPLOAD_ERR_OK) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                if (in_array($_FILES['additional_images']['type'][$i], $allowed_types)) {
                    $ext = pathinfo($_FILES['additional_images']['name'][$i], PATHINFO_EXTENSION);
                    $filename = uniqid('trip_', true) . '.' . $ext;
                    $destination_path = 'uploads/' . $filename;
                    if (move_uploaded_file($_FILES['additional_images']['tmp_name'][$i], $destination_path)) {
                        $uploaded_images[] = $filename;
                    } else {
                        $errors[] = "Failed to upload additional image " . ($i + 1) . ".";
                    }
                } else {
                    $errors[] = "Additional image " . ($i + 1) . " is not a valid image type.";
                }
            }
        }
    }

    // Handle main image deletion
    if (isset($_POST['delete_main_image']) && !empty($trip['image'])) {
        $main_image_path = 'uploads/' . $trip['image'];
        if (file_exists($main_image_path)) {
            unlink($main_image_path); // Delete the file
        }
        $image = null; // Set to null so DB will be updated
    }

    // Handle image deletions
    $images_to_delete = $_POST['delete_images'] ?? [];

    // Server-side validation
    $errors = [];
    if (empty($title) || strlen($title) < 3) {
        $errors[] = "Title must be at least 3 characters long.";
    }
    if (empty($start_date) || empty($end_date)) {
        $errors[] = "Start and end dates are required.";
    }
    if (isset($_POST['expenses']) && $_POST['expenses'] !== '' && is_numeric($_POST['expenses']) && floatval($_POST['expenses']) < 0) {
        $errors[] = "Expenses cannot be negative.";
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("UPDATE trips SET title = ?, destination = ?, location = ?, start_date = ?, end_date = ?, notes = ?, expenses = ?, image = ?, visibility = ? WHERE trip_id = ? AND user_id = ?");
            if ($stmt->execute([$title, $destination, $location, $start_date, $end_date, $notes, $expenses, $image, $visibility, $trip_id, $_SESSION['user_id']])) {
                
                // Delete selected images
                if (!empty($images_to_delete)) {
                    $stmt = $pdo->prepare("DELETE FROM trip_images WHERE image_id = ? AND trip_id = ?");
                    foreach ($images_to_delete as $image_id) {
                        $stmt->execute([$image_id, $trip_id]);
                    }
                }
                
                // Insert new additional images
                if (!empty($uploaded_images)) {
                    $stmt = $pdo->prepare("INSERT INTO trip_images (trip_id, image_filename) VALUES (?, ?)");
                    foreach ($uploaded_images as $image_filename) {
                        $stmt->execute([$trip_id, $image_filename]);
                    }
                }
                
                $pdo->commit();
                header("Location: dashboard.php");
                exit;
            } else {
                $pdo->rollBack();
                $errors[] = "Failed to update trip.";
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Failed to update trip: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Trip - Travel Tracker</title>
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
            <div class="bg-white bg-opacity-60 rounded-lg shadow-lg p-8 w-full max-w-2xl my-12">
                <h2 class="text-2xl font-bold mb-6 text-center text-blue-900">Edit Trip</h2>
                <?php if (!empty($errors)): ?>
                    <div class="bg-red-100 text-red-700 p-4 mb-4 rounded">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <form method="POST" class="space-y-4 max-w-lg mx-auto" enctype="multipart/form-data">
                    <div>
                        <label for="title" class="block text-sm font-medium mb-2">Trip Title</label>
                        <input type="text" name="title" id="title" required minlength="3" value="<?php echo htmlspecialchars($trip['title']); ?>" class="w-full p-2 border rounded">
                    </div>
                    <div>
                        <label for="destination" class="block text-sm font-medium mb-2">Destination</label>
                        <input type="text" name="destination" id="destination" value="<?php echo htmlspecialchars($trip['destination']); ?>" class="w-full p-2 border rounded">
                    </div>
                    <div>
                        <label for="location" class="block text-sm font-medium mb-2">Location</label>
                        <input type="text" name="location" id="location" value="<?php echo htmlspecialchars($trip['location']); ?>" class="w-full p-2 border rounded" placeholder="e.g. Paris, France">
                    </div>
                    <div>
                        <label for="start_date" class="block text-sm font-medium mb-2">Start Date</label>
                        <input type="date" name="start_date" id="start_date" required value="<?php echo htmlspecialchars($trip['start_date']); ?>" class="w-full p-2 border rounded">
                    </div>
                    <div>
                        <label for="end_date" class="block text-sm font-medium mb-2">End Date</label>
                        <input type="date" name="end_date" id="end_date" required value="<?php echo htmlspecialchars($trip['end_date']); ?>" class="w-full p-2 border rounded">
                    </div>
                    <div>
                        <label for="notes" class="block text-sm font-medium mb-2">Notes</label>
                        <textarea name="notes" id="notes" class="w-full p-2 border rounded"><?php echo htmlspecialchars($trip['notes']); ?></textarea>
                    </div>
                    <div>
                        <label for="expenses" class="block text-sm font-medium mb-2">Expenses ($)</label>
                        <input type="number" name="expenses" id="expenses" step="0.01" min="0" value="<?php echo htmlspecialchars($trip['expenses']); ?>" class="w-full p-2 border rounded">
                    </div>
                    <div>
                        <label for="visibility" class="block text-sm font-medium mb-2">Visibility</label>
                        <select name="visibility" id="visibility" class="w-full p-2 border rounded">
                            <option value="private" <?php if (($trip['visibility'] ?? 'private') === 'private') echo 'selected'; ?>>Private</option>
                            <option value="public" <?php if (($trip['visibility'] ?? 'private') === 'public') echo 'selected'; ?>>Public</option>
                        </select>
                    </div>
                    <div>
                        <label for="image" class="block text-sm font-medium mb-2">Main Trip Image</label>
                        <?php if (!empty($trip['image'])): ?>
                            <div class="mb-2">
                                <img src="uploads/<?php echo htmlspecialchars($trip['image']); ?>" alt="Trip Image" class="h-32 rounded shadow">
                                <label class="flex items-center mt-2">
                                    <input type="checkbox" name="delete_main_image" value="1" class="mr-2">
                                    <span class="text-sm text-red-600">Delete main image</span>
                                </label>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="image" id="image" accept="image/*" class="w-full p-2 border rounded">
                        <p class="text-xs text-gray-500 mt-1">This will be the primary image shown in the dashboard</p>
                    </div>
                    
                    <?php if (!empty($existing_images)): ?>
                    <div>
                        <label class="block text-sm font-medium mb-2">Current Additional Images</label>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-4">
                            <?php foreach ($existing_images as $img): ?>
                                <div class="relative group">
                                    <img src="uploads/<?php echo htmlspecialchars($img['image_filename']); ?>" 
                                         alt="Trip Image" 
                                         class="w-full h-24 object-cover rounded shadow">
                                    <div class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                        <label class="flex items-center text-white cursor-pointer">
                                            <input type="checkbox" name="delete_images[]" value="<?php echo $img['image_id']; ?>" class="mr-2">
                                            Delete
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div>
                        <label for="additional_images" class="block text-sm font-medium mb-2">Add More Images</label>
                        <input type="file" name="additional_images[]" id="additional_images" accept="image/*" multiple class="w-full p-2 border rounded">
                        <p class="text-xs text-gray-500 mt-1">You can select multiple images. All images will be shown in the trip view.</p>
                    </div>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Update Trip</button>
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