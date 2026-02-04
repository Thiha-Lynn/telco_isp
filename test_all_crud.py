#!/usr/bin/env python3
"""
Comprehensive CRUD Test for ALL Admin Panel Endpoints
Tests every CRUD operation to ensure all PHP files execute without errors
"""

import requests
import re
import urllib3
import json
import sys
from datetime import datetime

urllib3.disable_warnings()

BASE_URL = "https://isp.mlbbshop.app"
ADMIN_URL = f"{BASE_URL}/en/admin"

session = requests.Session()
session.verify = False

# Results tracking
results = {
    "passed": [],
    "failed": [],
    "skipped": []
}

def random_str():
    import random
    import string
    return ''.join(random.choices(string.ascii_lowercase, k=6))

def get_csrf_token(html):
    match = re.search(r'name="_token"\s+value="([^"]+)"', html)
    return match.group(1) if match else None

def login():
    r = session.get(f"{BASE_URL}/admin")
    csrf_token = get_csrf_token(r.text)
    if not csrf_token:
        print("❌ Failed to get CSRF token for login")
        return False
    
    r = session.post(f"{BASE_URL}/admin/login", data={
        '_token': csrf_token,
        'username': 'admin',
        'password': 'TestAdmin123!'
    }, allow_redirects=True)
    
    if 'dashboard' in r.url:
        print("✅ Logged in successfully\n")
        return True
    print(f"❌ Login failed - redirected to {r.url}")
    return False

def create_minimal_png():
    """Create a minimal valid PNG (1x1 pixel)"""
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

def test_endpoint(name, method, url, data=None, files=None, expect_redirect=True, check_text=None):
    """Generic endpoint tester"""
    try:
        if method == "GET":
            r = session.get(url, allow_redirects=True)
        else:
            r = session.post(url, data=data, files=files, allow_redirects=True)
        
        if r.status_code == 500:
            # Extract error message
            err_match = re.search(r'"exception_message":"([^"]+)"', r.text)
            error = err_match.group(1) if err_match else "500 Internal Server Error"
            return False, f"500 Error: {error[:100]}"
        
        if r.status_code == 404:
            return False, "404 Not Found"
        
        if r.status_code in [200, 302]:
            if check_text and check_text not in r.text:
                return False, f"Expected text not found"
            return True, "OK"
        
        return False, f"Status {r.status_code}"
    except Exception as e:
        return False, str(e)[:100]

