# 🏟️ TurfKick - Smart Turf Booking Platform

**A full-stack web application for seamless turf discovery, booking, and management**

![Status](https://img.shields.io/badge/status-production--ready-brightgreen)
![PHP](https://img.shields.io/badge/PHP-8.0+-blue)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-orange)
![License](https://img.shields.io/badge/license-MIT-green)

---

## 📖 Overview

TurfKick is a comprehensive turf booking platform that connects sports enthusiasts with quality playing facilities. The platform enables users to discover nearby turfs, check real-time availability, book slots instantly, and rent equipment—all in one place. Turf owners can manage their properties, track bookings, and optimize their operations through an intuitive dashboard.

### ✨ Key Features

#### For Players (Users)
- 🔍 **Smart Search & Filtering** - Find turfs by location, sport type, price range, and availability
- 📅 **Real-Time Availability** - View live slot availability with instant booking confirmation
- ⚽ **Equipment Rental** - Rent sports equipment along with your turf booking
- 📊 **Booking History** - Track upcoming, completed, and cancelled bookings
- ⭐ **Review System** - Rate and review turfs to help the community
- 🔐 **Secure Authentication** - JWT-based auth with CSRF protection

#### For Turf Owners
- 🏢 **Property Management** - Add, edit, and manage multiple turf properties
- 🕐 **Dynamic Slot Configuration** - Create custom time slots for different days
- 💰 **Equipment Inventory** - Manage rental equipment with pricing
- 📈 **Booking Dashboard** - View all bookings, revenue analytics, and occupancy rates
- 🔧 **Maintenance Requests** - Schedule maintenance and temporarily disable slots
- ✅ **Admin Approval Workflow** - Transparent approval process for new turfs

#### For Administrators
- 👥 **User Management** - Monitor and manage users and owners
- 🏟️ **Turf Approval** - Review and approve new turf listings
- 📋 **Booking Oversight** - View all platform bookings and handle disputes
- 🔧 **Maintenance Monitoring** - Approve/reject maintenance requests
- 🚫 **Content Moderation** - Toggle turf status and manage platform quality

---

## 🛠️ Tech Stack

### Frontend
- **HTML5 & CSS3** - Semantic markup with modern styling
- **Vanilla JavaScript (ES6+)** - Lightweight, performant client-side logic
- **Responsive Design** - Mobile-first approach with adaptive layouts

### Backend
- **PHP 8.0+** - Server-side business logic and API endpoints
- **PDO (PHP Data Objects)** - Secure database access with prepared statements
- **RESTful API Architecture** - Clean separation of concerns

### Database
- **MySQL 8.0+** - Relational database with InnoDB engine
- **Normalized Schema** - 10 tables with proper foreign key constraints
- **JSON Support** - Flexible storage for equipment IDs in bookings

### Security
- 🔒 **Prepared Statements** - Complete SQL injection prevention
- 🔐 **bcrypt Password Hashing** - Industry-standard password security
- 🛡️ **CSRF Protection** - Token-based cross-site request forgery prevention
- 🧹 **Input Sanitization** - XSS prevention through output encoding
- 📁 **File Upload Validation** - Type checking, size limits, secure naming
- 🔑 **Role-Based Access Control** - User, Owner, Admin permission levels

---

## 📁 Project Structure

```
turfkick/
├── api/                      # REST API endpoints
│   ├── admin/                # Admin-specific APIs
│   ├── owner/                # Owner-specific APIs
│   ├── create_booking.php    # Booking creation endpoint
│   ├── get_turfs.php         # Fetch available turfs
│   ├── login.php             # Authentication endpoint
│   ├── register.php          # User/owner registration
│   └── ...                   # Other endpoints
├── config/
│   └── db.php                # Database configuration
├── css/                      # Stylesheets
│   ├── style.css             # Global styles
│   ├── turf-details.css      # Turf detail modal styles
│   └── turfs.css             # Turf listing styles
├── includes/
│   └── helpers.php           # Utility functions & security helpers
├── js/                       # Client-side JavaScript
│   ├── admin.js              # Admin dashboard logic
│   ├── auth.js               # Authentication handling
│   ├── bookings.js           # User booking flow
│   └── owner.js              # Owner dashboard logic
├── uploads/                  # User-uploaded files (documents, images)
├── index.html                # Landing page
├── browse_turfs.html         # Turf browsing interface
├── owner_dashboard.html      # Owner management panel
├── admin_dashboard.html      # Admin control panel
├── schema.sql                # Database schema & seed data
└── README.md                 # This file
```

---

## 🚀 Quick Start

### Prerequisites
- PHP 8.0 or higher
- MySQL 8.0 or higher
- Apache/Nginx web server (or PHP built-in server for development)
- Modern web browser (Chrome, Firefox, Edge, Safari)

### Installation Steps

#### 1. Clone the Repository
```bash
git clone https://github.com/yourusername/turfkick.git
cd turfkick
```

#### 2. Configure Database
Edit `config/db.php` with your database credentials:
```php
$host = 'localhost';
$db   = 'turfkick_db';
$user = 'root';
$pass = 'your_password';
```

#### 3. Import Database Schema
```bash
mysql -u root -p < schema.sql
```

This creates the database, all tables, and inserts sample data including:
- 1 admin account (`admin@turfkick.com` / `password`)
- 1 sample owner (`owner@example.com` / `password`)
- 1 sample user (`john@example.com` / `password`)
- 1 sample turf with time slots

#### 4. Set Permissions
```bash
chmod 755 uploads/
```

#### 5. Launch the Application

**Option A: PHP Built-in Server (Development)**
```bash
php -S localhost:8000
```

**Option B: Apache/Nginx**
- Copy project files to your web root (e.g., `/var/www/html/turfkick`)
- Ensure mod_rewrite is enabled for Apache
- Access via `http://localhost/turfkick`

#### 6. Access the Platform
- **Landing Page**: `http://localhost:8000`
- **User Login**: Register or use `john@example.com` / `password`
- **Owner Login**: Use `owner@example.com` / `password`
- **Admin Panel**: Use `admin@turfkick.com` / `password`

---

## 📊 Database Schema

### Tables Overview

| Table | Description |
|-------|-------------|
| `users` | User accounts (players & turf owners) |
| `admins` | Administrator accounts |
| `turfs` | Turf property listings |
| `time_slots` | Available booking slots per turf |
| `bookings` | User reservations with unique constraint |
| `payments` | Payment transaction records |
| `turf_images` | Multiple images per turf |
| `equipment` | Rental equipment inventory |
| `reviews` | User ratings and comments |
| `maintenance_requests` | Turf maintenance tracking |

### Key Constraints
- **Unique Booking Constraint**: Prevents double-booking same turf + date + slot
- **Foreign Keys**: Cascading deletes maintain referential integrity
- **CHECK Constraints**: Rating values limited to 1-5 range
- **Indexes**: Optimized queries on location, sport category, and booking dates

---

## 🔐 Security Features

### Implemented Security Measures

1. **SQL Injection Prevention**
   - All queries use PDO prepared statements
   - No raw SQL concatenation with user input

2. **Cross-Site Scripting (XSS) Prevention**
   - Output encoding with `htmlspecialchars()`
   - Input sanitization before database storage

3. **CSRF Protection**
   - Session-based CSRF tokens
   - Token validation on all state-changing requests

4. **Password Security**
   - bcrypt hashing with automatic salt generation
   - Minimum password enforcement (recommended: 8+ chars)

5. **File Upload Security**
   - Whitelist-based file type validation (JPG, PNG, WEBP, PDF)
   - File size limits (5MB max)
   - Randomized filenames prevent overwrites
   - Files stored outside web root when possible

6. **Access Control**
   - Role-based middleware (`require_admin()`, `is_owner()`)
   - Session-based authentication
   - Ownership verification on resource modifications

7. **Error Handling**
   - Generic error messages for users
   - Detailed logging for developers
   - No SQL/state details exposed to clients

---

## 🌟 Resume Highlights

This project demonstrates proficiency in:

✅ **Full-Stack Development** - End-to-end feature implementation from UI to database  
✅ **Security Best Practices** - OWASP Top 10 mitigation strategies implemented  
✅ **Database Design** - Normalized schema with constraints, indexes, and relationships  
✅ **RESTful API Design** - Clean, consistent endpoint architecture  
✅ **Authentication & Authorization** - Multi-role system with secure session management  
✅ **File Handling** - Secure upload validation and storage  
✅ **Frontend Logic** - Dynamic UI updates without frameworks (vanilla JS)  
✅ **Problem Solving** - Double-booking prevention, slot conflict detection  

### Metrics
- **10 Database Tables** with proper normalization
- **20+ API Endpoints** covering all CRUD operations
- **3 User Roles** with distinct permissions
- **Zero SQL Injection Vulnerabilities** (prepared statements throughout)
- **100% Input Validation** on all user-facing forms

---

## 📝 API Endpoints

### Authentication
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/register.php` | Register new user/owner |
| POST | `/api/login.php` | Authenticate user |
| POST | `/api/logout.php` | End session |
| GET | `/api/get_token.php` | Fetch CSRF token |
| GET | `/api/get_user.php` | Get current user info |

### User Operations
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/get_turfs.php` | List all active turfs |
| GET | `/api/get_slots.php` | Get available slots for turf |
| POST | `/api/create_booking.php` | Book a turf slot |
| GET | `/api/get_bookings.php` | Get user's bookings |
| POST | `/api/cancel_booking.php` | Cancel a booking |
| POST | `/api/submit_review.php` | Submit turf review |
| PUT | `/api/update_profile.php` | Update user profile |

### Owner Operations
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/owner/get_turfs.php` | Get owner's turfs |
| POST | `/api/manage_turfs.php` | Create/edit turfs |
| POST | `/api/manage_slots.php` | Configure time slots |
| GET | `/api/get_equipment.php` | List equipment |
| POST | `/api/manage_equipment.php` | Manage equipment inventory |
| POST | `/api/maintenance_requests.php` | Submit maintenance request |

### Admin Operations
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/get_users.php` | List all users |
| GET | `/api/admin/get_turfs.php` | List all turfs |
| GET | `/api/admin/get_bookings.php` | View all bookings |
| POST | `/api/admin/approve_turf.php` | Approve pending turf |
| POST | `/api/admin/reject_turf.php` | Reject turf listing |
| POST | `/api/admin/handle_maintenance.php` | Process maintenance requests |

---

## 🧪 Testing

### Manual Testing Checklist

#### User Flow
- [ ] Register new user account
- [ ] Browse turfs with filters
- [ ] View turf details and availability
- [ ] Book a slot with optional equipment
- [ ] View booking history
- [ ] Cancel upcoming booking
- [ ] Submit review after completed booking

#### Owner Flow
- [ ] Register as turf owner with documents
- [ ] Add new turf property
- [ ] Configure time slots for week
- [ ] Add rental equipment
- [ ] View booking dashboard
- [ ] Submit maintenance request

#### Admin Flow
- [ ] Review pending turf approvals
- [ ] Approve/reject turfs
- [ ] Monitor platform bookings
- [ ] Handle maintenance requests
- [ ] Manage user accounts

### Sample Test Credentials
```
Admin:  admin@turfkick.com / password
Owner:  owner@example.com / password
User:   john@example.com / password
```

---

## 🎨 UI/UX Features

- **Clean, Modern Interface** - Intuitive navigation with minimal learning curve
- **Modal-Based Booking** - Contextual booking flow without page reloads
- **Visual Slot Selection** - Interactive time slot grid with real-time feedback
- **Responsive Layout** - Optimized for desktop, tablet, and mobile devices
- **Profile Avatars** - Auto-generated initials for user identification
- **Toast Notifications** - Non-intrusive success/error messages

---

## 🚧 Future Enhancements

Planned improvements for next iteration:

- [ ] **Payment Gateway Integration** - Razorpay/Stripe for online payments
- [ ] **Email Notifications** - Booking confirmations and reminders
- [ ] **Advanced Search** - Radius-based search with geolocation
- [ ] **Analytics Dashboard** - Revenue charts, occupancy heatmaps
- [ ] **Multi-Language Support** - i18n for regional languages
- [ ] **Progressive Web App** - Offline support and push notifications
- [ ] **Social Login** - Google/Facebook authentication
- [ ] **Chat Support** - Real-time customer assistance

---

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Code Style Guidelines
- Follow PSR-12 coding standards for PHP
- Use meaningful variable and function names
- Comment complex logic, not obvious code
- Validate all user inputs
- Write descriptive commit messages

---

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

---

## 👨‍💻 Author

**Your Name**  
Full-Stack Developer

📧 Email: your.email@example.com  
💼 LinkedIn: linkedin.com/in/yourprofile  
🐙 GitHub: github.com/yourusername  

---

## 🙏 Acknowledgments

- Inspired by real-world turf booking challenges
- Built with best practices from OWASP guidelines
- Thanks to the open-source community for tools and libraries

---

**⭐ If you found this project helpful, please give it a star!**

*Last Updated: August 2025*
