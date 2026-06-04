# Headless Multitenant CMS & E-Commerce Core v2

This project is a decoupled multitenant CMS and e-commerce foundation built using Laravel, Spatie Multitenancy, Laravel Cashier (Stripe), and Filament v3.

---

## 🛠️ Getting Started & Setup Guide

Follow these steps to completely reset your database and boot up the system from scratch.

### 1. Reset the Docker Databases
If you want to clear all existing tables, roles, users, and dynamic tenant databases, destroy your Docker volumes and restart the services.

Run this command in your host terminal:
```bash
docker-compose down -v && docker-compose up -d
```
> [!IMPORTANT]
> The `-v` flag deletes all persistent Docker volumes, resetting the MySQL and Redis containers to their pristine, original states.

---

### 2. Verify `.env` Configuration
Ensure your `.env` contains the correct database configuration matching the Docker services:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=monorepo
DB_USERNAME=monorepo
DB_PASSWORD=secret

# Landlord Database Settings
DB_LANDLORD_DATABASE=monorepo
```

---

### 3. Run Standard Migrations
Run the base migrations. This will create the base host tables and the core package's landlord tables (such as `tenants` and `domains` loaded from `CoreServiceProvider`).

Inside your workspace container (or using PHP inside WSL):
```bash
php artisan migrate
```

---

### 4. Run Landlord Installation Command
Run the installation command to initialize roles, default permissions, and register a platform administrator.

```bash
php artisan landlord:install
```

#### What this command does:
1. **Dynamic Spatie Migrations**: Evaluates and runs Spatie Permission migrations on-the-fly inside the landlord database context (without publishing files to your host repository).
2. **Role & Permission Seeding**: Seeds the `Platform Admin` role.
3. **Admin Provisioning**: Prompts you for a name, email, and password to create the initial Super Admin user.

---

### 5. Correct Nginx Web Root (If applicable)
Ensure that the Nginx configuration is pointed to your project's `public/` directory rather than the nested workspace path. 

In your `docker/nginx/default.conf` file, check the `root` directive:
```nginx
root /var/www/public;
```

If you modified this config, make sure to restart Nginx:
```bash
docker-compose restart nginx
```

---

### 6. Access the Landlord Control Panel
Once you've run the installation, you can log in to the admin panel using the credentials you generated in Step 4.

*   **URL**: `http://localhost/landlord`
*   **Login**: Uses the `landlord` auth guard, which resolves to `App\Models\LandlordUser`.
