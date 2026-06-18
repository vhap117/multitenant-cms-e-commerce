# Host Admin Application Technical Specifications

## 1. Foundation & Framework
- **Technology Stack:** Laravel Filament (TALL Stack: Tailwind CSS, Alpine.js, Laravel, Livewire).
- **Authentication Guard:** `landlord` (using the `LandlordUser` model).
- **Core Responsibility:** Centralized management of the SaaS platform. Allows internal platform administrators to oversee tenants, billing, and system health by directly manipulating models on the central `landlord` database connection.

## 2. Information Architecture (Sitemap)
- **Dashboard (`Dashboard.php`):** Global metrics (Total MRR, Active Tenants, Pending Provisioning Jobs).
- **Tenants Directory (`TenantResource.php`):** Paginated table of all stores with quick filters.
  - **Provision Tenant Wizard:** Multi-step form action to create new stores.
  - **Tenant View:** Detailed read/manage page for a specific store.
- **Platform Access (`LandlordUserResource.php`):** Manage internal team members and assign Spatie permissions.

---

## 3. Detailed UI/UX Workflows & Component Mapping

### A. Tenant Provisioning Workflow (The Wizard)
Implemented as a **Filament Wizard Form Component** attached to the `CreateRecord` page of the `TenantResource`.
- **Step 1: Store Details**
  - Inputs: `name` (text), `email` (email), `plan` (select from `TenantPlan` enum).
- **Step 2: Technical Setup**
  - Inputs: `domain` (text, validates uniqueness against existing tenants). The `database` name should be auto-calculated based on a slugified version of the domain and hidden from the user unless manually overridden.
- **Step 3: Initial Admin Account**
  - Inputs: `admin_name` (text), `admin_email` (email), `admin_password` (password, with a "Generate Random" toggle button).
- **Submission Hook:** Intercepts the default Filament creation to instead hydrate the `ProvisionTenantData` DTO and manually execute the `ProvisionNewTenantAction`.

### B. Tenant Monitoring & Asynchronous Progress
- **Implementation:** Utilize Filament's native polling mechanism (`poll` method on the Table) set to refresh every 5-10 seconds.
- **Visuals:** Use colored Badge components in the table to represent the `provisioning_status` field:
  - `pending` ➔ Gray (Waiting in queue)
  - `provisioning` ➔ Yellow/Warning (Currently migrating/seeding)
  - `active` ➔ Green/Success (Ready for use)
  - `failed` ➔ Red/Danger (Pipeline crashed)

### C. Tenant Management & Critical Actions
Implemented as **Header Actions** on the `ViewRecord` page of the `TenantResource`.
- **Suspend Action:** Triggers a modal requiring a "Suspension Reason". Upon submission, calls `SuspendTenantAction` and fires a success notification.
- **Reactivate Action:** Simple confirmation modal. Calls `ReactivateTenantAction`.
- **Danger Zone Tab:** A specific tab inside the detailed view.
  - Contains the **Destroy Environment** action.
  - Uses Filament's `requiresConfirmation()` and a custom form input that forces the admin to type the exact `$tenant->name` to unlock the submit button. Executes `DestroyTenantEnvironmentAction`.

### D. Billing Integration (Stripe Cashier)
Integrated deeply into the `TenantResource` as a dedicated **Billing Tab**.
- **Subscriptions List:** Queries the `BillingProvider` to show active/canceled subscriptions.
  - Action: **Swap Plan** (Opens a modal with a dropdown of available Stripe Price IDs).
  - Action: **Cancel Subscription** (Executes `cancelSubscription`).
- **Invoices List:** Displays past invoices with a native action to open/download the Stripe PDF receipt.

---

## 4. Development Prerequisites
Before scaffolding this Filament panel, ensure:
1. The `LandlordUser` model implements Filament's `FilamentUser` interface to grant access.
2. The `BillingProvider` contract in the core package exposes the necessary methods to retrieve tabular invoice and subscription data for the Filament tables.
