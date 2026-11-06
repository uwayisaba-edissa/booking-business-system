# Visit Bigogwe Booking System

A PHP and MySQL booking system for Rwanda tours. Customers can browse destinations, select optional activities, make a reservation, view a receipt, and find previous bookings. Administrators can manage destinations and booking/payment status.

## GitHub setup

This project uses environment variables for database configuration. Do not commit passwords, `.env` files, or local database dumps.

Copy the database defaults into your local environment as needed:

```text
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=visit_bigogwe
DB_USER=root
DB_PASS=
```

## Run with XAMPP

1. Start **Apache** and **MySQL** in the XAMPP Control Panel.
2. Keep this folder at `C:\xampp\htdocs\booking-business-system`.
3. Open [http://localhost/booking-business-system/](http://localhost/booking-business-system/) in a browser.

Always include `http://localhost` (or `http://127.0.0.1:8000` when using the PHP server) in the address. For example, `public/my-bookings.php` by itself is not a valid browser address and can cause `DNS_PROBE_FINISHED_NXDOMAIN`.

The application creates the `visit_bigogwe` database and its tables automatically using the local MySQL `root` account with an empty password. To initialize manually, import `schema.sql` in phpMyAdmin first.

For a different environment, set `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASS` before starting PHP.

### Run without Apache

If Apache is unavailable, start the PHP server from PowerShell:

```powershell
cd C:\xampp\htdocs\booking-business-system
C:\xampp\php\php.exe -S 127.0.0.1:8000 -t .
```

Then open [http://127.0.0.1:8000/](http://127.0.0.1:8000/). MySQL must still be running.

## Default admin

- Email: `admin@visitbigogwe.com`
- Password: `admin123`

Change the seeded password before using the system in production.

## Main routes

- `/` or `/index.php` — public destination listing
- `/public/booking.php` — booking form
- `/public/my-bookings.php` — find bookings by email and phone
- `/public/payment.php` — local demo RWF payment confirmation
- `/public/admin/login.php` — administrator login
- `/schema.sql` — repeatable database schema and sample data

## Validation

From PowerShell with XAMPP installed:

```powershell
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { & 'C:\xampp\php\php.exe' -l $_.FullName }
```
