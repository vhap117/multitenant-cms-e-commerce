# Core Package Business Specifications

This document outlines the architecture, domain logic, and business specifications for the `packages/my-vendor/core` package based on the current implementation.

## 1. Domain Entities & Relationships

The package relies on distinct Eloquent models carefully separated between Landlord (Central) and Tenant databases.

*   **`Tenant` (`landlord` connection)**
    *   **Purpose:** Represents a single isolated customer environment (e.g., a store or CMS instance).
    *   **Key Traits/Interfaces:** Extends Spatie's `Tenant`, uses Laravel Cashier `Billable`, implements `BillableEntity`, and uses `SoftDeletes`.
    *   **Core Attributes:** `name`, `email`, `plan` (Enum), `domain`, `database`, `is_active` (boolean), `provisioning_status`, `stripe_id`.
    *   **Business Logic:** Handles unified subscription checks. If on a `FREE` plan, grants access directly. If on a paid plan, delegates the check to Stripe Cashier.
*   **`LandlordUser` (`landlord` connection)**
    *   **Purpose:** Platform administrators who manage the multi-tenant SaaS application from the central landlord application.
    *   **Key Traits:** `Authenticatable`, `HasRoles` (Spatie Permissions).
    *   **Authentication Guard:** `landlord`.
*   **`User` (`tenant` connection)**
    *   **Purpose:** End-users and store administrators that exist within a specific tenant's database.
    *   **Key Traits:** `Authenticatable`, `MustVerifyEmail`, `HasRoles` (Spatie Permissions).
    *   **Authentication Guard:** `web`.
*   **`Role` & `Permission` (Dynamic connection)**
    *   **Purpose:** RBAC models extending Spatie Permissions.
    *   **Architecture:** Dynamically resolves the database connection based on context, allowing identical permission logic to govern both Landlord and Tenant applications.

---

## 2. State Machines & Business Rules

### Tenant Plans (`TenantPlan` Enum)
Defines the available subscription tiers for tenants:
*   `FREE` (`'free'`): Free Tier
*   `PRO` (`'pro'`): Pro Plan (Stripe-backed)
*   `ENTERPRISE` (`'enterprise'`): Enterprise Tier

### Tenant Provisioning Lifecycle
Tracked via the `provisioning_status` field on the `Tenant` model:
1.  **`pending`**: Tenant record created, job dispatched to the queue.
2.  **`provisioning`**: Queue worker is actively building the database and migrating data.
3.  **`active`**: Environment is ready and usable.
4.  **`failed`**: An error occurred during pipeline execution; database is cleaned up.

### Operational Status
Tracked via the `is_active` boolean on the `Tenant` model. Distinguishes between tenants that are operational versus those that are suspended (due to manual intervention or payment failures).

---

## 3. Primary Workflows

The package uses Laravel Pipelines extensively to break down complex asynchronous workflows into discrete pipes.

### Tenant Provisioning Lifecycle
1.  **Trigger:** `ProvisionNewTenantAction` is called with `ProvisionTenantData` DTO.
2.  **Step 1:** Creates a `Tenant` model with status `pending`.
3.  **Step 2:** Dispatches `BuildTenantEnvironmentJob` to the queue.
4.  **Job Execution:**
    *   Updates status to `provisioning`.
    *   Runs Pipeline: `CreateTenantDatabase` → `RunTenantMigrations` → `SeedTenantDefaultData`.
    *   On Success: Sets status to `active`, clears secure provisioning data, and fires the `TenantProvisioned` event.
    *   On Failure: Reverts status to `failed` and drops the corrupted database.

### Tenant Destruction Lifecycle
1.  **Trigger:** `DestroyTenantEnvironmentAction`
2.  **Database Transaction (Landlord):**
    *   Runs Pipeline: `DeleteTenantRecord` → `DeleteTenantStorageDirectory`.
3.  **Post-Transaction:** Executes `DropTenantDatabase` directly against the database server to remove the tenant's isolated storage.

