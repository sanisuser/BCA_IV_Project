# Goal
Use this workflow to quickly understand the full request flow of the BookHub PHP project (frontend browsing, authentication, cart/checkout/orders, admin panel, and backups), and to know **which files to open first** when debugging or adding features.

# Big picture architecture
- **Type**: Traditional PHP app (no framework routing). Each page is a `.php` file that is requested directly (e.g. `/page/book.php?id=123`).
- **Routing**: File-path based. Navigation links are hardcoded in templates (mainly `includes/header_navbar.php`).
- **Shared services**:
  - **Session + helpers**: `includes/functions.php`
  - **DB connection (mysqli)**: `includes/db.php` (`$conn`)
  - **Layout**: `includes/header_navbar.php`, `includes/footer.php`
- **Static assets**:
  - CSS: `assets/css/`, plus feature CSS in each feature folder
  - JS: `includes/js/`, plus feature JS in each feature folder
  - Images: `assets/images/...`

# Core invariants (important when tracing bugs)
- Most pages assume `SITE_URL` is `''` (empty string). URLs are built as `SITE_URL . '/path/file.php'`.
- Session is started in multiple places:
  - `includes/functions.php` starts a session if needed.
  - `includes/header_navbar.php` also starts a session if needed.
- DB connection is always the global `$conn` from `includes/db.php`.

# 1) Identify the entry point you are working on
Pick the feature area first, then follow the relevant flow below.

## Public homepage
- **URL**: `/index.php`
- **Files**:
  - `index.php` includes:
    - `includes/functions.php`
    - `includes/db.php`
    - `includes/header_navbar.php`
    - `frontend/home.php`
    - `includes/footer.php`

## Browse books
- **List page**: `/page/booklist.php`
- **Details page**: `/page/book.php?id=<book_id>`
- **DB**:
  - Reads from `books` table (and wishlist join for logged-in users)

## Authentication
- **Login form**: `/auth/login.php` -> POST to `order_cart_process/process/login_process.php`
- **Register form**: `/auth/register.php` -> POST to `order_cart_process/process/register_process.php`
- **Logout**: `/auth/logout.php`
- **Forgot password**:
  - `/auth/forgot_password.php`
  - `/auth/forgot_password_process.php`
  - `/auth/reset_password.php`

## Cart / Checkout / Orders
- **Cart page**: `/order_cart_process/cart.php`
- **Checkout page**: `/order_cart_process/checkout.php`
- **Orders page**: `/order_cart_process/orders.php`
- **Processes**:
  - Cart actions: `order_cart_process/process/cart_process.php`
  - Place order: `order_cart_process/process/order_process.php`

## Admin
- **Admin dashboard**: `/admin/index.php`
- **Access checks**:
  - Uses `is_logged_in()` and `is_admin()` from `includes/functions.php`
  - Admin panel link is shown only if `is_admin_panel_access()` is true (session flag)

## Scheduled backups
- **Script**: `/cron_backup.php`
- **Implementation**: `includes/DatabaseBackup.php`
- **Output**: writes SQL backup files into `/backups/`

# 2) Understand the common include stack (most pages)
When a page renders HTML, it typically follows this pattern:
1. Set `$page_title`
2. `require_once __DIR__ . '/../includes/header_navbar.php'`
3. `require_once __DIR__ . '/../includes/db.php'` (sometimes header pulls db if logged in)
4. Page-specific DB queries + HTML
5. `require_once __DIR__ . '/../includes/footer.php'`

# 3) Trace user state (session)
Open `includes/functions.php` and review:
- `is_logged_in()`
- `is_admin()`
- `is_admin_panel_access()`
- `get_user_id()`
- `redirect($url)`

The core session variables used:
- `$_SESSION['user_id']`
- `$_SESSION['role']` (expects `'admin'` for admins)
- `$_SESSION['admin_panel_access']` (must be `true` to show Admin Panel link)

# 4) Trace database access (mysqli)
Open `includes/db.php`:
- Creates `$conn = new mysqli(...)`

Typical pattern used throughout:
- `$stmt = $conn->prepare('...')`
- `$stmt->bind_param(...)`
- `$stmt->execute()`
- `$result = $stmt->get_result()`

# 5) Key end-to-end flows

## Flow A: Browse -> View book -> Add to cart
1. User opens `/page/booklist.php`
2. User opens `/page/book.php?id=...`
3. Add-to-cart action calls:
   - `/order_cart_process/process/cart_process.php?action=add&id=<book_id>`
4. `cart_process.php`:
   - Verifies login
   - Checks stock from `books`
   - Inserts/updates row in `cart`
   - Redirects to `/order_cart_process/cart.php`

## Flow B: Cart -> Checkout -> Place order
1. User opens `/order_cart_process/cart.php`
2. User opens `/order_cart_process/checkout.php`
   - Loads profile shipping address from `users.ship_address`
   - Loads cart items from `cart` join `books`
3. User submits checkout form to:
   - `/order_cart_process/process/order_process.php` with `POST action=place`
4. `order_process.php`:
   - Starts transaction
   - Locks cart items (`FOR UPDATE`)
   - Validates stock
   - Computes VAT (13%)
   - Inserts into `orders`
     - uses `address_id` if a saved address was selected
     - otherwise stores `ship_address`
   - Inserts into `order_items`
   - Decrements `books.stock`
   - Clears `cart`
   - Commits and redirects to `/order_cart_process/orders.php`

## Flow C: Login -> session -> navigation changes
1. User opens `/auth/login.php`
2. Submits to `/order_cart_process/process/login_process.php`
3. On success, session is set (at least `user_id`, and sometimes `role`)
4. `includes/header_navbar.php` reads session and:
   - Fetches basic user info (`username/full_name/profile_image`)
   - Shows links: Wishlist/Cart/Orders
   - Shows Admin Panel link only if `is_admin_panel_access()`

## Flow D: Admin -> dashboard
1. Admin opens `/admin/index.php`
2. File checks:
   - `is_logged_in()`
   - `is_admin()`
3. Loads stats and admin sections.

# 6) How to debug (fast checklist)
- If you see **"headers already sent"**:
  - check for stray output before redirects; note `/page/book.php` uses `ob_start()`.
- If login state doesn’t stick:
  - open `order_cart_process/process/login_process.php` and verify it sets `$_SESSION['user_id']`.
- If admin link doesn’t show:
  - confirm `$_SESSION['role']=='admin'` and `$_SESSION['admin_panel_access']===true`.
- If cart/checkout has issues:
  - trace `cart_process.php` and `order_process.php`.

# 7) Where to start reading code (recommended order)
1. `includes/functions.php`
2. `includes/header_navbar.php`
3. `includes/db.php`
4. `index.php` + `frontend/home.php`
5. `/page/booklist.php` and `/page/book.php`
6. `/order_cart_process/process/cart_process.php` and `/order_cart_process/process/order_process.php`
7. `/admin/index.php`
8. `/cron_backup.php` + `includes/DatabaseBackup.php`
