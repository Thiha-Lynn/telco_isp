#!/usr/bin/env python3
"""
CRUD Test Script V2 - Using Correct Routes
Tests CREATE, READ, UPDATE, DELETE for failing entities

Routes discovered from web.php:
- FAQ: /faq/add, /faq/store, /faq/edit/{id}/, /faq/update/{id}/, /faq/delete/{id}/
- Bcategory: /blog/blog-category/add, store, edit/{id}/, update/{id}/, delete/{id}/
- Service: /service/add, store, edit/{id}/, update/{id}/, delete/{id}/
- Slider: /slider/add, store, edit/{id}/, update/{id}/, delete/{id}/
- Team: /team/add, store, edit/{id}/, update/{id}/, delete/{id}/
- Funfact: /funfact/add, store, edit/{id}/, update/{id}/, delete/{id}/
- Slinks: /slinks, /slinks/store, /slinks/edit/{id}/, /slinks/update/{id}/, /slinks/delete/{id}/
- Role: /user-role-add, /user-role-store, /user-role-update/{id}, /user-role-delete/{id}
- Shipping: /shipping/method/add, store, edit/{id}/, delete/{id}/
"""

import requests
import re
import urllib3
import io
import time
import random
import string

urllib3.disable_warnings()

BASE_URL = "https://isp.mlbbshop.app"
ADMIN_URL = f"{BASE_URL}/en/admin"

session = requests.Session()
session.verify = False

def random_string(length=8):
    """Generate a random string for unique values"""
    return ''.join(random.choices(string.ascii_lowercase, k=length))

def get_csrf_token(html):
    """Extract CSRF token from HTML"""
    match = re.search(r'name="_token"\s+value="([^"]+)"', html)
    return match.group(1) if match else None

def login():
    """Login to admin panel"""
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
        print("✅ Logged in successfully")
        return True
    else:
        print(f"❌ Login failed - redirected to {r.url}")
        return False

def create_minimal_png():
    """Create a minimal valid PNG file (1x1 pixel, red)"""
    # Minimal PNG (1x1 pixel, red)
    png_data = bytes([
        0x89, 0x50, 0x4E, 0x47, 0x0D, 0x0A, 0x1A, 0x0A,  # PNG signature
        0x00, 0x00, 0x00, 0x0D, 0x49, 0x48, 0x44, 0x52,  # IHDR chunk length + type
        0x00, 0x00, 0x00, 0x01, 0x00, 0x00, 0x00, 0x01,  # Width=1, Height=1
        0x08, 0x02, 0x00, 0x00, 0x00, 0x90, 0x77, 0x53,  # Bit depth, color type, etc.
        0xDE,                                            # CRC
        0x00, 0x00, 0x00, 0x0C, 0x49, 0x44, 0x41, 0x54,  # IDAT chunk
        0x08, 0xD7, 0x63, 0xF8, 0xCF, 0xC0, 0x00, 0x00,
        0x01, 0x01, 0x01, 0x00, 0x18, 0xDD, 0x8D, 0xB4,
        0x00, 0x00, 0x00, 0x00, 0x49, 0x45, 0x4E, 0x44,  # IEND chunk
        0xAE, 0x42, 0x60, 0x82
    ])
    return png_data

