# Troubleshooting Guide: Data Loading Issues

If you are experiencing issues with data not loading from the database in your Web-based Electric Management System, please follow these steps to diagnose and resolve the problem.

## Common Causes and Solutions

### 1. Database Not Created or Incorrect Name

**Problem:** The PHP application cannot connect to the database because it either doesn't exist or the name in `config.php` does not match the actual database name.

**Solution:**
   - **Verify Database Existence:** Log in to your MySQL server (e.g., using phpMyAdmin, MySQL Workbench, or the command line) and confirm that a database named `electric_management_system` exists.
   - **Create Database (if missing):** If the database does not exist, create it. You can use the following SQL command:
     ```sql
     CREATE DATABASE electric_management_system;
     ```
   - **Check `config.php`:** Ensure that the `DB_NAME` constant in `backend/php/config.php` exactly matches your database name:
     ```php
     define(\'DB_NAME\', \'electric_management_system\');
     ```

### 2. Database Schema Not Imported

**Problem:** The database exists, but the tables (users, consumers, bills, etc.) are missing, meaning the `schema.sql` file was not imported.

**Solution:**
   - **Import `schema.sql`:** Navigate to the `electric_management_system` directory in your terminal and import the `schema.sql` file into your database. Replace `your_username` with your MySQL username.
     ```bash
     mysql -u your_username -p electric_management_system < database/schema.sql
     ```
     You will be prompted to enter your MySQL password.
   - **Verify Tables:** After importing, check your database to ensure all tables (e.g., `users`, `categories`, `consumers`, `readings`, `bills`, `payments`) are present.

### 3. Incorrect Database Credentials

**Problem:** The username or password specified in `config.php` does not match your MySQL server credentials.

**Solution:**
   - **Update `config.php`:** Open `backend/php/config.php` and update `DB_USER` and `DB_PASS` with your correct MySQL username and password.
     ```php
     define(\'DB_USER\', \'your_mysql_username\');
     define(\'DB_PASS\', \'your_mysql_password\');
     ```
     *Note: If you are using XAMPP/WAMP on a local machine, `DB_USER` is often `root` and `DB_PASS` is often empty (`''`).*

### 4. MySQL Server Not Running

**Problem:** The MySQL database server is not active, preventing any connection from the PHP application.

**Solution:**
   - **Start MySQL Server:** Ensure your MySQL server is running. If you are using XAMPP/WAMP, start the MySQL service from its control panel. For other setups, use the appropriate command (e.g., `sudo systemctl start mysql` on Linux).

### 5. No Sample Data Loaded

**Problem:** The database tables are created, and the connection is successful, but no data appears in the application.

**Solution:**
   - **Check `schema.sql` for Sample Data:** The `schema.sql` file includes sample data for `categories`, `users`, and `consumers`. Ensure these `INSERT` statements were executed during the schema import.
   - **Manually Insert Data:** If needed, you can manually insert data into the tables using SQL commands or a database management tool.

## Verification Steps

After attempting the solutions above, you can verify the database connection and data loading:

1.  **Access `config.php` directly:** Temporarily add `echo "Database connected successfully!";` after the `$conn->set_charset("utf8");` line in `backend/php/config.php`. Then, try to access `config.php` directly in your browser (e.g., `http://localhost/electric_management_system/backend/php/config.php`). If you see the success message, your connection is working.
2.  **Check PHP error logs:** If the page is blank or shows a generic error, check your web server's (Apache/Nginx) and PHP error logs for more detailed information. These logs are crucial for debugging.
3.  **Test API Endpoints:** After ensuring the database connection, try accessing the API endpoints directly in your browser (e.g., `http://localhost/electric_management_system/backend/php/consumers.php?action=get_all`). You should see JSON output if data is being retrieved correctly.

By systematically going through these steps, you should be able to identify and resolve the issue preventing data from loading in your system. Remember to remove any temporary `echo` statements from `config.php` once debugging is complete.
