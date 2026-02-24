# BookHub – Online Book Management System

## Overview
BookHub is a PHP + MySQL web application for browsing and managing books online. It includes user features like book browsing, cart and checkout, wishlist, and reviews, along with an admin panel for managing the catalog and orders.

## Features
### User
- Browse books with search and filters (genre, published year range, condition)
- Book details page (description, related books, reviews/ratings)
- Wishlist (add/remove)
- Shopping cart (add/update/remove items)
- Out-of-stock cart handling (remove option + checkout prevention)
- Checkout and order placement

### Admin
- Admin dashboard
- Manage books (add/edit/delete)
- Manage orders

## Tech Stack
- **Backend:** PHP (XAMPP)
- **Database:** MySQL (phpMyAdmin)
- **Server:** Apache (XAMPP)
- **Frontend:** HTML, CSS, JavaScript, Font Awesome

## Project Structure
- `admin/` – Admin panel pages, partials, and assets
- `assets/` – Images, icons, fonts, and static assets
- `backups/` – Database backups (`.sql`)
- `frontend/` – Home and landing UI sections
- `includes/` – Shared PHP utilities (DB connection, helper functions, navbar/header)
- `order_cart_process/` – Cart, checkout, and order processing
- `page/` – User-facing pages (book list, book details, etc.)

## Setup (Local Development with XAMPP)
### 1) Requirements
- XAMPP (Apache + MySQL)

### 2) Place the Project in `htdocs`
Copy the project folder into your XAMPP web root, e.g.
- `C:\xampp\htdocs\BCA_IV_Project`

### 3) Create Database and Import SQL
1. Start **Apache** and **MySQL** from the XAMPP control panel.
2. Open phpMyAdmin:
   - `http://localhost/phpmyadmin`
3. Create a database (example):
   - `bookhub`
4. Import the SQL file:
   - Use the latest file inside `backups/` (example: `backups/backup_bookhub_*.sql`)

### 4) Configure Database Credentials
Update your DB connection in:
- `includes/db.php`

Make sure these match your local MySQL credentials:
- Host (usually `localhost`)
- Username (often `root`)
- Password (often empty on local XAMPP)
- Database name (e.g. `bookhub`)

### 5) Run the Application
Open in browser:
- `http://localhost/BCA_IV_Project/`

## Usage
### Book Browsing
- Browse all books: `page/booklist.php`
- Book details: `page/book.php?id=BOOK_ID`
- Clicking **genre** or **published year** on the book details page opens the filtered book list.

### Cart & Checkout
- Cart: `order_cart_process/cart.php`
- Checkout: `order_cart_process/checkout.php`

## Notes
- If you see errors related to missing tables, re-check that the correct `.sql` file was imported.
- For image paths, ensure the `assets/` folder structure is intact.

## Screenshots (Optional)
Add screenshots here for documentation/academic submission:
- Home page
- Book list + filters
- Book details
- Cart
- Checkout
- Admin dashboard

## License
Educational / academic project.
