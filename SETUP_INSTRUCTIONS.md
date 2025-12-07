# MySQL Database Setup Instructions

## Prerequisites
- XAMPP installed and running
- Apache and MySQL services started in XAMPP Control Panel

## Step-by-Step Setup

### 1. Create the Database
1. Open your web browser and go to: `http://localhost/phpmyadmin`
2. Click on "SQL" tab at the top
3. Open the file `database_schema.sql` in a text editor
4. Copy all the SQL code
5. Paste it into the SQL query box in phpMyAdmin
6. Click "Go" button to execute

**Alternative Method:**
- In phpMyAdmin, click "Import" tab
- Click "Choose File" and select `database_schema.sql`
- Click "Go" button

### 2. Verify Database Creation
1. In phpMyAdmin left sidebar, you should see `attendance_system` database
2. Click on it to expand
3. You should see 4 tables:
   - `sections`
   - `students`
   - `attendance_sessions`
   - `attendance_records`

### 3. Configure Database Connection (Optional)
If you're using different MySQL credentials:
1. Open `db_config.php`
2. Modify these lines:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');  // Add password if needed
   define('DB_NAME', 'attendance_system');
   ```

### 4. Test the Application
1. Make sure Apache and MySQL are running in XAMPP
2. Open your browser and navigate to:
   `http://localhost/Attendance%20System/index.html`
3. The application should load
4. Try creating a new class to test if database is working

### 5. Troubleshooting

**"Failed to load data from database"**
- Check if MySQL is running in XAMPP Control Panel
- Verify database was created correctly in phpMyAdmin
- Check browser console (F12) for error messages

**"Database connection failed"**
- Verify MySQL service is running
- Check `db_config.php` credentials
- Ensure `attendance_system` database exists

**"API Error"**
- Check browser console for specific error
- Verify `api.php` and `db_config.php` are in the same folder as `index.html`
- Make sure PHP is enabled in XAMPP

### 6. Data Migration (If Needed)
If you have existing data in localStorage:
1. Open the old version of the site
2. Use browser console (F12) to export data:
   ```javascript
   console.log(localStorage.getItem('attendance_app_data'))
   ```
3. Copy the output
4. You'll need to manually recreate classes and students in the new database version

## Files Overview
- `database_schema.sql` - Database structure
- `db_config.php` - Database connection settings
- `api.php` - Backend API endpoints
- `script.js` - Frontend (updated to use API)
- `index.html` - Main application
- `style.css` - Styling

## Note
The old localStorage-based data will not automatically migrate. The application now uses MySQL for all data storage.
