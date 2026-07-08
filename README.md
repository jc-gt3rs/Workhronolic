# Workhronolic XAMPP Setup

## 1. Start XAMPP

Open XAMPP Manager and start:

- Apache Web Server
- MySQL Database

## 2. Import the database

1. Open `http://localhost/phpmyadmin`.
2. Go to the Import tab.
3. Choose `database/schema.sql` from this repo.
4. Click Import.

The script creates the `workhronolic` database, all tables, and seed data.

## 3. Open the app

When this repo is linked or copied into XAMPP's `htdocs` folder, open:

`http://localhost/Workhronolic/`

## 4. Seed accounts

All seeded accounts use this password:

`password`

Available accounts:

- Owner: `dathan@startup.io`
- Manager: `mia@startup.io`
- Employee: `jc@startup.io`
- Pending employee: `paolo@startup.io`

Seed company join code:

`AA-7K2M9Q`

## 5. Database settings

The app uses XAMPP defaults:

- Host: `127.0.0.1`
- User: `root`
- Password: empty
- Database: `workhronolic`

To override them, set these environment variables before Apache starts:

- `WORKHRONOLIC_DB_HOST`
- `WORKHRONOLIC_DB_USER`
- `WORKHRONOLIC_DB_PASS`
- `WORKHRONOLIC_DB_NAME`
- `WORKHRONOLIC_TIMEZONE` (defaults to `Asia/Manila`)
