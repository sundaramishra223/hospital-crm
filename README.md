# Hospital & Clinic Management CRM System

## 🏥 Overview

A comprehensive Hospital & Clinic Management CRM system built with PHP, MySQL, HTML, CSS, and JavaScript. This system provides complete management solution for hospitals and clinics with multi-role access, patient management, billing system, and much more.

## ✨ Features

### 🔐 Multi-Role Authentication System
- **Admin**: Complete system control and management
- **Doctor**: Patient consultation and medical records
- **Patient**: View medical history and bills
- **Nurse**: Patient vitals and care management
- **Staff**: Limited administrative access
- **Pharmacy**: Medicine inventory management
- **Lab Technician**: Test management and results
- **Receptionist**: Patient registration and appointments
- **Intern**: Limited access based on role hierarchy

### 👨‍⚕️ Doctor Management
- Complete doctor profiles with credentials
- Education, experience, certificates, and awards
- Department assignments
- Contact details (admin only access)
- Profile images and specializations

### 👨‍💼 Patient Management
- Complete patient registration system
- Inpatient ↔ Outpatient conversion
- Medical history tracking
- Vitals monitoring with charts
- Emergency contact management
- Visit reason and attendant details

### 💰 Advanced Billing System
- Multi-currency support (INR, USD, EUR, BTC)
- Invoice generation with auto-calculations
- Multiple payment methods (Cash, Card, UPI, Net Banking, Cheque, Crypto)
- Tax and discount management
- Payment tracking and receipts
- Automatic bill item additions for consultations, tests, medicines

### 🏥 Department Management
- Optional department system (admin controlled)
- Doctor/nurse/lab/pharmacy assignments
- Department-wise test and service management

### 🛏️ Bed Management
- Real-time bed availability
- Multiple bed types (General, Private, ICU, Emergency)
- Patient assignments
- Daily pricing system

### 💊 Pharmacy Management
- Medicine inventory with SKU/Non-SKU support
- Stock management with minimum alerts
- Batch numbers and expiry tracking
- Price management (admin/receptionist)
- Prescription tracking

### 🔬 Laboratory Management
- Test catalog management
- Department-wise test assignments
- Result uploads (lab tech only)
- Auto-billing integration
- Normal range specifications

### 📊 Advanced Analytics
- Revenue and financial reports
- Patient statistics and demographics
- Department performance metrics
- Interactive charts and graphs
- System health monitoring

### 🎨 Modern UI/UX
- Cliniva Angular theme inspired design
- 100% responsive design
- Day/Night mode toggle
- Customizable colors, logo, favicon, title
- Beautiful gradient headers
- Modern card-based layout

### 🔒 Security Features
- Encrypted password system
- Role-based access control
- Activity logging
- Session management
- Input sanitization

### 📱 Additional Features
- Attendance management system
- Salary management
- Feedback system
- Home visit scheduling
- Video consultation support
- Equipment management
- Insurance provider management
- Multi-hospital support

## 🚀 Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Modern web browser

### Setup Instructions

1. **Clone/Download the project**
   ```bash
   git clone [repository-url]
   cd hospital-crm
   ```

2. **Database Setup**
   ```bash
   # Create database and import structure
   mysql -u root -p
   source hospital_crm.sql
   ```

3. **Configure Database Connection**
   ```php
   // Edit config/database.php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'hospital_crm');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   ```

4. **Set Permissions**
   ```bash
   chmod 755 uploads/
   chmod 755 logs/
   ```

5. **Access the System**
   - Open browser and navigate to your domain
   - Default admin login:
     - Username: `admin`
     - Password: `password`
     - Role: `Admin`

## 📝 Default Login Credentials

| Role | Username | Password |
|------|----------|----------|
| Admin | admin | password |
| Doctor | dr.sharma | password |

**Note**: Change default passwords immediately after first login.

## 🗂️ Project Structure

