# Travel Tracker

A simple PHP and MySQL web application for logging and managing personal trips. Users can register, log in, create trips, upload trip photos, and control whether a trip is private or shareable.

## What This App Does

- User authentication (register, login, logout)
- Personal dashboard with search and trip cards
- Add, edit, view, and delete trips
- Upload one main image and multiple additional trip images
- Trip visibility controls (`private` or `public`)
- Basic account settings update (username, email, and password)

## Tech Stack

- Backend: PHP (PDO)
- Database: MySQL / MariaDB
- Frontend: HTML, Tailwind CSS (CDN), JavaScript
- Assets: Local CSS, JS, images, uploads directory

## Project Structure

```text
travel_tracker/
|- index.php             # Landing page
|- login.php             # Login page
|- register.php          # Registration page
|- dashboard.php         # Main app dashboard
|- add_trip.php          # Create a trip
|- edit_trip.php         # Update a trip
|- view_trip.php         # View trip details
|- settings.php          # Account settings
|- logout.php            # Logout endpoint
|- database_update.sql   # SQL update for additional trip images table
|- api/
|  |- get_trips.php      # Fetch/delete trips via AJAX
|- config/
|  |- db_connect.php     # PDO connection setup
|  |- Database.php       # OOP DB helper class
|  |- Trip.php           # Trip model/helper
|  |- User.php           # User model/helper
|- css/
|  |- style.css
|- js/
|  |- script.js
|- images/
|- uploads/
```

## Prerequisites

- PHP 8.0+
- MySQL 5.7+ (or MariaDB equivalent)
- A local web server stack (XAMPP, WAMP, Laragon, or Apache/Nginx + PHP)

## How To Run Locally

1. Clone or copy this project into your local web root.
2. Create a MySQL database, for example:
   - Database name: `travel_tracker_db`
3. Create required base tables:
   - `users`
   - `trips`
4. Apply the included SQL update script:
   - Run `database_update.sql` to create the `trip_images` table and index.
5. Open `config/db_connect.php` and set your local database values.
6. Make sure the `uploads/` folder exists and is writable by the web server.
7. Start Apache/Nginx and MySQL.
8. Open the app in your browser:
   - `http://localhost/travel_tracker/`

## Database Notes

The app expects these main tables:

- `users` (stores account info and hashed passwords)
- `trips` (stores trip details and main image)
- `trip_images` (stores additional images per trip)

`database_update.sql` creates only `trip_images`. If your project is fresh, create `users` and `trips` first, then apply the update script.

## Configuration (Do Not Commit Secrets)

In `config/db_connect.php`, use your local values only:

```php
$host = 'localhost';
$dbname = 'travel_tracker_db';
$username = 'your_db_username';
$password = 'your_db_password';
```

Do not commit real production credentials to GitHub.

## Security and Privacy Notes

- Passwords are hashed using PHP `password_hash`/`password_verify`.
- Inputs are validated/sanitized in forms.
- Prepared statements are used for SQL queries.
- Private trips are restricted to owners in `view_trip.php`.

## Known Limitations

- This repository currently includes an update SQL script, not a full initial schema migration set.
- Tailwind is loaded from CDN (internet connection needed unless replaced with local build).
- Some paths (like uploads) depend on web server write permissions.

## Suggested Improvements

- Add a full `schema.sql` for first-time setup.
- Move DB credentials to environment variables.
- Add CSRF protection for forms.
- Add automated tests and linting.

## License

No license file is currently included. Add one (for example, MIT) before public distribution if needed.
