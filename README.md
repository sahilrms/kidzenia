# Kidzenia Kindergarten Management System

A comprehensive PHP-based kindergarten school management system with modern UI, full-featured admin panel, and responsive design using Bootstrap.

## Features

### 🎓 Core Features
- **Student Management**: Complete CRUD operations for student records
- **Class Management**: Organize students into classes with teacher assignments
- **Attendance Tracking**: Daily attendance with statistics and reporting
- **Announcement System**: Create and manage announcements with notifications
- **Gallery Management**: Upload and organize school photos and events
- **User Authentication**: Secure login system with role-based access

### 📱 Modern UI/UX
- **Responsive Design**: Works perfectly on desktop, tablet, and mobile
- **Bootstrap 5**: Modern styling framework
- **Interactive Dashboard**: Real-time statistics and activity feeds
- **Beautiful Animations**: Smooth transitions and hover effects

### 🔔 Notification System
- **Real-time Notifications**: Instant alerts for important updates
- **Targeted Messaging**: Send notifications to specific user groups
- **What's New Section**: Latest announcements and updates

### 👥 User Roles
- **Administrator**: Full system access and management
- **Teacher**: Class management and student monitoring
- **Parent**: View child's progress and school updates

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL/MariaDB
- Apache/Nginx web server
- XAMPP (for local development)

### Step 1: Database Setup

1. Create a new database named `kidzenia_db`
2. Import the database schema from `database/schema.sql`
3. Update database credentials in `config/database.php`

```sql
-- Import the schema file
mysql -u root -p kidzenia_db < database/schema.sql
```

### Step 2: Configuration

1. Update site configuration in `config/config.php`:
   - Set your site URL
   - Configure email settings
   - Adjust session settings

2. Ensure proper file permissions:
   ```bash
   chmod -R 755 uploads/
   chmod -R 755 config/
   ```

### Step 3: Web Server Setup

#### Apache Configuration
Ensure `mod_rewrite` is enabled and create `.htaccess`:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [L]
```

#### XAMPP Setup
1. Place the project in `htdocs/kidzenia/`
2. Start Apache and MySQL services
3. Access via `http://localhost/kidzenia/`

### Step 4: Create Upload Directories

Create the following directories with proper permissions:
```bash
mkdir -p uploads/students
mkdir -p uploads/gallery
mkdir -p uploads/events
chmod -R 755 uploads/
```

## Default Login Credentials

- **Username**: `admin`
- **Password**: `admin123`

⚠️ **Important**: Change the default password after first login!

## Directory Structure

```
kidzenia/
├── admin/                  # Admin panel pages
│   ├── index.php          # Dashboard
│   ├── students.php       # Student management
│   ├── classes.php        # Class management
│   ├── attendance.php     # Attendance tracking
│   ├── announcements.php  # Announcement management
│   └── gallery.php        # Gallery management
├── auth/                  # Authentication pages
│   ├── login.php          # Login page
│   └── logout.php         # Logout handler
├── config/                # Configuration files
│   ├── database.php       # Database connection
│   ├── config.php         # Site configuration
│   └── functions.php      # Helper functions
├── database/              # Database files
│   └── schema.sql         # Database schema
├── uploads/               # File uploads
│   ├── students/          # Student photos
│   └── gallery/           # Gallery images
├── index.php              # Homepage
└── README.md              # This file
```

## Database Schema

The system uses the following main tables:

- **users**: User accounts and authentication
- **students**: Student records and information
- **classes**: Class information and assignments
- **attendance**: Daily attendance records
- **announcements**: School announcements
- **notifications**: User notifications
- **gallery**: Photo gallery management
- **events**: School events and activities

## Usage Guide

### For Administrators

1. **Login**: Access the admin panel at `/admin/`
2. **Dashboard**: View statistics and recent activities
3. **Student Management**: Add, edit, and manage student records
4. **Class Management**: Create classes and assign teachers
5. **Attendance**: Mark daily attendance and view reports
6. **Announcements**: Create and publish school announcements
7. **Gallery**: Upload and manage school photos

### For Teachers

1. **Login**: Use assigned credentials
2. **View Classes**: See assigned classes and students
3. **Mark Attendance**: Record daily attendance for students
4. **View Announcements**: Stay updated with school news

### For Parents

1. **Login**: Use provided credentials
2. **View Child's Progress**: Monitor attendance and activities
3. **Receive Notifications**: Get updates about school events
4. **View Gallery**: See photos of school activities

## Customization

### Changing Colors and Theme

Edit the CSS variables in the `<style>` section of each page:

```css
:root {
    --primary-color: #667eea;
    --secondary-color: #764ba2;
    --accent-color: #f093fb;
}
```

### Adding New Features

1. Create new PHP files in the appropriate directory
2. Update the navigation menu
3. Add database tables if needed
4. Update user permissions

### Email Configuration

Update email settings in `config/config.php`:

```php
define('SMTP_HOST', 'your-smtp-host');
define('SMTP_USER', 'your-email');
define('SMTP_PASS', 'your-password');
```

## Security Considerations

1. **Change Default Password**: Always change the default admin password
2. **File Permissions**: Ensure proper file permissions on sensitive directories
3. **SQL Injection**: All queries use prepared statements
4. **XSS Protection**: Input sanitization and output encoding
5. **Session Security**: Secure session configuration

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials in `config/database.php`
   - Ensure MySQL service is running
   - Verify database exists and schema is imported

2. **File Upload Issues**
   - Check upload directory permissions
   - Ensure PHP file upload limits are sufficient
   - Verify file size restrictions

3. **Login Issues**
   - Check session configuration
   - Verify database user records
   - Clear browser cookies and cache

4. **Page Not Found (404)**
   - Check Apache mod_rewrite is enabled
   - Verify .htaccess file exists
   - Check file permissions

### Error Reporting

Enable error reporting for debugging:

```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## Support and Maintenance

### Regular Maintenance Tasks

1. **Database Backups**: Regularly backup the database
2. **File Cleanup**: Remove old uploads and temporary files
3. **Security Updates**: Keep PHP and server software updated
4. **Log Monitoring**: Check error logs regularly

### Performance Optimization

1. **Database Indexing**: Ensure proper indexes on frequently queried columns
2. **Image Optimization**: Compress uploaded images
3. **Caching**: Implement caching for frequently accessed data
4. **CDN**: Use CDN for static assets in production

## License

This project is open-source and available under the MIT License.

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## Version History

- **v1.0.0**: Initial release with core features
  - User authentication
  - Student management
  - Class management
  - Attendance tracking
  - Announcement system
  - Gallery management

---

**Note**: This is a comprehensive kindergarten management system designed for modern educational institutions. The system is continuously being improved with new features and enhancements.
# kidzenia
