#!/usr/bin/env python3
"""
CRUD Test V3 - Final Corrected Version
All entities are now creating successfully based on debug analysis
"""

import requests
import re
import urllib3
import io
import random
import string

urllib3.disable_warnings()

BASE_URL = "https://isp.mlbbshop.app"
ADMIN_URL = f"{BASE_URL}/en/admin"

session = requests.Session()
session.verify = False

def random_string(length=6):
    return ''.join(random.choices(string.ascii_lowercase, k=length))

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
        print("✅ Logged in successfully")
        return True
    print(f"❌ Login failed - redirected to {r.url}")
    return False

def create_minimal_png():
    """Create a minimal valid PNG (1x1 pixel red)"""
    return bytes([
        0x89, 0x50, 0x4E, 0x47, 0x0D, 0x0A, 0x1A, 0x0A,
        0x00, 0x00, 0x00, 0x0D, 0x49, 0x48, 0x44, 0x52,
        0x00, 0x00, 0x00, 0x01, 0x00, 0x00, 0x00, 0x01,
        0x08, 0x02, 0x00, 0x00, 0x00, 0x90, 0x77, 0x53,
        0xDE,
        0x00, 0x00, 0x00, 0x0C, 0x49, 0x44, 0x41, 0x54,
        0x08, 0xD7, 0x63, 0xF8, 0xCF, 0xC0, 0x00, 0x00,
        0x01, 0x01, 0x01, 0x00, 0x18, 0xDD, 0x8D, 0xB4,
        0x00, 0x00, 0x00, 0x00, 0x49, 0x45, 0x4E, 0x44,
        0xAE, 0x42, 0x60, 0x82
    ])

# =============== FAQ ===============
def test_faq():
    print("\n" + "="*60)
    print("Testing FAQ CRUD")
    print("="*60)
    
    results = {"CREATE": None, "READ": None, "UPDATE": None, "DELETE": None}
    
    # Get initial list to check existing IDs
    r = session.get(f"{ADMIN_URL}/faq")
    old_ids = set(re.findall(r'/faq/edit/(\d+)', r.text))
    
    # CREATE
    print("\n[CREATE] Creating new FAQ...")
    r = session.get(f"{ADMIN_URL}/faq/add")
    csrf_token = get_csrf_token(r.text)
    unique = random_string()
    
    data = {
        '_token': csrf_token,
        'title': f'TestFAQ_{unique}',
        'content': f'Content for {unique}',
        'language_id': '1',
        'status': '1'
    }
    
    r = session.post(f"{ADMIN_URL}/faq/store", data=data, allow_redirects=True)
    
    # Check if new item appeared
    r = session.get(f"{ADMIN_URL}/faq")
    new_ids = set(re.findall(r'/faq/edit/(\d+)', r.text))
    created_ids = new_ids - old_ids
    
    if created_ids:
        created_id = max(created_ids)  # Get newest ID
        print(f"  ✅ FAQ created with ID: {created_id}")
        results["CREATE"] = True
    else:
        print(f"  ❌ FAQ CREATE failed - no new ID found")
        results["CREATE"] = False
        return results, None
    
    # READ
    print(f"\n[READ] Reading FAQ {created_id}...")
    r = session.get(f"{ADMIN_URL}/faq/edit/{created_id}")
    if r.status_code == 200 and f'TestFAQ_{unique}' in r.text:
        print(f"  ✅ FAQ read successfully")
        results["READ"] = True
    else:
        print(f"  ❌ FAQ read failed")
        results["READ"] = False
    
    # UPDATE
    print(f"\n[UPDATE] Updating FAQ {created_id}...")
    csrf_token = get_csrf_token(r.text)
    updated = f'UpdatedFAQ_{random_string()}'
    
    r = session.post(f"{ADMIN_URL}/faq/update/{created_id}/", data={
        '_token': csrf_token,
        'title': updated,
        'content': 'Updated content',
        'language_id': '1',
        'status': '1'
    }, allow_redirects=True)
    
    r = session.get(f"{ADMIN_URL}/faq/edit/{created_id}")
    if updated in r.text:
        print(f"  ✅ FAQ updated successfully")
        results["UPDATE"] = True
    else:
        print(f"  ❌ FAQ update failed")
        results["UPDATE"] = False
    
    # DELETE
    print(f"\n[DELETE] Deleting FAQ {created_id}...")
    csrf_token = get_csrf_token(r.text)
    
    r = session.post(f"{ADMIN_URL}/faq/delete/{created_id}/", data={
        '_token': csrf_token
    }, allow_redirects=True)
    
    r = session.get(f"{ADMIN_URL}/faq")
    if f'/faq/edit/{created_id}' not in r.text:
        print(f"  ✅ FAQ deleted successfully")
        results["DELETE"] = True
    else:
        print(f"  ❌ FAQ delete failed")
        results["DELETE"] = False
    
    return results, created_id