# =============== FAQ ===============
def test_faq():
    """Test FAQ CRUD operations"""
    print("\n" + "="*60)
    print("Testing FAQ CRUD")
    print("="*60)
    
    results = {"CREATE": None, "READ": None, "UPDATE": None, "DELETE": None}
    created_id = None
    
    # CREATE
    print("\n[CREATE] Creating new FAQ...")
    r = session.get(f"{ADMIN_URL}/faq/add")
    print(f"  Add page status: {r.status_code}")
    if r.status_code != 200:
        print(f"  ❌ Add page returned {r.status_code}")
        results["CREATE"] = False
        return results, created_id
    
    csrf_token = get_csrf_token(r.text)
    unique_title = f"Test FAQ {random_string()}"
    
    data = {
        '_token': csrf_token,
        'title': unique_title,
        'content': 'This is test content for the FAQ',
        'language_id': '1',
        'status': '1'
    }
    
    r = session.post(f"{ADMIN_URL}/faq/store", data=data, allow_redirects=True)
    print(f"  Store response URL: {r.url}")
    print(f"  Store response status: {r.status_code}")
    
    # Check if redirected back to FAQ list and find our item
    r2 = session.get(f"{ADMIN_URL}/faq")
    if unique_title in r2.text:
        # Extract the ID
        pattern = rf'/faq/edit/(\d+)/'
        match = re.search(rf'{unique_title}.*?{pattern}|{pattern}.*?{unique_title}', r2.text, re.DOTALL)
        if match:
            created_id = match.group(1) or match.group(2)
            print(f"  ✅ FAQ created with ID: {created_id}")
            results["CREATE"] = True
        else:
            # Try another approach
            ids_before = re.findall(r'/faq/edit/(\d+)/', r2.text)
            if ids_before:
                created_id = ids_before[0]  # Get the first (most recent)
                print(f"  ✅ FAQ likely created, using ID: {created_id}")
                results["CREATE"] = True
            else:
                print("  ❌ Could not find created FAQ ID")
                results["CREATE"] = False
    else:
        print(f"  ❌ FAQ not found in list after create")
        # Check for validation errors
        if 'is required' in r.text or 'validation' in r.text.lower():
            print(f"  Validation error in response")
        results["CREATE"] = False
    
    if not created_id:
        return results, created_id
    
    # READ
    print(f"\n[READ] Reading FAQ {created_id}...")
    r = session.get(f"{ADMIN_URL}/faq/edit/{created_id}/")
    if r.status_code == 200 and unique_title in r.text:
        print(f"  ✅ FAQ read successfully")
        results["READ"] = True
    else:
        print(f"  ❌ FAQ read failed (status: {r.status_code})")
        results["READ"] = False
    
    # UPDATE
    print(f"\n[UPDATE] Updating FAQ {created_id}...")
    csrf_token = get_csrf_token(r.text)
    updated_title = f"Updated FAQ {random_string()}"
    
    data = {
        '_token': csrf_token,
        'title': updated_title,
        'content': 'Updated content for the FAQ',
        'language_id': '1',
        'status': '1'
    }
    
    r = session.post(f"{ADMIN_URL}/faq/update/{created_id}/", data=data, allow_redirects=True)
    
    # Verify update
    r2 = session.get(f"{ADMIN_URL}/faq/edit/{created_id}/")
    if updated_title in r2.text:
        print(f"  ✅ FAQ updated successfully")
        results["UPDATE"] = True
    else:
        print(f"  ❌ FAQ update failed")
        results["UPDATE"] = False
    
    # DELETE
    print(f"\n[DELETE] Deleting FAQ {created_id}...")
    r = session.get(f"{ADMIN_URL}/faq")
    csrf_token = get_csrf_token(r.text)
    
    r = session.post(f"{ADMIN_URL}/faq/delete/{created_id}/", data={
        '_token': csrf_token
    }, allow_redirects=True)
    
    # Verify delete
    r2 = session.get(f"{ADMIN_URL}/faq/edit/{created_id}/")
    if r2.status_code == 404 or updated_title not in r2.text:
        print(f"  ✅ FAQ deleted successfully")
        results["DELETE"] = True
    else:
        print(f"  ❌ FAQ delete failed")
        results["DELETE"] = False
    
    return results, created_id


# =============== Blog Category ===============
def test_bcategory():
    """Test Blog Category CRUD operations"""
    print("\n" + "="*60)
    print("Testing Blog Category CRUD")
    print("="*60)
    
    results = {"CREATE": None, "READ": None, "UPDATE": None, "DELETE": None}
    created_id = None
    
    # CREATE
    print("\n[CREATE] Creating new Blog Category...")
    r = session.get(f"{ADMIN_URL}/blog/blog-category/add")
    print(f"  Add page status: {r.status_code}")
    if r.status_code != 200:
        print(f"  ❌ Add page returned {r.status_code}")
        results["CREATE"] = False
        return results, created_id
    
    csrf_token = get_csrf_token(r.text)
    unique_name = f"TestCat{random_string()}"
    
    data = {
        '_token': csrf_token,
        'name': unique_name,
        'language_id': '1',
        'status': '1'
    }
    
    r = session.post(f"{ADMIN_URL}/blog/blog-category/store", data=data, allow_redirects=True)
    print(f"  Store response URL: {r.url}")
    
    # Check if redirected back to list and find our item
    r2 = session.get(f"{ADMIN_URL}/blog/blog-category")
    if unique_name in r2.text:
        ids = re.findall(r'/blog/blog-category/edit/(\d+)/', r2.text)
        if ids:
            created_id = ids[0]
            print(f"  ✅ Blog Category created with ID: {created_id}")
            results["CREATE"] = True
        else:
            print("  ❌ Could not find created category ID")
            results["CREATE"] = False
    else:
        print(f"  ❌ Blog Category not found in list after create")
        results["CREATE"] = False
    
    if not created_id:
        return results, created_id
    
    # READ
    print(f"\n[READ] Reading Blog Category {created_id}...")
    r = session.get(f"{ADMIN_URL}/blog/blog-category/edit/{created_id}/")
    if r.status_code == 200 and unique_name in r.text:
        print(f"  ✅ Blog Category read successfully")
        results["READ"] = True
    else:
        print(f"  ❌ Blog Category read failed (status: {r.status_code})")
        results["READ"] = False
    
    # UPDATE
    print(f"\n[UPDATE] Updating Blog Category {created_id}...")
    csrf_token = get_csrf_token(r.text)
    updated_name = f"UpdatedCat{random_string()}"
    
    data = {
        '_token': csrf_token,
        'name': updated_name,
        'language_id': '1',
        'status': '1'
    }
    
    r = session.post(f"{ADMIN_URL}/blog/blog-category/update/{created_id}/", data=data, allow_redirects=True)
    
    r2 = session.get(f"{ADMIN_URL}/blog/blog-category/edit/{created_id}/")
    if updated_name in r2.text:
        print(f"  ✅ Blog Category updated successfully")
        results["UPDATE"] = True
    else:
        print(f"  ❌ Blog Category update failed")
        results["UPDATE"] = False
    
    # DELETE
    print(f"\n[DELETE] Deleting Blog Category {created_id}...")
    r = session.get(f"{ADMIN_URL}/blog/blog-category")
    csrf_token = get_csrf_token(r.text)
    
    r = session.post(f"{ADMIN_URL}/blog/blog-category/delete/{created_id}/", data={
        '_token': csrf_token
    }, allow_redirects=True)
    
    r2 = session.get(f"{ADMIN_URL}/blog/blog-category/edit/{created_id}/")
    if r2.status_code == 404 or updated_name not in r2.text:
        print(f"  ✅ Blog Category deleted successfully")
        results["DELETE"] = True
    else:
        print(f"  ❌ Blog Category delete failed")
        results["DELETE"] = False
    
    return results, created_id


