# Mass Data Importer - Complete Documentation

## Overview
Complete Laravel 12 application with AdminLTE 2 interface for mass data import and management. Features comprehensive data visualization, import tracking, user management with role-based permissions, and audit trails.

## Features

### 🔐 Authentication & User Management
- **Login System**: Email/password authentication with session management
- **User Roles**: Admin and Editor roles with Spatie Laravel Permission
- **User CRUD**: Complete user management with permissions and email validation
- **Access Control**: Route-based permission checking with middleware

### 📊 Data Import System
- **Multi-file Upload**: Support for related CSV files (orders, customers, products, etc.)
- **Background Processing**: Laravel queue system with job monitoring
- **Validation**: Comprehensive data validation with error reporting
- **Retry Logic**: Failed imports can be retried with error clearing
- **Email Notifications**: Automatic notifications on import completion/failure

### 🗄️ Imported Data Management
- **Dynamic Datasets**: Configurable data types (orders, customers, products, stock, suppliers, tracking)
- **Advanced Search**: Multi-column search with real-time filtering
- **Data Export**: Excel export of filtered datasets
- **Audit Trails**: Complete activity logging for all data operations
- **Bulk Operations**: Mass delete with permission checking

### 📈 Import Tracking & Analytics
- **Import History**: Complete log of all import operations
- **Status Monitoring**: Real-time import status tracking (pending, processing, completed, failed)
- **Detailed Logs**: Error logs, validation issues, and processing statistics
- **Performance Analytics**: Import duration, success rates, user activity
- **Statistics Dashboard**: Visual charts and performance metrics

## Technology Stack

### Backend
- **Laravel 12**: Latest Laravel framework with modern PHP features
- **MySQL**: Primary database with comprehensive schema
- **Queue System**: Laravel Horizon for background job processing
- **Spatie Permission**: Role and permission management
- **PhpSpreadsheet**: Excel file processing and export

### Frontend
- **AdminLTE 2**: Bootstrap 3 based admin template (CDN version)
- **jQuery**: JavaScript interactions and AJAX requests
- **Chart.js**: Data visualization for statistics
- **Font Awesome**: Icon library for UI elements

## Installation & Setup

### Prerequisites
```bash
# Required software
- PHP 8.2+
- MySQL 8.0+
- Composer
- Node.js (for asset compilation)
```

### Installation Steps

1. **Clone & Install Dependencies**
```bash
git clone <repository>
cd massdata-importer-test
composer install
```

2. **Environment Configuration**
```bash
cp .env.example .env
php artisan key:generate
```

3. **Database Configuration**
Edit `.env` file:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=massdata_import
DB_USERNAME=your_username
DB_PASSWORD=your_password

QUEUE_CONNECTION=database
```

4. **Database Setup**
```bash
# Create database
mysql -u root -p
CREATE DATABASE massdata_import;
exit

# Run migrations
php artisan migrate

# Seed admin user
php artisan db:seed --class=AdminUserSeeder
```

5. **Queue Worker Setup**
```bash
# Start queue worker (for background imports)
php artisan queue:work
```

6. **Start Development Server**
```bash
php artisan serve
```

## Usage Guide

### First Login
- **URL**: `http://localhost:8000/admin/login`
- **Default Admin**: `admin@example.com` / `password`

### User Management

#### Creating Users
1. Navigate to **Users** in sidebar
2. Click **Add New User**
3. Fill required fields: Name, Email, Password
4. Assign role: Admin or Editor
5. Save user

#### Managing Permissions
- **Admin Role**: Full access to all features
- **Editor Role**: Can import and view data, cannot manage users

### Data Import Process

#### Single Import Type
1. Go to **Data Import**
2. Select import type from dropdown
3. Upload CSV file
4. Review column mapping
5. Submit for processing

#### Multi-file Import
1. Select import type that requires multiple files
2. Upload each required file type
3. System validates all files before processing
4. Monitor progress in **Imports** section

#### Import Configuration
Edit `config/imports.php` to add new import types:
```php
'new_type' => [
    'label' => 'New Data Type',
    'model' => App\Models\NewModel::class,
    'files' => [
        'main' => [
            'label' => 'Main Data File',
            'required' => true,
            'headers_to_db' => [
                'CSV Column' => 'database_field'
            ]
        ]
    ]
]
```

### Data Management

#### Viewing Imported Data
1. Navigate to **Imported Data**
2. Select dataset type (Orders, Customers, etc.)
3. Use search filters to find specific records
4. Export filtered results to Excel

#### Search & Filtering
- **Global Search**: Searches across all displayed columns
- **Real-time**: Results update as you type
- **Export**: Export current filtered view to Excel

#### Audit Trails
- All data operations are logged
- View complete history of changes
- Track user actions and timestamps

### Import Monitoring

#### Viewing Import History
1. Go to **Imports** section
2. Filter by user, type, status, or date range
3. Click on any import to view details

#### Import Details
- Processing statistics (success rate, duration)
- Error logs with row-specific issues
- File information and validation results
- Retry options for failed imports