### Landlord Setup Lifecycle
1.  **Trigger:** `InstallLandlordAction`
2.  **Execution:** Runs a full pipeline to bootstrap the SaaS core:
    *   `CreateLandlordDatabase` → `RunLandlordMigrations` → `SeedLandlordDefaultData` → `ProvisionPlatformAdmin`.

### Tenant Suspension Lifecycle
1.  **Trigger:** `SuspendTenantAction` (Manual action or triggered by failed Stripe payment).
2.  **Execution:**
    *   Runs Pipeline: `DeactivateTenantRecord` (`is_active` = false) → `TerminateTenantSessions` (forces logout of tenant users) → `DispatchSuspensionNotification`.

### Tenant Reactivation Lifecycle
1.  **Trigger:** `ReactivateTenantAction` (Manual action or triggered by successful Stripe payment recovery).
2.  **Execution:**
    *   Runs Pipeline: `MarkTenantActiveRecord` (`is_active` = true) → `ClearTenantCache` → `DispatchReactivationEmail`.

### Tenant Domain Update Lifecycle
1.  **Trigger:** `UpdateTenantDomainAction`
2.  **Execution:**
    *   Runs Pipeline: `ValidateDomainAvailability` → `UpdateTenantRecord` → `UpdateWebserverConfig` (Provisioning the proxy/Nginx configuration).

---

## 4. Event-Driven Architecture

### Provisioning Events
*   **Event:** `TenantProvisioned` (Fired by `BuildTenantEnvironmentJob`)
    *   **Listener 1:** `ProvisionTenantAdminListener`
        *   Makes the tenant current.
        *   Uses `DefaultTenantAdminProvisioner` to create the `User` record inside the tenant DB.
        *   Assigns the `Super Admin` role to the user.
    *   **Listener 2:** `SendStoreReadyNotification`
        *   Generates a secure password reset token for the new admin.
        *   Dispatches the `StoreReadyEmail` notification directing the user to their new custom domain.

### Routing & Request Lifecycle Events
*   **Event:** `MadeTenantCurrentEvent` (Fired natively by Spatie Multitenancy)
    *   **Listener:** `ConfigureTenantUrlListener`
        *   Dynamically forces Laravel's root URL generator (`URL::forceRootUrl`) and `app.url` config to match the active tenant's domain, ensuring all generated links (emails, API responses) use the tenant's specific URL.

### Billing & Subscription Events
*   **Event:** `WebhookReceived` (Fired by Laravel Cashier)
    *   **Listener:** `StripeWebhookListener`
        *   Interprets Stripe payloads and maps them to Domain Actions.
        *   `invoice.payment_failed` → Triggers `SuspendTenantAction`.
        *   `invoice.payment_succeeded` → Triggers `ReactivateTenantAction`.
        *   `customer.subscription.deleted` → Triggers `DestroyTenantEnvironmentAction` (Immediate destruction upon cancellation).

---

## 5. Data Contracts (DTOs)

Strictly typed Data Transfer Objects are used to pass information into the core actions.

### `ProvisionTenantData`
The primary payload for creating a new tenant environment.
*   `name` (string): The display name of the tenant/store.
*   `email` (string): Primary contact email.
*   `domain` (string): The requested fully qualified domain name.
*   `database` (string): The generated isolated database name.
*   `plan` (`TenantPlan` Enum): Defaults to `FREE`.
*   `adminUser` (`TenantAdminUserData`|null): Optional nested DTO for the initial admin account.

### `TenantAdminUserData`
The payload for bootstrapping the tenant's first administrative user.
*   `name` (string): Admin's full name.
*   `email` (string): Admin's login email.
*   `password` (string): The raw password (usually a placeholder until they reset it via the welcome email).

---

## 6. Key Interface Contracts
The package heavily utilizes interfaces to allow for interchangeable implementations:
*   `BillableEntity`: Guarantees a model can interact with the `BillingProvider` (methods for retrieving email, name, and billing identifiers).
*   `BillingProvider`: An abstraction over Cashier/Stripe to allow swapping payment gateways.
*   `LandlordAdminProvisioner` / `TenantAdminProvisioner`: Abstractions for how users are created and assigned roles during environment bootstrapping.