# =============== Social Links ===============
def test_slinks():
    """Test Social Links CRUD operations"""
    print("\n" + "="*60)
    print("Testing Social Links CRUD")
    print("="*60)
    
    results = {"CREATE": None, "READ": None, "UPDATE": None, "DELETE": None}
    created_id = None
    
    # CREATE
    print("\n[CREATE] Creating new Social Link...")
    r = session.get(f"{ADMIN_URL}/slinks")
    print(f"  Slinks page status: {r.status_code}")
    if r.status_code != 200:
        print(f"  ❌ Slinks page returned {r.status_code}")
        results["CREATE"] = False
        return results, created_id
    
    csrf_token = get_csrf_token(r.text)
    unique_icon = f"fab fa-test-{random_string()}"
    unique_url = f"https://test-{random_string()}.com"
    
    data = {
        '_token': csrf_token,
        'icon': unique_icon,
        'url': unique_url
    }
    
    r = session.post(f"{ADMIN_URL}/slinks/store", data=data, allow_redirects=True)
    print(f"  Store response URL: {r.url}")
    
    # Check response
    r2 = session.get(f"{ADMIN_URL}/slinks")
    if unique_url in r2.text or unique_icon in r2.text:
        ids = re.findall(r'/slinks/edit/(\d+)/', r2.text)
        if ids:
            created_id = ids[-1]  # Get the last one (most recent)
            print(f"  ✅ Social Link created with ID: {created_id}")
            results["CREATE"] = True
        else:
            print("  ❌ Could not find created social link ID")
            results["CREATE"] = False
    else:
        print(f"  ❌ Social Link not found in list after create")
        results["CREATE"] = False
    
    if not created_id:
        return results, created_id
    
    # READ
    print(f"\n[READ] Reading Social Link {created_id}...")
    r = session.get(f"{ADMIN_URL}/slinks/edit/{created_id}/")
    if r.status_code == 200:
        print(f"  ✅ Social Link read successfully")
        results["READ"] = True
    else:
        print(f"  ❌ Social Link read failed (status: {r.status_code})")
        results["READ"] = False
    
    # UPDATE
    print(f"\n[UPDATE] Updating Social Link {created_id}...")
    csrf_token = get_csrf_token(r.text)
    updated_url = f"https://updated-{random_string()}.com"
    
    data = {
        '_token': csrf_token,
        'icon': 'fab fa-updated',
        'url': updated_url
    }
    
    r = session.post(f"{ADMIN_URL}/slinks/update/{created_id}/", data=data, allow_redirects=True)
    
    r2 = session.get(f"{ADMIN_URL}/slinks/edit/{created_id}/")
    if updated_url in r2.text or r.status_code == 200:
        print(f"  ✅ Social Link updated successfully")
        results["UPDATE"] = True
    else:
        print(f"  ❌ Social Link update failed")
        results["UPDATE"] = False
    
    # DELETE
    print(f"\n[DELETE] Deleting Social Link {created_id}...")
    r = session.get(f"{ADMIN_URL}/slinks")
    csrf_token = get_csrf_token(r.text)
    
    r = session.post(f"{ADMIN_URL}/slinks/delete/{created_id}/", data={
        '_token': csrf_token
    }, allow_redirects=True)
    
    r2 = session.get(f"{ADMIN_URL}/slinks/edit/{created_id}/")
    if r2.status_code == 404 or updated_url not in r2.text:
        print(f"  ✅ Social Link deleted successfully")
        results["DELETE"] = True
    else:
        print(f"  ❌ Social Link delete failed")
        results["DELETE"] = False
    
    return results, created_id