```
hospital-crm/
├── config/
│   └── database.php          # Database configuration
├── includes/
│   ├── functions.php         # Core functions
│   ├── header.php           # Header component
│   ├── sidebar.php          # Sidebar navigation
│   ├── footer.php           # Footer component
│   └── admin_modals.php     # Admin modal dialogs
├── modules/
│   ├── doctors.php          # Doctor management
│   ├── patients.php         # Patient management
│   ├── billing.php          # Billing system
│   └── [other modules]      # Additional modules
├── dashboards/
│   ├── admin.php           # Admin dashboard
│   └── [role dashboards]   # Role-specific dashboards
├── assets/
│   ├── css/                # Stylesheets
│   ├── js/                 # JavaScript files
│   └── images/             # Images and icons
├── uploads/                # File uploads
├── api/                    # API endpoints
├── index.php              # Main entry point
├── login.php              # Login page
├── logout.php             # Logout handler
├── hospital_crm.sql       # Database structure
└── README.md              # This file
```

## 🔧 Configuration

### Theme Customization
- Access Admin → Settings → Appearance
- Change logo, favicon, colors, title
- Toggle day/night mode
- Customize hospital information

### User Roles & Permissions
- Admin can enable/disable any role
- Department assignments are optional
- Role-based module access control
- Intern system with hierarchy

### System Settings
- Multi-currency support
- Payment method configuration
- Email/SMS notifications (extensible)
- Backup and restore options

## 📊 Key Features Breakdown

### Patient Flow
1. **Registration** (Admin/Receptionist)
2. **Appointment Booking** (Any authorized role)
3. **Consultation** (Doctor)
4. **Tests/Prescriptions** (Doctor → Lab/Pharmacy)
5. **Billing** (Auto-generated)
6. **Payment** (Multiple methods)
7. **Discharge/Follow-up**

### Data Security
- All passwords are encrypted using PHP's password_hash()
- SQL injection protection with prepared statements
- XSS protection with input sanitization
- Role-based access control
- Activity logging for audit trails

### Integration Ready
- RESTful API endpoints
- JSON data exchange
- Extensible module system
- Database relationships with foreign keys
- Scalable architecture

## 🐛 Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials in `config/database.php`
   - Ensure MySQL service is running
   - Verify database exists

2. **Permission Denied**
   - Check file/folder permissions
   - Ensure web server can write to uploads/

3. **Login Issues**
   - Verify user exists in database
   - Check password encryption
   - Clear browser cache/cookies

4. **Missing Functions**
   - Ensure all files are uploaded
   - Check include paths
   - Verify database tables exist

## 📈 Future Enhancements

- [ ] Mobile app integration
- [ ] Real-time notifications
- [ ] Advanced reporting dashboard
- [ ] Telemedicine integration
- [ ] AI-powered diagnostics
- [ ] Multi-language support
- [ ] Cloud storage integration
- [ ] Advanced analytics with ML

## 🤝 Contributing

1. Fork the repository
2. Create feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Open Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👨‍💻 Developer

**Developed with ❤️ by Hospital CRM Team**

- Comprehensive requirement analysis
- Modern UI/UX design
- Robust backend architecture
- Extensive testing and validation

## 📞 Support

For support and queries:
- Email: support@hospitalcrm.com
- Documentation: [Wiki](wiki-url)
- Issues: [GitHub Issues](issues-url)

---

**Note**: This system is designed for production use with proper security measures. Always follow best practices for deployment and maintenance.

## 🎯 System Requirements Met

✅ Multi-role login system with encrypted passwords  
✅ Complete doctor management with all details  
✅ Patient management with inpatient/outpatient conversion  
✅ Advanced billing system with multi-currency support  
✅ Department management with assignments  
✅ Bed management system  
✅ Pharmacy management with inventory  
✅ Laboratory management with test assignments  
✅ Equipment management  
✅ Attendance and salary management  
✅ Feedback and rating system  
✅ Customizable UI with day/night mode  
✅ 100% responsive design  
✅ Role-based access control  
✅ Activity logging and security  
✅ Intern system with hierarchy  
✅ Home visits and video consultations  
✅ Insurance and billing integration  

**Total Tables**: 25+ comprehensive database tables  
**Total Features**: 50+ advanced features  
**Security Level**: Enterprise-grade  
**UI/UX**: Modern and responsive  
**Code Quality**: Production-ready
