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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Travel Tracker</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body class="bg-cover bg-center bg-fixed min-h-screen" style="background-image: url('images/travel8.jpg'); font-family: 'Inter', Arial, sans-serif;">
    <div class="flex flex-col min-h-screen bg-black bg-opacity-60">
        <nav class="bg-transparent text-gray-900 p-4 px-10 shadow-none">
            <div class="container mx-auto flex justify-between items-center">
                <img src="images/logo.png" alt="Travel Tracker Logo" class="h-10 w-auto mr-4">
                <div class="space-x-4">
                    <a href="dashboard.php" class="text-white hover:text-blue-200 transition inline-flex items-center align-middle <?php $currentPage = basename($_SERVER['PHP_SELF']); if($currentPage==='dashboard.php') echo 'underline underline-offset-4'; ?>">
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
        <div class="container mx-auto flex-1 flex flex-col items-center justify-start py-4 px-4 sm:px-8 lg:px-16">
            <h2 class="text-4xl font-extrabold mb-2 text-white text-center">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
            <p class="text-white mb-4 text-center">Track, manage, and relive your journeys with ease.</p>
            <input type="text" id="tripSearch" placeholder="Search by title, destination, or location..." class="mb-12 w-full max-w-lg p-3 rounded-lg border border-blue-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 shadow bg-white bg-opacity-80 placeholder-gray-500">
            <div id="tripList" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 w-full mb-20"></div>
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
        document.addEventListener('DOMContentLoaded', () => {
            let allTrips = [];
            fetch('api/get_trips.php')
                .then(response => response.json())
                .then(data => {
                    allTrips = data;
                    renderTrips(data);
                });

            const tripSearch = document.getElementById('tripSearch');
            tripSearch.addEventListener('input', function() {
                const query = this.value.toLowerCase();
                const filtered = allTrips.filter(trip =>
                    (trip.title && trip.title.toLowerCase().includes(query)) ||
                    (trip.destination && trip.destination.toLowerCase().includes(query)) ||
                    (trip.location && trip.location.toLowerCase().includes(query))
                );
                renderTrips(filtered);
            });

            function renderTrips(trips) {
                // Sort trips by start_date descending
                trips.sort((a, b) => new Date(b.start_date) - new Date(a.start_date));
                const tripList = document.getElementById('tripList');
                tripList.innerHTML = '';
                if (trips.length === 0) {
                    tripList.innerHTML = '<div class="col-span-full text-center text-gray-400 text-lg">No trips found.</div>';
                    return;
                }
                trips.forEach(trip => {
                    const badge = trip.visibility === 'public'
                        ? '<span class="inline-block bg-green-100 text-green-700 text-xs px-2 py-1 rounded-full mr-2">Public</span>'
                        : '<span class="inline-block bg-gray-200 text-gray-700 text-xs px-2 py-1 rounded-full mr-2">Private</span>';
                    const card = `
                        <div class="bg-white bg-opacity-80 rounded-xl shadow-lg flex flex-col h-full transition-transform hover:-translate-y-1 hover:shadow-2xl p-3 sm:p-4 m-1" style="min-width:0;">
                            ${trip.image ? `<img src="uploads/${trip.image}" alt="Trip Image" class="w-full h-28 object-cover rounded-lg mb-2">` : '<div class="w-full h-28 bg-blue-100 flex items-center justify-center rounded-lg text-blue-400 text-3xl mb-2">🧳</div>'}
                            <div class="flex-1 flex flex-col">
                                <div class="flex items-center mb-1">${badge}<h3 class="text-base font-semibold text-blue-800 ml-1 truncate">${trip.title}</h3></div>
                                ${trip.destination ? `<div class='text-gray-600 text-xs mb-1'><span class='font-medium'>Destination:</span> ${trip.destination}</div>` : ''}
                                ${trip.location ? `<div class='text-gray-600 text-xs mb-1'><span class='font-medium'>Location:</span> ${trip.location}</div>` : ''}
                                <div class="text-gray-600 text-xs mb-1"><span class="font-medium">Dates:</span> ${trip.start_date} to ${trip.end_date}</div>
                                ${trip.expenses !== null && trip.expenses !== '' ? `<div class='text-gray-600 text-xs mb-1'><span class='font-medium'>Expenses:</span> $${trip.expenses}</div>` : ''}
                                ${trip.notes ? `<div class='text-gray-600 text-xs mb-2'><span class='font-medium'>Notes:</span> ${trip.notes}</div>` : ''}
                                ${trip.visibility === 'public' ? `<div class='text-xs mb-2 text-blue-700'>🔗 <a href='view_trip.php?id=${trip.trip_id}' target='_blank' class='text-blue-600 underline'>Shareable Link</a></div>` : ''}
                                <div class="mt-auto flex space-x-2 pt-1">
                                    <a href="view_trip.php?id=${trip.trip_id}" title="View" class="flex items-center justify-center bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-full p-1.5 transition">
                                        <svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor'>
                                            <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M15 12a3 3 0 11-6 0 3 3 0 016 0z' />
                                            <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z' />
                                        </svg>
                                    </a>
                                    <a href="edit_trip.php?id=${trip.trip_id}" title="Edit" class="flex items-center justify-center bg-green-100 hover:bg-green-200 text-green-700 rounded-full p-1.5 transition">
                                        <svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor'>
                                            <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M16.862 5.487l1.65 1.65a2.25 2.25 0 010 3.182l-7.5 7.5a2.25 2.25 0 01-1.06.594l-3.19.797a.75.75 0 01-.91-.91l.797-3.19a2.25 2.25 0 01.594-1.06l7.5-7.5a2.25 2.25 0 013.182 0z' />
                                            <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19.5 8.25l-1.5-1.5' />
                                        </svg>
                                    </a>
                                    <button onclick="deleteTrip(${trip.trip_id})" title="Delete" class="flex items-center justify-center bg-red-100 hover:bg-red-200 text-red-700 rounded-full p-1.5 transition">
                                        <svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor'>
                                            <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M6 7.5V6.75A2.25 2.25 0 018.25 4.5h7.5A2.25 2.25 0 0118 6.75v.75M9.75 11.25v4.5m4.5-4.5v4.5M4.5 7.5h15m-1.5 0v9A2.25 2.25 0 0115.75 18.75h-7.5A2.25 2.25 0 016 16.5v-9' />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>`;
                    tripList.innerHTML += card;
                });
            }
        });

        function deleteTrip(tripId) {
            showConfirmationPopup(
                'Are you sure you want to delete this trip?',
                'No',
                'Yes',
                () => {
                    fetch('api/get_trips.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `action=delete&trip_id=${tripId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Failed to delete trip.');
                        }
                    });
                },
                'red'
            );
        }

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