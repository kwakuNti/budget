# Nkansah Family Budget Manager

A comprehensive family budget management system built with PHP, MySQL, and JavaScript. This application helps families track contributions, manage expenses, and monitor financial goals.

## Features

### Family Management
- Family group creation and management
- Member management (both registered users and family-only members)
- Role-based access control (admin, head, member, child)
- Monthly contribution tracking and goal setting

### Financial Tracking
- Family contribution tracking
- Expense management with categories (DSTV, WiFi, utilities, dining, maintenance, etc.)
- Monthly cycle management with automatic debt tracking
- Real-time family pool balance calculation

### Personal Budget Features
- Personal income and expense tracking
- Budget allocation with 50-30-20 rule support
- Personal goals and savings targets
- Salary and income source management

### Mobile Money Integration
- MoMo account management for multiple networks (MTN, Vodafone, AirtelTigo)
- Transaction tracking and balance management
- API integration ready for live MoMo operations

### Dashboard & Analytics
- Real-time dashboard with family statistics
- Monthly performance tracking
- Member contribution analytics
- Debt tracking and management
- Activity logs and recent transactions

## Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 8.0+
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Styling**: Custom CSS with responsive design
- **Development Environment**: MAMP/XAMPP compatible

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 8.0 or higher
- Web server (Apache/Nginx)
- MAMP/XAMPP (for local development)

### Setup Instructions

1. **Clone the repository**
   ```bash
   git clone https://github.com/kwakuNti/budget.git
   cd budget
   ```

2. **Database Setup**
   - Create a new MySQL database named `budget`
   - Import the database schema:
     ```bash
     mysql -u root -p budget < db/budget.sql
     ```

3. **Configuration**
   - Copy `config/connection.php.example` to `config/connection.php`
   - Update database credentials in `config/connection.php`:
     ```php
     $servername = "localhost";
     $username = "your_db_username";
     $password = "your_db_password";
     $dbname = "budget";
     ```

4. **Web Server Setup**
   - Place the project in your web server document root
   - For MAMP: `/Applications/MAMP/htdocs/budget-app/`
   - For XAMPP: `C:\xampp\htdocs\budget-app\`

5. **Access the Application**
   - Open your browser and navigate to: `http://localhost/budget-app/`
   - Default login credentials:
     - Username: `nkansah_admin`
     - Password: `family123`

## Project Structure

```
budget-app/
├── actions/           # Form handlers and backend actions
├── ajax/             # AJAX endpoints for dynamic content
├── api/              # REST API endpoints
├── config/           # Database and configuration files
├── cron/             # Scheduled tasks and maintenance
├── db/               # Database schema and migrations
├── includes/         # Reusable PHP functions and utilities
├── public/           # Static assets (CSS, JS, images)
│   ├── css/         # Stylesheets
│   └── js/          # JavaScript files
├── templates/        # HTML templates and pages
├── .gitignore       # Git ignore rules
├── .htaccess        # Apache configuration
├── index.php        # Application entry point
└── README.md        # This file
```

## Key Features Explained

### Monthly Cycles
The system automatically creates monthly contribution cycles that:
- Track member contributions against their goals
- Calculate completion rates and progress
- Handle debt tracking for missed contributions
- Provide closure and archival at month-end

### Member Management
Support for two types of members:
- **Registered Users**: Have full system access with login credentials
- **Family Members Only**: Tracked in the system but don't have login access

### Mobile Money Integration
- Supports Ghana's major MoMo networks (MTN, Vodafone, AirtelTigo)
- Ready for API integration with live MoMo services
- Transaction tracking and balance management
- Fee calculation and reporting

### Debt Tracking
- Automatic debt calculation for missed contributions
- Debt history and clearance tracking
- Monthly debt reports and analytics
- Configurable debt limits and handling

## API Endpoints

- `GET /api/dashboard_data.php` - Dashboard statistics and data
- `POST /actions/contribution.php` - Record family contributions
- `POST /actions/expense_handler.php` - Record family expenses
- `GET /ajax/get_cycle_dashboard.php` - Current cycle information
- `POST /ajax/cycle_management.php` - Cycle operations

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Security Considerations

- All database queries use prepared statements
- Input validation and sanitization
- Session management with secure tokens
- Password hashing with PHP's password_hash()
- CSRF protection on forms

## Database Schema

The application uses a normalized database schema with:
- User and family management tables
- Financial transaction tables
- Monthly cycle and performance tracking
- Personal budget and goal tables
- Mobile money integration tables
- System configuration and logging tables

## License

This project is proprietary software for the Nkansah Family. All rights reserved.

## Support

For support or questions, please contact the development team or create an issue in the repository.

## Changelog

### Version 2.0 (Current)
- Added comprehensive MoMo integration
- Enhanced dashboard with real-time analytics
- Improved debt tracking and management
- Added personal budget features
- Enhanced security and validation
- Mobile-responsive design improvements

### Version 1.0
- Initial family budget tracking system
- Basic contribution and expense management
- User authentication and family groups
- Monthly cycle management