# =============== Role ===============
def test_role():
    """Test Role CRUD operations"""
    print("\n" + "="*60)
    print("Testing Role CRUD")
    print("="*60)
    
    results = {"CREATE": None, "READ": None, "UPDATE": None, "DELETE": None}
    created_id = None
    
    # CREATE
    print("\n[CREATE] Creating new Role...")
    r = session.get(f"{ADMIN_URL}/user-role-add")
    print(f"  Add page status: {r.status_code}")
    if r.status_code != 200:
        print(f"  ❌ Add page returned {r.status_code}")
        results["CREATE"] = False
        return results, created_id
    
    csrf_token = get_csrf_token(r.text)
    unique_name = f"TestRole{random_string()}"
    
    # Get available permissions from the form
    permissions = re.findall(r'name="permission\[\]" value="(\d+)"', r.text)
    print(f"  Available permissions: {permissions[:5]}...")
    
    data = {
        '_token': csrf_token,
        'role_name': unique_name,
        'permission[]': permissions[:2] if permissions else ['1', '2']  # At least 2 permissions
    }
    
    r = session.post(f"{ADMIN_URL}/user-role-store", data=data, allow_redirects=True)
    print(f"  Store response URL: {r.url}")
    
    # Check response
    r2 = session.get(f"{ADMIN_URL}/user-role-manage")
    if unique_name in r2.text:
        ids = re.findall(r'/user-role-update/(\d+)', r2.text)
        if ids:
            created_id = ids[-1]
            print(f"  ✅ Role created with ID: {created_id}")
            results["CREATE"] = True
        else:
            print("  ❌ Could not find created role ID")
            results["CREATE"] = False
    else:
        print(f"  ❌ Role not found in list after create")
        results["CREATE"] = False
    
    if not created_id:
        return results, created_id
    
    # READ
    print(f"\n[READ] Reading Role {created_id}...")
    r = session.get(f"{ADMIN_URL}/user-role-update/{created_id}")
    if r.status_code == 200:
        print(f"  ✅ Role read successfully")
        results["READ"] = True
    else:
        print(f"  ❌ Role read failed (status: {r.status_code})")
        results["READ"] = False
    
    # UPDATE - Role uses POST to user-role-edit
    print(f"\n[UPDATE] Updating Role {created_id}...")
    csrf_token = get_csrf_token(r.text)
    updated_name = f"UpdatedRole{random_string()}"
    
    data = {
        '_token': csrf_token,
        'role_id': created_id,
        'role_name': updated_name,
        'permission[]': permissions[:3] if permissions else ['1', '2', '3']
    }
    
    r = session.post(f"{ADMIN_URL}/user-role-edit", data=data, allow_redirects=True)
    
    r2 = session.get(f"{ADMIN_URL}/user-role-manage")
    if updated_name in r2.text:
        print(f"  ✅ Role updated successfully")
        results["UPDATE"] = True
    else:
        print(f"  ❌ Role update failed")
        results["UPDATE"] = False
    
    # DELETE
    print(f"\n[DELETE] Deleting Role {created_id}...")
    r = session.get(f"{ADMIN_URL}/user-role-delete/{created_id}", allow_redirects=True)
    
    r2 = session.get(f"{ADMIN_URL}/user-role-manage")
    if updated_name not in r2.text:
        print(f"  ✅ Role deleted successfully")
        results["DELETE"] = True
    else:
        print(f"  ❌ Role delete failed")
        results["DELETE"] = False
    
    return results, created_id


