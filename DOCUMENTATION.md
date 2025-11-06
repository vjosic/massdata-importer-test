# Mass Data Importer - Complete Documentation

## Overview
Complete Laravel 12 application with AdminLTE 2 interface for mass data import and management. Features comprehensive data visualization, import tracking, user management with role-based permissions, audit trails, and advanced multi-table dataset support.

## Features

### 🔐 Authentication & User Management
- **Login System**: Email/password authentication with session management
- **User Roles**: Admin and Editor roles with Spatie Laravel Permission
- **User CRUD**: Complete user management with permissions and email validation
- **Access Control**: Route-based permission checking with middleware

### 📊 Data Import System
- **Multi-file Upload**: Support for related CSV files (orders, customers, products, etc.)
- **Multi-table Datasets**: Advanced support for datasets with multiple tables (e.g., inventory with products + stock_levels)
- **Background Processing**: Laravel queue system with job monitoring
- **Advanced Validation**: Comprehensive data validation with configurable rules and foreign key support
- **Retry Logic**: Failed imports can be retried with error clearing
- **Email Notifications**: Automatic notifications on import completion/failure
- **Smart Type Conversion**: Automatic data type conversion with support for nullable integer fields

### 🗄️ Imported Data Management
- **Dynamic Datasets**: Configurable data types (orders, customers, products, stock, suppliers, tracking)
- **Tab-based Multi-table View**: Seamless navigation between related tables in datasets
- **Advanced Search**: Multi-column search with real-time filtering across all dataset tables
- **Clean Interface**: Streamlined UI without redundant navigation elements
- **Data Export**: Excel export of filtered datasets
- **Audit Trails**: Complete activity logging for all data operations with user tracking
- **Bulk Operations**: Mass delete with permission checking

### 📈 Import Tracking
- **Import History**: Complete log of all import operations
- **Status Monitoring**: Real-time import status tracking (pending, processing, completed, failed)
- **Detailed Logs**: Error logs, validation issues, and processing information with user information
- **Performance Monitoring**: Import duration and success rates

### 🛠️ Maintenance & Cleanup
- **Import Cleanup**: Automated cleanup of old imports with configurable retention policies
- **Dry Run Mode**: Preview cleanup actions before execution
- **Selective Cleanup**: Filter by status, age, and other criteria
- **File Management**: Automatic cleanup of associated import files

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
# Complete setup with single command
php artisan db:setup

# Or manual setup (alternative)
php artisan db:setup --skip-db  # if database already exists
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS massdata_import;"
php artisan migrate
php artisan db:seed --class=AdminUserSeeder
```

**Database Setup Options:**
```bash
# Interactive setup (recommended)
php artisan db:setup

# Force setup without confirmation  
php artisan db:setup --force

# Skip specific steps
php artisan db:setup --skip-db        # Skip database creation
php artisan db:setup --skip-migrate   # Skip migrations  
php artisan db:setup --skip-seed      # Skip admin user seeding
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
1. Select import type that requires multiple files (e.g., Inventory)
2. Upload each required file type (products_file and stock_levels_file)
3. System validates all files before processing
4. Monitor progress in **Imports** section

#### Advanced Multi-table Datasets
- **Inventory Dataset**: Automatically combines products and stock_levels data
- **Tab Navigation**: Switch between related tables seamlessly
- **Unified Search**: Search across all tables in a dataset
- **Foreign Key Management**: Automatic validation with fallback options

#### Import Configuration
Edit `config/imports.php` to add new import types:
```php
'new_type' => [
    'label' => 'New Data Type',
    'files' => [
        'main_file' => [
            'label' => 'Main Data File',
            'headers_to_db' => [
                'CSV Column' => [
                    'label' => 'Field Label',
                    'type' => 'string|integer|date|email',
                    'validation' => ['required|present|nullable', 'additional_rules']
                ]
            ]
        ]
    ]
]
```

