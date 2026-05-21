# AtelierWeb - Training Workshop Registration System

A complete CRUD web application for managing training workshop registrations built with PHP, JavaScript, Bootstrap 5, and MySQL.

## Features

### For Visitors/Users
- **Landing Page**: Beautiful hero section with call-to-action
- **Workshop Catalog**: View all available workshops with details
- **Registration Form**: Complete form with validation
- **User Authentication**: Login system with different roles

### For Administrators
- **Admin Dashboard**: View all registrations in a searchable table
- **CRUD Operations**: Edit and delete registrations
- **Real-time Search**: JavaScript filtering of registration data
- **Role-based Access**: Admin-only access to management features

## Technologies Used

- **Frontend**: HTML5, Bootstrap 5, Vanilla JavaScript
- **Backend**: PHP (Procedural)
- **Database**: MySQL
- **Styling**: Custom CSS with Bootstrap enhancements

## Project Structure

```
atelier_web/
├── index.php              # Home page (workshops list)
├── landing.php            # Landing page (public)
├── login.php              # Login page
├── logout.php             # Logout handler
├── inscription.php        # Registration form
├── traitement_inscription.php # Form processing
├── admin.php              # Admin dashboard
├── modifier.php           # Edit registration
├── supprimer.php          # Delete registration
├── test_db.php            # Database test script
├── config/
│   └── db.php             # Database configuration
├── assets/
│   ├── css/
│   │   └── style.css      # Custom styles
│   └── js/
│       └── script.js      # Client-side validation + search
└── sql/
    └── init.sql           # Database initialization
```

## Installation

1. **Clone or place the project** in your web server directory:
   ```bash
   cd /Applications/MAMP/htdocs/projects/
   ```

2. **Initialize the database**:
   ```bash
   /Applications/MAMP/Library/bin/mysql80/bin/mysql -u root -proot -P 8889 < atelier_web/sql/init.sql
   ```

3. **Configure database** (if needed):
   Edit `config/db.php` with your MySQL credentials:
   ```php
   $db_host = 'localhost';
   $db_user = 'root';
   $db_pass = 'root';
   $db_name = 'atelier_web_db';
   $db_port = 8889;
   ```

4. **Access the application**:
   Open your browser and navigate to:
   ```
   http://localhost:8888/projects/atelier_web/landing.php
   ```

## Default Login Credentials

- **Admin Account**:
  - Email: `admin@atelier.com`
  - Password: `admin123`
  - Role: Administrator (full access)

- **User Account**:
  - Email: `user@atelier.com`
  - Password: `user123`
  - Role: Normal user (registration only)

## Database Schema

### Users Table
- `id`, `email`, `password`, `role`, `first_name`, `last_name`, `created_at`

### Workshops Table
- `id`, `title`, `date_atelier`, `duration`, `max_places`, `reserved`, `description`

### Registrations Table
- `id`, `first_name`, `last_name`, `email`, `phone`, `workshop_id`, `level`, `mode`, `comment`, `created_at`

## Features in Detail

### Form Validation
- **Client-side**: JavaScript validation before submission
- **Server-side**: PHP validation with error handling
- **Required fields**: First name, last name, email, phone, workshop, level, mode
- **Email format**: Valid email address validation
- **Phone validation**: Digits only
- **Comment validation**: Minimum 10 characters if filled

### Security Features
- SQL injection prevention with `mysqli_real_escape_string()`
- Session-based authentication
- Role-based access control
- Password hashing with `password_hash()` and `password_verify()`

### User Experience
- Responsive Bootstrap 5 design
- Mobile-friendly interface
- Real-time search filtering
- Success/error messages with session storage
- Confirmation dialogs for delete actions

## Usage Flow

1. **Landing Page** → Public welcome page with login options
2. **Login** → Authenticate as user or admin
3. **User Dashboard** → View workshops and register
4. **Admin Dashboard** → Manage all registrations
5. **Registration Form** → Complete workshop registration
6. **Admin Actions** → Edit/delete registrations as needed

## Browser Support

- Chrome (recommended)
- Firefox
- Safari
- Edge

## Development Notes

- Built with procedural PHP for learning purposes
- Uses MySQLi for database operations
- Bootstrap 5 for responsive design
- Vanilla JavaScript for client-side functionality
- Session-based flash messages for user feedback

## License

This project is created for educational purposes as part of a training workshop.