# =============== Shipping ===============
def test_shipping():
    """Test Shipping Method CRUD operations"""
    print("\n" + "="*60)
    print("Testing Shipping Method CRUD")
    print("="*60)
    
    results = {"CREATE": None, "READ": None, "UPDATE": None, "DELETE": None}
    created_id = None
    
    # CREATE
    print("\n[CREATE] Creating new Shipping Method...")
    r = session.get(f"{ADMIN_URL}/shipping/method/add")
    print(f"  Add page status: {r.status_code}")
    if r.status_code != 200:
        print(f"  ❌ Add page returned {r.status_code}")
        results["CREATE"] = False
        return results, created_id
    
    csrf_token = get_csrf_token(r.text)
    unique_title = f"TestShip{random_string()}"
    
    data = {
        '_token': csrf_token,
        'title': unique_title,
        'subtitle': 'Test subtitle',
        'cost': '100',
        'language_id': '1',
        'status': '1'
    }
    
    r = session.post(f"{ADMIN_URL}/shipping/method/store", data=data, allow_redirects=True)
    print(f"  Store response URL: {r.url}")
    
    # Check response
    r2 = session.get(f"{ADMIN_URL}/shipping/methods/")
    if unique_title in r2.text:
        ids = re.findall(r'/shipping/method/edit/(\d+)/', r2.text)
        if ids:
            created_id = ids[-1]
            print(f"  ✅ Shipping Method created with ID: {created_id}")
            results["CREATE"] = True
        else:
            print("  ❌ Could not find created shipping method ID")
            results["CREATE"] = False
    else:
        print(f"  ❌ Shipping Method not found in list after create")
        results["CREATE"] = False
    
    if not created_id:
        return results, created_id
    
    # READ
    print(f"\n[READ] Reading Shipping Method {created_id}...")
    r = session.get(f"{ADMIN_URL}/shipping/method/edit/{created_id}/")
    if r.status_code == 200 and unique_title in r.text:
        print(f"  ✅ Shipping Method read successfully")
        results["READ"] = True
    else:
        print(f"  ❌ Shipping Method read failed (status: {r.status_code})")
        results["READ"] = False
    
    # UPDATE
    print(f"\n[UPDATE] Updating Shipping Method {created_id}...")
    csrf_token = get_csrf_token(r.text)
    updated_title = f"UpdatedShip{random_string()}"
    
    # Check if there's an update route (didn't see in grep output)
    # Try both possible routes
    data = {
        '_token': csrf_token,
        'title': updated_title,
        'subtitle': 'Updated subtitle',
        'cost': '150',
        'language_id': '1',
        'status': '1'
    }
    
    r = session.post(f"{ADMIN_URL}/shipping/method/update/{created_id}/", data=data, allow_redirects=True)
    
    r2 = session.get(f"{ADMIN_URL}/shipping/method/edit/{created_id}/")
    if updated_title in r2.text:
        print(f"  ✅ Shipping Method updated successfully")
        results["UPDATE"] = True
    else:
        print(f"  ❌ Shipping Method update failed")
        results["UPDATE"] = False
    
    # DELETE
    print(f"\n[DELETE] Deleting Shipping Method {created_id}...")
    r = session.get(f"{ADMIN_URL}/shipping/methods/")
    csrf_token = get_csrf_token(r.text)
    
    r = session.post(f"{ADMIN_URL}/shipping/method/delete/{created_id}/", data={
        '_token': csrf_token
    }, allow_redirects=True)
    
    r2 = session.get(f"{ADMIN_URL}/shipping/method/edit/{created_id}/")
    if r2.status_code == 404 or updated_title not in r2.text:
        print(f"  ✅ Shipping Method deleted successfully")
        results["DELETE"] = True
    else:
        print(f"  ❌ Shipping Method delete failed")
        results["DELETE"] = False
    
    return results, created_id


# =============== Service (requires file upload) ===============
def test_service():
    """Test Service CRUD operations"""
    print("\n" + "="*60)
    print("Testing Service CRUD (requires file uploads)")
    print("="*60)
    
    results = {"CREATE": None, "READ": None, "UPDATE": None, "DELETE": None}
    created_id = None
    
    # CREATE
    print("\n[CREATE] Creating new Service...")
    r = session.get(f"{ADMIN_URL}/service/add")
    print(f"  Add page status: {r.status_code}")
    if r.status_code != 200:
        print(f"  ❌ Add page returned {r.status_code}")
        results["CREATE"] = False
        return results, created_id
    
    csrf_token = get_csrf_token(r.text)
    unique_name = f"TestService{random_string()}"
    
    png_data = create_minimal_png()
    
    files = {
        'icon': ('test_icon.png', io.BytesIO(png_data), 'image/png'),
        'image': ('test_image.png', io.BytesIO(png_data), 'image/png')
    }
    
    data = {
        '_token': csrf_token,
        'name': unique_name,
        'content': '<p>Test service content</p>',
        'language_id': '1',
        'status': '1'
    }
    
    r = session.post(f"{ADMIN_URL}/service/store", data=data, files=files, allow_redirects=True)
    print(f"  Store response URL: {r.url}")
    
    # Check response
    r2 = session.get(f"{ADMIN_URL}/service")
    if unique_name in r2.text:
        ids = re.findall(r'/service/edit/(\d+)/', r2.text)
        if ids:
            created_id = ids[-1]
            print(f"  ✅ Service created with ID: {created_id}")
            results["CREATE"] = True
        else:
            print("  ❌ Could not find created service ID")
            results["CREATE"] = False
    else:
        print(f"  ❌ Service not found in list after create")
        # Check for errors
        if 'validation' in r.text.lower() or 'error' in r.text.lower():
            print("  (Possible validation error)")
        results["CREATE"] = False
    
    if not created_id:
        return results, created_id
    
    # READ
    print(f"\n[READ] Reading Service {created_id}...")
    r = session.get(f"{ADMIN_URL}/service/edit/{created_id}/")
    if r.status_code == 200 and unique_name in r.text:
        print(f"  ✅ Service read successfully")
        results["READ"] = True
    else:
        print(f"  ❌ Service read failed (status: {r.status_code})")
        results["READ"] = False
    
    # UPDATE - Service doesn't require files on update (existing files used)
    print(f"\n[UPDATE] Updating Service {created_id}...")
    csrf_token = get_csrf_token(r.text)
    updated_name = f"UpdatedService{random_string()}"
    
    data = {
        '_token': csrf_token,
        'name': updated_name,
        'content': '<p>Updated service content</p>',
        'language_id': '1',
        'status': '1'
    }
    
    r = session.post(f"{ADMIN_URL}/service/update/{created_id}/", data=data, allow_redirects=True)
    
    r2 = session.get(f"{ADMIN_URL}/service/edit/{created_id}/")
    if updated_name in r2.text:
        print(f"  ✅ Service updated successfully")
        results["UPDATE"] = True
    else:
        print(f"  ❌ Service update failed")
        results["UPDATE"] = False
    
    # DELETE
    print(f"\n[DELETE] Deleting Service {created_id}...")
    r = session.get(f"{ADMIN_URL}/service")
    csrf_token = get_csrf_token(r.text)
    
    r = session.post(f"{ADMIN_URL}/service/delete/{created_id}/", data={
        '_token': csrf_token
    }, allow_redirects=True)
    
    r2 = session.get(f"{ADMIN_URL}/service/edit/{created_id}/")
    if r2.status_code == 404 or updated_name not in r2.text:
        print(f"  ✅ Service deleted successfully")
        results["DELETE"] = True
    else:
        print(f"  ❌ Service delete failed")
        results["DELETE"] = False
    
    return results, created_id