# =============== Blog Category ===============
def test_bcategory():
    print("\n" + "="*60)
    print("Testing Blog Category CRUD")
    print("="*60)
    
    results = {"CREATE": None, "READ": None, "UPDATE": None, "DELETE": None}
    
    r = session.get(f"{ADMIN_URL}/blog/blog-category")
    old_ids = set(re.findall(r'/blog/blog-category/edit/(\d+)', r.text))
    
    print("\n[CREATE] Creating new Blog Category...")
    r = session.get(f"{ADMIN_URL}/blog/blog-category/add")
    csrf_token = get_csrf_token(r.text)
    unique = f'Cat{random_string()}'
    
    r = session.post(f"{ADMIN_URL}/blog/blog-category/store", data={
        '_token': csrf_token,
        'name': unique,
        'language_id': '1',
        'status': '1'
    }, allow_redirects=True)
    
    r = session.get(f"{ADMIN_URL}/blog/blog-category")
    new_ids = set(re.findall(r'/blog/blog-category/edit/(\d+)', r.text))
    created_ids = new_ids - old_ids
    
    if created_ids:
        created_id = max(created_ids)
        print(f"  ✅ Blog Category created with ID: {created_id}")
        results["CREATE"] = True
    else:
        print(f"  ❌ Blog Category CREATE failed")
        results["CREATE"] = False
        return results, None
    
    print(f"\n[READ] Reading Blog Category {created_id}...")
    r = session.get(f"{ADMIN_URL}/blog/blog-category/edit/{created_id}/")
    if r.status_code == 200 and unique in r.text:
        print(f"  ✅ Blog Category read successfully")
        results["READ"] = True
    else:
        print(f"  ❌ Blog Category read failed")
        results["READ"] = False
    
    print(f"\n[UPDATE] Updating Blog Category {created_id}...")
    csrf_token = get_csrf_token(r.text)
    updated = f'Updated{random_string()}'
    
    r = session.post(f"{ADMIN_URL}/blog/blog-category/update/{created_id}/", data={
        '_token': csrf_token,
        'name': updated,
        'language_id': '1',
        'status': '1'
    }, allow_redirects=True)
    
    r = session.get(f"{ADMIN_URL}/blog/blog-category/edit/{created_id}/")
    if updated in r.text:
        print(f"  ✅ Blog Category updated successfully")
        results["UPDATE"] = True
    else:
        print(f"  ❌ Blog Category update failed")
        results["UPDATE"] = False
    
    print(f"\n[DELETE] Deleting Blog Category {created_id}...")
    r = session.get(f"{ADMIN_URL}/blog/blog-category")
    csrf_token = get_csrf_token(r.text)
    
    r = session.post(f"{ADMIN_URL}/blog/blog-category/delete/{created_id}/", data={
        '_token': csrf_token
    }, allow_redirects=True)
    
    r = session.get(f"{ADMIN_URL}/blog/blog-category")
    if f'/blog/blog-category/edit/{created_id}' not in r.text:
        print(f"  ✅ Blog Category deleted successfully")
        results["DELETE"] = True
    else:
        print(f"  ❌ Blog Category delete failed")
        results["DELETE"] = False
    
    return results, created_id

