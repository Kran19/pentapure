import os
import re

files = [
    'resources/views/admin/categories.blade.php',
    'resources/views/admin/users.blade.php',
    'resources/views/admin/grades.blade.php',
    'resources/views/attendance/workers.blade.php',
    'resources/views/attendance/departments.blade.php',
    'resources/views/admin/stock.blade.php'
]

css_block = """
<style>
/* White and Orange Theme for Forms */
.white-orange-card {
    background-color: #ffffff !important;
    border: 1px solid #e5e7eb !important;
    border-radius: 12px !important;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05) !important;
}
.white-orange-card .card-title,
.white-orange-card h4 {
    color: #333333 !important;
    font-weight: 700 !important;
}
.white-orange-card label {
    color: #4b5563 !important;
    font-weight: 600 !important;
}
.white-orange-card input,
.white-orange-card select,
.white-orange-card textarea {
    background-color: #f9fafb !important;
    border: 1px solid #d1d5db !important;
    color: #333333 !important;
    -webkit-text-fill-color: #333333 !important;
}
.white-orange-card input::placeholder,
.white-orange-card textarea::placeholder {
    color: #9ca3af !important;
    -webkit-text-fill-color: #9ca3af !important;
}
.white-orange-card .btn-primary,
.white-orange-card button[type="submit"] {
    background-color: #f59e0b !important;
    color: #ffffff !important;
    -webkit-text-fill-color: #ffffff !important;
    border: none !important;
}
.white-orange-card .btn-secondary,
.white-orange-card button[type="button"] {
    background-color: #e5e7eb !important;
    color: #374151 !important;
    -webkit-text-fill-color: #374151 !important;
    border: none !important;
}
.white-orange-card span {
    color: #333333 !important;
}
</style>
"""

stock_css_append = """
<style>
/* Absolute override for all text in stock popup */
.swal-stock-popup * {
    color: #333333 !important;
    -webkit-text-fill-color: #333333 !important;
}
.swal-stock-popup input,
.swal-stock-popup select,
.swal-stock-popup textarea,
.swal-stock-popup option {
    background-color: #ffffff !important;
    color: #333333 !important;
    -webkit-text-fill-color: #333333 !important;
}
.swal-stock-popup input::placeholder,
.swal-stock-popup textarea::placeholder {
    color: #9ca3af !important;
    -webkit-text-fill-color: #9ca3af !important;
}
.swal-stock-popup .swal-cancel-btn-secondary,
.swal-stock-popup .swal-cancel-btn {
    background-color: #e5e7eb !important;
    color: #374151 !important;
    -webkit-text-fill-color: #374151 !important;
}
.swal-stock-popup .swal-confirm-btn-primary,
.swal-stock-popup .swal-confirm-btn,
.swal-stock-popup .swal2-confirm {
    background-color: #f59e0b !important;
    color: #ffffff !important;
    -webkit-text-fill-color: #ffffff !important;
}
</style>
"""

for file in files:
    path = file
    if not os.path.exists(path):
        continue
    
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # 1. Inject CSS block if not present
    if 'White and Orange Theme for Forms' not in content:
        content += css_block
        
    if 'stock.blade.php' in path and 'Absolute override for all text in stock popup' not in content:
        content += stock_css_append
        
    # 2. Add white-orange-card class to the main form cards
    content = re.sub(r'id="category-form-card"\s+class="card"', 'id="category-form-card" class="card white-orange-card"', content)
    content = re.sub(r'id="user-form-card"\s+class="card"', 'id="user-form-card" class="card white-orange-card"', content)
    content = re.sub(r'id="grade-form-card"\s+class="card"', 'id="grade-form-card" class="card white-orange-card"', content)
    content = re.sub(r'id="worker-form-card"\s+class="card"', 'id="worker-form-card" class="card white-orange-card"', content)
    content = re.sub(r'id="dept-form-card"\s+class="card"', 'id="dept-form-card" class="card white-orange-card"', content)
    content = re.sub(r'id="stock-form-card"\s+class="card"', 'id="stock-form-card" class="card white-orange-card"', content)
    
    with open(path, 'w', encoding='utf-8') as f:
        f.write(content)