#### Statistics Dashboard
- Overall import performance metrics
- Visual charts for status and type distribution
- User activity tracking
- Processing time analysis

## Configuration Files

### Import Types (`config/imports.php`)
```php
return [
    'customer_orders' => [
        'label' => 'Customer Orders',
        'model' => App\Models\Order::class,
        'files' => [
            'orders' => [...],
            'customers' => [...],
            'products' => [...]
        ]
    ]
];
```

### Key Features:
- **Multi-file Support**: Link related data across files
- **Validation Rules**: Define required fields and formats
- **Column Mapping**: Map CSV headers to database fields
- **Relationships**: Handle foreign key relationships

## Database Schema

### Core Tables
- `users` - User accounts and authentication
- `imports` - Import operation tracking
- `import_errors` - Validation error details
- `audits` - Activity logging and audit trails

### Data Tables
- `orders` - Customer order records
- `customers` - Customer information
- `products` - Product catalog
- `stock_levels` - Inventory tracking
- `suppliers` - Supplier information
- `tracking` - Shipment tracking

## API Endpoints

### Import Management
```php
GET    /admin/imports              # List all imports
GET    /admin/imports/{id}         # View import details
POST   /admin/imports/{id}/retry   # Retry failed import
GET    /admin/imports/statistics   # View statistics
```

### Data Management
```php
GET    /admin/data/{type}          # View dataset
POST   /admin/data/{type}/export   # Export to Excel
DELETE /admin/data/{type}/{id}     # Delete record
```

## Customization

### Adding New Import Types

1. **Create Model**
```php
php artisan make:model NewDataType -m
```

2. **Update Migration**
```php
Schema::create('new_data_types', function (Blueprint $table) {
    $table->id();
    $table->string('field1');
    $table->string('field2');
    $table->timestamps();
});
```

3. **Configure Import**
Add to `config/imports.php`:
```php
'new_data_type' => [
    'label' => 'New Data Type',
    'model' => App\Models\NewDataType::class,
    'files' => [
        'main' => [
            'label' => 'Main File',
            'required' => true,
            'headers_to_db' => [
                'CSV Header' => 'database_field'
            ]
        ]
    ]
]
```

### Customizing Validation
Modify validation in `ProcessImportJob.php`:
```php
$rules = [
    'field1' => 'required|string|max:255',
    'field2' => 'required|email'
];
```

### Adding Custom Permissions
```php
// In AdminUserSeeder or database seeder
Permission::create(['name' => 'custom.permission']);
$role->givePermissionTo('custom.permission');
```

## Troubleshooting

### Common Issues

#### Import Fails Immediately
- Check file format (CSV with proper encoding)
- Verify column headers match configuration
- Ensure required files are uploaded

#### Queue Jobs Not Processing
```bash
# Check queue worker is running
php artisan queue:work

# Check for failed jobs
php artisan queue:failed

# Restart queue worker
php artisan queue:restart
```

#### Permission Denied Errors
- Verify user has correct role assignment
- Check route middleware permissions
- Clear application cache: `php artisan cache:clear`

#### Database Connection Issues
- Verify database credentials in `.env`
- Check database server is running
- Test connection: `php artisan migrate:status`

### Performance Optimization

#### Large File Imports
- Increase PHP memory limit: `memory_limit = 512M`
- Adjust max execution time: `max_execution_time = 300`
- Use chunk processing in import job

#### Database Performance
- Add indexes for frequently searched columns
- Optimize queries in data controllers
- Consider database connection pooling

## Security Considerations

### File Upload Security
- Validate file types and extensions
- Scan uploaded files for malware
- Limit file sizes and upload rates
- Store uploads outside web root

### Data Protection
- Sanitize all user inputs
- Use parameterized queries
- Implement CSRF protection
- Regular security updates

### Access Control
- Enforce role-based permissions
- Log all administrative actions
- Implement session timeouts
- Use strong password policies

## Maintenance

### Regular Tasks
```bash
# Clear expired sessions
php artisan session:gc

# Clean up old import files
php artisan import:cleanup

# Backup database
mysqldump -u username -p massdata_import > backup.sql

# Update dependencies
composer update
```

### Monitoring
- Monitor queue job failures
- Track import success rates
- Monitor disk space for uploads
- Watch application logs for errors

## Support & Development

### File Structure
```
app/
├── Http/Controllers/Admin/     # Admin panel controllers
├── Jobs/                       # Background job classes
├── Models/                     # Eloquent models
├── Events/                     # Application events
└── Listeners/                  # Event listeners

resources/views/admin/          # Admin panel templates
config/imports.php              # Import configuration
database/migrations/            # Database schema
```

### Contributing
1. Follow PSR-12 coding standards
2. Write tests for new features
3. Update documentation for changes
4. Use meaningful commit messages

---

## Version Information
- **Laravel**: 12.x
- **PHP**: 8.2+
- **AdminLTE**: 2.4.18
- **Database**: MySQL 8.0+

For technical support or feature requests, please refer to the project repository or contact the development team.