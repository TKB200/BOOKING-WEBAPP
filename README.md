# BOOKING-WEBAPP
System Setup Instructions

1. Start XAMPP
   - Open XAMPP Control Panel.
   - Start "Apache" and "MySQL".

2. Database Setup
   - Open your browser and go to: http://localhost/phpmyadmin
   - Click "Import" tab.
   - Choose the file "c:\xampp\htdocs\Meeting Booking\database.sql".
   - Click "Go" at the bottom to import the database structure.

3. Create Admin User
   - Open your browser and go to: http://localhost/Meeting Booking/setup_admin.php
   - You should see "Admin user created successfully" or "Admin user already exists".

4. Use the System
   - Go to: http://localhost/Meeting Booking/
   - Login with Admin credentials:
     - Email: Admin@booking
     - Password: Admin@123
   - Or Register a new user to book meetings.

Files:
- index.php: Login page.
- register.php: Registration page.
- dashboard.php: User dashboard (Book meetings).
- admin_dashboard.php: Admin dashboard (Approve meetings).
- style.css: Custom styling.
- images/: Contains images.


