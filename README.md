# KickOff Elite (WAMP Setup)

## 1) Requirements
- WAMP with Apache and MySQL running
- PHP 8 or newer

## 2) Project Location
- Put the full project folder inside C:\wamp64\www\kickoff

## 3) Open the Site
- Start WAMP.
- Open http://localhost/kickoff/
- The project is already prepared to route requests to the correct public entry file.

## 4) Database
In phpMyAdmin:
1. Create a database named football_simple.
2. Import database/schema.sql.

## 5) Default Admin
- Username: admin
- Password: Admin12345

## 6) Config Setup
- Copy config/config.example.php to config/config.php.
- Put your local database, Mapbox, and SMTP values in config/config.php.
- Do not upload real passwords or API secrets to GitHub.

## 7) Important Notes
- Main config file: config/config.php
- base_path must stay /kickoff for this setup.
- Login OTP can use email if SMTP is configured.
