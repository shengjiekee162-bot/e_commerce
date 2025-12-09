# E-Commerce Platform

A full-featured e-commerce platform built with PHP and MySQL.

## Features

### User Management
- User registration and login
- Role-based access control (Admin, Buyer, Seller)
- Password reset functionality
- Remember me feature
- Session management

### Admin Panel
- Product management (Add, Edit, Delete)
- Category management (Add, Edit, Delete)
- User management
- Dashboard

### Products
- Product listing with images
- Category filtering
- Search functionality
- Stock management
- Price management

## Installation

1. Clone the repository
2. Import the database schema
3. Configure database connection in `config/db.php`
4. Set up XAMPP or similar PHP/MySQL environment
5. Access via `http://localhost/e_commerce/`

## Database Configuration

Update `config/db.php` with your database credentials:
```php
$localhost = 'localhost';
$server = 'root';
$password = 'your_password';
$database = 'ecommerce_db';
```

## Default Admin Credentials

- Email: admin@ecommerce.local
- Password: admin123

## Technologies Used

- PHP 7.4+
- MySQL
- Bootstrap 5
- JavaScript

## Project Structure

```
e_commerce/
├── admin/              # Admin panel files
├── config/             # Configuration files
├── css/                # Stylesheets
├── includes/           # Reusable components
├── uploads/            # Uploaded files
└── *.php              # Main application files
```

## License

MIT License