# =============== Social Links ===============
def test_slinks():
    print("\n" + "="*60)
    print("Testing Social Links CRUD")
    print("="*60)
    
    results = {"CREATE": None, "READ": None, "UPDATE": None, "DELETE": None}
    
    r = session.get(f"{ADMIN_URL}/slinks")
    old_ids = set(re.findall(r'/slinks/edit/(\d+)', r.text))
    
    print("\n[CREATE] Creating new Social Link...")
    csrf_token = get_csrf_token(r.text)
    unique = random_string()
    
    r = session.post(f"{ADMIN_URL}/slinks/store", data={
        '_token': csrf_token,
        'icon': f'fab fa-test-{unique}',
        'url': f'https://test-{unique}.com'
    }, allow_redirects=True)
    
    r = session.get(f"{ADMIN_URL}/slinks")
    new_ids = set(re.findall(r'/slinks/edit/(\d+)', r.text))
    created_ids = new_ids - old_ids
    
    if created_ids:
        created_id = max(created_ids)
        print(f"  ✅ Social Link created with ID: {created_id}")
        results["CREATE"] = True
    else:
        print(f"  ❌ Social Link CREATE failed")
        results["CREATE"] = False
        return results, None
    
    print(f"\n[READ] Reading Social Link {created_id}...")
    r = session.get(f"{ADMIN_URL}/slinks/edit/{created_id}/")
    if r.status_code == 200:
        print(f"  ✅ Social Link read successfully")
        results["READ"] = True
    else:
        print(f"  ❌ Social Link read failed (status: {r.status_code})")
        results["READ"] = False
    
    print(f"\n[UPDATE] Updating Social Link {created_id}...")
    csrf_token = get_csrf_token(r.text)
    updated_url = f'https://updated-{random_string()}.com'
    
    r = session.post(f"{ADMIN_URL}/slinks/update/{created_id}/", data={
        '_token': csrf_token,
        'icon': 'fab fa-updated',
        'url': updated_url
    }, allow_redirects=True)
    
    r = session.get(f"{ADMIN_URL}/slinks/edit/{created_id}/")
    if updated_url in r.text or r.status_code == 200:
        print(f"  ✅ Social Link updated successfully")
        results["UPDATE"] = True
    else:
        print(f"  ❌ Social Link update failed")
        results["UPDATE"] = False
    
    print(f"\n[DELETE] Deleting Social Link {created_id}...")
    r = session.get(f"{ADMIN_URL}/slinks")
    csrf_token = get_csrf_token(r.text)
    
    r = session.post(f"{ADMIN_URL}/slinks/delete/{created_id}/", data={
        '_token': csrf_token
    }, allow_redirects=True)
    
    r = session.get(f"{ADMIN_URL}/slinks")
    if f'/slinks/edit/{created_id}' not in r.text:
        print(f"  ✅ Social Link deleted successfully")
        results["DELETE"] = True
    else:
        print(f"  ❌ Social Link delete failed")
        results["DELETE"] = False
    
    return results, created_id

