<?php

declare(strict_types=1);

require_once __DIR__ . '/../app.php';

use PHPUnit\Framework\TestCase;

final class BookingTest extends TestCase
{
    protected function setUp(): void
    {
        global $BOOKINGS;
        $BOOKINGS = [];
    }

    public function testCreateBookingSuccess(): void
    {
        $futureDate = (new DateTimeImmutable('+2 days'))->format('Y-m-d');

        $booking = createBooking('Alice', 'garden-tour', $futureDate, '10:30');

        $this->assertSame('Alice', $booking['client_name']);
        $this->assertSame('Sunrise Garden Tour', $booking['place_name']);
        $this->assertSame($futureDate, $booking['visit_date']);
    }

    public function testCreateBookingRejectsDuplicateSlot(): void
    {
        $futureDate = (new DateTimeImmutable('+2 days'))->format('Y-m-d');
        createBooking('Alice', 'garden-tour', $futureDate, '10:30');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('already booked');

        createBooking('Bob', 'garden-tour', $futureDate, '10:30');
    }

    public function testCreateBookingRejectsPastDate(): void
    {
        $pastDate = (new DateTimeImmutable('-1 day'))->format('Y-m-d');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('past');

        createBooking('Carol', 'garden-tour', $pastDate, '09:00');
    }

    public function testIndexPageRenders(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SESSION = [];

        ob_start();
        renderIndex();
        $output = ob_get_clean();

        $this->assertNotFalse($output);
        $this->assertStringContainsString('Reserve a place to visit', $output);
    }
}
