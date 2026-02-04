#!/usr/bin/env python3
"""
Comprehensive Test - Tests ALL admin panel CRUD endpoints
Ensures all PHP files execute without errors
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

def create_minimal_png():
    return bytes([
        0x89, 0x50, 0x4E, 0x47, 0x0D, 0x0A, 0x1A, 0x0A,
        0x00, 0x00, 0x00, 0x0D, 0x49, 0x48, 0x44, 0x52,
        0x00, 0x00, 0x00, 0x01, 0x00, 0x00, 0x00, 0x01,
        0x08, 0x02, 0x00, 0x00, 0x00, 0x90, 0x77, 0x53,
        0xDE, 0x00, 0x00, 0x00, 0x0C, 0x49, 0x44, 0x41,
        0x54, 0x08, 0xD7, 0x63, 0xF8, 0xCF, 0xC0, 0x00,
        0x00, 0x01, 0x01, 0x01, 0x00, 0x18, 0xDD, 0x8D,
        0xB4, 0x00, 0x00, 0x00, 0x00, 0x49, 0x45, 0x4E,
        0x44, 0xAE, 0x42, 0x60, 0x82
    ])

import random
import string
def rand_str():
    return ''.join(random.choices(string.ascii_lowercase, k=6))

# Define all CRUD entities
ENTITIES = [
    {
        'name': 'Blog',
        'list': '/blog',
        'add': '/blog/add',
        'create_data': lambda: {
            'title': f'Test Blog {rand_str()}',
            'category_id': '1',
            'details': 'Test content',
            'source': 'Test source',
            'meta_tag': 'test',
            'meta_description': 'test desc'
        },
        'file_field': 'image'
    },
    {
        'name': 'Product',
        'list': '/product',
        'add': '/product/add',
        'create_data': lambda: {
            'type': 'physical',
            'product_type': 'device',
            'title': f'Test Product {rand_str()}',
            'current_price': '100',
            'previous_price': '120',
            'short_description': 'Short desc',
            'description': 'Long desc',
            'stock': '10'
        },
        'file_field': 'thumbnail'
    },
    {
        'name': 'Offer',
        'list': '/offer',
        'add': '/offer/add',
        'create_data': lambda: {
            'title': f'Test Offer {rand_str()}',
            'text': 'Offer text',
            'details': 'Offer details'
        },
        'file_field': 'image'
    },
    {
        'name': 'Testimonial',
        'list': '/testimonial',
        'add': '/testimonial/add',
        'create_data': lambda: {
            'name': f'Test Person {rand_str()}',
            'designation': 'Tester',
            'comment': 'Great service!'
        },
        'file_field': 'image'
    },
    {
        'name': 'Entertainment',
        'list': '/entertainment',
        'add': '/entertainment/add',
        'create_data': lambda: {
            'name': f'Entertainment {rand_str()}',
            'package_id': '1'
        },
        'file_field': 'icon'
    },
    {
        'name': 'Media',
        'list': '/media',
        'add': '/media/add',
        'create_data': lambda: {
            'name': f'Media {rand_str()}',
            'url': 'https://example.com'
        },
        'file_field': 'image'
    },
    {
        'name': 'Branch',
        'list': '/branch',
        'add': '/branch/add',
        'create_data': lambda: {
            'name': f'Branch {rand_str()}',
            'address': '123 Test St',
            'phone': '1234567890',
            'city': 'Test City',
            'latitude': '0',
            'longitude': '0'
        },
        'file_field': None
    },
    {
        'name': 'Currency',
        'list': '/currency',
        'add': '/currency/add',
        'create_data': lambda: {
            'name': f'TCU',
            'symbol': '$',
            'rate': '1.0'
        },
        'file_field': None
    },
    {
        'name': 'Subscriber',
        'list': '/mailsubscriber',
        'add': None,  # No add for subscribers
        'create_data': None,
        'file_field': None
    },
    {
        'name': 'Dynamic Page',
        'list': '/dynamic-page',
        'add': '/dynamic-page/add',
        'create_data': lambda: {
            'title': f'Page {rand_str()}',
            'slug': f'page-{rand_str()}',
            'details': 'Page content'
        },
        'file_field': None
    },
    {
        'name': 'Package',
        'list': '/package',
        'add': '/package/add',
        'create_data': lambda: {
            'name': f'Package {rand_str()}',
            'price': '29.99',
            'chinese': 'Chinese text',
            'myanmar': 'Myanmar text',
            'speed': '100',
            'device': '5',
            'title': 'Best Value',
            'featured': '0'
        },
        'file_field': 'icon'
    },
    {
        'name': 'Section Title',
        'list': '/sectiontitle',
        'add': None,  # Usually just edit
        'create_data': None,
        'file_field': None
    },
    {
        'name': 'About',
        'list': '/about',
        'add': '/about/add',
        'create_data': lambda: {
            'title': f'About {rand_str()}',
            'text': 'About text content'
        },
        'file_field': 'image'
    },
    {
        'name': 'Language',
        'list': '/languages',
        'add': '/language/add',
        'create_data': lambda: {
            'name': f'Lang {rand_str()}',
            'code': f'{rand_str()[:2]}',
            'icon': 'flag-icon'
        },
        'file_field': None
    },
    {
        'name': 'Promotion',
        'list': '/promotion',
        'add': '/promotion/add',
        'create_data': lambda: {
            'title': f'Promo {rand_str()}',
            'description': 'Promo description',
            'promotion_type': 'discount',
            'chinese': 'Chinese text',
            'myanmar': 'Myanmar text',
            'duration': '30',
            'extra_month': '0',
            'extra_days': '0'
        },
        'file_field': 'image'
    },
    {
        'name': 'Payment Process',
        'list': '/payment-process',
        'add': '/payment-process/add',
        'create_data': lambda: {
            'title': f'Payment {rand_str()}',
            'subtitle': 'Pay subtitle',
            'bind_id': '1',
            'description': 'Payment description'
        },
        'file_field': 'icon'
    },
    {
        'name': 'Maintenance Settings',
        'list': '/maintainance-settings',
        'add': '/add-maintainance-settings',
        'create_data': lambda: {
            'subject': f'Maint {rand_str()}',
            'date': '2026-02-10',
            'from_time': '00:00',
            'to_time': '06:00',
            'message': 'Maintenance in progress'
        },
        'file_field': None
    }
]

def test_list_page(entity):
    """Test that list page loads without errors"""
    url = f"{ADMIN_URL}{entity['list']}"
    r = session.get(url, allow_redirects=True)
    
    if r.status_code == 500:
        err_match = re.search(r'"exception_message":"([^"]+)"', r.text)
        error = err_match.group(1) if err_match else "500 Error"
        return False, f"500: {error[:80]}"
    
    if r.status_code == 404:
        return False, "404 Not Found"
    
    if r.status_code == 200:
        return True, "OK"
    
    return False, f"Status {r.status_code}"

def test_add_page(entity):
    """Test that add page loads without errors"""
    if not entity.get('add'):
        return None, "No add page"
    
    url = f"{ADMIN_URL}{entity['add']}"
    r = session.get(url, allow_redirects=True)
    
    if r.status_code == 500:
        err_match = re.search(r'"exception_message":"([^"]+)"', r.text)
        error = err_match.group(1) if err_match else "500 Error"
        return False, f"500: {error[:80]}"
    
    if r.status_code == 404:
        return False, "404 Not Found"
    
    if r.status_code == 200:
        return True, "OK"
    
    return False, f"Status {r.status_code}"

def test_create(entity):
    """Test creating a new item"""
    if not entity.get('add') or not entity.get('create_data'):
        return None, "Cannot create"
    
    # First get the add page to get CSRF token
    add_url = f"{ADMIN_URL}{entity['add']}"
    r = session.get(add_url)
    csrf_token = get_csrf_token(r.text)
    
    if not csrf_token:
        return False, "No CSRF token"
    
    # Build the POST data
    data = entity['create_data']()
    data['_token'] = csrf_token
    
    # Create store URL (same as add or replace /add with /store)
    store_url = add_url.replace('/add', '/store')
    
    # Handle file upload if needed
    files = None
    if entity.get('file_field'):
        files = {entity['file_field']: ('test.png', create_minimal_png(), 'image/png')}
    
    r = session.post(store_url, data=data, files=files, allow_redirects=True)
    
    if r.status_code == 500:
        err_match = re.search(r'"exception_message":"([^"]+)"', r.text)
        error = err_match.group(1) if err_match else "500 Error"
        return False, f"500: {error[:80]}"
    
    if r.status_code == 200:
        # Check for success message or redirect back to list
        if 'successfully' in r.text.lower() or entity['list'] in r.url:
            # Try to extract the ID from the URL or content
            return True, "Created OK"
    
    return False, f"Status {r.status_code}"

def main():
    print("="*70)
    print("COMPREHENSIVE CRUD ENDPOINT TEST")
    print("="*70)
    
    if not login():
        print("❌ Login failed!")
        return
    
    print("✅ Logged in successfully\n")
    
    total_tests = 0
    passed_tests = 0
    failed_tests = 0
    skipped_tests = 0
    
    failures = []
    
    for entity in ENTITIES:
        name = entity['name']
        print(f"\n--- Testing {name} ---")
        
        # Test LIST
        success, msg = test_list_page(entity)
        total_tests += 1
        if success:
            passed_tests += 1
            print(f"  LIST:   ✅ {msg}")
        elif success is None:
            skipped_tests += 1
            print(f"  LIST:   ⏭️  {msg}")
        else:
            failed_tests += 1
            print(f"  LIST:   ❌ {msg}")
            failures.append(f"{name} LIST: {msg}")
        
        # Test ADD page
        success, msg = test_add_page(entity)
        if success is not None:
            total_tests += 1
            if success:
                passed_tests += 1
                print(f"  ADD:    ✅ {msg}")
            else:
                failed_tests += 1
                print(f"  ADD:    ❌ {msg}")
                failures.append(f"{name} ADD: {msg}")
        else:
            print(f"  ADD:    ⏭️  {msg}")
        
        # Test CREATE
        success, msg = test_create(entity)
        if success is not None:
            total_tests += 1
            if success:
                passed_tests += 1
                print(f"  CREATE: ✅ {msg}")
            else:
                failed_tests += 1
                print(f"  CREATE: ❌ {msg}")
                failures.append(f"{name} CREATE: {msg}")
        else:
            print(f"  CREATE: ⏭️  {msg}")
    
    # Print summary
    print("\n" + "="*70)
    print("SUMMARY")
    print("="*70)
    print(f"Total tests: {total_tests}")
    print(f"Passed:      {passed_tests} ✅")
    print(f"Failed:      {failed_tests} ❌")
    print(f"Skipped:     {skipped_tests} ⏭️")
    print(f"Pass rate:   {passed_tests/total_tests*100:.1f}%")
    
    if failures:
        print(f"\n--- FAILURES ({len(failures)}) ---")
        for f in failures:
            print(f"  ❌ {f}")
    
    print("\n" + "="*70)

if __name__ == "__main__":
    main()
