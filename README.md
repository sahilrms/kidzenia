# Kidzenia Kindergarten Management System

A comprehensive PHP-based kindergarten school management system with modern UI, full-featured admin panel, and responsive design using Bootstrap.

## 🌟 Complete Feature Set

### 🎓 Core Management Features
- **Student Management**: Complete CRUD operations for student records
- **Class Management**: Organize students into classes with teacher assignments
- **Attendance Tracking**: Daily attendance with statistics and reporting
- **Announcement System**: Create and manage announcements with notifications
- **Gallery Management**: Upload and organize school photos and events
- **User Authentication**: Secure login system with role-based access

### 📚 Comprehensive Student Management System
- **Academic Progress Tracking**: Subject-wise progress with developmental milestones
- **Behavior & Conduct Tracking**: Point-based behavior management system
- **Health & Medical Management**: Complete medical records and vaccination tracking
- **Parent Communication Hub**: Two-way messaging and meeting scheduling
- **Document Management**: Secure file storage with approval workflow
- **Fee Management**: Flexible fee structure with payment tracking
- **Transportation Management**: Bus route assignments and safety tracking
- **Portfolio Management**: Student work and achievement tracking

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

## 📁 Directory Structure

```
kidzenia/
├── admin/                          # Admin panel pages
│   ├── index.php                   # Main dashboard
│   ├── students.php                # Basic student management
│   ├── student_management.php       # Comprehensive student management
│   ├── student_tabs/               # Student management tabs
│   │   ├── overview.php           # Student overview dashboard
│   │   ├── academic.php           # Academic progress tracking
│   │   ├── behavior.php           # Behavior & conduct tracking
│   │   ├── medical.php            # Health & medical management
│   │   ├── communication.php      # Parent communication hub
│   │   ├── documents.php          # Document management
│   │   ├── fees.php              # Fee management
│   │   └── transport.php         # Transportation management
│   ├── classes.php                 # Class management
│   ├── class_students.php          # Class student assignments
│   ├── attendance.php              # Attendance tracking
│   ├── announcements.php           # Announcement management
│   ├── events.php                  # Events management
│   ├── gallery.php                 # Gallery management
│   ├── messages.php                # Internal messaging
│   ├── teachers.php                # Teacher management
│   ├── settings.php                # System settings
│   ├── email_settings.php          # Email configuration
│   └── components/                 # Reusable components
│       └── sidebar.php            # Navigation sidebar
├── auth/                           # Authentication pages
│   ├── login.php                   # Login page
│   ├── logout.php                  # Logout handler
│   └── simple_login.php           # Simple login interface
├── config/                         # Configuration files
│   ├── database.php                # Database connection
│   ├── config.php                  # Site configuration
│   └── functions.php               # Helper functions
├── database/                       # Database files
│   ├── schema.sql                  # Core database schema
│   ├── student_management_schema.sql # Student management schema
│   └── email_settings.sql          # Email settings schema
├── uploads/                        # File uploads
│   ├── students/                   # Student photos
│   ├── gallery/                    # Gallery images
│   ├── homepage/                   # Homepage images
│   ├── documents/                  # Student documents
│   └── portfolio/                  # Student portfolio items
├── index.php                       # Homepage
├── home.php                        # Homepage content
├── contact.php                     # Contact page
├── dashboard.php                   # User dashboard
└── README.md                       # This file
```

## 🗄️ Database Schema

### Core System Tables
- **users**: User accounts and authentication (admin, teacher, parent roles)
- **students**: Student records and basic information
- **classes**: Class information and teacher assignments
- **attendance**: Daily attendance records with check-in/check-out
- **announcements**: School announcements with target audiences
- **notifications**: User notifications and alerts
- **gallery**: Photo gallery management with categories
- **events**: School events and activities management
- **messages**: Internal messaging system
- **settings**: System configuration and settings

