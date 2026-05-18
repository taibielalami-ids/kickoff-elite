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

If the folder name is not kickoff, use the folder name in the URL.
Example: if the folder is C:\wamp64\www\kickoff-elite-main, open http://localhost/kickoff-elite-main/

## Default Admin Account
Username: admin
Password: Admin12345

## User Account
You can register a new user from the Register page.

## Login Codes
Email sending is disabled by default so the project works without Gmail setup.
When a login or verification code is needed, the site shows a fallback code message on the page.


## Optional Email And Map Setup
The project works without email or Mapbox credentials.

If you want real email delivery or Mapbox maps, create this local file:
config/config.local.php

Put only your private local settings there.
This file is ignored by GitHub, so your credentials stay on your PC.

For Mapbox, add your Mapbox public token under app then mapbox_token.

For real email delivery, add your SMTP settings under mail then smtp.
For Gmail, use a Gmail App Password, not your normal Gmail password.

Do not put real credentials in GitHub commits.

Example content for config.local.php:

```php
<?php
return [
    'app' => [
        'mapbox_token' => 'your_mapbox_token_here',
    ],
    'mail' => [
        'from_address' => 'your_email@gmail.com',
        'smtp' => [
            'host' => 'smtp.gmail.com',
            'username' => 'your_email@gmail.com',
            'password' => 'your_gmail_app_password',
        ],
    ],
];
```
## If The Database Does Not Connect
Open config/config.php and check these values:
- database name: football_simple
- username: root
- password: empty by default

If your WAMP MySQL has a password, put it in config/config.php.

## If You See Not Found
Check the folder name inside C:\wamp64\www\.
The URL must match that folder name.

Correct example:
Folder: C:\wamp64\www\kickoff
URL: http://localhost/kickoff/

Wrong example:
Folder: C:\wamp64\www\kickoff-elite-main
URL: http://localhost/kickoff/

## Important
The app detects the folder name automatically. Usually you do not need to edit app.base_path.





