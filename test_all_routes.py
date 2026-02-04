#!/usr/bin/env python3
"""
Comprehensive Route Verification - Tests ALL admin routes work without PHP errors
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

# All admin GET routes to test
ROUTES = [
    # Dashboard & Auth
    '/dashboard',
    '/profile',
    '/logout',
    
    # Content Management
    '/about',
    '/about/add',
    '/about/contact-info',
    '/blog',
    '/blog/add',
    '/blog/blog-category',
    '/blog/blog-category/add',
    '/faq',
    '/faq/add',
    '/testimonial',
    '/testimonial/add',
    '/service',
    '/service/add',
    '/slider',
    '/slider/add',
    '/team',
    '/team/add',
    '/funfact',
    '/funfact/add',
    '/offer',
    '/offer/add',
    '/entertainment',
    '/entertainment/add',
    '/media',
    '/media/add',
    '/package',
    '/package/add',
    '/product',
    '/product/add',
    '/dynamic-page',
    '/dynamic-page/add',
    '/sectiontitle',
    '/slinks',
    '/slinks/add',
    
    # User Management
    '/user-role-manage',
    '/user-role-add',
    '/register/users',
    '/register/users/form',
    '/mailsubscriber',
    
    # Orders
    '/package/all-order',
    '/package/pending-order',
    '/package/inprogress-order',
    '/package/compleated-order',
    '/product/all/orders',
    '/product/pending/orders',
    '/product/processing/orders',
    '/product/completed/orders',
    '/bill-pay',
    
    # Payments
    '/payment/gateways',
    '/payment-process',
    '/payment-process/add',
    '/promotion',
    '/promotion/add',
    '/extra-months',
    '/preferential-activities',
    '/kbzpay',
    '/cbpay',
    
    # Queries
    '/payment-query',
    '/fault-query',
    '/install-query',
    '/bind-user-query',
    '/message-to-user',
    
    # Settings
    '/basicinfo',
    '/footer',
    '/currency',
    '/currency/add',
    '/branch',
    '/branch/add',
    '/languages',
    '/language/add',
    '/shipping/methods',
    '/shipping/method/add',
    '/maintainance-settings',
    '/add-maintainance-settings',
    '/error-message',
    '/page-visibility',
    '/cookie-alert',
    '/custom-css',
    '/email-config',
    '/email-templates',
    '/groupemail',
    '/app-banner',
    '/bank/settings',
    '/backup',
    '/cache-clear',
    '/marketting-information',
]

def test_route(path):
    """Test a route returns 200 or valid redirect, not 500"""
    url = f"{ADMIN_URL}{path}"
    try:
        r = session.get(url, allow_redirects=True, timeout=30)
        
        if r.status_code == 500:
            return 'ERROR', '500 Server Error'
        elif r.status_code == 404:
            return 'WARN', '404 Not Found'
        elif r.status_code == 200:
            # Check for PHP errors in content
            if 'Parse error' in r.text or 'Fatal error' in r.text:
                return 'ERROR', 'PHP Error'
            return 'OK', ''
        elif r.status_code in [301, 302]:
            return 'OK', 'Redirect'
        else:
            return 'WARN', f'Status {r.status_code}'
    except Exception as e:
        return 'ERROR', str(e)[:40]

def main():
    print("="*70)
    print("COMPREHENSIVE ROUTE VERIFICATION")
    print("="*70)
    
    if not login():
        print("❌ Login failed!")
        return
    
    print("✅ Logged in successfully\n")
    
    ok_count = 0
    warn_count = 0
    error_count = 0
    errors = []
    warnings = []
    
    for route in ROUTES:
        status, msg = test_route(route)
        
        if status == 'OK':
            ok_count += 1
            print(f"  ✅ {route}")
        elif status == 'WARN':
            warn_count += 1
            print(f"  ⚠️  {route}: {msg}")
            warnings.append(f"{route}: {msg}")
        else:
            error_count += 1
            print(f"  ❌ {route}: {msg}")
            errors.append(f"{route}: {msg}")
    
    total = len(ROUTES)
    print("\n" + "="*70)
    print("SUMMARY")
    print("="*70)
    print(f"Total routes tested: {total}")
    print(f"OK:       {ok_count} ✅")
    print(f"Warnings: {warn_count} ⚠️")
    print(f"Errors:   {error_count} ❌")
    print(f"Success rate: {(ok_count+warn_count)/total*100:.1f}%")
    
    if errors:
        print(f"\n--- ERRORS ({len(errors)}) ---")
        for e in errors:
            print(f"  ❌ {e}")
    
    if warnings:
        print(f"\n--- WARNINGS ({len(warnings)}) ---")
        for w in warnings:
            print(f"  ⚠️  {w}")
    
    print("\n" + "="*70)

if __name__ == "__main__":
    main()