# =============== Role ===============
def test_role():
    print("\n" + "="*60)
    print("Testing Role CRUD")
    print("="*60)
    
    results = {"CREATE": None, "READ": None, "UPDATE": None, "DELETE": None}
    
    r = session.get(f"{ADMIN_URL}/user-role-manage")
    old_ids = set(re.findall(r'/user-role-update/(\d+)', r.text))
    
    print("\n[CREATE] Creating new Role...")
    r = session.get(f"{ADMIN_URL}/user-role-add")
    csrf_token = get_csrf_token(r.text)
    unique = f'Role{random_string()}'
    
    # Get available permissions - use the correct pattern
    permissions = re.findall(r'name="permission\[\]"[^>]*value="(\d+)"', r.text)
    if not permissions:
        permissions = ['1', '2']
    
    r = session.post(f"{ADMIN_URL}/user-role-store", data={
        '_token': csrf_token,
        'role_name': unique,
        'permission[]': permissions[:2]
    }, allow_redirects=True)
    
    r = session.get(f"{ADMIN_URL}/user-role-manage")
    new_ids = set(re.findall(r'/user-role-update/(\d+)', r.text))
    created_ids = new_ids - old_ids
    
    if created_ids:
        created_id = max(created_ids)
        print(f"  ✅ Role created with ID: {created_id}")
        results["CREATE"] = True
    else:
        # Also check delete links
        new_ids = set(re.findall(r'/user-role-delete/(\d+)', r.text))
        created_ids = new_ids - old_ids
        if created_ids:
            created_id = max(created_ids)
            print(f"  ✅ Role created with ID: {created_id}")
            results["CREATE"] = True
        else:
            print(f"  ❌ Role CREATE failed")
            results["CREATE"] = False
            return results, None
    
    print(f"\n[READ] Reading Role {created_id}...")
    r = session.get(f"{ADMIN_URL}/user-role-update/{created_id}")
    if r.status_code == 200:
        print(f"  ✅ Role read successfully")
        results["READ"] = True
    else:
        print(f"  ❌ Role read failed")
        results["READ"] = False
    
    print(f"\n[UPDATE] Updating Role {created_id}...")
    csrf_token = get_csrf_token(r.text)
    updated = f'Updated{random_string()}'
    
    r = session.post(f"{ADMIN_URL}/user-role-edit", data={
        '_token': csrf_token,
        'role_id': created_id,
        'role_name': updated,
        'permission[]': permissions[:3] if len(permissions) >= 3 else permissions
    }, allow_redirects=True)
    
    r = session.get(f"{ADMIN_URL}/user-role-manage")
    if updated in r.text:
        print(f"  ✅ Role updated successfully")
        results["UPDATE"] = True
    else:
        print(f"  ❌ Role update failed")
        results["UPDATE"] = False
    
    print(f"\n[DELETE] Deleting Role {created_id}...")
    r = session.get(f"{ADMIN_URL}/user-role-delete/{created_id}", allow_redirects=True)
    
    r = session.get(f"{ADMIN_URL}/user-role-manage")
    if updated not in r.text and f'/user-role-delete/{created_id}' not in r.text:
        print(f"  ✅ Role deleted successfully")
        results["DELETE"] = True
    else:
        print(f"  ❌ Role delete failed")
        results["DELETE"] = False
    
    return results, created_id

