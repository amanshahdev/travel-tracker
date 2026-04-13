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
$stmt = $pdo->prepare("SELECT * FROM trips WHERE trip_id = ?");
$stmt->execute([$trip_id]);
$trip = $stmt->fetch();

if (!$trip) {
    header("Location: dashboard.php");
    exit;
}

// Get additional images for this trip
$stmt = $pdo->prepare("SELECT image_filename FROM trip_images WHERE trip_id = ? ORDER BY upload_date ASC");
$stmt->execute([$trip_id]);
$additional_images = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Restrict private trips to owner only
if ($trip['visibility'] === 'private' && (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $trip['user_id'])) {
    echo '<div class="flex items-center justify-center min-h-screen"><div class="bg-white p-8 rounded shadow text-center"><h2 class="text-2xl font-bold mb-4">This trip is private.</h2><p>You do not have permission to view this trip.</p></div></div>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Trip - Travel Tracker</title>
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
            <div class="bg-white bg-opacity-60 rounded-lg shadow-lg p-8 w-full max-w-2xl">
                <h2 class="text-2xl font-bold mb-6 text-center text-blue-900"><?php echo htmlspecialchars($trip['title']); ?></h2>
                <div class="bg-white p-6 rounded-lg shadow-lg bg-opacity-90">
                    <!-- Main Image -->
                    <?php if (!empty($trip['image'])): ?>
                        <div class="mb-4">
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">Preview
                                
                            
                            
                            Image</h3>
                            <img src="uploads/<?php echo htmlspecialchars($trip['image']); ?>" alt="Trip Image" class="w-full h-48 object-cover rounded shadow">
                        </div>
                    <?php endif; ?>
                    
                    <!-- Additional Images -->
                    <?php if (!empty($additional_images)): ?>
                        <div class="mb-4">
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">Additional Images</h3>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                <?php foreach ($additional_images as $image_filename): ?>
                                    <div class="relative group">
                                        <img src="uploads/<?php echo htmlspecialchars($image_filename); ?>" 
                                             alt="Trip Image" 
                                             class="w-full h-32 object-cover rounded shadow cursor-pointer transition-transform hover:scale-105"
                                             onclick="openImageModal('uploads/<?php echo htmlspecialchars($image_filename); ?>')">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <p><strong>Destination:</strong> <?php echo htmlspecialchars($trip['destination']); ?></p>
                    <p><strong>Location:</strong> <?php echo htmlspecialchars($trip['location']); ?></p>
                    <p><strong>Start Date:</strong> <?php echo htmlspecialchars($trip['start_date']); ?></p>
                    <p><strong>End Date:</strong> <?php echo htmlspecialchars($trip['end_date']); ?></p>
                    <p><strong>Expenses:</strong> $<?php echo htmlspecialchars($trip['expenses']); ?></p>
                    <p><strong>Notes:</strong> <?php echo htmlspecialchars($trip['notes']); ?></p>
                    <p><strong>Visibility:</strong> <?php echo htmlspecialchars($trip['visibility']); ?></p>
                    <div class="mt-4">
                        <a href="edit_trip.php?id=<?php echo $trip['trip_id']; ?>" class="text-green-600 mr-2">Edit</a>
                        <a href="dashboard.php" class="text-blue-600">Back to Dashboard</a>
                    </div>
                </div>
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

    <!-- Image Modal -->
    <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 hidden">
        <div class="relative max-w-4xl max-h-full mx-4">
            <img id="modalImage" src="" alt="Full size image" class="max-w-full max-h-full object-contain rounded-lg">
            <button onclick="closeImageModal()" class="absolute top-4 right-4 text-white bg-black bg-opacity-50 hover:bg-opacity-75 rounded-full p-2 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
    
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

        function openImageModal(imageSrc) {
            const modal = document.getElementById('imageModal');
            const modalImage = document.getElementById('modalImage');
            modalImage.src = imageSrc;
            modal.classList.remove('hidden');
        }

        function closeImageModal() {
            const modal = document.getElementById('imageModal');
            modal.classList.add('hidden');
        }

        // Close image modal when clicking outside
        document.getElementById('imageModal').onclick = (e) => {
            if (e.target.id === 'imageModal') {
                closeImageModal();
            }
        };
    </script>
</body>
</html>