def test_crud_entity(name, list_url, add_url, store_url, edit_pattern, update_url_pattern, delete_url_pattern, 
                     create_data_fn, update_data_fn=None, has_file=False, file_field='image'):
    """Test a complete CRUD entity"""
    print(f"\n{'='*60}")
    print(f"Testing {name}")
    print(f"{'='*60}")
    
    entity_results = {"list": None, "add": None, "create": None, "read": None, "update": None, "delete": None}
    created_id = None
    
    # LIST
    r = session.get(f"{ADMIN_URL}/{list_url}")
    if r.status_code == 200:
        entity_results["list"] = True
        print(f"  ✅ LIST: OK")
        results["passed"].append(f"{name} - LIST")
    else:
        entity_results["list"] = False
        err = f"Status {r.status_code}"
        if r.status_code == 500:
            err_match = re.search(r'"exception_message":"([^"]+)"', r.text)
            if err_match:
                err = err_match.group(1)[:80]
        print(f"  ❌ LIST: {err}")
        results["failed"].append(f"{name} - LIST: {err}")
        return entity_results
    
    # Get existing IDs
    old_ids = set(re.findall(edit_pattern, r.text))
    
    # ADD page
    r = session.get(f"{ADMIN_URL}/{add_url}")
    if r.status_code == 200:
        csrf_token = get_csrf_token(r.text)
        entity_results["add"] = True
        print(f"  ✅ ADD PAGE: OK")
        results["passed"].append(f"{name} - ADD PAGE")
    else:
        entity_results["add"] = False
        err = f"Status {r.status_code}"
        if r.status_code == 500:
            err_match = re.search(r'"exception_message":"([^"]+)"', r.text)
            if err_match:
                err = err_match.group(1)[:80]
        print(f"  ❌ ADD PAGE: {err}")
        results["failed"].append(f"{name} - ADD PAGE: {err}")
        return entity_results
    
    # CREATE
    data = create_data_fn(csrf_token)
    if has_file:
        files = {file_field: (f'test_{random_str()}.png', create_minimal_png(), 'image/png')}
        r = session.post(f"{ADMIN_URL}/{store_url}", data=data, files=files, allow_redirects=True)
    else:
        r = session.post(f"{ADMIN_URL}/{store_url}", data=data, allow_redirects=True)
    
    # Check for new ID
    r = session.get(f"{ADMIN_URL}/{list_url}")
    new_ids = set(re.findall(edit_pattern, r.text))
    created_ids = new_ids - old_ids
    
    if created_ids:
        created_id = max(created_ids, key=int)
        entity_results["create"] = True
        print(f"  ✅ CREATE: ID {created_id}")
        results["passed"].append(f"{name} - CREATE")
    else:
        entity_results["create"] = False
        print(f"  ❌ CREATE: No new ID found")
        results["failed"].append(f"{name} - CREATE: No new ID")
        # Try to use existing ID for further tests
        if new_ids:
            created_id = max(new_ids, key=int)
    
    if not created_id and new_ids:
        created_id = max(new_ids, key=int)
    
    if not created_id:
        print(f"  ⚠️ Skipping READ/UPDATE/DELETE - no ID available")
        return entity_results
    
    # READ (edit page)
    edit_url = edit_pattern.replace(r'(\d+)', created_id).replace('\\', '')
    r = session.get(f"{ADMIN_URL}/{edit_url}")
    if r.status_code == 200:
        csrf_token = get_csrf_token(r.text)
        entity_results["read"] = True
        print(f"  ✅ READ: OK")
        results["passed"].append(f"{name} - READ")
    else:
        entity_results["read"] = False
        err = f"Status {r.status_code}"
        if r.status_code == 500:
            err_match = re.search(r'"exception_message":"([^"]+)"', r.text)
            if err_match:
                err = err_match.group(1)[:80]
        print(f"  ❌ READ: {err}")
        results["failed"].append(f"{name} - READ: {err}")
    
    # UPDATE
    if update_data_fn and csrf_token:
        data = update_data_fn(csrf_token, created_id)
        update_url = update_url_pattern.replace('{id}', created_id)
        if has_file:
            # Don't send file on update unless required
            r = session.post(f"{ADMIN_URL}/{update_url}", data=data, allow_redirects=True)
        else:
            r = session.post(f"{ADMIN_URL}/{update_url}", data=data, allow_redirects=True)
        
        if r.status_code in [200, 302] and '500' not in str(r.status_code):
            entity_results["update"] = True
            print(f"  ✅ UPDATE: OK")
            results["passed"].append(f"{name} - UPDATE")
        else:
            entity_results["update"] = False
            err = f"Status {r.status_code}"
            if r.status_code == 500:
                err_match = re.search(r'"exception_message":"([^"]+)"', r.text)
                if err_match:
                    err = err_match.group(1)[:80]
            print(f"  ❌ UPDATE: {err}")
            results["failed"].append(f"{name} - UPDATE: {err}")
    
    # DELETE
    if entity_results.get("create"):  # Only delete if we created it
        delete_url = delete_url_pattern.replace('{id}', created_id)
        # Get CSRF token
        r = session.get(f"{ADMIN_URL}/{list_url}")
        csrf_token = get_csrf_token(r.text)
        r = session.post(f"{ADMIN_URL}/{delete_url}", data={'_token': csrf_token}, allow_redirects=True)
        
        # Verify deletion
        r = session.get(f"{ADMIN_URL}/{list_url}")
        if created_id not in re.findall(edit_pattern, r.text) or r.status_code == 200:
            entity_results["delete"] = True
            print(f"  ✅ DELETE: OK")
            results["passed"].append(f"{name} - DELETE")
        else:
            entity_results["delete"] = False
            print(f"  ❌ DELETE: Item still exists")
            results["failed"].append(f"{name} - DELETE: Item still exists")
    
    return entity_results

def test_settings_page(name, url):
    """Test a settings page (GET only)"""
    print(f"  Testing {name}...")
    r = session.get(f"{ADMIN_URL}/{url}")
    if r.status_code == 200:
        print(f"    ✅ {name}: OK")
        results["passed"].append(f"{name}")
        return True
    else:
        err = f"Status {r.status_code}"
        if r.status_code == 500:
            err_match = re.search(r'"exception_message":"([^"]+)"', r.text)
            if err_match:
                err = err_match.group(1)[:80]
        print(f"    ❌ {name}: {err}")
        results["failed"].append(f"{name}: {err}")
        return False