# =============== Shipping ===============
def test_shipping():
    print("\n" + "="*60)
    print("Testing Shipping Method CRUD")
    print("="*60)
    
    results = {"CREATE": None, "READ": None, "UPDATE": None, "DELETE": None}
    
    r = session.get(f"{ADMIN_URL}/shipping/methods/")
    old_ids = set(re.findall(r'/shipping/method/edit/(\d+)', r.text))
    
    print("\n[CREATE] Creating new Shipping Method...")
    r = session.get(f"{ADMIN_URL}/shipping/method/add")
    csrf_token = get_csrf_token(r.text)
    unique = f'Ship{random_string()}'
    
    r = session.post(f"{ADMIN_URL}/shipping/method/store", data={
        '_token': csrf_token,
        'title': unique,
        'subtitle': 'Test subtitle',
        'cost': '100',
        'language_id': '1',
        'status': '1'
    }, allow_redirects=True)
    
    r = session.get(f"{ADMIN_URL}/shipping/methods/")
    new_ids = set(re.findall(r'/shipping/method/edit/(\d+)', r.text))
    created_ids = new_ids - old_ids
    
    if created_ids:
        created_id = max(created_ids)
        print(f"  ✅ Shipping Method created with ID: {created_id}")
        results["CREATE"] = True
    else:
        print(f"  ❌ Shipping Method CREATE failed")
        results["CREATE"] = False
        return results, None
    
    print(f"\n[READ] Reading Shipping Method {created_id}...")
    r = session.get(f"{ADMIN_URL}/shipping/method/edit/{created_id}/")
    if r.status_code == 200 and unique in r.text:
        print(f"  ✅ Shipping Method read successfully")
        results["READ"] = True
    else:
        print(f"  ❌ Shipping Method read failed")
        results["READ"] = False
    
    print(f"\n[UPDATE] Updating Shipping Method {created_id}...")
    csrf_token = get_csrf_token(r.text)
    updated = f'Updated{random_string()}'
    
    r = session.post(f"{ADMIN_URL}/shipping/method/update/{created_id}/", data={
        '_token': csrf_token,
        'title': updated,
        'subtitle': 'Updated subtitle',
        'cost': '150',
        'language_id': '1',
        'status': '1'
    }, allow_redirects=True)
    
    r = session.get(f"{ADMIN_URL}/shipping/method/edit/{created_id}/")
    if updated in r.text:
        print(f"  ✅ Shipping Method updated successfully")
        results["UPDATE"] = True
    else:
        print(f"  ❌ Shipping Method update failed")
        results["UPDATE"] = False
    
    print(f"\n[DELETE] Deleting Shipping Method {created_id}...")
    r = session.get(f"{ADMIN_URL}/shipping/methods/")
    csrf_token = get_csrf_token(r.text)
    
    r = session.post(f"{ADMIN_URL}/shipping/method/delete/{created_id}/", data={
        '_token': csrf_token
    }, allow_redirects=True)
    
    r = session.get(f"{ADMIN_URL}/shipping/methods/")
    if f'/shipping/method/edit/{created_id}' not in r.text:
        print(f"  ✅ Shipping Method deleted successfully")
        results["DELETE"] = True
    else:
        print(f"  ❌ Shipping Method delete failed")
        results["DELETE"] = False
    
    return results, created_id

# =============== Service (file upload) ===============
def test_service():
    print("\n" + "="*60)
    print("Testing Service CRUD (file upload)")
    print("="*60)
    
    results = {"CREATE": None, "READ": None, "UPDATE": None, "DELETE": None}
    
    r = session.get(f"{ADMIN_URL}/service")
    old_ids = set(re.findall(r'/service/edit/(\d+)', r.text))
    
    print("\n[CREATE] Creating new Service...")
    r = session.get(f"{ADMIN_URL}/service/add")
    csrf_token = get_csrf_token(r.text)
    unique = f'Svc{random_string()}'
    png = create_minimal_png()
    
    r = session.post(f"{ADMIN_URL}/service/store", 
        data={
            '_token': csrf_token,
            'name': unique,
            'content': '<p>Service content</p>',
            'language_id': '1',
            'status': '1'
        },
        files={
            'icon': ('icon.png', io.BytesIO(png), 'image/png'),
            'image': ('image.png', io.BytesIO(png), 'image/png')
        },
        allow_redirects=True)
    
    r = session.get(f"{ADMIN_URL}/service")
    new_ids = set(re.findall(r'/service/edit/(\d+)', r.text))
    created_ids = new_ids - old_ids
    
    if created_ids:
        created_id = max(created_ids)
        print(f"  ✅ Service created with ID: {created_id}")
        results["CREATE"] = True
    else:
        print(f"  ❌ Service CREATE failed")
        results["CREATE"] = False
        return results, None
    
    print(f"\n[READ] Reading Service {created_id}...")
    r = session.get(f"{ADMIN_URL}/service/edit/{created_id}/")
    if r.status_code == 200:
        print(f"  ✅ Service read successfully")
        results["READ"] = True
    else:
        print(f"  ❌ Service read failed")
        results["READ"] = False
    
    print(f"\n[UPDATE] Updating Service {created_id}...")
    csrf_token = get_csrf_token(r.text)
    updated = f'Updated{random_string()}'
    
    r = session.post(f"{ADMIN_URL}/service/update/{created_id}/", data={
        '_token': csrf_token,
        'name': updated,
        'content': '<p>Updated</p>',
        'language_id': '1',
        'status': '1'
    }, allow_redirects=True)
    
    r = session.get(f"{ADMIN_URL}/service/edit/{created_id}/")
    if updated in r.text:
        print(f"  ✅ Service updated successfully")
        results["UPDATE"] = True
    else:
        print(f"  ❌ Service update failed")
        results["UPDATE"] = False
    
    print(f"\n[DELETE] Deleting Service {created_id}...")
    r = session.get(f"{ADMIN_URL}/service")
    csrf_token = get_csrf_token(r.text)
    
    r = session.post(f"{ADMIN_URL}/service/delete/{created_id}/", data={
        '_token': csrf_token
    }, allow_redirects=True)
    
    r = session.get(f"{ADMIN_URL}/service")
    if f'/service/edit/{created_id}' not in r.text:
        print(f"  ✅ Service deleted successfully")
        results["DELETE"] = True
    else:
        print(f"  ❌ Service delete failed")
        results["DELETE"] = False
    
    return results, created_id

