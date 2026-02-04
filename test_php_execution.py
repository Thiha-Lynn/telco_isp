#!/usr/bin/env python3
"""
Comprehensive PHP Code Execution Test
Tests all CRUD endpoints and checks for PHP errors in responses
"""

import requests
import re
import json
from urllib3.exceptions import InsecureRequestWarning
requests.packages.urllib3.disable_warnings(InsecureRequestWarning)

BASE_URL = "https://isp.mlbbshop.app"
ADMIN_URL = f"{BASE_URL}/en/admin"
session = requests.Session()
session.verify = False

# Track results
results = {
    'total': 0,
    'passed': 0,
    'php_errors': [],
    'http_errors': [],
    'tested_endpoints': []
}

# PHP error patterns to detect
PHP_ERROR_PATTERNS = [
    r'Fatal error',
    r'Parse error',
    r'Warning:.*on line',
    r'Notice:.*on line',
    r'Undefined variable',
    r'Undefined index',
    r'Undefined array key',
    r'Call to undefined',
    r'Class .* not found',
    r'Method .* does not exist',
    r'SQLSTATE\[',
    r'ErrorException',
    r'Exception in',
    r'Stack trace:',
    r'Whoops!',
    r'Symfony\\Component\\.*Exception',
    r'Illuminate\\.*Exception',
    r'TypeError:',
    r'ArgumentCountError',
    r'ReflectionException',
    r'BadMethodCallException',
    r'InvalidArgumentException',
    r'RuntimeException',
    r'LogicException',
    r'<br\s*/>\s*<b>.*error</b>',
    r'error_log',
]

def check_for_php_errors(response, endpoint_name):
    """Check response for PHP errors"""
    errors_found = []
    text = response.text
    
    for pattern in PHP_ERROR_PATTERNS:
        matches = re.findall(pattern, text, re.IGNORECASE)
        if matches:
            errors_found.extend(matches[:2])  # Limit to first 2 matches per pattern
    
    return errors_found

def get_csrf_token(html):
    """Extract CSRF token from HTML"""
    match = re.search(r'name="_token"\s+value="([^"]+)"', html)
    if not match:
        match = re.search(r'value="([^"]+)"\s+name="_token"', html)
    return match.group(1) if match else None

def test_endpoint(method, url, name, data=None, files=None, expect_redirect=True):
    """Test an endpoint and check for PHP errors"""
    results['total'] += 1
    results['tested_endpoints'].append(name)
    
    try:
        if method == 'GET':
            r = session.get(url, allow_redirects=True, timeout=30)
        else:
            r = session.post(url, data=data, files=files, allow_redirects=expect_redirect, timeout=30)
        
        # Check HTTP status
        if r.status_code >= 500:
            results['http_errors'].append(f"{name}: HTTP {r.status_code}")
            print(f"  ❌ {name} - HTTP {r.status_code}")
            return False, r
        
        # Check for PHP errors in response
        php_errors = check_for_php_errors(r, name)
        if php_errors:
            results['php_errors'].append(f"{name}: {', '.join(php_errors[:3])}")
            print(f"  ⚠️  {name} - PHP errors: {php_errors[0][:50]}...")
            return False, r
        
        # Success
        results['passed'] += 1
        print(f"  ✅ {name}")
        return True, r
        
    except Exception as e:
        results['http_errors'].append(f"{name}: {str(e)[:50]}")
        print(f"  ❌ {name} - {str(e)[:50]}")
        return False, None

def create_test_image():
    """Create minimal PNG for file uploads"""
    import struct
    import zlib
    
    def create_png():
        signature = b'\x89PNG\r\n\x1a\n'
        ihdr_data = struct.pack('>IIBBBBB', 1, 1, 8, 2, 0, 0, 0)
        ihdr_crc = zlib.crc32(b'IHDR' + ihdr_data) & 0xffffffff
        ihdr = struct.pack('>I', 13) + b'IHDR' + ihdr_data + struct.pack('>I', ihdr_crc)
        raw_data = b'\x00\xff\x00\x00'
        compressed = zlib.compress(raw_data)
        idat_crc = zlib.crc32(b'IDAT' + compressed) & 0xffffffff
        idat = struct.pack('>I', len(compressed)) + b'IDAT' + compressed + struct.pack('>I', idat_crc)
        iend_crc = zlib.crc32(b'IEND') & 0xffffffff
        iend = struct.pack('>I', 0) + b'IEND' + struct.pack('>I', iend_crc)
        return signature + ihdr + idat + iend
    
    return create_png()

