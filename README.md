# KickOff Elite

KickOff Elite is a PHP MVC website for 5v5 football pitch reservations.

## What You Need
- WAMP installed

## How To Download
1. Click the green Code button on GitHub.
2. Click Download ZIP.
3. Extract the ZIP.
4. Rename the extracted folder to kickoff.
5. Put the kickoff folder inside C:\wamp64\www\.

Final folder path must be:
C:\wamp64\www\kickoff

## Database Setup
1. Open phpMyAdmin.
2. Create a database named football_simple.
3. Import this file:
database/schema.sql

## Run The Site
Start WAMP, then open this link in the browser:
http://localhost/kickoff/

## Default Admin Account
Username: admin
Password: Admin12345

## User Account
You can register a new user from the Register page.

## Login Codes
Email sending is disabled by default so the project works without Gmail setup.
When a login or verification code is needed, the site shows a fallback code message on the page.

## If The Database Does Not Connect
Open config/config.php and check these values:
- database name: football_simple
- username: root
- password: empty by default

If your WAMP MySQL has a password, put it in config/config.php.

## Important
Do not change app.base_path unless you also change the folder name or URL.
For the normal setup, the folder name must be kickoff and app.base_path must stay /kickoff.