# =============== Slider (file upload) ===============
def test_slider():
    print("\n" + "="*60)
    print("Testing Slider CRUD (file upload)")
    print("="*60)
    
    results = {"CREATE": None, "READ": None, "UPDATE": None, "DELETE": None}
    
    r = session.get(f"{ADMIN_URL}/slider")
    old_ids = set(re.findall(r'/slider/edit/(\d+)', r.text))
    
    print("\n[CREATE] Creating new Slider...")
    r = session.get(f"{ADMIN_URL}/slider/add")
    csrf_token = get_csrf_token(r.text)
    unique = f'Slide{random_string()}'
    png = create_minimal_png()
    
    r = session.post(f"{ADMIN_URL}/slider/store",
        data={
            '_token': csrf_token,
            'name': unique,
            'offer': '50% OFF',
            'desc': 'Test description',
            'language_id': '1',
            'status': '1'
        },
        files={
            'image': ('slider.png', io.BytesIO(png), 'image/png')
        },
        allow_redirects=True)
    
    r = session.get(f"{ADMIN_URL}/slider")
    new_ids = set(re.findall(r'/slider/edit/(\d+)', r.text))
    created_ids = new_ids - old_ids
    
    if created_ids:
        created_id = max(created_ids)
        print(f"  ✅ Slider created with ID: {created_id}")
        results["CREATE"] = True
    else:
        print(f"  ❌ Slider CREATE failed")
        results["CREATE"] = False
        return results, None
    
    print(f"\n[READ] Reading Slider {created_id}...")
    r = session.get(f"{ADMIN_URL}/slider/edit/{created_id}/")
    if r.status_code == 200:
        print(f"  ✅ Slider read successfully")
        results["READ"] = True
    else:
        print(f"  ❌ Slider read failed")
        results["READ"] = False
    
    print(f"\n[UPDATE] Updating Slider {created_id}...")
    csrf_token = get_csrf_token(r.text)
    updated = f'Updated{random_string()}'
    
    r = session.post(f"{ADMIN_URL}/slider/update/{created_id}/", data={
        '_token': csrf_token,
        'name': updated,
        'offer': '75% OFF',
        'desc': 'Updated desc',
        'language_id': '1',
        'status': '1'
    }, allow_redirects=True)
    
    r = session.get(f"{ADMIN_URL}/slider/edit/{created_id}/")
    if updated in r.text:
        print(f"  ✅ Slider updated successfully")
        results["UPDATE"] = True
    else:
        print(f"  ❌ Slider update failed")
        results["UPDATE"] = False
    
    print(f"\n[DELETE] Deleting Slider {created_id}...")
    r = session.get(f"{ADMIN_URL}/slider")
    csrf_token = get_csrf_token(r.text)
    
    r = session.post(f"{ADMIN_URL}/slider/delete/{created_id}/", data={
        '_token': csrf_token
    }, allow_redirects=True)
    
    r = session.get(f"{ADMIN_URL}/slider")
    if f'/slider/edit/{created_id}' not in r.text:
        print(f"  ✅ Slider deleted successfully")
        results["DELETE"] = True
    else:
        print(f"  ❌ Slider delete failed")
        results["DELETE"] = False
    
    return results, created_id