def rand_str(length=8):
    import random, string
    return ''.join(random.choices(string.ascii_lowercase, k=length))

# ============================================================
# LOGIN TO ADMIN
# ============================================================
def admin_login():
    print("\n" + "="*70)
    print("ADMIN LOGIN")
    print("="*70)
    
    r = session.get(f"{BASE_URL}/admin", timeout=30)
    csrf = get_csrf_token(r.text)
    
    if not csrf:
        print("  ❌ Could not get CSRF token")
        return False
    
    r = session.post(f"{BASE_URL}/admin/login", data={
        '_token': csrf,
        'email': 'admin',
        'password': 'TestAdmin123!'
    }, allow_redirects=True, timeout=30)
    
    # Check if we're logged in by accessing dashboard
    r2 = session.get(f"{ADMIN_URL}/dashboard", timeout=30)
    if r2.status_code == 200 and ('dashboard' in r2.text.lower() or 'admin' in r2.url.lower()):
        print("  ✅ Admin login successful")
        return True
    else:
        print(f"  ❌ Admin login failed (status: {r2.status_code})")
        return False

# ============================================================
# TEST ALL CRUD ENTITIES
# ============================================================
CRUD_ENTITIES = [
    # (name, url_prefix, create_data_func, has_image)
    ('About', 'about', lambda: {'language_id': '1', 'title': f'Test About {rand_str()}', 'text': 'Test content'}, False),
    ('Blog', 'blog', lambda: {'language_id': '1', 'title': f'Test Blog {rand_str()}', 'text': 'Test content', 'categories[]': '1'}, True),
    ('FAQ', 'faq', lambda: {'language_id': '1', 'status': '1', 'title': f'Test FAQ {rand_str()}', 'content': 'Test content'}, False),
    ('Testimonial', 'testimonial', lambda: {'language_id': '1', 'name': f'Test Person {rand_str()}', 'designation': 'Tester', 'text': 'Great service'}, True),
    ('Service', 'service', lambda: {'language_id': '1', 'title': f'Test Service {rand_str()}', 'text': 'Service description'}, True),
    ('Slider', 'slider', lambda: {'language_id': '1', 'title': f'Test Slider {rand_str()}', 'text': 'Slider text', 'button_text': 'Click', 'button_url': '#'}, True),
    ('Team', 'team', lambda: {'language_id': '1', 'name': f'Test Team {rand_str()}', 'designation': 'Developer'}, True),
    ('Funfact', 'funfact', lambda: {'language_id': '1', 'title': f'Test Funfact {rand_str()}', 'value': '100'}, False),
    ('Offer', 'offer', lambda: {'language_id': '1', 'title': f'Test Offer {rand_str()}', 'text': 'Offer description'}, True),
    ('Entertainment', 'entertainment', lambda: {'language_id': '1', 'title': f'Test Ent {rand_str()}', 'text': 'Entertainment text'}, True),
    ('Media', 'mediazone', lambda: {'language_id': '1', 'media_link': 'https://youtube.com/test', 'title': f'Test Media {rand_str()}'}, False),
    ('Package', 'package', lambda: {'language_id': '1', 'title': f'Test Package {rand_str()}', 'price': '99.99', 'text': 'Package details', 'package_type': 'monthly'}, True),
    ('Product', 'product', lambda: {'language_id': '1', 'title': f'Test Product {rand_str()}', 'price': '49.99', 'text': 'Product details', 'feature_id[]': '1', 'feature_value[]': 'Test'}, True),
    ('DynamicPage', 'daynamicpage', lambda: {'language_id': '1', 'title': f'Test Page {rand_str()}', 'text': 'Page content', 'meta_keywords': 'test', 'meta_description': 'test'}, False),
    ('SocialLinks', 'slinks', lambda: {'icon': 'fab fa-facebook', 'link': f'https://facebook.com/{rand_str()}'}, False),
    ('Promotion', 'promotion', lambda: {'language_id': '1', 'title': f'Test Promo {rand_str()}', 'text': 'Promo text'}, True),
    ('Branch', 'branch', lambda: {'language_id': '1', 'branch_name': f'Test Branch {rand_str()}', 'phone': '123456', 'email': 'test@test.com', 'address': 'Test Address', 'iframe': '<iframe>test</iframe>'}, False),
    ('Currency', 'currency', lambda: {'name': f'TEST{rand_str()[:3].upper()}', 'sign': '$', 'value': '1.0'}, False),
    ('Language', 'language', lambda: {'name': f'Test Lang {rand_str()}', 'code': rand_str()[:2], 'direction': 'ltr', 'is_default': '0'}, False),
    ('Shipping', 'shipping', lambda: {'language_id': '1', 'title': f'Test Ship {rand_str()}', 'charge': '10'}, False),
]