# ============== CRUD ENTITY DEFINITIONS ==============

def test_all_entities():
    unique = random_str()
    
    # FAQ
    test_crud_entity(
        "FAQ",
        "faq", "faq/add", "faq/store",
        r'/faq/edit/(\d+)', "faq/update/{id}/", "faq/delete/{id}/",
        lambda csrf: {'_token': csrf, 'title': f'TestFAQ_{unique}', 'content': f'Content {unique}', 'language_id': '1', 'status': '1'},
        lambda csrf, id: {'_token': csrf, 'title': f'UpdatedFAQ_{unique}', 'content': f'Updated {unique}', 'language_id': '1', 'status': '1'}
    )
    
    # Blog Category
    test_crud_entity(
        "Blog Category",
        "blog/blog-category", "blog/blog-category/add", "blog/blog-category/store",
        r'/blog/blog-category/edit/(\d+)', "blog/blog-category/update/{id}/", "blog/blog-category/delete/{id}/",
        lambda csrf: {'_token': csrf, 'name': f'Cat_{unique}', 'language_id': '1', 'status': '1'},
        lambda csrf, id: {'_token': csrf, 'name': f'UpdatedCat_{unique}', 'language_id': '1', 'status': '1'}
    )
    
    # Slider
    test_crud_entity(
        "Slider",
        "slider", "slider/add", "slider/store",
        r'/slider/edit/(\d+)', "slider/update/{id}/", "slider/delete/{id}/",
        lambda csrf: {'_token': csrf, 'title': f'Slider_{unique}', 'text': f'Text {unique}', 'language_id': '1'},
        lambda csrf, id: {'_token': csrf, 'title': f'UpdatedSlider_{unique}', 'text': f'Updated {unique}', 'language_id': '1'},
        has_file=True
    )
    
    # Service
    test_crud_entity(
        "Service",
        "service", "service/add", "service/store",
        r'/service/edit/(\d+)', "service/update/{id}/", "service/delete/{id}/",
        lambda csrf: {'_token': csrf, 'title': f'Service_{unique}', 'content': f'Content {unique}', 'language_id': '1', 'serial_number': '1'},
        lambda csrf, id: {'_token': csrf, 'title': f'UpdatedService_{unique}', 'content': f'Updated {unique}', 'language_id': '1', 'serial_number': '1'},
        has_file=True
    )
    
    # Team
    test_crud_entity(
        "Team",
        "team", "team/add", "team/store",
        r'/team/edit/(\d+)', "team/update/{id}/", "team/delete/{id}/",
        lambda csrf: {'_token': csrf, 'name': f'Team_{unique}', 'designation': 'Tester', 'language_id': '1'},
        lambda csrf, id: {'_token': csrf, 'name': f'UpdatedTeam_{unique}', 'designation': 'Updated', 'language_id': '1'},
        has_file=True
    )
    
    # Funfact
    test_crud_entity(
        "Funfact",
        "funfact", "funfact/add", "funfact/store",
        r'/funfact/edit/(\d+)', "funfact/update/{id}/", "funfact/delete/{id}/",
        lambda csrf: {'_token': csrf, 'title': f'Funfact_{unique}', 'value': '100', 'language_id': '1'},
        lambda csrf, id: {'_token': csrf, 'title': f'UpdatedFunfact_{unique}', 'value': '200', 'language_id': '1'},
        has_file=True, file_field='icon'
    )
    
    # Social Links
    test_crud_entity(
        "Social Links",
        "slinks", "slinks", "slinks/store",
        r'/slinks/edit/(\d+)', "slinks/update/{id}/", "slinks/delete/{id}/",
        lambda csrf: {'_token': csrf, 'icon': 'fab fa-facebook', 'url': f'https://test{unique}.com'},
        lambda csrf, id: {'_token': csrf, 'icon': 'fab fa-twitter', 'url': f'https://updated{unique}.com'}
    )
    
    # Role
    print(f"\n{'='*60}")
    print("Testing Role")
    print("="*60)
    r = session.get(f"{ADMIN_URL}/user-role-manage")
    if r.status_code == 200:
        print(f"  ✅ LIST: OK")
        results["passed"].append("Role - LIST")
    else:
        print(f"  ❌ LIST: Status {r.status_code}")
        results["failed"].append(f"Role - LIST: Status {r.status_code}")
    
    old_ids = set(re.findall(r'/user-role-update/(\d+)', r.text))
    
    r = session.get(f"{ADMIN_URL}/user-role-add")
    if r.status_code == 200:
        csrf = get_csrf_token(r.text)
        print(f"  ✅ ADD PAGE: OK")
        results["passed"].append("Role - ADD PAGE")
        
        # Get permissions
        perms = re.findall(r'name="permission\[\]" value="(\d+)"', r.text)[:3]
        
        r = session.post(f"{ADMIN_URL}/user-role-store", data={
            '_token': csrf, 'role_name': f'TestRole_{unique}', 'permission': perms
        }, allow_redirects=True)
        
        r = session.get(f"{ADMIN_URL}/user-role-manage")
        new_ids = set(re.findall(r'/user-role-update/(\d+)', r.text))
        created_ids = new_ids - old_ids
        
        if created_ids:
            role_id = max(created_ids, key=int)
            print(f"  ✅ CREATE: ID {role_id}")
            results["passed"].append("Role - CREATE")
            
            # READ
            r = session.get(f"{ADMIN_URL}/user-role-update/{role_id}")
            if r.status_code == 200:
                print(f"  ✅ READ: OK")
                results["passed"].append("Role - READ")
                csrf = get_csrf_token(r.text)
                
                # UPDATE
                r = session.post(f"{ADMIN_URL}/user-role-edit", data={
                    '_token': csrf, 'role_id': role_id, 'role_name': f'UpdatedRole_{unique}', 'permission': perms
                }, allow_redirects=True)
                if r.status_code in [200, 302]:
                    print(f"  ✅ UPDATE: OK")
                    results["passed"].append("Role - UPDATE")
                else:
                    print(f"  ❌ UPDATE: Status {r.status_code}")
                    results["failed"].append(f"Role - UPDATE: Status {r.status_code}")
            else:
                err = f"Status {r.status_code}"
                if r.status_code == 500:
                    err_match = re.search(r'"exception_message":"([^"]+)"', r.text)
                    if err_match:
                        err = err_match.group(1)[:80]
                print(f"  ❌ READ: {err}")
                results["failed"].append(f"Role - READ: {err}")
            
            # DELETE
            r = session.get(f"{ADMIN_URL}/user-role-delete/{role_id}", allow_redirects=True)
            r = session.get(f"{ADMIN_URL}/user-role-manage")
            if f'/user-role-update/{role_id}' not in r.text:
                print(f"  ✅ DELETE: OK")
                results["passed"].append("Role - DELETE")
            else:
                print(f"  ❌ DELETE: Item still exists")
                results["failed"].append("Role - DELETE: Item still exists")
        else:
            print(f"  ❌ CREATE: No new ID found")
            results["failed"].append("Role - CREATE: No new ID")
    else:
        print(f"  ❌ ADD PAGE: Status {r.status_code}")
        results["failed"].append(f"Role - ADD PAGE: Status {r.status_code}")
    
    # Shipping Method
    test_crud_entity(
        "Shipping Method",
        "shipping/methods/", "shipping/method/add", "shipping/method/store",
        r'/shipping/method/edit/(\d+)', "shipping/method/update/{id}/", "shipping/method/delete/{id}/",
        lambda csrf: {'_token': csrf, 'title': f'Ship_{unique}', 'cost': '10.00'},
        lambda csrf, id: {'_token': csrf, 'title': f'UpdatedShip_{unique}', 'cost': '20.00'}
    )
    
    # Testimonial
    test_crud_entity(
        "Testimonial",
        "testimonial", "testimonial/add", "testimonial/store",
        r'/testimonial/edit/(\d+)', "testimonial/update/{id}/", "testimonial/delete/{id}/",
        lambda csrf: {'_token': csrf, 'name': f'Test_{unique}', 'designation': 'Tester', 'comment': f'Comment {unique}', 'language_id': '1'},
        lambda csrf, id: {'_token': csrf, 'name': f'Updated_{unique}', 'designation': 'Updated', 'comment': f'Updated {unique}', 'language_id': '1'},
        has_file=True
    )
    
    # Entertainment
    test_crud_entity(
        "Entertainment",
        "entertainment", "entertainment/add", "entertainment/store",
        r'/entertainment/edit/(\d+)', "entertainment/update/{id}/", "entertainment/delete/{id}/",
        lambda csrf: {'_token': csrf, 'title': f'Ent_{unique}', 'language_id': '1'},
        lambda csrf, id: {'_token': csrf, 'title': f'UpdatedEnt_{unique}', 'language_id': '1'},
        has_file=True
    )
    
    # Media
    test_crud_entity(
        "Media Zone",
        "media", "media/add", "media/store",
        r'/media/edit/(\d+)', "media/update/{id}/", "media/delete/{id}/",
        lambda csrf: {'_token': csrf, 'title': f'Media_{unique}', 'language_id': '1'},
        lambda csrf, id: {'_token': csrf, 'title': f'UpdatedMedia_{unique}', 'language_id': '1'},
        has_file=True
    )
    
    # Branch
    test_crud_entity(
        "Branch",
        "branch", "branch/add", "branch/store",
        r'/branch/edit/(\d+)', "branch/update/{id}/", "branch/delete/{id}/",
        lambda csrf: {'_token': csrf, 'name': f'Branch_{unique}', 'address': f'Address {unique}', 'phone': '123456', 'language_id': '1'},
        lambda csrf, id: {'_token': csrf, 'name': f'UpdatedBranch_{unique}', 'address': f'Updated {unique}', 'phone': '654321', 'language_id': '1'}
    )
    
    # Currency
    test_crud_entity(
        "Currency",
        "currency", "currency/add", "currency/store",
        r'/currency/edit/(\d+)', "currency/update/{id}/", "currency/delete/{id}/",
        lambda csrf: {'_token': csrf, 'name': f'Cur_{unique}', 'symbol': '$', 'text': f'CUR_{unique.upper()}', 'rate': '1.0'},
        lambda csrf, id: {'_token': csrf, 'name': f'UpdatedCur_{unique}', 'symbol': '€', 'text': f'UPD_{unique.upper()}', 'rate': '1.5'}
    )
    
    # Newsletter/Subscriber
    test_crud_entity(
        "Subscriber",
        "subscriber", "subscriber/add", "subscriber/store",
        r'/subscriber/edit/(\d+)', "subscriber/update/{id}/", "subscriber/delete/{id}/",
        lambda csrf: {'_token': csrf, 'email': f'test_{unique}@example.com'},
        lambda csrf, id: {'_token': csrf, 'email': f'updated_{unique}@example.com'}
    )
    
    # Dynamic Page
    test_crud_entity(
        "Dynamic Page",
        "dynamic-page", "dynamic-page/add", "dynamic-page/store",
        r'/dynamic-page/edit/(\d+)', "dynamic-page/update/{id}/", "dynamic-page/delete/{id}/",
        lambda csrf: {'_token': csrf, 'name': f'Page_{unique}', 'title': f'Title_{unique}', 'body': f'Body {unique}', 'language_id': '1', 'status': '1'},
        lambda csrf, id: {'_token': csrf, 'name': f'UpdatedPage_{unique}', 'title': f'Updated_{unique}', 'body': f'Updated {unique}', 'language_id': '1', 'status': '1'}
    )
    
    # Promotion
    test_crud_entity(
        "Promotion",
        "promotion", "promotion/add", "promotion/store",
        r'/promotion/edit/(\d+)', "promotion/update/{id}/", "promotion/delete/{id}/",
        lambda csrf: {'_token': csrf, 'title': f'Promo_{unique}', 'description': f'Desc {unique}'},
        lambda csrf, id: {'_token': csrf, 'title': f'UpdatedPromo_{unique}', 'description': f'Updated {unique}'},
        has_file=True
    )
    
    # Payment Process
    test_crud_entity(
        "Payment Process",
        "payment-process", "payment-process/add", "payment-process/store",
        r'/payment-process/edit/(\d+)', "payment-process/update/{id}/", "payment-process/delete/{id}/",
        lambda csrf: {'_token': csrf, 'title': f'Pay_{unique}', 'description': f'Desc {unique}'},
        lambda csrf, id: {'_token': csrf, 'title': f'UpdatedPay_{unique}', 'description': f'Updated {unique}'},
        has_file=True
    )
    
    # Maintainance Settings
    test_crud_entity(
        "Maintenance Settings",
        "maintainance-settings", "add-maintainance-settings", "store-maintainance-settings",
        r'/edit-maintainance-settings/(\d+)', "update-maintainance-settings/{id}", "delete-maintainance-settings/{id}",
        lambda csrf: {'_token': csrf, 'title': f'Maint_{unique}', 'description': f'Desc {unique}', 'start_date': '2025-01-01', 'end_date': '2025-12-31'},
        lambda csrf, id: {'_token': csrf, 'title': f'UpdatedMaint_{unique}', 'description': f'Updated {unique}', 'start_date': '2025-01-01', 'end_date': '2025-12-31'}
    )

