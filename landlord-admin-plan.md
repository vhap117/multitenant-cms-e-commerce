# Landlord Domain Implementation Plan

## Goal
Build the Landlord domain in the main Laravel app utilizing the `my-vendor/core` package. This involves creating a rapid Admin UI using Filament and exposing all Landlord functionalities via a REST API.

> [!IMPORTANT]
> **User Review Required**: Please review the proposed architecture and answer the open questions before we begin implementation.

## Open Questions

> [!WARNING]
> 1. **API Authentication**: Which authentication package do you prefer for the REST API (e.g., Laravel Sanctum or Laravel Passport)? Sanctum is usually sufficient unless OAuth2 features are strictly required.
> 2. **Permissions UI**: Should we build custom Filament resources for Spatie Roles & Permissions, or use an existing Filament plugin (e.g., `althinect/filament-spatie-roles-permissions`)?
> 3. **API Scope**: Is the REST API intended to be consumed by a separate frontend SPA for the Landlord, or by 3rd-party services?

## Proposed Changes

### 1. Main App Configuration & Dependencies
- Require `my-vendor/core` via Composer.
- Install and configure `filament/filament`.
- Install API authentication package (e.g., `laravel/sanctum`).

#### [MODIFY] [composer.json](file:///Ubuntu/home/victor/Projects/headless-multitenant-cms-ecommerce-v2/composer.json)
#### [MODIFY] [config/app.php](file:///Ubuntu/home/victor/Projects/headless-multitenant-cms-ecommerce-v2/config/app.php)

---

### 2. Filament Admin Panel (Landlord UI)
- Register a new Filament Panel named `landlord`.
- Create the following resources to manage core models:
  - `TenantResource`: CRUD operations for `VHAP\Core\Models\Tenant`. Include actions to trigger core processes (e.g., suspend, run migrations).
  - `LandlordUserResource`: Manage landlord administrators (`VHAP\Core\Models\LandlordUser`).
  - `RoleResource` & `PermissionResource`: Manage Spatie permissions on the `landlord` guard.

#### [NEW] [app/Providers/Filament/LandlordPanelProvider.php](file:///Ubuntu/home/victor/Projects/headless-multitenant-cms-ecommerce-v2/app/Providers/Filament/LandlordPanelProvider.php)
#### [NEW] [app/Filament/Landlord/Resources/TenantResource.php](file:///Ubuntu/home/victor/Projects/headless-multitenant-cms-ecommerce-v2/app/Filament/Landlord/Resources/TenantResource.php)
#### [NEW] [app/Filament/Landlord/Resources/LandlordUserResource.php](file:///Ubuntu/home/victor/Projects/headless-multitenant-cms-ecommerce-v2/app/Filament/Landlord/Resources/LandlordUserResource.php)

---

### 3. REST API Implementation
- Define API routes under a `landlord` prefix in `routes/api.php` or a dedicated `routes/landlord.php` file.
- Implement Controllers to handle CRUD and custom actions, using `core` package actions.
- Map the REST API to use the same logic as Filament actions to keep business logic centralized.

#### [MODIFY] [routes/api.php](file:///Ubuntu/home/victor/Projects/headless-multitenant-cms-ecommerce-v2/routes/api.php)
#### [NEW] [app/Http/Controllers/Api/Landlord/TenantController.php](file:///Ubuntu/home/victor/Projects/headless-multitenant-cms-ecommerce-v2/app/Http/Controllers/Api/Landlord/TenantController.php)
#### [NEW] [app/Http/Controllers/Api/Landlord/LandlordUserController.php](file:///Ubuntu/home/victor/Projects/headless-multitenant-cms-ecommerce-v2/app/Http/Controllers/Api/Landlord/LandlordUserController.php)

---

### 4. Integration with `core` Actions
- Ensure Filament and API both trigger `my-vendor/core` package actions such as `SetupTenantAdmin`, `CreateTenantDatabase`, and billing actions when a tenant is created or updated.

## Verification Plan

### Automated Tests
- Create Pest or PHPUnit tests for the Landlord API endpoints to verify responses, authorization, and multi-tenancy safety.
- Write tests to ensure `core` actions are dispatched correctly upon API and Filament requests.

### Manual Verification
- Access the `/landlord` Filament route locally, authenticate, and successfully create a new Tenant via UI.
- Use an API client (like Postman/Insomnia) to authenticate and hit the Landlord API to create a Tenant, verifying the payload.