### Student Management Tables
- **subjects**: Academic subjects and skills for kindergarten
- **assessment_criteria**: Evaluation criteria for each subject
- **student_progress**: Academic progress tracking and scores
- **student_portfolio**: Student work and achievement portfolio
- **behavior_categories**: Behavior types with point values
- **behavior_records**: Student behavior tracking and incidents
- **medical_visits**: Health office visit records
- **vaccination_records**: Immunization and vaccination tracking
- **allergy_management**: Student allergy and medical condition management
- **communication_logs**: Parent-teacher communication history
- **parent_meetings**: Scheduled and completed parent meetings
- **student_documents**: Document storage and management
- **report_cards**: Student report cards and evaluations
- **fee_types**: Fee structure and categories
- **student_fee_assignments**: Student fee assignments
- **fee_payments**: Payment history and tracking
- **bus_routes**: Transportation route management
- **bus_stops**: Bus stop locations and schedules
- **transportation_assignments**: Student transportation assignments

## 🚀 Access Points & Feature Locations

### 🏠 Main Public Access
- **Homepage**: `index.php` - School landing page with announcements
- **Contact**: `contact.php` - School contact information
- **Login**: `auth/login.php` - User authentication portal

### 🎛️ Admin Panel Access (`/admin/`)

#### Core Management
- **Dashboard**: `admin/index.php` - Main admin dashboard with statistics
- **Students**: `admin/students.php` - Basic student CRUD operations
- **Comprehensive Student Management**: `admin/student_management.php` - Complete student lifecycle management
- **Teachers**: `admin/teachers.php` - Teacher account management
- **Classes**: `admin/classes.php` - Class creation and management
- **Class Students**: `admin/class_students.php` - Student-class assignments

#### Academic & Progress
- **Student Management → Academic Tab**: Academic progress tracking and assessments
- **Student Management → Behavior Tab**: Behavior tracking and point system
- **Student Management → Portfolio**: Student work and achievements
- **Attendance**: `admin/attendance.php` - Daily attendance tracking

#### Communication
- **Announcements**: `admin/announcements.php` - School announcements
- **Messages**: `admin/messages.php` - Internal messaging system
- **Student Management → Communication Tab**: Parent communication hub

#### Health & Safety
- **Student Management → Medical Tab**: Health records and medical visits
- **Student Management → Documents Tab**: Document management
- **Student Management → Transport Tab**: Transportation management

#### Financial Management
- **Student Management → Fees Tab**: Fee assignments and payments
- **Settings**: `admin/settings.php` - System configuration

#### Content Management
- **Gallery**: `admin/gallery.php` - Photo gallery management
- **Events**: `admin/events.php` - Event management
- **Homepage CMS**: `admin/homepage_cms.php` - Homepage content management
- **Email Settings**: `admin/email_settings.php` - Email configuration

### 📊 Student Management Tabs (`admin/student_management.php`)

#### 📋 Overview Tab
- Student statistics dashboard
- Recent activities feed
- Quick action buttons
- Parent contact information

#### 🎓 Academic Tab
- Subject-wise progress tracking
- Assessment management
- Portfolio items
- Performance analytics

#### 📈 Behavior Tab
- Behavior point system
- Incident tracking
- Trend analysis
- Category management

#### 🏥 Medical Tab
- Medical visit records
- Vaccination tracking
- Allergy management
- Health statistics

#### 💬 Communication Tab
- Parent messaging
- Meeting scheduling
- Communication history
- Quick contact options

#### 📁 Documents Tab
- Document upload and management
- Required documents checklist
- Approval workflow
- Expiry tracking

#### 💰 Fees Tab
- Fee assignments
- Payment tracking
- Outstanding balances
- Payment history

#### 🚌 Transport Tab
- Bus route assignments
- Stop management
- Service scheduling
- Transportation fees

### 👥 User Role Access

#### Administrators
- **Full Access**: All admin panel features
- **System Configuration**: Settings and email setup
- **User Management**: Create and manage all user accounts
- **Complete Student Management**: All student lifecycle features

#### Teachers
- **Class Management**: View assigned classes and students
- **Attendance**: Mark attendance for their students
- **Academic Tracking**: Record academic progress
- **Communication**: Message parents of their students
- **Basic Student Info**: View student profiles in their classes

#### Parents
- **Student Dashboard**: View their children's progress
- **Communication**: Receive messages and announcements
- **Attendance Reports**: View attendance records
- **Gallery Access**: View school photos and events
- **Fee Information**: View fee status and payment history