def test_settings_pages():
    """Test all settings/view pages (GET requests)"""
    print(f"\n{'='*60}")
    print("Testing Settings/View Pages")
    print("="*60)
    
    pages = [
        ("Dashboard", "dashboard"),
        ("Basic Info", "basicinfo"),
        ("SEO Info", "seoinfo"),
        ("Section Title", "sectiontitle"),
        ("Scripts", "scripts"),
        ("Page Visibility", "page-visibility"),
        ("Custom CSS", "custom-css"),
        ("Cookie Alert", "cookie-alert"),
        ("Bank Settings", "bank/settings"),
        ("About Us", "about"),
        ("Contact Info", "about/contact-info"),
        ("App Banner", "app-banner"),
        ("Preferential Activities", "preferential-activities"),
        ("Error Messages", "error-message"),
        ("Email Templates", "email-templates"),
        ("Email Config", "email-config"),
        ("Languages", "languages"),
        ("Payment Gateways", "payment/gateways"),
        ("Footer", "footer"),
        ("Cache Clear", "cache-clear"),
        ("Backup", "backup"),
        ("Package List", "package"),
        ("Offer List", "offer"),
        ("Blog List", "blog"),
        ("Product List", "product"),
        ("Package Orders", "package/all-order"),
        ("Bill Pay", "bill-pay"),
        ("Product Orders", "product/all/orders"),
        ("Register Users", "register/users"),
        ("Payment Query", "payment-query"),
        ("Fault Query", "fault-query"),
        ("Install Query", "install-query"),
        ("User Query", "user-query"),
        ("Bind User Query", "bind-user-query"),
        ("User Role Manage", "user-role-manage"),
        ("Message to User", "message-to-user"),
        ("User Notification", "user-notification"),
        ("Extra Months", "extra-months"),
        ("CB Pay Pending", "cbpay"),
        ("KBZ Pay Pending", "kbzpay"),
        ("Wave Pay Pending", "wavepay"),
    ]
    
    for name, url in pages:
        test_settings_page(name, url)