def test_crud_operations():
    print("\n" + "="*70)
    print("TESTING ALL CRUD OPERATIONS (PHP EXECUTION)")
    print("="*70)
    
    test_image = create_test_image()
    
    for entity_name, url_prefix, data_func, has_image in CRUD_ENTITIES:
        print(f"\n  --- {entity_name} CRUD ---")
        
        # 1. INDEX (List)
        test_endpoint('GET', f"{ADMIN_URL}/{url_prefix}", f"{entity_name} Index")
        
        # 2. ADD PAGE
        ok, r = test_endpoint('GET', f"{ADMIN_URL}/{url_prefix}/add", f"{entity_name} Add Page")
        
        if ok and r:
            csrf = get_csrf_token(r.text)
            if csrf:
                # 3. STORE (Create)
                data = data_func()
                data['_token'] = csrf
                
                files = None
                if has_image:
                    files = {'image': ('test.png', test_image, 'image/png')}
                
                ok, r = test_endpoint('POST', f"{ADMIN_URL}/{url_prefix}/store", f"{entity_name} Store", data, files)
                
                # 4. Find created ID for edit/update/delete
                if ok and r:
                    match = re.search(rf'{url_prefix}/edit/(\d+)', r.text)
                    if not match:
                        # Try to get from list
                        r2 = session.get(f"{ADMIN_URL}/{url_prefix}")
                        matches = re.findall(rf'{url_prefix}/edit/(\d+)', r2.text)
                        if matches:
                            entity_id = matches[-1]
                        else:
                            entity_id = None
                    else:
                        entity_id = match.group(1)
                    
                    if entity_id:
                        # 5. EDIT PAGE
                        ok, r = test_endpoint('GET', f"{ADMIN_URL}/{url_prefix}/edit/{entity_id}", f"{entity_name} Edit Page")
                        
                        if ok and r:
                            csrf = get_csrf_token(r.text)
                            if csrf:
                                # 6. UPDATE
                                update_data = data_func()
                                update_data['_token'] = csrf
                                update_data['id'] = entity_id
                                
                                files = None
                                if has_image:
                                    files = {'image': ('test.png', test_image, 'image/png')}
                                
                                test_endpoint('POST', f"{ADMIN_URL}/{url_prefix}/update/{entity_id}", f"{entity_name} Update", update_data, files)
                        
                        # 7. DELETE
                        r = session.get(f"{ADMIN_URL}/{url_prefix}")
                        csrf = get_csrf_token(r.text)
                        if csrf:
                            test_endpoint('POST', f"{ADMIN_URL}/{url_prefix}/delete/{entity_id}", f"{entity_name} Delete", {'_token': csrf})

# ============================================================
# TEST ADDITIONAL ADMIN PAGES
# ============================================================
def test_admin_pages():
    print("\n" + "="*70)
    print("TESTING ADDITIONAL ADMIN PAGES")
    print("="*70)
    
    admin_pages = [
        ('dashboard', 'Dashboard'),
        ('basicinfo', 'Basic Info'),
        ('role', 'User Roles'),
        ('users', 'Users List'),
        ('paymentgatewey', 'Payment Gateways'),
        ('paymentprocess', 'Payment Process'),
        ('maintainance', 'Maintenance Settings'),
        ('bcategory', 'Blog Categories'),
        ('scategory', 'Service Categories'),
        ('email/setting', 'Email Settings'),
        ('sectiontitle', 'Section Titles'),
        ('skill', 'Skills'),
        ('portfolio', 'Portfolio'),
        ('subscriber', 'Subscribers'),
        ('sitemap', 'Sitemap'),
    ]
    
    for url, name in admin_pages:
        test_endpoint('GET', f"{ADMIN_URL}/{url}", name)

