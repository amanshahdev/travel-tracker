-- Create trip_images table for storing multiple images per trip
CREATE TABLE IF NOT EXISTS trip_images (
    image_id INT AUTO_INCREMENT PRIMARY KEY,
    trip_id INT NOT NULL,
    image_filename VARCHAR(255) NOT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES trips(trip_id) ON DELETE CASCADE
);

-- Add index for better performance
CREATE INDEX idx_trip_images_trip_id ON trip_images(trip_id);

-- Keep the existing image column in trips table for backward compatibility
-- The main image will be stored in trips.image, additional images in trip_images table 