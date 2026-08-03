# Pentapure ERP/CRM Master Adversarial Production Certification Matrix

**Git Commit Tested**: `34c3da90d0c9f708d62e828b62cea9227db67470`
**Target Environment**: MySQL (`pp_certification` database) & SQLite (`:memory:`)
**Scope**: All ERP/CRM modules (Attendance business logic frozen & excluded).

| Certification Domain | Test Vector / Target | Attack / Validation Scenario | Expected Result | SQLite Result | MySQL Result | Status |
|---|---|---|---|---|---|---|
| Test Count Audit | 36 Automated Tests | Re-audit test method count vs documentation | 36 tests / 137+ assertions | 36 PASS | 36 PASS | PASS |
| MySQL Compatibility | Database Schema & Drivers | Run full migration & feature test suite on MySQL (`pp_certification`) | 0 DB exceptions, identical status | PASS | PASS | PASS |
| Security / RBAC | Cross-Role Endpoint Attack | Sales/Cashier sending direct HTTP POST to Admin endpoints | 403 Forbidden | PASS | PASS | PASS |
| Security / RBAC | Cashier Visibility Leak | Cashier A requesting Cashier C's ledger / bills | 403 Forbidden / 404 | PASS | PASS | PASS |
| Security / RBAC | Product-Grade Forgery | Submitting unassigned grade for selected product | 422 Unprocessable | PASS | PASS | PASS |
| Data Security | Mass-Assignment / ID Tampering | Injecting `role`, `user_id`, or `status` in form payloads | Ignored / Overwritten by Auth context | PASS | PASS | PASS |
| Concurrency | Stock Deduction Race Condition | Simultaneous dispatches exceeding available stock | Only 1 succeeds, Stock >= 0 | PASS | PASS | PASS |
| Stock Reconciliation | Stock Ledger Invariant | `SUM(IN) - SUM(OUT)` per stage, grade, location | Zero discrepancy | PASS | PASS | PASS |
| Cash Reconciliation | Financial Ledger Invariant | `Opening + IN - OUT = Closing` for daily balances | Zero discrepancy | PASS | PASS | PASS |
| Dispatch Revert | Multi-Round Revert Torture | Reverting single round, double revert attempt, redispatch | Exact stock restoration, double revert 404 | PASS | PASS | PASS |
| PDF Visual Integrity | Dompdf & FPDI Bill Merging | Generating all 10 PDFs with images, missing logos, attached PDFs | Valid `%PDF` header, non-zero bytes, safe fallback | PASS | PASS | PASS |
| Route Integrity | 100% Non-Attendance View Routes | GET requests across all 7 non-attendance roles | HTTP 200 OK | PASS | PASS | PASS |
| Debug & Leak Audit | Codebase Inspection | Scan for `dd()`, `dump()`, `console.log()`, Windows paths | Zero production leaks | PASS | PASS | PASS |