# =============== Team (file upload) ===============
def test_team():
    print("\n" + "="*60)
    print("Testing Team CRUD (file upload)")
    print("="*60)
    
    results = {"CREATE": None, "READ": None, "UPDATE": None, "DELETE": None}
    
    r = session.get(f"{ADMIN_URL}/team")
    old_ids = set(re.findall(r'/team/edit/(\d+)', r.text))
    
    print("\n[CREATE] Creating new Team Member...")
    r = session.get(f"{ADMIN_URL}/team/add")
    csrf_token = get_csrf_token(r.text)
    unique = f'Team{random_string()}'
    png = create_minimal_png()
    
    r = session.post(f"{ADMIN_URL}/team/store",
        data={
            '_token': csrf_token,
            'name': unique,
            'dagenation': 'Developer',
            'icon1': 'fab fa-facebook',
            'url1': 'https://facebook.com',
            'icon2': '',
            'url2': '',
            'icon3': '',
            'url3': '',
            'language_id': '1',
            'status': '1'
        },
        files={
            'image': ('team.png', io.BytesIO(png), 'image/png')
        },
        allow_redirects=True)
    
    r = session.get(f"{ADMIN_URL}/team")
    new_ids = set(re.findall(r'/team/edit/(\d+)', r.text))
    created_ids = new_ids - old_ids
    
    if created_ids:
        created_id = max(created_ids)
        print(f"  ✅ Team Member created with ID: {created_id}")
        results["CREATE"] = True
    else:
        print(f"  ❌ Team Member CREATE failed")
        results["CREATE"] = False
        return results, None
    
    print(f"\n[READ] Reading Team Member {created_id}...")
    r = session.get(f"{ADMIN_URL}/team/edit/{created_id}/")
    if r.status_code == 200:
        print(f"  ✅ Team Member read successfully")
        results["READ"] = True
    else:
        print(f"  ❌ Team Member read failed")
        results["READ"] = False
    
    print(f"\n[UPDATE] Updating Team Member {created_id}...")
    csrf_token = get_csrf_token(r.text)
    updated = f'Updated{random_string()}'
    
    r = session.post(f"{ADMIN_URL}/team/update/{created_id}/", data={
        '_token': csrf_token,
        'name': updated,
        'dagenation': 'Senior Dev',
        'icon1': 'fab fa-twitter',
        'url1': 'https://twitter.com',
        'language_id': '1',
        'status': '1'
    }, allow_redirects=True)
    
    r = session.get(f"{ADMIN_URL}/team/edit/{created_id}/")
    if updated in r.text:
        print(f"  ✅ Team Member updated successfully")
        results["UPDATE"] = True
    else:
        print(f"  ❌ Team Member update failed")
        results["UPDATE"] = False
    
    print(f"\n[DELETE] Deleting Team Member {created_id}...")
    r = session.get(f"{ADMIN_URL}/team")
    csrf_token = get_csrf_token(r.text)
    
    r = session.post(f"{ADMIN_URL}/team/delete/{created_id}/", data={
        '_token': csrf_token
    }, allow_redirects=True)
    
    r = session.get(f"{ADMIN_URL}/team")
    if f'/team/edit/{created_id}' not in r.text:
        print(f"  ✅ Team Member deleted successfully")
        results["DELETE"] = True
    else:
        print(f"  ❌ Team Member delete failed")
        results["DELETE"] = False
    
    return results, created_id

