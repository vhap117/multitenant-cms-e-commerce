# Landlord Admin Application Proposal

## 1. Technology Stack Recommendation

**Recommended Stack:** **Laravel Filament** (TALL Stack: Tailwind CSS, Alpine.js, Laravel, Livewire)

**Rationale:**
The backend logic is heavily reliant on Laravel's core features—Eloquent state machines, Spatie Multitenancy, Laravel Cashier, and Pipeline-driven asynchronous workflows. Adopting Laravel Filament as the primary interface for Platform Admins (LandlordUsers) provides several massive advantages:
- **Native Eloquent Integration:** Directly manipulates the `Tenant`, `LandlordUser`, and RBAC models on the `landlord` database connection without needing an intermediary REST or GraphQL API.
- **Rapid UI Construction:** Delivers a premium, highly interactive SPA-like experience using Livewire, with hundreds of pre-built, beautifully designed UI components (Tables, Forms, Widgets).
- **Pipeline & Job Synchronization:** Filament's native notification and job monitoring system pairs flawlessly with the `provisioning_status` lifecycle, easily polling and updating the UI when a `BuildTenantEnvironmentJob` completes.
- **Unified Security Model:** Easily restricts access using the existing `landlord` authentication guard and Spatie Roles & Permissions.

*Alternative Considerations:* A Next.js SPA or Vue Inertia application could be used. However, replicating the deep domain validation and routing logic in a separate JavaScript frontend would introduce significant overhead and duplicate the state machine logic already cleanly encapsulated in Laravel.

---

## 2. Information Architecture (Sitemap/Navigation)

The Admin Application will be structured around managing the global lifecycle of the platform.

```mermaid
mindmap
  root((Landlord Panel))
    Dashboard
      Global Metrics
      Recent Activity
    Tenant Management
      Tenant Directory
      Provision Wizard
      Tenant Details
    Billing & Subscriptions
      Plans Overview
      Stripe Invoices
    Platform Settings
      Landlord Users
      Roles & Permissions
      Job Monitor
```

### Primary Navigation Nodes
1. **Dashboard:** High-level overview of system health.
2. **Tenants:** The core directory for searching, filtering, and managing tenant lifecycles.
3. **Billing:** Stripe integration views for overriding plans or managing disputes.
4. **Access Control:** Managing `LandlordUser` accounts and Spatie roles.

---

## 3. Key Views & Domain Logic Mapping

The UI screens will map one-to-one with the actions defined in `ARCHITECTURE.md`.

### A. Global Dashboard
- **Visuals:** Top row of metric cards (Total Tenants, Active Tenants, MRR). A prominent data chart showing tenant acquisition over time.
- **Functionality:** Highlights tenants that are currently in `pending` or `provisioning` states. If a tenant has a `failed` provisioning status, it surfaces an alert to the admin to investigate the pipeline failure.

### B. Tenant Directory
- **Visuals:** A comprehensive data table.
- **Filters:** By `TenantPlan` (FREE, PRO, ENTERPRISE), Operational Status (`is_active`), and `provisioning_status`.
- **Backend Mapping:** 
  - **Suspend Action:** A bulk or row-level action triggering the `SuspendTenantAction` (Executes the `DeactivateTenantRecord` -> `TerminateTenantSessions` pipeline).
  - **Reactivate Action:** Triggers the `ReactivateTenantAction`.
  - **Destroy Action:** Placed behind a strict confirmation modal. Triggers the `DestroyTenantEnvironmentAction` (Pipeline: `DeleteTenantRecord` -> `DeleteTenantStorageDirectory` -> `DropTenantDatabase`).

### C. Tenant Provisioning Wizard (Create Form)
- **Visuals:** A sleek, step-by-step wizard.
- **Backend Mapping:** Instantiates a `ProvisionTenantData` DTO.
  - **Step 1:** Core Details (`name`, `email`, `plan`).
  - **Step 2:** Technical Setup (`domain`, auto-generates `database` name).
  - **Step 3:** First Admin Account (`TenantAdminUserData` DTO capturing name, email, and temporary password).
- **Execution:** Submitting the wizard triggers the `ProvisionNewTenantAction`, pushing the model into `pending` state and dispatching the `BuildTenantEnvironmentJob`.

### D. View Tenant (Infolist)
- **Visuals:** A detailed resource view with tabs for configuration.
- **Domain Tab:** Provides a form to trigger the `UpdateTenantDomainAction` (Pipeline: `ValidateDomainAvailability` -> `UpdateTenantRecord` -> `UpdateWebserverConfig`).
- **Billing Tab:** Interfaces with the `BillingProvider` (Stripe Cashier) to show active subscriptions, past due invoices, and payment methods.

---

## 4. Visual Mockups

> [!NOTE]
> Below are structural wireframe representations of the key screens designed to convey the layout, user flow, and component hierarchy.

````carousel
### Screen 1: Main Tenant Dashboard
```mermaid
block-beta
  columns 4
  Menu["Sidebar Menu\n- Dashboard\n- Tenants\n- Billing\n- Settings"]:1
  space:3
  block:Metrics:3
    columns 3
    M1["Total Tenants\n1,204\n(+12%)"] M2["Active MRR\n$45,200\n(+5%)"] M3["Provisioning\n3 Pending"]
  end
  space:1
  block:Table:3
    columns 1
    THeader["Recent Tenants Directory"]
    Row1["Tenant: Acme Corp | Plan: PRO | Status: ACTIVE | Actions: [View] [Suspend]"]
    Row2["Tenant: Beta Store | Plan: FREE | Status: PROVISIONING | Actions: [View] [Cancel]"]
    Row3["Tenant: Charlie CMS| Plan: ENTERPRISE | Status: FAILED | Actions: [View] [Retry]"]
  end
  Menu -- "Navigate" --> Metrics
```
<!-- slide -->
### Screen 2: Tenant Provisioning Wizard
```mermaid
block-beta
  columns 1
  Header["Wizard: Provision New Tenant Environment"]
  block:Steps:1
    columns 3
    S1["1. Store Details\n(Active)"] S2["2. Tech Setup\n(Pending)"] S3["3. Admin User\n(Pending)"]
  end
  block:Form:1
    columns 2
    F1["Store Name:\n[               ]"] F2["Contact Email:\n[               ]"]
    P["Select Plan:\n(o) FREE   ( ) PRO   ( ) ENTERPRISE"] space
  end
  block:Footer:1
    columns 2
    Btn1["[ Cancel ]"] Btn2["[ Next Step -> ]"]
  end
```
<!-- slide -->
### Screen 3: Tenant Details & Danger Zone
```mermaid
block-beta
  columns 4
  Sidebar["Sidebar Menu\n- Dashboard\n- Tenants\n- Billing"]:1
  block:Content:3
    columns 1
    Title["Acme Corp - PRO Tier"]
    Tabs["[Overview] [Domains] [Billing] [Danger Zone]"]
    space
    Warning["Suspension Notice\nTenant is active. Suspending will force logout all users."]
    Action["[ Suspend Tenant ]"]
    space
    Danger["Destruction Zone\nRequires typing tenant name to confirm."]
    Destroy["[ Destroy Database & Storage ]"]
  end
```
````

### Action Plan
If this proposal is approved, the immediate next steps would be:
1. Initialize the Laravel Filament panel with the `landlord` auth guard.
2. Build the `TenantResource` reflecting the Directory and View logic.
3. Implement the `Provisioning Wizard` utilizing Filament's native form actions to construct the necessary `ProvisionTenantData` DTOs and dispatch the pipeline jobs.
