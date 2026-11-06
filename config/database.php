<?php

declare(strict_types=1);

class Database
{
    private static ?PDO $instance = null;
    private static bool $bootstrapped = false;

    private function __construct()
    {
    }

    private static function connection(string $databaseName = ''): PDO
    {
        $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
        $port = $_ENV['DB_PORT'] ?? '3306';
        $user = $_ENV['DB_USER'] ?? 'root';
        $pass = $_ENV['DB_PASS'] ?? '';

        $dsn = $databaseName === ''
            ? sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $host, $port)
            : sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $databaseName);

        try {
            return new PDO(
                $dsn,
                $user,
                $pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $exception) {
            throw new RuntimeException('Database connection failed: ' . $exception->getMessage());
        }
    }

    private static function initializeSchema(): void
    {
        if (self::$bootstrapped) {
            return;
        }

        $pdo = self::$instance;

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                full_name VARCHAR(150) NOT NULL,
                email VARCHAR(150) NOT NULL UNIQUE,
                phone VARCHAR(30) NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                role ENUM("admin", "customer") NOT NULL DEFAULT "customer",
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS destinations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(200) NOT NULL,
                slug VARCHAR(200) NOT NULL UNIQUE,
                location VARCHAR(200) NOT NULL,
                description TEXT NOT NULL,
                featured_image VARCHAR(255) DEFAULT NULL,
                price_per_person DECIMAL(10,2) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS activities (
                id INT AUTO_INCREMENT PRIMARY KEY,
                destination_id INT NOT NULL,
                activity_name VARCHAR(200) NOT NULL,
                extra_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_destination_activity (destination_id, activity_name),
                CONSTRAINT fk_activities_destination FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE
            ) ENGINE=InnoDB'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS bookings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NULL,
                destination_id INT NOT NULL,
                booking_date DATE NOT NULL,
                number_of_guests INT NOT NULL,
                total_price DECIMAL(10,2) NOT NULL,
                payment_method ENUM("mobile_money", "card", "cash") NOT NULL DEFAULT "cash",
                payment_status ENUM("pending", "paid") NOT NULL DEFAULT "pending",
                booking_status ENUM("pending", "confirmed", "cancelled") NOT NULL DEFAULT "pending",
                reference_code VARCHAR(80) NOT NULL UNIQUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_bookings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
                CONSTRAINT fk_bookings_destination FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE,
                CONSTRAINT chk_bookings_guests CHECK (number_of_guests > 0)
            ) ENGINE=InnoDB'
        );
        $bookingColumns = $pdo->query("SHOW COLUMNS FROM bookings LIKE 'payment_method'")->fetch();
        if ($bookingColumns === false) {
            $pdo->exec(
                "ALTER TABLE bookings ADD payment_method ENUM('mobile_money', 'card', 'cash') NOT NULL DEFAULT 'cash' AFTER total_price"
            );
        }

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS booking_activities (
                id INT AUTO_INCREMENT PRIMARY KEY,
                booking_id INT NOT NULL,
                activity_id INT NOT NULL,
                CONSTRAINT fk_booking_activities_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
                CONSTRAINT fk_booking_activities_activity FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE CASCADE,
                UNIQUE KEY uq_booking_activity (booking_id, activity_id)
            ) ENGINE=InnoDB'
        );

        $duplicateActivities = $pdo->query(
            'SELECT destination_id, activity_name
             FROM activities
             GROUP BY destination_id, activity_name
             HAVING COUNT(*) > 1'
        )->fetchAll();

        if ($duplicateActivities !== []) {
            $pdo->exec(
                'DELETE duplicate_activity
                 FROM activities duplicate_activity
                 INNER JOIN activities retained_activity
                    ON retained_activity.destination_id = duplicate_activity.destination_id
                   AND retained_activity.activity_name = duplicate_activity.activity_name
                   AND retained_activity.id < duplicate_activity.id'
            );
        }

        $activityIndex = $pdo->query("SHOW INDEX FROM activities WHERE Key_name = 'uq_destination_activity'")->fetch();
        if ($activityIndex === false) {
            $pdo->exec(
                'ALTER TABLE activities ADD UNIQUE KEY uq_destination_activity (destination_id, activity_name)'
            );
        }

        $adminStmt = $pdo->prepare(
            'INSERT INTO users (full_name, email, phone, password_hash, role)
             VALUES (:full_name, :email, :phone, :password_hash, :role)
             ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), phone = VALUES(phone), role = VALUES(role)'
        );
        $adminStmt->execute([
            ':full_name' => 'System Admin',
            ':email' => 'admin@visitbigogwe.com',
            ':phone' => '+250788000000',
            ':password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
            ':role' => 'admin',
        ]);

        $destinations = [
            [
                'title' => 'Bigogwe Scenic Escape',
                'slug' => 'bigogwe-scenic-escape',
                'location' => 'Bigogwe, Rwanda',
                'description' => 'A refreshing countryside getaway with mountain views, local culture, and guided exploration.',
                'featured_image' => 'https://images.unsplash.com/photo-1501785888041-af3ef285b470',
                'price_per_person' => 42000,
            ],
            [
                'title' => 'Lake Kivu Weekend Retreat',
                'slug' => 'lake-kivu-weekend-retreat',
                'location' => 'Rubavu, Rwanda',
                'description' => 'Enjoy lakeside relaxation, boat rides, and warm community experiences in one of Rwanda\'s most beautiful regions.',
                'featured_image' => 'https://images.unsplash.com/photo-1506744038136-46273834b3fb',
                'price_per_person' => 52000,
            ],
            [
                'title' => 'Hiking & Heritage Tour',
                'slug' => 'hiking-heritage-tour',
                'location' => 'Burera, Rwanda',
                'description' => 'Combine a guided hike with local heritage visits for a memorable highland experience.',
                'featured_image' => 'https://images.unsplash.com/photo-1527631746610-bca00a040d60',
                'price_per_person' => 48000,
            ],
        ];

        $destinationInsert = $pdo->prepare(
            'INSERT INTO destinations (title, slug, location, description, featured_image, price_per_person)
             VALUES (:title, :slug, :location, :description, :featured_image, :price_per_person)
             ON DUPLICATE KEY UPDATE title = VALUES(title), location = VALUES(location), description = VALUES(description), featured_image = VALUES(featured_image), price_per_person = VALUES(price_per_person)'
        );

        foreach ($destinations as $destination) {
            $destinationInsert->execute([
                ':title' => $destination['title'],
                ':slug' => $destination['slug'],
                ':location' => $destination['location'],
                ':description' => $destination['description'],
                ':featured_image' => $destination['featured_image'],
                ':price_per_person' => $destination['price_per_person'],
            ]);
        }

        $activitySeed = [
            ['slug' => 'bigogwe-scenic-escape', 'activity_name' => 'Hiking Adventure', 'extra_cost' => 9000],
            ['slug' => 'bigogwe-scenic-escape', 'activity_name' => 'Camping Experience', 'extra_cost' => 12000],
            ['slug' => 'bigogwe-scenic-escape', 'activity_name' => 'Cultural Cow Tour', 'extra_cost' => 7000],
            ['slug' => 'lake-kivu-weekend-retreat', 'activity_name' => 'Boat Ride', 'extra_cost' => 10000],
            ['slug' => 'lake-kivu-weekend-retreat', 'activity_name' => 'Sunset Picnic', 'extra_cost' => 8000],
            ['slug' => 'hiking-heritage-tour', 'activity_name' => 'Mountain Trek', 'extra_cost' => 11000],
            ['slug' => 'hiking-heritage-tour', 'activity_name' => 'Local Heritage Visit', 'extra_cost' => 6000],
        ];

        $activityInsert = $pdo->prepare(
            'INSERT INTO activities (destination_id, activity_name, extra_cost)
             SELECT d.id, :activity_name, :extra_cost
             FROM destinations d
             WHERE d.slug = :slug
             ON DUPLICATE KEY UPDATE activity_name = VALUES(activity_name), extra_cost = VALUES(extra_cost)'
        );

        foreach ($activitySeed as $activity) {
            $activityInsert->execute([
                ':slug' => $activity['slug'],
                ':activity_name' => $activity['activity_name'],
                ':extra_cost' => $activity['extra_cost'],
            ]);
        }

        self::$bootstrapped = true;
    }

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $dbName = $_ENV['DB_NAME'] ?? 'visit_bigogwe';

            $rootConnection = self::connection();
            $rootConnection->exec(sprintf('CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci', $dbName));

            self::$instance = self::connection($dbName);
            self::initializeSchema();
        }

        return self::$instance;
    }

    private function __clone()
    {
    }
}

function db(): PDO
{
    return Database::getInstance();
}

function appUrl(string $path): string
{
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $basePath = preg_replace('#/public(?:/admin)?/[^/]*$#', '', $scriptName);
    if ($basePath === null || $basePath === $scriptName) {
        $basePath = dirname($scriptName);
    }

    $basePath = str_replace('\\', '/', $basePath);
    if ($basePath === '.' || $basePath === '/') {
        $basePath = '';
    }

    return rtrim($basePath, '/') . '/' . ltrim($path, '/');
}