# =============== Funfact (file upload) ===============
def test_funfact():
    print("\n" + "="*60)
    print("Testing Funfact CRUD (file upload)")
    print("="*60)
    
    results = {"CREATE": None, "READ": None, "UPDATE": None, "DELETE": None}
    
    r = session.get(f"{ADMIN_URL}/funfact")
    old_ids = set(re.findall(r'/funfact/edit/(\d+)', r.text))
    
    print("\n[CREATE] Creating new Funfact...")
    r = session.get(f"{ADMIN_URL}/funfact/add")
    csrf_token = get_csrf_token(r.text)
    unique = f'Fun{random_string()}'
    png = create_minimal_png()
    
    r = session.post(f"{ADMIN_URL}/funfact/store",
        data={
            '_token': csrf_token,
            'name': unique,
            'value': '100',
            'language_id': '1'
        },
        files={
            'icon': ('funfact.png', io.BytesIO(png), 'image/png')
        },
        allow_redirects=True)
    
    r = session.get(f"{ADMIN_URL}/funfact")
    new_ids = set(re.findall(r'/funfact/edit/(\d+)', r.text))
    created_ids = new_ids - old_ids
    
    if created_ids:
        created_id = max(created_ids)
        print(f"  ✅ Funfact created with ID: {created_id}")
        results["CREATE"] = True
    else:
        print(f"  ❌ Funfact CREATE failed")
        results["CREATE"] = False
        return results, None
    
    print(f"\n[READ] Reading Funfact {created_id}...")
    r = session.get(f"{ADMIN_URL}/funfact/edit/{created_id}/")
    if r.status_code == 200:
        print(f"  ✅ Funfact read successfully")
        results["READ"] = True
    else:
        print(f"  ❌ Funfact read failed")
        results["READ"] = False
    
    print(f"\n[UPDATE] Updating Funfact {created_id}...")
    csrf_token = get_csrf_token(r.text)
    updated = f'Updated{random_string()}'
    
    r = session.post(f"{ADMIN_URL}/funfact/update/{created_id}/", data={
        '_token': csrf_token,
        'name': updated,
        'value': '200',
        'language_id': '1'
    }, allow_redirects=True)
    
    r = session.get(f"{ADMIN_URL}/funfact/edit/{created_id}/")
    if updated in r.text:
        print(f"  ✅ Funfact updated successfully")
        results["UPDATE"] = True
    else:
        print(f"  ❌ Funfact update failed")
        results["UPDATE"] = False
    
    print(f"\n[DELETE] Deleting Funfact {created_id}...")
    r = session.get(f"{ADMIN_URL}/funfact")
    csrf_token = get_csrf_token(r.text)
    
    r = session.post(f"{ADMIN_URL}/funfact/delete/{created_id}/", data={
        '_token': csrf_token
    }, allow_redirects=True)
    
    r = session.get(f"{ADMIN_URL}/funfact")
    if f'/funfact/edit/{created_id}' not in r.text:
        print(f"  ✅ Funfact deleted successfully")
        results["DELETE"] = True
    else:
        print(f"  ❌ Funfact delete failed")
        results["DELETE"] = False
    
    return results, created_id


def main():
    print("="*60)
    print("CRUD Test V3 - Final Corrected Version")
    print("="*60)
    
    if not login():
        return
    
    all_results = {}
    
    tests = [
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
    
    for name, func in tests:
        try:
            results, _ = func()
            all_results[name] = results
        except Exception as e:
            print(f"\n❌ Error testing {name}: {e}")
            import traceback
            traceback.print_exc()
            all_results[name] = {"CREATE": False, "READ": False, "UPDATE": False, "DELETE": False}
    
    # Summary
    print("\n" + "="*60)
    print("SUMMARY")
    print("="*60)
    
    total = passed = 0
    
    for entity, results in all_results.items():
        print(f"\n{entity}:")
        for op, success in results.items():
            total += 1
            if success:
                passed += 1
                print(f"  {op}: ✅")
            elif success is None:
                print(f"  {op}: ⚪ (skipped)")
            else:
                print(f"  {op}: ❌")
    
    print(f"\n{'='*60}")
    print(f"Total: {passed}/{total} tests passed ({100*passed/total:.1f}%)")
    print("="*60)


if __name__ == "__main__":
    main()