#### Validation Configuration
Advanced validation rules support:
```php
'reserved_quantity' => [
    'label' => 'Reserved Quantity',
    'type' => 'integer',
    'validation' => ['present', 'integer', 'min:0'], // Allows 0 values
],
'email' => [
    'label' => 'Email Address',  
    'type' => 'email',
    'validation' => ['required', 'email', 'unique' => [
        'table' => 'customers',
        'column' => 'email'
    ]]
]
```

### Data Management

#### Viewing Imported Data
1. Navigate to **Imported Data**
2. Select dataset type (Orders, Customers, Products, Inventory, etc.)
3. **Multi-table Datasets**: Use tabs to navigate between related tables
4. Use search filters to find specific records
5. Export filtered results to Excel

#### Multi-table Dataset Features
- **Inventory Dataset**: 
  - **Products Tab**: View all product information
  - **Stock Levels Tab**: View inventory levels per warehouse
- **Unified Interface**: Clean, sidebar-free design focused on data
- **Tab Persistence**: Search and filters maintained when switching tabs

#### Search & Filtering
- **Global Search**: Searches across all displayed columns
- **Real-time**: Results update as you type
- **Multi-table Support**: Search works across all tables in dataset
- **Export**: Export current filtered view to Excel

#### Audit Trails
- All data operations are logged with user information
- View complete history of changes
- Track user actions and timestamps
- AJAX-powered audit popups for detailed record history

### Import Monitoring

#### Viewing Import History
1. Go to **Imports** section
2. Filter by user, type, status, or date range
3. Click on any import to view details
4. **Accurate Statistics**: Fixed row counting and processing time display

#### Import Details
- **Processing Information**: Duration calculation and success tracking
- **User Information**: Proper user display in import logs
- **Error logs**: Row-specific issues with validation details
- **File information**: Upload validation and processing results
- **Retry options**: Re-run failed imports with error clearing
- Processing time analysis

## Configuration Files

### Import Types (`config/imports.php`)
```php
return [
    'inventory' => [
        'label' => 'Import Inventory Data',
        'files' => [
            'products_file' => [
                'label' => 'Products File',
                'headers_to_db' => [
                    'sku' => [
                        'label' => 'SKU',
                        'type' => 'string',
                        'validation' => ['required', 'unique' => [
                            'table' => 'products',
                            'column' => 'sku'
                        ]]
                    ]
                ]
            ],
            'stock_levels_file' => [
                'label' => 'Stock Levels File',
                'headers_to_db' => [
                    'reserved_quantity' => [
                        'label' => 'Reserved Quantity',
                        'type' => 'integer',
                        'validation' => ['present', 'integer', 'min:0']
                    ]
                ]
            ]
        ]
    ]
];
```

### Key Configuration Features:
- **Multi-file Support**: Link related data across multiple CSV files
- **Advanced Validation**: Support for present, required, nullable rules
- **Integer Zero Support**: Proper handling of 0 values in numeric fields
- **Foreign Key Management**: Configurable constraint validation
- **Column Mapping**: Detailed CSV header to database field mapping
- **Type Conversion**: Automatic data type conversion with null handling

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

# Clean up old import files and records
php artisan import:cleanup

# Preview cleanup without deleting (dry run)
php artisan import:cleanup --dry-run

# Clean imports older than 7 days
php artisan import:cleanup --days=7

# Clean only failed imports
php artisan import:cleanup --status=failed

# Clean all imports regardless of status
php artisan import:cleanup --status=all

# Backup database
mysqldump -u username -p massdata_import > backup.sql

# Update dependencies
composer update
```

### Import Cleanup Command
The `import:cleanup` command provides comprehensive cleanup functionality:

#### Command Options:
- `--days=X`: Keep imports newer than X days (default: 30)
- `--status=completed|failed|all`: Filter by import status (default: completed)
- `--dry-run`: Preview what would be deleted without actually deleting

#### What Gets Cleaned:
- **Import Records**: Old import operation records
- **Import Errors**: Associated validation errors
- **Audit Records**: Related audit trail entries  
- **Import Files**: Uploaded CSV files from storage

#### Examples:
```bash
# Safe preview of what would be cleaned
php artisan import:cleanup --dry-run --days=7

