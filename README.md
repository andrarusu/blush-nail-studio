
# Blush & Co. Nail Studio

A responsive nail salon website with a booking request form and a staff administration area, built as a web development portfolio project.

I created this project to practise connecting a customer-facing website to a PHP and MySQL backend.

**Blush & Co. is a fictional salon created for demonstration purposes.** The example reviews and customer details are not real.

## Features

### Customer website

- Responsive design for desktop and mobile
- Nail treatment and service information
- Booking request form
- Booking requests saved to a MySQL database
- Confirmation after a successful submission
- Clearly labelled example reviews

### Staff area

- Staff login
- Protected administration page
- View booking requests stored in MySQL
- Log out securely from the staff area

## Technologies used

- HTML5
- CSS3
- JavaScript
- PHP
- MySQL
- XAMPP for local development

## Running the project locally

This project was developed and tested locally using XAMPP.

1. Install XAMPP and start **Apache** and **MySQL**.
2. Place the project folder in `C:\xampp\htdocs\blush-nail-studio`.
3. Create and configure the local MySQL database.
4. Add a private `config.local.php` file with the database connection details.
5. Open `http://localhost/blush-nail-studio/index.html` in your browser.

**Note:** The database setup is not yet included in this repository, so the PHP features are not ready for one-click installation on another computer. Open the website through a PHP-capable server, not VS Code Live Server.

## Security and demo information

Database credentials are kept in `config.local.php`, which is excluded from Git.

This repository contains the source code, not a publicly hosted PHP/MySQL application. Use fictional customer information when testing the booking form.

## Project status

The booking form, local database connection, staff login and administration page have been tested locally.

I plan to add screenshots and improve the setup instructions as I continue developing the project.
