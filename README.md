# Saw Room Booking

This project is a room booking application built with Laravel.

## Requirements

- PHP >= 8.1
- Composer
- Node.js and npm
- MySQL or PostgreSQL

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/your-username/saw-room-booking.git
   cd saw-room-booking
   ```
2. Install PHP dependencies:
   ```bash
   composer install
   ```
3. Install Node.js dependencies:
   ```bash
   npm install
   ```
4. Copy the environment file and set your variables:
   ```bash
   cp .env.example .env
   # Edit .env with your database and service credentials
   ```
5. Generate the application key:
   ```bash
   php artisan key:generate
   ```
6. Run migrations and seeders:
   ```bash
   php artisan migrate --seed
   ```
7. Build frontend assets:
   ```bash
   npm run build
   ```
8. Start the development server:
   ```bash
   php artisan serve
   ```

## Useful Commands

- Run tests:
  ```bash
  php artisan test
  ```
- Build assets in development mode:
  ```bash
  npm run dev
  ```

## Notes
- Do not commit your `.env` file to GitHub.
- The `storage/` and `vendor/` folders should not be committed either.
- If you use public files in `storage/app/public`, run:
  ```bash
  php artisan storage:link
  ```

## Contributing
Contributions are welcome! Open an issue or pull request for suggestions or improvements.
