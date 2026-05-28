# Core Package Features Summary

This document outlines all the features successfully built into the core package so far, categorized by feature domain, including a comprehensive list of the specific classes involved in orchestrating them.

## 1. Landlord Setup Pipeline
Handles the initial installation and bootstrapping of the overarching Landlord environment (central database, core admin user, and Spatie roles).

**Actions & Commands**
- `VHAP\Core\Console\Commands\InstallLandlordCommand`
- `VHAP\Core\Actions\InstallLandlordAction`

**Pipes**
- `VHAP\Core\Actions\Pipes\LandlordSetup\CreateLandlordDatabase`
- `VHAP\Core\Actions\Pipes\LandlordSetup\RunLandlordMigrations`
- `VHAP\Core\Actions\Pipes\LandlordSetup\SeedLandlordDefaultData`
- `VHAP\Core\Actions\Pipes\LandlordSetup\ProvisionPlatformAdmin`

**Provisioning Strategies**
- `VHAP\Core\Contracts\LandlordAdminProvisioner` (Contract)
- `VHAP\Core\Provisioners\DefaultLandlordAdminProvisioner`

---

## 2. Tenant Provisioning Pipeline
Handles the complete creation of a new Tenant, from physical database generation and migrations to seeding their default Spatie roles and administrative user.

**Actions**
- `VHAP\Core\Actions\ProvisionNewTenantAction`

**Pipes**
- `VHAP\Core\Actions\Pipes\Provision\CreateTenantDatabase`
- `VHAP\Core\Actions\Pipes\Provision\RunTenantMigrations`
- `VHAP\Core\Actions\Pipes\Provision\SeedTenantDefaultData`
- `VHAP\Core\Actions\Pipes\Provision\SetupTenantAdmin`

**Provisioning Strategies**
- `VHAP\Core\Contracts\TenantAdminProvisioner` (Contract)
- `VHAP\Core\Provisioners\DefaultTenantAdminProvisioner`

**Events & Listeners**
- `VHAP\Core\Events\TenantProvisioned`
- `VHAP\Core\Listeners\ProvisionTenantAdminListener`

---

## 3. Tenant Lifecycle Management
Orchestrates the suspension, reactivation, and permanent destruction of a tenant's environment using the Pipeline pattern.

**Actions**
- `VHAP\Core\Actions\DestroyTenantEnvironmentAction`
- `VHAP\Core\Actions\SuspendTenantAction`
- `VHAP\Core\Actions\ReactivateTenantAction`

**Pipes (Destruction)**
- `VHAP\Core\Actions\Pipes\Destruction\DeleteTenantRecord`
- `VHAP\Core\Actions\Pipes\Destruction\DeleteTenantStorageDirectory`
- `VHAP\Core\Actions\Pipes\Destruction\DropTenantDatabase`

**Pipes (Suspension)**
- `VHAP\Core\Actions\Pipes\Suspension\DeactivateTenantRecord`
- `VHAP\Core\Actions\Pipes\Suspension\DispatchSuspensionNotification`
- `VHAP\Core\Actions\Pipes\Suspension\TerminateTenantSessions`

**Pipes (Reactivation)**
- `VHAP\Core\Actions\Pipes\Reactivation\ClearTenantCache`
- `VHAP\Core\Actions\Pipes\Reactivation\DispatchReactivationEmail`
- `VHAP\Core\Actions\Pipes\Reactivation\MarkTenantActiveRecord`

---

## 4. Tenant Domain & Routing Management
Manages updates to tenant domains and dynamically updates the Laravel URL configuration when a tenant is made current.

**Actions**
- `VHAP\Core\Actions\UpdateTenantDomainAction`

**Pipes**
- `VHAP\Core\Actions\Pipes\Domain\UpdateTenantRecord`
- `VHAP\Core\Actions\Pipes\Domain\UpdateWebserverConfig`
- `VHAP\Core\Actions\Pipes\Domain\ValidateDomainAvailability`

**Events & Listeners**
- `VHAP\Core\Listeners\ConfigureTenantUrlListener` (Listens to Spatie's `MadeTenantCurrentEvent`)

---

## 5. Database Strategy Pattern
Abstracts the physical database creation process, allowing the package to seamlessly switch between MySQL and SQLite (essential for fast local testing).

**Landlord Strategies**
- `VHAP\Core\Contracts\LandlordDatabaseCreator` (Contract)
- `VHAP\Core\Database\MysqlLandlordDatabaseCreator`
- `VHAP\Core\Database\SqliteLandlordDatabaseCreator`

**Tenant Strategies**
- `VHAP\Core\Contracts\TenantDatabaseCreator` (Contract)
- `VHAP\Core\Database\MysqlTenantDatabaseCreator`
- `VHAP\Core\Database\SqliteTenantDatabaseCreator`

---

## 6. Authentication, Authorization & Core Models
Custom Eloquent models and Service Providers configured to dynamically resolve their database connections, enabling seamless dual-database capabilities for Spatie Permissions.

**Models**
- `VHAP\Core\Models\Tenant`
- `VHAP\Core\Models\User` (Tenant)
- `VHAP\Core\Models\LandlordUser`
- `VHAP\Core\Models\Role` (Dynamic Spatie Role Override)
- `VHAP\Core\Models\Permission` (Dynamic Spatie Permission Override)

**Service Providers**
- `VHAP\Core\CoreServiceProvider` (Dynamically registers the `landlord` authentication guard and binds Strategy interfaces)