# Clean completed imports older than 2 weeks
php artisan import:cleanup --days=14

# Emergency cleanup of all imports older than 1 day
php artisan import:cleanup --days=1 --status=all
```

### Monitoring
- Monitor queue job failures
- Track import success rates
- Monitor disk space for uploads
- Watch application logs for errors

## Recent Updates & Bug Fixes

### Version 1.2.0 - UI/UX Improvements & Bug Fixes

#### UI/UX Improvements & Bug Fixes ✅
- **Fixed Import Display**: Corrected row counting and processing time display
- **User Display**: Resolved "User: Unknown" in import logs by adding proper relation loading

#### Advanced Multi-table Support ✅
- **Tab-based Navigation**: Implemented seamless tabs for multi-table datasets
- **Inventory Dataset**: Complete support for products + stock_levels tables
- **Unified Search**: Search functionality works across all tables in dataset
- **Clean Interface**: Removed redundant sidebar for streamlined data view

#### Validation & Data Handling ✅
- **Integer Zero Support**: Fixed validation to properly handle 0 values in numeric fields
- **Advanced Validation Rules**: Support for `present|integer|min:0` patterns
- **Foreign Key Management**: Improved constraint handling with validation fallbacks
- **Type Conversion**: Enhanced data type conversion with proper null handling

#### Maintenance & Cleanup ✅
- **Import Cleanup Command**: New `php artisan import:cleanup` with configurable options
- **Dry Run Mode**: Preview cleanup actions before execution
- **Selective Cleanup**: Filter by age, status, and other criteria
- **Automatic File Cleanup**: Remove associated import files during cleanup

#### Technical Improvements ✅
- **ProcessImportJob**: Enhanced processing and error handling
- **ImportsController**: Fixed processing time calculation and user relation loading
- **ImportedDataController**: Multi-table dataset support with tab navigation
- **Database Schema**: Optimized foreign key constraints and default values

### File Structure
```
app/
├── Console/Commands/           # Artisan commands (ImportCleanup)
├── Http/Controllers/Admin/     # Admin panel controllers
├── Jobs/                       # Background job classes
├── Models/                     # Eloquent models
├── Events/                     # Application events
└── Listeners/                  # Event listeners

resources/views/admin/          # Admin panel templates
├── imported-data/              # Multi-table dataset views
├── imports/                    # Import management views
└── users/                      # User management views

config/imports.php              # Import configuration
database/migrations/            # Database schema
```

### Known Issues & Solutions

#### Common Problems Fixed:
1. **"reserved_quantity field is required" Error**: 
   - **Solution**: Updated validation to use `present|integer|min:0` instead of `required|min:0`
   - **Why**: Laravel treats 0 as empty when using `required` validator

2. **Import Display Issues**:
   - **Solution**: Fixed row counting logic in ProcessImportJob
   - **Implementation**: Proper array counting after header removal

3. **Processing Time Shows "N/A"**:
   - **Solution**: Corrected Carbon date difference calculation
   - **Fix**: Changed from `$end->diffInSeconds($start)` to `$start->diffInSeconds($end)`

4. **User Shows "Unknown" in Logs**:
   - **Solution**: Added user relation loading in logs method
   - **Implementation**: `$import->load('user')` before AJAX response

#### Validation Best Practices:
```php
// For numeric fields that can be 0
'validation' => ['present', 'integer', 'min:0']

// For required fields that cannot be empty
'validation' => ['required', 'string', 'max:255']

// For nullable fields with defaults
'validation' => ['nullable', 'integer', 'min:0']
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
- **Version**: 1.2.0 (November 2025)

### Recent Updates:
- ✅ Fixed import display issues
- ✅ Enhanced multi-table dataset support with tabs
- ✅ Improved validation for numeric fields
- ✅ Added comprehensive import cleanup functionality
- ✅ Streamlined UI with sidebar removal for data views

For technical support or feature requests, please refer to the project repository or contact the development team.