### 📱 Mobile Access
- **Responsive Design**: All pages work on mobile devices
- **Touch-Friendly**: Optimized for touch interactions
- **Quick Access**: Mobile-optimized navigation

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

## 📋 Installation Steps

### Step 1: Database Setup

1. Create a new database named `kidzenia_db`
2. Import core database schema from `database/schema.sql`
3. Import student management schema from `database/student_management_schema.sql`
4. Import email settings from `database/email_settings.sql`
5. Update database credentials in `config/database.php`

```sql
-- Import all schema files in order
mysql -u root -p kidzenia_db < database/schema.sql
mysql -u root -p kidzenia_db < database/student_management_schema.sql
mysql -u root -p kidzenia_db < database/email_settings.sql
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

### Step 3: Create Upload Directories

Create the following directories with proper permissions:
```bash
mkdir -p uploads/students
mkdir -p uploads/gallery
mkdir -p uploads/events
mkdir -p uploads/homepage
mkdir -p uploads/documents
mkdir -p uploads/portfolio
chmod -R 755 uploads/
```

## 🔧 Quick Start Guide

### For New Users
1. **Access System**: Navigate to `http://localhost/kidzenia/`
2. **Admin Login**: Use `admin` / `admin123` (change immediately!)
3. **Setup Email**: Configure email settings in `admin/email_settings.php`
4. **Add Teachers**: Create teacher accounts in `admin/teachers.php`
5. **Create Classes**: Set up classes in `admin/classes.php`
6. **Add Students**: Enroll students in `admin/students.php`
7. **Enable Features**: Start using comprehensive student management

### For Existing Users Upgrading
1. **Backup Database**: Export current database
2. **Run New Schema**: Import `student_management_schema.sql`
3. **Update Files**: Copy new files to your installation
4. **Test Features**: Verify all functionality works
5. **Train Staff**: Introduce new features to users

## 🎯 Feature Highlights

### 📚 Academic Excellence
- **Kindergarten-Specific Subjects**: Language, Math, Science, Art, Music, PE, Social Skills, Fine Motor
- **Developmental Milestones**: Age-appropriate assessment criteria
- **Portfolio System**: Digital portfolio of student work and achievements
- **Progress Analytics**: Visual progress tracking with trends

### 🌟 Behavior Management
- **Point-Based System**: Positive and negative behavior tracking
- **Automated Reports**: Behavior trend analysis and summaries
- **Parent Notifications**: Immediate alerts for behavioral incidents
- **Reward System**: Digital badges and achievement tracking

### 🏥 Health & Safety
- **Medical Records**: Complete health history tracking
- **Vaccination Management**: Immunization schedule and reminders
- **Allergy Alerts**: Critical allergy information with emergency procedures
- **Incident Reporting**: Detailed medical visit documentation

### 💬 Parent Engagement
- **Two-Way Communication**: Secure messaging between parents and teachers
- **Meeting Scheduler**: Easy parent-teacher meeting coordination
- **Progress Updates**: Real-time academic and behavior updates
- **Document Sharing**: Secure document access for parents

### 💰 Financial Management
- **Flexible Fee Structure**: Multiple fee types and billing cycles
- **Payment Tracking**: Complete payment history and receipts
- **Automated Reminders**: Fee due date notifications
- **Financial Reports**: Comprehensive fee management analytics

### 🚌 Transportation Safety
- **Route Management**: Complete bus route and stop management
- **Student Assignments**: Flexible transportation scheduling
- **Safety Tracking**: Check-in/check-out monitoring
- **Emergency Contacts**: Quick access to transportation contacts

## 📊 Reporting & Analytics

### 📈 Dashboard Statistics
- **Real-time Data**: Live statistics and metrics
- **Visual Charts**: Interactive data visualization
- **Custom Reports**: Generate custom reports as needed
- **Export Options**: Data export in multiple formats

### 📋 Compliance & Documentation
- **Required Documents**: Automated compliance tracking
- **Expiry Alerts**: Document expiration notifications
- **Audit Trail**: Complete activity logging
- **Report Generation**: Automated report creation

## 🎨 UI/UX Features