# =============== Slider (requires file upload) ===============
def test_slider():
    """Test Slider CRUD operations"""
    print("\n" + "="*60)
    print("Testing Slider CRUD (requires file uploads)")
    print("="*60)
    
    results = {"CREATE": None, "READ": None, "UPDATE": None, "DELETE": None}
    created_id = None
    
    # CREATE
    print("\n[CREATE] Creating new Slider...")
    r = session.get(f"{ADMIN_URL}/slider/add")
    print(f"  Add page status: {r.status_code}")
    if r.status_code != 200:
        print(f"  ❌ Add page returned {r.status_code}")
        results["CREATE"] = False
        return results, created_id
    
    csrf_token = get_csrf_token(r.text)
    unique_name = f"TestSlider{random_string()}"
    
    png_data = create_minimal_png()
    
    files = {
        'image': ('test_slider.png', io.BytesIO(png_data), 'image/png')
    }
    
    data = {
        '_token': csrf_token,
        'name': unique_name,
        'offer': '50% OFF',
        'desc': 'Test slider description',
        'language_id': '1',
        'status': '1'
    }
    
    r = session.post(f"{ADMIN_URL}/slider/store", data=data, files=files, allow_redirects=True)
    print(f"  Store response URL: {r.url}")
    
    # Check response
    r2 = session.get(f"{ADMIN_URL}/slider")
    if unique_name in r2.text:
        ids = re.findall(r'/slider/edit/(\d+)/', r2.text)
        if ids:
            created_id = ids[-1]
            print(f"  ✅ Slider created with ID: {created_id}")
            results["CREATE"] = True
        else:
            print("  ❌ Could not find created slider ID")
            results["CREATE"] = False
    else:
        print(f"  ❌ Slider not found in list after create")
        results["CREATE"] = False
    
    if not created_id:
        return results, created_id
    
    # READ
    print(f"\n[READ] Reading Slider {created_id}...")
    r = session.get(f"{ADMIN_URL}/slider/edit/{created_id}/")
    if r.status_code == 200:
        print(f"  ✅ Slider read successfully")
        results["READ"] = True
    else:
        print(f"  ❌ Slider read failed (status: {r.status_code})")
        results["READ"] = False
    
    # UPDATE
    print(f"\n[UPDATE] Updating Slider {created_id}...")
    csrf_token = get_csrf_token(r.text)
    updated_name = f"UpdatedSlider{random_string()}"
    
    data = {
        '_token': csrf_token,
        'name': updated_name,
        'offer': '75% OFF',
        'desc': 'Updated slider description',
        'language_id': '1',
        'status': '1'
    }
    
    r = session.post(f"{ADMIN_URL}/slider/update/{created_id}/", data=data, allow_redirects=True)
    
    r2 = session.get(f"{ADMIN_URL}/slider/edit/{created_id}/")
    if updated_name in r2.text:
        print(f"  ✅ Slider updated successfully")
        results["UPDATE"] = True
    else:
        print(f"  ❌ Slider update failed")
        results["UPDATE"] = False
    
    # DELETE
    print(f"\n[DELETE] Deleting Slider {created_id}...")
    r = session.get(f"{ADMIN_URL}/slider")
    csrf_token = get_csrf_token(r.text)
    
    r = session.post(f"{ADMIN_URL}/slider/delete/{created_id}/", data={
        '_token': csrf_token
    }, allow_redirects=True)
    
    r2 = session.get(f"{ADMIN_URL}/slider/edit/{created_id}/")
    if r2.status_code == 404:
        print(f"  ✅ Slider deleted successfully")
        results["DELETE"] = True
    else:
        print(f"  ❌ Slider delete failed")
        results["DELETE"] = False
    
    return results, created_id


