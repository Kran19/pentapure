# Pentapure ERP/CRM - Architecture & Business Analysis

## 1. Executive Summary
Pentapure is a comprehensive, monolithic ERP and CRM application built on Laravel 11/12 (PHP 8.2+). It serves as the core operational backbone for a business that manages raw material procurement, manufacturing (semi-finished and finished goods), inventory tracking, sales, dispatching, cash management, and employee attendance. The system is heavily role-based, segmenting operations among various user roles (Admin, Raw, Semi, Finished, Sales, Dispatch, Cashier, Attendance) to mimic the real-world departmental flow of a manufacturing and trading business.

## 2. Complete Project Architecture
**Framework:** Laravel (PHP 8.2+)
**Architecture Pattern:** Monolithic MVC (Model-View-Controller).
The architecture relies primarily on standard Laravel conventions without heavy abstractions like Repository or Service layers.
- **Controllers:** Controllers are fat, housing business logic, validation, and direct database aggregation queries.
- **Models:** Eloquent ORM is used with relationships, basic accessors, mutators, and some local scopes. The system relies heavily on model events (Observers/Booted methods) to trigger notifications (e.g., dispatch alerts, cash alerts).
- **Views:** Blade templating engine with modular folders mapping to user roles (`resources/views/admin`, `raw`, `sales`, etc.).
- **Middleware:** A custom `auth.role` middleware provides Role-Based Access Control (RBAC).
- **Frontend Assets:** Managed via Vite, utilizing Vanilla JS, jQuery, and AJAX/Axios for interactive forms (like dynamic order items and stock addition).
- **Notifications:** Web Push Notifications (`laravel-notification-channels/webpush`) integrated directly into Eloquent events.
- **PDF Generation:** Extensive use of `barryvdh/laravel-dompdf` for standard reports and `setasign/fpdi` for merging dynamic content with uploaded user documents (e.g., attaching expense bills to a cashier statement).

## 3. Database Overview
The database is highly relational, tracking every movement of goods and cash.

**Core Entities & Business Purpose:**
- `users`: System users. Includes custom fields like `role`, `parent_id` (hierarchy), `branch`, `permissions` (JSON), and `visible_cashiers` (JSON for cross-cashier visibility).
- `products`: Defines inventory items. `type` (`RAW`, `FINISHED`) dictates where it's used. `threshold` triggers low stock alerts.
- `grades`: Qualities/variants of products. Associated via pivot `grade_product`.
- `stocks`: The central inventory ledger. Every movement is an `IN` or `OUT` transaction attached to a `stage` (`RAW`, `SEMI`, `FINISHED`), `grade`, and `location_id`.
- `locations`: Physical or logical warehouses.
- `stock_limits`: Custom low-stock alert thresholds per product+stage+grade.
- `orders` & `order_items`: Sales orders placed for customers (`companies`).
- `dispatch_logs`, `dispatch_log_items`, `dispatch_item_locations`: Tracks the physical loading and shipping of `orders` via `transporters`, including Proof of Delivery (LR images).
- `companies`: Customers purchasing finished goods.
- `transporters`: Shipping companies used for dispatch.
- `purchase_orders`: Internal requests from production staff to the Admin for raw material procurement.
- `production_logs` & `production_log_inputs`: Tracks manufacturing. Inputs (Raw/Semi) are consumed, and Outputs (Semi/Finished) are generated.
- `transactions` & `transaction_bills` & `transaction_logs`: Cashier ledger. Tracks cash `IN` / `OUT`, categorized by expense `categories`. Uploaded images/PDFs are saved as bills.
- `workers`, `departments`, `attendances`: HR module tracking daily employee presence and wages.

## 4. Inventory Workflow
Pentapure utilizes a ledger-based inventory system. Net stock is never stored as a static column; it is always calculated dynamically as `SUM(IN) - SUM(OUT)` per product, stage, grade, and location.

1. **Inward:** Raw materials arrive and are entered by the `RAW` role (or Admin), creating `IN` transactions in `stocks` at the `RAW` stage.
2. **Transfer/Deduction:** When materials are moved or used, an `OUT` transaction is recorded.
3. **Location Management:** Stock exists at specific `locations`. Dispatches and deductions pull from available locations dynamically using FIFO or fallback to the 'Main Warehouse'.
4. **Limits & Alerts:** The dashboard calculates live stock and warns the Admin if quantities fall below product thresholds or custom `stock_limits`.

