#!/usr/bin/env python3
"""
Final Verification Test - Check ALL admin pages load without PHP errors
"""

import requests
import re
import urllib3
urllib3.disable_warnings()

BASE_URL = "https://isp.mlbbshop.app"
ADMIN_URL = f"{BASE_URL}/en/admin"

session = requests.Session()
session.verify = False

def get_csrf_token(html):
    match = re.search(r'name="_token"\s+value="([^"]+)"', html)
    return match.group(1) if match else None

def login():
    r = session.get(f"{BASE_URL}/admin")
    csrf_token = get_csrf_token(r.text)
    r = session.post(f"{BASE_URL}/admin/login", data={
        '_token': csrf_token,
        'username': 'admin',
        'password': 'TestAdmin123!'
    }, allow_redirects=True)
    return 'dashboard' in r.url

# All admin pages to test (list pages, settings pages, etc.)
PAGES = [
    # Dashboard & Core
    ('Dashboard', '/dashboard'),
    ('Profile', '/profile'),
    
    # Content Management
    ('About', '/about'),
    ('Blog', '/blog'),
    ('Blog Categories', '/blog/blog-category'),
    ('FAQ', '/faq'),
    ('Testimonial', '/testimonial'),
    ('Service', '/service'),
    ('Slider', '/slider'),
    ('Team', '/team'),
    ('Funfact', '/funfact'),
    ('Offer', '/offer'),
    ('Entertainment', '/entertainment'),
    ('Media', '/media'),
    ('Package', '/package'),
    ('Product', '/product'),
    ('Dynamic Page', '/dynamic-page'),
    ('Section Title', '/sectiontitle'),
    
    # User Management  
    ('Roles', '/role'),
    ('Social Links', '/social'),
    ('Subscriber', '/mailsubscriber'),
    ('Registered Users', '/register/users'),
    
    # Orders
    ('Package Orders', '/package/all-order'),
    ('Product Orders', '/product/all/orders'),
    ('Bill Pay', '/bill-pay'),
    
    # Payments
    ('Payment Gateways', '/payment/gateways'),
    ('Payment Process', '/payment-process'),
    ('Promotion', '/promotion'),
    
    # Queries
    ('Payment Query', '/payment-query'),
    ('Fault Query', '/fault-query'),
    ('Install Query', '/install-query'),
    ('Bind User Query', '/bind-user-query'),
    
    # Settings
    ('Basic Info', '/basicinfo'),
    ('Contact Info', '/about/contact-info'),
    ('Footer', '/footer'),
    ('Currency', '/currency'),
    ('Branch', '/branch'),
    ('Language', '/languages'),
    ('Shipping', '/shipping'),
    ('Maintenance Settings', '/maintainance-settings'),
    ('Error Messages', '/error-message'),
    ('Page Visibility', '/page-visibility'),
    ('Cookie Alert', '/cookie-alert'),
    ('Custom CSS', '/custom-css'),
    ('Email Config', '/email-config'),
    ('Email Templates', '/email-templates'),
    ('App Banner', '/app-banner'),
    ('Bank Settings', '/bank/settings'),
    ('Backup', '/backup'),
]

def test_page(name, path):
    """Test that a page loads without errors"""
    url = f"{ADMIN_URL}{path}"
    try:
        r = session.get(url, allow_redirects=True, timeout=30)
        
        if r.status_code == 500:
            err_match = re.search(r'"exception_message":"([^"]+)"', r.text)
            error = err_match.group(1) if err_match else "500 Error"
            return False, f"500: {error[:60]}"
        
        if r.status_code == 404:
            return False, "404 Not Found"
        
        if r.status_code == 200:
            # Check for PHP errors in page content
            if 'Parse error' in r.text or 'Fatal error' in r.text or 'Syntax error' in r.text:
                return False, "PHP Error in page"
            return True, "OK"
        
        return False, f"Status {r.status_code}"
    except Exception as e:
        return False, str(e)[:50]

def main():
    print("="*70)
    print("ADMIN PANEL PAGES VERIFICATION TEST")
    print("="*70)
    
    if not login():
        print("❌ Login failed!")
        return
    
    print("✅ Logged in successfully\n")
    
    passed = 0
    failed = 0
    failures = []
    
    for name, path in PAGES:
        success, msg = test_page(name, path)
        if success:
            passed += 1
            print(f"  ✅ {name}")
        else:
            failed += 1
            print(f"  ❌ {name}: {msg}")
            failures.append(f"{name}: {msg}")
    
    total = passed + failed
    print("\n" + "="*70)
    print("SUMMARY")
    print("="*70)
    print(f"Total pages tested: {total}")
    print(f"Passed: {passed} ✅")
    print(f"Failed: {failed} ❌")
    print(f"Pass rate: {passed/total*100:.1f}%")
    
    if failures:
        print(f"\n--- FAILURES ({len(failures)}) ---")
        for f in failures:
            print(f"  ❌ {f}")
    
    print("\n" + "="*70)

if __name__ == "__main__":
    main()
