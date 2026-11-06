CREATE DATABASE IF NOT EXISTS visit_bigogwe CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE visit_bigogwe;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(30) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'customer') NOT NULL DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS destinations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL UNIQUE,
    location VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    featured_image VARCHAR(255) DEFAULT NULL,
    price_per_person DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    destination_id INT NOT NULL,
    activity_name VARCHAR(200) NOT NULL,
    extra_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_destination_activity (destination_id, activity_name),
    CONSTRAINT fk_activities_destination
        FOREIGN KEY (destination_id) REFERENCES destinations(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    destination_id INT NOT NULL,
    booking_date DATE NOT NULL,
    number_of_guests INT NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    payment_method ENUM('mobile_money', 'card', 'cash') NOT NULL,
    payment_status ENUM('pending', 'paid') NOT NULL DEFAULT 'pending',
    booking_status ENUM('pending', 'confirmed', 'cancelled') NOT NULL DEFAULT 'pending',
    reference_code VARCHAR(80) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_bookings_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_bookings_destination
        FOREIGN KEY (destination_id) REFERENCES destinations(id)
        ON DELETE CASCADE,
    CONSTRAINT chk_bookings_guests CHECK (number_of_guests > 0)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS booking_activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    activity_id INT NOT NULL,
    CONSTRAINT fk_booking_activities_booking
        FOREIGN KEY (booking_id) REFERENCES bookings(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_booking_activities_activity
        FOREIGN KEY (activity_id) REFERENCES activities(id)
        ON DELETE CASCADE,
    UNIQUE KEY uq_booking_activity (booking_id, activity_id)
) ENGINE=InnoDB;

CREATE INDEX idx_destinations_slug ON destinations(slug);
CREATE INDEX idx_bookings_user ON bookings(user_id);
CREATE INDEX idx_bookings_destination ON bookings(destination_id);
CREATE INDEX idx_bookings_date ON bookings(booking_date);
CREATE INDEX idx_booking_activities_activity ON booking_activities(activity_id);

INSERT INTO users (full_name, email, phone, password_hash, role)
VALUES (
    'System Admin',
    'admin@visitbigogwe.com',
    '+250788000000',
    '$2y$10$ALckeQ0Pk6GamFHA/875leW4M75dYgyI/gOL9PcnmrJNWeNb0XZ0G',
    'admin'
)
ON DUPLICATE KEY UPDATE
    full_name = VALUES(full_name),
    phone = VALUES(phone),
    password_hash = VALUES(password_hash),
    role = VALUES(role);

INSERT INTO destinations (title, slug, location, description, featured_image, price_per_person)
VALUES
    ('Bigogwe Scenic Escape', 'bigogwe-scenic-escape', 'Bigogwe, Rwanda', 'A refreshing countryside getaway with mountain views, local culture, and guided exploration.', 'https://images.unsplash.com/photo-1501785888041-af3ef285b470', 42000),
    ('Lake Kivu Weekend Retreat', 'lake-kivu-weekend-retreat', 'Rubavu, Rwanda', 'Enjoy lakeside relaxation, boat rides, and warm community experiences in one of Rwanda\'s most beautiful regions.', 'https://images.unsplash.com/photo-1506744038136-46273834b3fb', 52000),
    ('Hiking & Heritage Tour', 'hiking-heritage-tour', 'Burera, Rwanda', 'Combine a guided hike with local heritage visits for a memorable highland experience.', 'https://images.unsplash.com/photo-1527631746610-bca00a040d60', 48000)
ON DUPLICATE KEY UPDATE
    location = VALUES(location),
    description = VALUES(description),
    featured_image = VALUES(featured_image),
    price_per_person = VALUES(price_per_person);

INSERT INTO activities (destination_id, activity_name, extra_cost)
SELECT id, 'Hiking Adventure', 9000 FROM destinations WHERE slug = 'bigogwe-scenic-escape'
UNION ALL
SELECT id, 'Camping Experience', 12000 FROM destinations WHERE slug = 'bigogwe-scenic-escape'
UNION ALL
SELECT id, 'Cultural Cow Tour', 7000 FROM destinations WHERE slug = 'bigogwe-scenic-escape'
UNION ALL
SELECT id, 'Boat Ride', 10000 FROM destinations WHERE slug = 'lake-kivu-weekend-retreat'
UNION ALL
SELECT id, 'Sunset Picnic', 8000 FROM destinations WHERE slug = 'lake-kivu-weekend-retreat'
UNION ALL
SELECT id, 'Mountain Trek', 11000 FROM destinations WHERE slug = 'hiking-heritage-tour'
UNION ALL
SELECT id, 'Local Heritage Visit', 6000 FROM destinations WHERE slug = 'hiking-heritage-tour'
ON DUPLICATE KEY UPDATE
    activity_name = VALUES(activity_name),
    extra_cost = VALUES(extra_cost);
