<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

class BookingController
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = db();
    }

    public function process(array $input, ?int $loggedInUserId = null): array
    {
        if (!isset($input['csrf_token']) || empty($_SESSION['csrf_token'] ?? '')) {
            throw new InvalidArgumentException('Invalid security token. Please refresh the page and try again.');
        }

        if (!hash_equals($_SESSION['csrf_token'], (string) $input['csrf_token'])) {
            throw new InvalidArgumentException('Security token mismatch. Please try again.');
        }

        $fullName = trim((string) ($input['full_name'] ?? ''));
        $email = strtolower(trim((string) ($input['email'] ?? '')));
        $phone = trim((string) ($input['phone'] ?? ''));
        $destinationId = (int) ($input['destination_id'] ?? 0);
        $bookingDate = trim((string) ($input['booking_date'] ?? ''));
        $guestCount = (int) ($input['number_of_guests'] ?? 0);
        $paymentMethod = trim((string) ($input['payment_method'] ?? ''));
        $activityIds = isset($input['activities']) && is_array($input['activities'])
            ? array_values(array_filter(array_map('intval', $input['activities'])))
            : [];

        if ($fullName === '' || $email === '' || $phone === '') {
            throw new InvalidArgumentException('Please provide your full name, email, and phone number.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Please enter a valid email address.');
        }

        if ($destinationId <= 0) {
            throw new InvalidArgumentException('Please select a destination.');
        }

        if ($guestCount <= 0) {
            throw new InvalidArgumentException('Number of guests must be at least 1.');
        }

        if ($bookingDate === '') {
            throw new InvalidArgumentException('Please select a booking date.');
        }

        $bookingDateObj = DateTimeImmutable::createFromFormat('Y-m-d', $bookingDate);
        if ($bookingDateObj === false || $bookingDateObj->format('Y-m-d') !== $bookingDate) {
            throw new InvalidArgumentException('Please provide a valid booking date.');
        }

        if ($bookingDateObj < new DateTimeImmutable('today')) {
            throw new InvalidArgumentException('Booking date cannot be in the past.');
        }

        if (!in_array($paymentMethod, ['mobile_money', 'card', 'cash'], true)) {
            throw new InvalidArgumentException('Please select a payment method.');
        }

        $destination = $this->getDestinationById($destinationId);
        if ($destination === null) {
            throw new InvalidArgumentException('Selected destination is not available.');
        }

        $selectedActivities = $this->getSelectedActivities($destinationId, $activityIds);
        if (count($selectedActivities) !== count(array_unique($activityIds))) {
            throw new InvalidArgumentException('One or more selected activities are not available for this destination.');
        }
        $baseTotal = (float) $destination['price_per_person'] * $guestCount;
        $activityTotal = 0.0;

        foreach ($selectedActivities as $activity) {
            $activityTotal += (float) $activity['extra_cost'];
        }

        $totalPrice = round($baseTotal + $activityTotal, 2);
        $userId = $loggedInUserId ?? $this->ensureCustomerUser($fullName, $email, $phone);

        $referenceCode = $this->generateReferenceCode();

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO bookings (user_id, destination_id, booking_date, number_of_guests, total_price, payment_method, payment_status, booking_status, reference_code)
                 VALUES (:user_id, :destination_id, :booking_date, :number_of_guests, :total_price, :payment_method, :payment_status, :booking_status, :reference_code)'
            );

            $stmt->execute([
                ':user_id' => $userId,
                ':destination_id' => $destinationId,
                ':booking_date' => $bookingDate,
                ':number_of_guests' => $guestCount,
                ':total_price' => $totalPrice,
                ':payment_method' => $paymentMethod,
                ':payment_status' => 'pending',
                ':booking_status' => 'pending',
                ':reference_code' => $referenceCode,
            ]);

            $bookingId = (int) $this->pdo->lastInsertId();

            if ($selectedActivities !== []) {
                $activityInsert = $this->pdo->prepare(
                    'INSERT INTO booking_activities (booking_id, activity_id) VALUES (:booking_id, :activity_id)'
                );

                foreach ($selectedActivities as $activity) {
                    $activityInsert->execute([
                        ':booking_id' => $bookingId,
                        ':activity_id' => (int) $activity['id'],
                    ]);
                }
            }

            $this->pdo->commit();
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }

        return [
            'booking_id' => $bookingId,
            'reference_code' => $referenceCode,
            'destination' => $destination['title'],
            'total_price' => $totalPrice,
            'payment_method' => $paymentMethod,
            'booking_date' => $bookingDate,
            'number_of_guests' => $guestCount,
        ];
    }

    private function getDestinationById(int $destinationId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM destinations WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $destinationId]);
        $result = $stmt->fetch();

        return $result !== false ? $result : null;
    }

    private function getSelectedActivities(int $destinationId, array $activityIds): array
    {
        if ($activityIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($activityIds), '?'));
        $query = sprintf(
            'SELECT id, activity_name, extra_cost FROM activities WHERE destination_id = ? AND id IN (%s)',
            $placeholders
        );

        $params = [$destinationId];
        foreach ($activityIds as $activityId) {
            $params[] = $activityId;
        }

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function ensureCustomerUser(string $fullName, string $email, string $phone): int
    {
        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user !== false) {
            $userId = (int) $user['id'];
            $updateStmt = $this->pdo->prepare('UPDATE users SET full_name = :full_name, phone = :phone WHERE id = :id');
            $updateStmt->execute([
                ':full_name' => $fullName,
                ':phone' => $phone,
                ':id' => $userId,
            ]);
            return $userId;
        }

        $passwordHash = password_hash('guest-' . bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
        $insertStmt = $this->pdo->prepare(
            'INSERT INTO users (full_name, email, phone, password_hash, role) VALUES (:full_name, :email, :phone, :password_hash, :role)'
        );
        $insertStmt->execute([
            ':full_name' => $fullName,
            ':email' => $email,
            ':phone' => $phone,
            ':password_hash' => $passwordHash,
            ':role' => 'customer',
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    private function generateReferenceCode(): string
    {
        $rand = strtoupper(bin2hex(random_bytes(4)));
        $date = date('Ymd');
        return 'VBG-' . $date . '-' . $rand;
    }
}
