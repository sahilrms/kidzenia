# Kidzenia Kindergarten Management System

A comprehensive PHP-based kindergarten school management system with modern UI and responsive design.

## Features

### Core Management
- **Student Management**: Complete CRUD operations with comprehensive student lifecycle management
- **Class Management**: Organize students into classes with teacher assignments
- **Attendance Tracking**: Daily attendance with statistics and reporting
- **Announcement System**: Create and manage announcements with notifications
- **Gallery Management**: Upload and organize school photos and events
- **User Authentication**: Secure login system with role-based access (Admin, Teacher, Parent)

### Comprehensive Student Management
- **Academic Progress**: Subject-wise progress tracking and assessments
- **Behavior Management**: Point-based behavior tracking system
- **Health & Medical**: Medical records, vaccination tracking, and allergy management
- **Parent Communication**: Two-way messaging and meeting scheduling
- **Document Management**: Secure file storage with approval workflow
- **Fee Management**: Flexible fee structure with payment tracking
- **Transportation**: Bus route assignments and safety tracking
- **Portfolio**: Student work and achievement tracking

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL/MariaDB
- Apache/Nginx web server
- XAMPP (for local development)

### Quick Setup

1. **Database Setup**
   ```bash
   mysql -u root -p kidzenia_db < database/schema.sql
   mysql -u root -p kidzenia_db < database/student_management_schema.sql
   mysql -u root -p kidzenia_db < database/email_settings.sql
   ```

2. **Configuration**
   - Update database credentials in `config/database.php`
   - Set site URL in `config/config.php`
   - Configure email settings

3. **Create Upload Directories**
   ```bash
   mkdir -p uploads/students uploads/gallery uploads/homepage uploads/documents uploads/portfolio
   chmod -R 755 uploads/
   ```

4. **Access System**
   - Navigate to `http://localhost/kidzenia/`
   - Login with: `admin` / `admin123` (change immediately!)

## Access Points

### Public Access
- **Homepage**: `index.php` - School landing page
- **Contact**: `contact.php` - School contact information
- **Login**: `auth/login.php` - User authentication

### Admin Panel (`/admin/`)
- **Dashboard**: Main admin dashboard with statistics
- **Students**: Basic and comprehensive student management
- **Teachers**: Teacher account management
- **Classes**: Class creation and management
- **Attendance**: Daily attendance tracking
- **Announcements**: School announcements
- **Gallery**: Photo gallery management
- **Events**: Event management
- **Settings**: System configuration

### Student Management Features
Access comprehensive student management through `admin/student_management.php` with tabs:
- **Overview**: Student statistics and recent activities
- **Academic**: Progress tracking and assessments
- **Behavior**: Behavior tracking and point system
- **Medical**: Health records and medical visits
- **Communication**: Parent messaging and meetings
- **Documents**: Document upload and management
- **Fees**: Fee assignments and payments
- **Transport**: Bus route assignments

## Default Credentials

- **Username**: `admin`
- **Password**: `admin123`

⚠️ **Important**: Change the default password after first login!

## Security Notes

- Change default password immediately
- Ensure proper file permissions on sensitive directories
- All database queries use prepared statements for SQL injection protection
- Input sanitization and output encoding for XSS protection

## Support

For troubleshooting and common issues, check the inline documentation accessible through the admin panel's "Features Guide" link.

---

**Kidzenia Kindergarten Management System** - Empowering educators, engaging parents, and nurturing student success through technology-driven education management.
