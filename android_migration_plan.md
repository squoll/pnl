# Mobile App Preparation Plan (Refined)

## Executive Summary
The current application is a server-side rendered PHP application where logic and presentation are tightly coupled. To support a compelling Android application (Native or Hybrid), we must decouple the business logic into a JSON API and implementing a mobile-first UI/UX strategy.

## Strategic Goals
1.  **Decoupling**: Move logic from `pages/*.php` to reusable Service Classes.
2.  **API Creation**: Build a RESTful API (`/api/v1/`) that consumes these Services.
3.  **Authentication**: Implement Token-Based Authentication (JWT) for stateless mobile access.
4.  **Mobile UX**: Upgrade form generic "mobile-friendly" to "native-like" experience.

## Implementation Steps

### Phase 1: Service Layer Extraction
*Goal: Centralize logic so both Web and API can use it.*

#### [NEW] `includes/Database.php`
- Singleton PDO wrapper (better than global `$conn`).

#### [NEW] `includes/Services/`
- **`AuthService.php`**: Login, Logout, Session validation (Logic from `login.php`).
- **`ClientService.php`**: CRUD operations for Clients (Logic from `tv_clients.php`).
- **`ProviderService.php`**: CRUD for Providers.
- **`StatsService.php`**: Dashboard statistics calculation.

### Phase 2: REST API Implementation
*Goal: Provide JSON endpoints for the Mobile App.*

#### [NEW] `api/index.php` (Router)
- Central entry point handling routing (e.g., `GET /api/clients`, `POST /api/login`).

#### [NEW] `api/Controllers/`
- **`AuthController.php`**: Handles JSON input/output for auth.
- **`ClientController.php`**: Handles JSON input/output for clients.

### Phase 3: Android App Features Support
*Goal: Enable mobile-specific capabilities.*

- **Push Notifications**: Store FCM tokens in `tv_users` or new table.
- **Offline Sync**: Add `updated_at` timestamps to all tables to allow efficient sync.

### Phase 4: Mobile UI/UX Redesign
*Goal: Create a native-feeling experience for Android.*

- **Navigation**:
    - Replace Hamburger Menu with **Bottom Navigation Bar** for primary actions (Clients, Map, Stats, Settings).
    - Use Tab Layouts for sub-views (e.g., Inside Client details: Info | Subscription | Logs).

- **Interactions**:
    - **Swipe Actions**: Implement swipe-to-edit and swipe-to-delete for list items.
    - **Pull-to-Refresh**: Replace dedicated "Refresh" buttons with native pull gestures.
    - **Touch Targets**: Ensure all buttons and inputs meet the 48dp minimum touch target size.

- **Visual Feedback**:
    - **Skeleton Loaders**: Use shimmering skeletons instead of spinning loaders for smoother perceived performance.
    - **Toast Notifications**: Replace browser alerts (`alert()`) with non-blocking Toasts or Snackbars.

- **Offline UI**:
    - "No Connection" banners.
    - "Syncing..." indicators when connection is restored.

## Required Refactoring Example

**Current (`tv_clients.php`):**
```php
$stmt = $conn->query("SELECT * FROM tv_clients");
$clients = $stmt->fetchAll();
// HTML generation...
```

**Target Architecture:**
**Web (`tv_clients.php`):**
```php
$clients = $clientService->getAllClients();
// HTML generation...
```

**API (`api/clients.php`):**
```php
$clients = $clientService->getAllClients();
echo json_encode($clients);
```

## Next Steps
1.  Approve this architectural shift.
2.  Begin with **Phase 1**: creating `AuthService` and `ClientService`.
