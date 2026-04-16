# Web-based Electric Management, Billing, and Collection System

This project is a fully functional, mobile-responsive web application for managing electricity consumers, meter readings, billing, and payment collection. Built with HTML, CSS, JavaScript, and PHP, it features a modern, adaptive design that works seamlessly on desktops, tablets, and smartphones.

## Project Structure

```
electric_management_system/
├── backend/
│   └── php/              # PHP backend files (config, auth, APIs)
├── frontend/
│   ├── css/              # CSS stylesheets
│   ├── html/             # HTML pages
│   └── js/               # JavaScript files
├── database/
│   └── schema.sql        # SQL database schema
└── README.md             # Project documentation
```

## Database Setup

1.  **Create Database:** Create a new MySQL database named `electric_management_system`.
2.  **Import Schema:** Import the `schema.sql` file into the newly created database. This will create all the necessary tables.

    ```bash
    mysql -u your_username -p electric_management_system < database/schema.sql
    ```

    Replace `your_username` with your MySQL username. You will be prompted to enter your password.

## Getting Started (Visual Studio Code)

1.  **Clone/Download:** Obtain the project files and open the `electric_management_system` folder in Visual Studio Code.
2.  **Web Server:** Set up a local web server (e.g., Apache, Nginx) with PHP support. Configure the web server to point to the `electric_management_system` directory.
3.  **Database Configuration:** (To be added in backend development phase) Update the database connection details in the PHP configuration file.

## Modules and Features

-   User Authentication & Authorization
-   Consumer Management (CRUD)
-   Meter Management (Readings, Consumption)
-   Billing System (Generation, Viewing)
-   Collection & Payment (Recording, History)
-   Dashboard & Reports