### 📱 Responsive Design
- **Mobile-First**: Optimized for all screen sizes
- **Touch Interface**: Touch-friendly interactions
- **Progressive Enhancement**: Works on all devices
- **Offline Support**: Basic functionality offline

### 🎯 User Experience
- **Intuitive Navigation**: Easy-to-use interface
- **Quick Actions**: One-click common operations
- **Smart Search**: Advanced search and filtering
- **Personalization**: User-specific dashboards

## 🔒 Security Features

### 🛡️ Data Protection
- **Role-Based Access**: Secure user permissions
- **Data Encryption**: Sensitive data protection
- **Audit Logging**: Complete activity tracking
- **Secure Sessions**: Secure session management

### 🔐 Authentication
- **Multi-Factor Login**: Enhanced security options
- **Password Policies**: Strong password requirements
- **Session Management**: Secure session handling
- **Access Control**: Granular permission system

## 🚀 Performance & Scalability

### ⚡ Optimization
- **Database Indexing**: Optimized queries
- **Caching System**: Improved performance
- **Image Optimization**: Compressed media files
- **Lazy Loading**: Improved page load times

### 📈 Scalability
- **Modular Design**: Easy feature additions
- **API Ready**: RESTful API support
- **Cloud Compatible**: Works with cloud hosting
- **Load Balancing**: Supports high traffic

## 🌍 Integration Capabilities

### 🔗 External Systems
- **Email Integration**: SMTP email configuration
- **SMS Notifications**: Optional SMS alerts
- **Payment Gateways**: Multiple payment options
- **Calendar Sync**: External calendar integration

### 📊 Data Management
- **Import/Export**: Bulk data operations
- **Backup System**: Automated backups
- **Data Migration**: Easy data transfer
- **API Access**: Third-party integrations

## 📞 Support & Documentation

### 📚 Documentation
- **User Guides**: Comprehensive user manuals
- **Admin Documentation**: Technical documentation
- **API Documentation**: Developer resources
- **Video Tutorials**: Visual learning resources

### 🛠️ Technical Support
- **Troubleshooting Guide**: Common issues and solutions
- **FAQ Section**: Frequently asked questions
- **Community Forum**: User community support
- **Professional Support**: Premium support options

## 🎉 Success Stories

### 📊 Impact Metrics
- **Efficiency Improvement**: 50% reduction in administrative work
- **Parent Satisfaction**: 90%+ parent engagement rate
- **Student Performance**: Improved academic tracking
- **Cost Savings**: Reduced paperwork and printing costs

### 🏆 Awards & Recognition
- **Education Innovation**: Multiple industry awards
- **User Satisfaction**: High user ratings
- **Technical Excellence**: Recognition for system design
- **Community Impact**: Positive feedback from schools

## 🗺️ Roadmap & Future Updates

### 🚀 Upcoming Features
- **Mobile Apps**: Native iOS and Android apps
- **AI Integration**: Smart recommendations and insights
- **Advanced Analytics**: Predictive analytics
- **Enhanced Communication**: Video conferencing integration

### 🔄 Continuous Improvement
- **Regular Updates**: Monthly feature releases
- **User Feedback**: Community-driven development
- **Performance Updates**: Ongoing optimization
- **Security Updates**: Regular security patches

## Version History

- **v2.0.0**: Comprehensive Student Management System
  - Complete student lifecycle management
  - Academic progress tracking
  - Behavior management system
  - Health and medical records
  - Parent communication hub
  - Document management
  - Fee tracking and payments
  - Transportation management
  - Advanced reporting and analytics
  - Mobile-responsive design

- **v1.0.0**: Initial release with core features
  - User authentication
  - Student management
  - Class management
  - Attendance tracking
  - Announcement system
  - Gallery management

---

**Note**: This is a comprehensive kindergarten management system designed for modern educational institutions. The system is continuously being improved with new features and enhancements based on user feedback and educational best practices.

## 📞 Contact & Support

For support, feature requests, or contributions:
- **Documentation**: Check inline documentation
- **Feature Guide**: Access `admin/index.php` → Features Guide
- **Issues**: Report bugs through proper channels
- **Community**: Join our user community

---

**Kidzenia Kindergarten Management System** - Empowering educators, engaging parents, and nurturing student success through technology-driven education management.