## 5. Purchase Workflow
Since regular production staff cannot purchase externally, they use the system to request materials:
1. `RAW`, `SEMI`, or `FINISHED` user identifies a shortage.
2. They generate a `PurchaseOrder` in the system for a specific product and quantity.
3. Status is `PENDING`.
4. `ADMIN` reviews the PO on their dashboard.
5. `ADMIN` approves the PO (Changes status to `DONE`). (Note: Actual supplier billing integration seems to be handled outside this specific PO loop or recorded manually as stock adjustments).

## 6. Sales Workflow
1. `SALES` user creates an `Order`.
2. They select a `Company` (Customer) and a `Transporter`.
3. They add `OrderItems` (Product, Grade, Quantity, Price).
4. The system validates if the user has access to sell these products based on `allowed_roles`.
5. The Order is saved with `status = OPEN` and `dispatch_status = PENDING`.
6. The `DISPATCH` team sees the pending order, physically loads the goods, captures the Lorry Receipt (LR), and logs a `DispatchLog`.
7. Stock is automatically deducted (`OUT`) from `FINISHED` stage.
8. Order `dispatch_status` updates to `DONE`.

## 7. Cashier Workflow
1. `CASHIER` logs into their portal.
2. They record cash movements (`IN` for receipts, `OUT` for expenses).
3. They categorize the expense (e.g., Petty Cash, Fuel, Maintenance) using `categories`.
4. They can upload supporting documents (Images/PDFs of receipts) which are stored in `transaction_bills`.
5. Model events instantly fire a Web Push Notification to the `ADMIN` alerting them of the cash movement.
6. Cashiers can download a generated PDF ledger statement. The system uses DomPDF for the statement table, and FPDI to automatically append/merge the uploaded receipt images and PDFs to the end of the statement document.

## 8. Business Process Flow (Lifecycle)
**Example Daily Lifecycle:**
- **Morning:** `ATTENDANCE` role marks `workers` as present. `ADMIN` reviews dashboards.
- **Procurement:** `RAW` user receives a supplier truck, enters stock `IN` to the warehouse.
- **Production:** `FINISHED` role consumes 50kg of Raw Material X (`OUT` from `RAW` stock) to produce 45kg of Finished Good Y (`IN` to `FINISHED` stock) and logs it in `ProductionLog`.
- **Sales:** `SALES` rep gets a call from a client, places an `Order` for 45kg of Good Y.
- **Dispatch:** `DISPATCH` team sees the order, loads it onto a `Transporter` truck, deducts the `FINISHED` stock, and uploads the shipping receipt.
- **Finance:** `CASHIER` pays the transporter in cash, logs an `OUT` transaction, and uploads a photo of the receipt. `ADMIN` gets a real-time notification on their phone.

## 9. Module Breakdown
- **Admin Module:** Complete oversight. Manage users, products, global stock adjustments, view all logs, approve POs, and view cash flow across all branches.
- **Raw Module:** Manage raw material intake and view raw stock ledger.
- **Semi & Finished Modules:** Production departments. They request raw materials and record manufacturing outputs.
- **Sales Module:** CRM and Order generation. Manage Companies (customers) and generate sales orders.
- **Dispatch Module:** Fulfillment. Fulfill open sales orders and manage stock leaving the warehouse.
- **Cashier Module:** Financial ledger. Log daily cash operations, attach proofs, and generate end of day reports.
- **Attendance Module:** HR. Manage departments, workers, daily attendance, and calculate basic wages based on fixed/daily salaries.

## 10. Route Map
- `GET /` -> Redirects to login.
- `GET/POST /login`, `POST /logout` -> Authentication.
- `GET /api/notifications/*` -> Web Push Notification management.
- `RAW Routes (/raw/*)` -> `home`, `action` (Inward form), `po` (Purchase Orders), `history`.
- `SEMI Routes (/semi/*)` -> `home`, `action` (Production form), `history`.
- `FINISHED Routes (/finished/*)` -> Similar to semi, handles final product creation.
- `SALES Routes (/sales/*)` -> `home`, `action` (Order creation), `history`.
- `DISPATCH Routes (/dispatch/*)` -> `home`, `action` (Fulfill orders), `history`.
- `CASHIER Routes (/cashier/*)` -> `home`, `action` (Log transactions), `ledger`, `/history/pdf` (Generate FPDI merged reports).
- `ADMIN Routes (/admin/*)` -> `dashboard`, `users`, `products`, `stock`, `po`, `logs`, `cashier-logs`, `grades`, `dispatch-activity`.
- `ATTENDANCE Routes (/attendance/*)` -> `dashboard`, `departments`, `workers`, `daily`, `reports`.