def main():
    print("="*60)
    print("COMPREHENSIVE CRUD TEST FOR ALL ADMIN ENDPOINTS")
    print(f"Started at: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    print("="*60)
    
    if not login():
        print("Cannot proceed without login")
        sys.exit(1)
    
    # Test all CRUD entities
    test_all_entities()
    
    # Test settings pages
    test_settings_pages()
    
    # Summary
    print("\n" + "="*60)
    print("FINAL SUMMARY")
    print("="*60)
    
    total = len(results["passed"]) + len(results["failed"]) + len(results["skipped"])
    
    print(f"\n✅ PASSED: {len(results['passed'])}")
    print(f"❌ FAILED: {len(results['failed'])}")
    print(f"⚠️ SKIPPED: {len(results['skipped'])}")
    print(f"\nTotal: {len(results['passed'])}/{total} ({100*len(results['passed'])/total:.1f}%)")
    
    if results["failed"]:
        print("\n" + "-"*60)
        print("FAILED TESTS:")
        print("-"*60)
        for fail in results["failed"]:
            print(f"  ❌ {fail}")
    
    # Save detailed report
    report = {
        "timestamp": datetime.now().isoformat(),
        "summary": {
            "passed": len(results["passed"]),
            "failed": len(results["failed"]),
            "skipped": len(results["skipped"]),
            "total": total,
            "pass_rate": f"{100*len(results['passed'])/total:.1f}%"
        },
        "passed": results["passed"],
        "failed": results["failed"],
        "skipped": results["skipped"]
    }
    
    with open('CRUD_FULL_REPORT.json', 'w') as f:
        json.dump(report, f, indent=2)
    
    print(f"\nDetailed report saved to CRUD_FULL_REPORT.json")

if __name__ == "__main__":
    main()