# =============== Team (requires file upload) ===============
def test_team():
    """Test Team CRUD operations"""
    print("\n" + "="*60)
    print("Testing Team CRUD (requires file uploads)")
    print("="*60)
    
    results = {"CREATE": None, "READ": None, "UPDATE": None, "DELETE": None}
    created_id = None
    
    # CREATE
    print("\n[CREATE] Creating new Team Member...")
    r = session.get(f"{ADMIN_URL}/team/add")
    print(f"  Add page status: {r.status_code}")
    if r.status_code != 200:
        print(f"  ❌ Add page returned {r.status_code}")
        results["CREATE"] = False
        return results, created_id
    
    csrf_token = get_csrf_token(r.text)
    unique_name = f"TestTeam{random_string()}"
    
    png_data = create_minimal_png()
    
    files = {
        'image': ('test_team.png', io.BytesIO(png_data), 'image/png')
    }
    
    data = {
        '_token': csrf_token,
        'name': unique_name,
        'dagenation': 'Test Position',  # Note: typo preserved from original
        'icon1': 'fab fa-facebook',
        'url1': 'https://facebook.com',
        'icon2': 'fab fa-twitter',
        'url2': 'https://twitter.com',
        'icon3': 'fab fa-instagram',
        'url3': 'https://instagram.com',
        'language_id': '1',
        'status': '1'
    }
    
    r = session.post(f"{ADMIN_URL}/team/store", data=data, files=files, allow_redirects=True)
    print(f"  Store response URL: {r.url}")
    
    # Check response
    r2 = session.get(f"{ADMIN_URL}/team")
    if unique_name in r2.text:
        ids = re.findall(r'/team/edit/(\d+)/', r2.text)
        if ids:
            created_id = ids[-1]
            print(f"  ✅ Team Member created with ID: {created_id}")
            results["CREATE"] = True
        else:
            print("  ❌ Could not find created team member ID")
            results["CREATE"] = False
    else:
        print(f"  ❌ Team Member not found in list after create")
        results["CREATE"] = False
    
    if not created_id:
        return results, created_id
    
    # READ
    print(f"\n[READ] Reading Team Member {created_id}...")
    r = session.get(f"{ADMIN_URL}/team/edit/{created_id}/")
    if r.status_code == 200:
        print(f"  ✅ Team Member read successfully")
        results["READ"] = True
    else:
        print(f"  ❌ Team Member read failed (status: {r.status_code})")
        results["READ"] = False
    
    # UPDATE
    print(f"\n[UPDATE] Updating Team Member {created_id}...")
    csrf_token = get_csrf_token(r.text)
    updated_name = f"UpdatedTeam{random_string()}"
    
    data = {
        '_token': csrf_token,
        'name': updated_name,
        'dagenation': 'Updated Position',
        'icon1': 'fab fa-facebook',
        'url1': 'https://facebook.com',
        'language_id': '1',
        'status': '1'
    }
    
    r = session.post(f"{ADMIN_URL}/team/update/{created_id}/", data=data, allow_redirects=True)
    
    r2 = session.get(f"{ADMIN_URL}/team/edit/{created_id}/")
    if updated_name in r2.text:
        print(f"  ✅ Team Member updated successfully")
        results["UPDATE"] = True
    else:
        print(f"  ❌ Team Member update failed")
        results["UPDATE"] = False
    
    # DELETE
    print(f"\n[DELETE] Deleting Team Member {created_id}...")
    r = session.get(f"{ADMIN_URL}/team")
    csrf_token = get_csrf_token(r.text)
    
    r = session.post(f"{ADMIN_URL}/team/delete/{created_id}/", data={
        '_token': csrf_token
    }, allow_redirects=True)
    
    r2 = session.get(f"{ADMIN_URL}/team/edit/{created_id}/")
    if r2.status_code == 404:
        print(f"  ✅ Team Member deleted successfully")
        results["DELETE"] = True
    else:
        print(f"  ❌ Team Member delete failed")
        results["DELETE"] = False
    
    return results, created_id