## 11. Controller Responsibilities
- **AuthController:** Login/Logout session management.
- **AdminController:** A massive controller handling dashboard statistics, user management, product CRUD, global stock manipulation, PO approvals, and global activity logs. Contains heavy SQL aggregations for dashboard metrics.
- **Raw/Semi/FinishedControllers:** Manage views and form submissions specific to their department's stock additions and production logs.
- **SalesController:** Handles Customer (Company) CRUD, Transporter CRUD, and complex Order generation/updating with validation against available stock and allowed roles.
- **DispatchController:** Handles marking orders as dispatched, capturing LR images, and executing the actual stock deduction logic.
- **CashierController:** Manages financial transactions. Notably handles file uploads (receipts) and complex PDF merging using `setasign/fpdi` to combine statements with uploaded images.
- **AttendanceController:** Manages worker profiles and daily presence arrays.
- **HistoryPdfController:** Shared utility to generate tabular PDF reports of stock history.

## 12. Model Relationships
- **User:** `hasMany` (Stocks, ProductionLogs, Transactions, Orders, DispatchLogs, PurchaseOrders). `belongsTo` (User as parent).
- **Product:** `belongsToMany` (Grades). `hasMany` (Stocks, ProductionLogs, ProductionInputs, OrderItems).
- **Order:** `belongsTo` (User, Company, Transporter). `hasMany` (OrderItems). `hasOne` (DispatchLog).
- **Transaction:** `belongsTo` (User). `hasMany` (TransactionBill).
- **ProductionLog:** `belongsTo` (User, Product). `hasMany` (ProductionLogInput).

## 13. Risks & Technical Debt
1. **Fat Controllers:** Controllers like `AdminController` and `CashierController` are thousands of lines long, mixing business logic, complex PDF generation, file handling, and heavy DB queries.
2. **Dynamic Stock Calculation (`SUM(IN) - SUM(OUT)`):** While perfectly accurate, calculating net stock by aggregating every single transaction in history on every page load will cause severe performance degradation as the database grows.
3. **Lack of FormRequests:** Validation logic is coupled tightly inside the controller methods, making them longer and harder to test.
4. **Hardcoded Role Logic:** The application relies heavily on hardcoded string checks (`if ($role === 'ADMIN')`) scattered across routes, middleware, models, and controllers.
5. **No Service/Repository Layer:** Controllers directly interact with Eloquent and DB facades.
6. **PDF Generation Blocking:** Complex PDF generation (especially merging multiple images via FPDI) happens synchronously, which could lead to HTTP timeouts if a cashier has uploaded dozens of large images for a month's ledger.

## 14. Suggestions (Without Implementing)
1. **Implement Stock Snapshots:** Run a nightly cron job to calculate closing stock balances and store them in a static table. Queries should only aggregate transactions from the last snapshot date onward.
2. **Extract Service Classes:** Move PDF generation (`generateCashierPdf`), Order processing, and Stock deduction logic into dedicated Service classes (e.g., `OrderService`, `PdfReportService`).
3. **Use FormRequests:** Move all `$request->validate(...)` arrays into dedicated FormRequest classes to clean up controllers.
4. **Queued Jobs for PDFs:** For large reports, dispatch a job to generate the PDF and notify the user via the existing Web Push system when it's ready for download.
5. **Database Indexing:** Ensure compound indexes exist on `stocks (product_id, stage, grade)` and `transactions (user_id, created_at)` as these are heavily queried for sums.

## 15. Questions that should be clarified before future development
1. **Stock Aggregation Scaling:** How many transactions does the system expect per month? If high, we must implement caching or stock snapshotting immediately before `SUM(IN) - SUM(OUT)` queries crash the dashboard.
2. **Bill of Materials (BOM):** Does manufacturing have fixed formulas? Currently, users manually enter input quantities for production. A BOM system could automate this.
3. **Multi-Branch Isolation:** Is there a strict requirement that different branches cannot see each other's data? Currently, `visible_cashiers` handles finance isolation, but inventory seems mostly global (restricted only by `allowed_roles` and physical `location`).
4. **Supplier Billing:** Purchase Orders only exist as internal requests. Does the system need to track Supplier Invoices, Accounts Payable, and Payments to Suppliers?
5. **Production Roles:** The system has distinct `SEMI` and `FINISHED` roles. Can a single worker handle both, or are they strictly segregated in the physical plant?
