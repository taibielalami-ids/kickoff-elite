# KickOff Elite (WAMP Setup)

## 1) Requirements
- WAMP (Apache + MySQL running)
- PHP 8+

## 2) Project Location
- Put this full folder in: `C:\wamp64\www\kickoff`

## 3) WAMP Alias
Create/edit: `C:\wamp64\alias\kickoff.conf`

```apache
Alias /kickoff "C:/wamp64/www/kickoff/public"
<Directory "C:/wamp64/www/kickoff/public/">
    Options +Indexes +FollowSymLinks +MultiViews
    AllowOverride All
    Require local
</Directory>
```

Then restart WAMP.

## 4) Database
In phpMyAdmin:
1. Create DB: `football_simple`
2. Import only:
   - `database/schema.sql`

## 5) Open the Site
- `http://localhost/kickoff/`

## 6) Default Admin
- Username: `admin`
- Password: `Admin12345`

## 7) Config Setup
- Copy config/config.example.php to config/config.php.
- Put your local database, Mapbox, and SMTP values in config/config.php.
- Do not upload real passwords or API secrets to GitHub.

## 8) Important Notes
- Main config file: `config/config.php`
- `base_path` must stay `/kickoff` for this setup.
- SMTP is empty by default. Login OTP still works using fallback code shown in app flash messages.