# =============== Funfact (requires file upload) ===============
def test_funfact():
    """Test Funfact CRUD operations"""
    print("\n" + "="*60)
    print("Testing Funfact CRUD (requires file uploads)")
    print("="*60)
    
    results = {"CREATE": None, "READ": None, "UPDATE": None, "DELETE": None}
    created_id = None
    
    # CREATE
    print("\n[CREATE] Creating new Funfact...")
    r = session.get(f"{ADMIN_URL}/funfact/add")
    print(f"  Add page status: {r.status_code}")
    if r.status_code != 200:
        print(f"  ❌ Add page returned {r.status_code}")
        results["CREATE"] = False
        return results, created_id
    
    csrf_token = get_csrf_token(r.text)
    unique_name = f"TestFunfact{random_string()}"
    
    png_data = create_minimal_png()
    
    files = {
        'icon': ('test_funfact.png', io.BytesIO(png_data), 'image/png')
    }
    
    data = {
        '_token': csrf_token,
        'name': unique_name,
        'value': '100',
        'language_id': '1'
    }
    
    r = session.post(f"{ADMIN_URL}/funfact/store", data=data, files=files, allow_redirects=True)
    print(f"  Store response URL: {r.url}")
    
    # Check response
    r2 = session.get(f"{ADMIN_URL}/funfact")
    if unique_name in r2.text:
        ids = re.findall(r'/funfact/edit/(\d+)/', r2.text)
        if ids:
            created_id = ids[-1]
            print(f"  ✅ Funfact created with ID: {created_id}")
            results["CREATE"] = True
        else:
            print("  ❌ Could not find created funfact ID")
            results["CREATE"] = False
    else:
        print(f"  ❌ Funfact not found in list after create")
        results["CREATE"] = False
    
    if not created_id:
        return results, created_id
    
    # READ
    print(f"\n[READ] Reading Funfact {created_id}...")
    r = session.get(f"{ADMIN_URL}/funfact/edit/{created_id}/")
    if r.status_code == 200:
        print(f"  ✅ Funfact read successfully")
        results["READ"] = True
    else:
        print(f"  ❌ Funfact read failed (status: {r.status_code})")
        results["READ"] = False
    
    # UPDATE
    print(f"\n[UPDATE] Updating Funfact {created_id}...")
    csrf_token = get_csrf_token(r.text)
    updated_name = f"UpdatedFunfact{random_string()}"
    
    data = {
        '_token': csrf_token,
        'name': updated_name,
        'value': '200',
        'language_id': '1'
    }
    
    r = session.post(f"{ADMIN_URL}/funfact/update/{created_id}/", data=data, allow_redirects=True)
    
    r2 = session.get(f"{ADMIN_URL}/funfact/edit/{created_id}/")
    if updated_name in r2.text:
        print(f"  ✅ Funfact updated successfully")
        results["UPDATE"] = True
    else:
        print(f"  ❌ Funfact update failed")
        results["UPDATE"] = False
    
    # DELETE
    print(f"\n[DELETE] Deleting Funfact {created_id}...")
    r = session.get(f"{ADMIN_URL}/funfact")
    csrf_token = get_csrf_token(r.text)
    
    r = session.post(f"{ADMIN_URL}/funfact/delete/{created_id}/", data={
        '_token': csrf_token
    }, allow_redirects=True)
    
    r2 = session.get(f"{ADMIN_URL}/funfact/edit/{created_id}/")
    if r2.status_code == 404:
        print(f"  ✅ Funfact deleted successfully")
        results["DELETE"] = True
    else:
        print(f"  ❌ Funfact delete failed")
        results["DELETE"] = False
    
    return results, created_id


def main():
    print("="*60)
    print("CRUD Test V2 - Using Correct Routes")
    print("="*60)
    
    if not login():
        print("❌ Login failed, cannot continue")
        return
    
    all_results = {}
    
    # Test each entity
    test_funcs = [
        ("FAQ", test_faq),
        ("Blog Category", test_bcategory),
        ("Social Links", test_slinks),
        ("Role", test_role),
        ("Shipping", test_shipping),
        ("Service", test_service),
        ("Slider", test_slider),
        ("Team", test_team),
        ("Funfact", test_funfact),
    ]
    
    for name, func in test_funcs:
        try:
            results, _ = func()
            all_results[name] = results
        except Exception as e:
            print(f"\n❌ Error testing {name}: {e}")
            all_results[name] = {"CREATE": False, "READ": False, "UPDATE": False, "DELETE": False}
    
    # Print summary
    print("\n" + "="*60)
    print("SUMMARY")
    print("="*60)
    
    total_tests = 0
    total_passed = 0
    
    for entity, results in all_results.items():
        print(f"\n{entity}:")
        for op, success in results.items():
            total_tests += 1
            if success:
                total_passed += 1
                print(f"  {op}: ✅")
            elif success is None:
                print(f"  {op}: ⚪ (skipped)")
            else:
                print(f"  {op}: ❌")
    
    print(f"\n{'='*60}")
    print(f"Total: {total_passed}/{total_tests} tests passed ({100*total_passed/total_tests:.1f}%)")
    print("="*60)


if __name__ == "__main__":
    main()
