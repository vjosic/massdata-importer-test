# MassData Importer Test

A Laravel 10 application demonstrating **dynamic data import management** using **AdminLTE 2**, **MySQL**, and **queued background jobs**.
The project allows configurable CSV/XLSX imports with validation, audit trails, and permission-based access.

---

## 🚀 Features

* Laravel 10 + AdminLTE 2 frontend layout
* Authentication (login only)
* Role & permission management (Spatie package)
* Dynamic import types loaded from `config/imports.php`
* Background import execution via Laravel Queues
* Validation, audit, and error logging for skipped rows
* Pagination, filtering, and exporting imported datasets

---

## ⚙️ Requirements

* PHP 8.1+
* Composer
* MySQL / MariaDB
* Node.js (optional, for asset building)
* Laravel 10

---

## 🗄️ Database Setup

Run MySQL and create the database manually:

```sql
CREATE DATABASE massdata_import CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

Then update `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=massdata_import
DB_USERNAME=root
DB_PASSWORD=vlado10
```

Run migrations and seeders:

```bash
php artisan migrate
php artisan db:seed
```

---

## 🧩 Configuration

All import types are defined in:

```
config/imports.php
```

Each import definition contains:

* `label` → visible name in the UI
* `permission_required` → determines visibility
* `files` → file definitions for each importable dataset
* `headers_to_db` → column mapping, type conversion, and validation rules
* `update_or_create` → unique keys for update detection

To add a new import type:

1. Create its DB table(s) manually with migrations.
2. Add a new section in `config/imports.php`.
3. Create the required permission (e.g. `import-products`).

The UI and backend logic will adapt automatically.

---

## 📂 Test Data

Test CSV files are located in `/storage/test_imports/`.
They cover multiple validation scenarios:

* **Orders** – validates `in`, `exists`, and `required` rules
* **Inventory** – tests numeric and date validation
* **Suppliers** – tests `unique` and `email` validation

Each import job logs skipped rows and audit entries for updates.

---

## 🧠 Validation Rules Supported

| Rule                  | Description                              |
| --------------------- | ---------------------------------------- |
| `required`            | Value cannot be empty                    |
| `unique:table,column` | Must not exist in specified table/column |
| `exists:table,column` | Must exist in specified table/column     |
| `in:val1,val2,...`    | Must be one of defined values            |
| `email`               | Must be valid email format               |
| `nullable`            | Can be empty                             |
| `min:0`               | Must be greater than or equal to 0       |

Rows that fail validation are skipped and logged in `import_logs` with details:

* Import type
* Row number
* Column name
* Invalid value
* Validation message

---

## 🧾 Audit System

Whenever an existing row is updated (based on `update_or_create` keys):

* The change is recorded in `import_audits` table
* Logged with:

  * Import type
  * Row ID
  * Column name
  * Old and new values

---

## 🖥️ Running the Project

```bash
composer install
php artisan migrate --seed
php artisan serve
```

Visit: [http://127.0.0.1:8000](http://127.0.0.1:8000)

---

## 🧱 AdminLTE 2 Assets

Static files are located in `public/adminlte/`:

```
dist/
plugins/
favicon.ico
```

---

## 📬 Queue Worker

Imports are processed asynchronously:

```bash
php artisan queue:work
```

If an import fails, an email notification is sent (currently logged via `MAIL_MAILER=log`).

---

## 🧑‍💻 Author

**Vladimir Josić**
Laravel Developer / PHP Engineer

---

*Project created as part of the MassData OOP & Laravel import test.*