# ============================================================
# TEST FRONTEND PAGES
# ============================================================
def test_frontend():
    print("\n" + "="*70)
    print("TESTING FRONTEND PAGES (PHP EXECUTION)")
    print("="*70)
    
    frontend_pages = [
        ('/', 'Homepage'),
        ('/about', 'About'),
        ('/service', 'Services'),
        ('/package', 'Packages'),
        ('/products', 'Products'),
        ('/blog', 'Blog'),
        ('/contact', 'Contact'),
        ('/faq', 'FAQ'),
        ('/branch', 'Branch'),
        ('/media', 'Media'),
        ('/login', 'Login'),
        ('/user/register', 'Register'),
        ('/gallery', 'Gallery'),
        ('/team', 'Team'),
        ('/testimonial', 'Testimonials'),
    ]
    
    for url, name in frontend_pages:
        test_endpoint('GET', f"{BASE_URL}{url}", f"Frontend: {name}")

# ============================================================
# TEST API ENDPOINTS
# ============================================================
def test_api():
    print("\n" + "="*70)
    print("TESTING API ENDPOINTS (PHP EXECUTION)")
    print("="*70)
    
    api_endpoints = [
        # V1 API
        ('GET', '/api/v1/packages', 'API v1: Packages'),
        ('GET', '/api/v1/banners', 'API v1: Banners'),
        ('GET', '/api/v1/maintenance-status', 'API v1: Maintenance Status'),
        ('GET', '/api/v1/app-version', 'API v1: App Version'),
        ('GET', '/api/v1/bind-users', 'API v1: Bind Users'),
        ('GET', '/api/v1/notifications', 'API v1: Notifications'),
        
        # Legacy API
        ('GET', '/api/get-package', 'Legacy: Get Package'),
        ('GET', '/api/get-banner', 'Legacy: Get Banner'),
        ('GET', '/api/get-error-code', 'Legacy: Error Codes'),
        ('GET', '/api/check-maintenance', 'Legacy: Check Maintenance'),
        ('GET', '/api/get-Preferential-activities', 'Legacy: Preferential'),
        ('GET', '/api/get-notification', 'Legacy: Notifications'),
        ('GET', '/api/get-access-token', 'Legacy: Access Token'),
        ('GET', '/api/get-new-package', 'Legacy: New Package'),
        ('GET', '/api/mbtprofile', 'Legacy: MBT Profile'),
    ]
    
    for method, url, name in api_endpoints:
        test_endpoint(method, f"{BASE_URL}{url}", name)

# ============================================================
# PRINT SUMMARY
# ============================================================
def print_summary():
    print("\n" + "="*70)
    print("PHP CODE EXECUTION TEST SUMMARY")
    print("="*70)
    
    pass_rate = (results['passed'] / results['total'] * 100) if results['total'] > 0 else 0
    
    print(f"\n📊 Total Endpoints Tested: {results['total']}")
    print(f"✅ Passed (No PHP Errors): {results['passed']}")
    print(f"❌ Failed: {results['total'] - results['passed']}")
    print(f"📈 Pass Rate: {pass_rate:.1f}%")
    
    if results['php_errors']:
        print(f"\n⚠️  PHP ERRORS DETECTED ({len(results['php_errors'])}):")
        for err in results['php_errors'][:10]:
            print(f"   • {err[:80]}")
    
    if results['http_errors']:
        print(f"\n❌ HTTP ERRORS ({len(results['http_errors'])}):")
        for err in results['http_errors'][:10]:
            print(f"   • {err[:80]}")
    
    print("\n" + "="*70)
    if pass_rate == 100:
        print("🎉 ALL PHP CODE EXECUTED WITHOUT ERRORS!")
    elif pass_rate >= 95:
        print("✅ PHP code execution is stable with minor issues")
    elif pass_rate >= 80:
        print("⚠️  Some PHP errors detected - review needed")
    else:
        print("❌ Significant PHP errors detected - immediate attention required")
    print("="*70)

# ============================================================
# MAIN
# ============================================================
if __name__ == "__main__":
    print("="*70)
    print("COMPREHENSIVE PHP CODE EXECUTION TEST")
    print("Testing all CRUD endpoints for PHP errors")
    print("="*70)
    
    if admin_login():
        test_crud_operations()
        test_admin_pages()
        test_frontend()
        test_api()
    
    print_summary()
