#!/usr/bin/env python3
"""
Fixed Full CRUD Functionality Test for ISP Admin Panel
Tests CREATE, UPDATE, DELETE operations with correct form fields

Field mappings based on actual blade templates:
- FAQ: title, content, language_id, status
- Blog Category: name, language_id, status  
- Service: name, content, icon (file), image (file), language_id, status
- Slider: name, offer, desc, image (file), language_id, status
- Team: name, dagenation, image (file), icon1-3, url1-3, language_id, status
- Funfact: name, value, icon (file), language_id
- Social Links: icon, url (routes: slinks, storeSlinks)
- Role: role_name, permission[] (route: user-role-store)
- Shipping: title, subtitle, cost, language_id, status
"""

import requests
import json
import re
import os
from datetime import datetime

BASE_URL = "https://isp.mlbbshop.app"
ADMIN_USERNAME = "admin"
ADMIN_PASSWORD = "TestAdmin123!"
DEFAULT_LOCALE = "en"

# Create a small test image
def create_test_image():
    """Create a minimal valid PNG image for testing"""
    # Minimal 1x1 pixel red PNG
    png_data = bytes([
        0x89, 0x50, 0x4E, 0x47, 0x0D, 0x0A, 0x1A, 0x0A,  # PNG signature
        0x00, 0x00, 0x00, 0x0D, 0x49, 0x48, 0x44, 0x52,  # IHDR chunk
        0x00, 0x00, 0x00, 0x01, 0x00, 0x00, 0x00, 0x01,
        0x08, 0x02, 0x00, 0x00, 0x00, 0x90, 0x77, 0x53,
        0xDE, 0x00, 0x00, 0x00, 0x0C, 0x49, 0x44, 0x41,  # IDAT chunk
        0x54, 0x08, 0xD7, 0x63, 0xF8, 0xCF, 0xC0, 0x00,
        0x00, 0x00, 0x03, 0x00, 0x01, 0x00, 0x18, 0xDD,
        0x8D, 0xB4, 0x00, 0x00, 0x00, 0x00, 0x49, 0x45,  # IEND chunk
        0x4E, 0x44, 0xAE, 0x42, 0x60, 0x82
    ])
    return png_data


class CRUDTester:
    def __init__(self):
        self.session = requests.Session()
        self.session.verify = False
        self.results = []
        self.csrf_token = None
        self.language_id = "1"  # Default language ID (English)
        
    def login(self):
        """Login and get CSRF token"""
        print("Logging in as admin...")
        
        # Get login page for CSRF token
        r = self.session.get(f"{BASE_URL}/admin")
        match = re.search(r'name="_token"\s+value="([^"]+)"', r.text)
        if match:
            self.csrf_token = match.group(1)
        else:
            match = re.search(r'"_token"[^>]+value="([^"]+)"', r.text)
            if match:
                self.csrf_token = match.group(1)
        
        if not self.csrf_token:
            print("✗ Could not get CSRF token!")
            return False
        
        # Login - POST to /admin/login with username
        r = self.session.post(f"{BASE_URL}/admin/login", data={
            "_token": self.csrf_token,
            "username": ADMIN_USERNAME,
            "password": ADMIN_PASSWORD
        }, allow_redirects=True)
        
        # Check if logged in
        r2 = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/dashboard")
        if r2.status_code == 200 and "login" not in r2.url.lower():
            print("✓ Login successful!\n")
            return True
            
        print(f"✗ Login failed! URL: {r.url}")
        return False
    
    def get_csrf_token(self, url):
        """Get fresh CSRF token from a page"""
        r = self.session.get(url)
        match = re.search(r'name="_token"\s+value="([^"]+)"', r.text)
        if match:
            self.csrf_token = match.group(1)
        return r
    
    def test_result(self, operation, entity, success, message=""):
        """Record test result"""
        status = "✓" if success else "✗"
        result = {"operation": operation, "entity": entity, "success": success, "message": message}
        self.results.append(result)
        print(f"  {status} {operation} {entity}: {message}")
        return success

    # ==================== FAQ CRUD ====================
    def test_faq_crud(self):
        """
        FAQ form fields: title, content, language_id, status
        Routes: admin.faq.create, admin.faq.store
        """
        print("\n" + "="*60)
        print("Testing FAQ Full CRUD")
        print("="*60)
        
        # CREATE
        print("\n[CREATE] Adding new FAQ...")
        r = self.get_csrf_token(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/faq/create")
        
        faq_data = {
            "_token": self.csrf_token,
            "language_id": self.language_id,
            "title": f"Test FAQ {datetime.now().strftime('%H%M%S')}",
            "content": "<p>This is a test FAQ answer created by automated testing.</p>",
            "status": "1"
        }
        
        r = self.session.post(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/faq/store", data=faq_data, allow_redirects=True)
        
        # Check if created by looking for success message or redirected to list
        created_id = None
        r_list = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/faq")
        ids = re.findall(r'/admin/faq/(\d+)/edit', r_list.text)
        if ids:
            created_id = ids[0]
            self.test_result("CREATE", "FAQ", True, f"Created ID: {created_id}")
        else:
            # Check for validation errors in response
            if "required" in r.text.lower() or "error" in r.text.lower():
                self.test_result("CREATE", "FAQ", False, "Validation error in response")
            else:
                self.test_result("CREATE", "FAQ", False, "No ID found, but no obvious errors")
            return
        
        # READ
        print("\n[READ] Reading FAQ...")
        r = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/faq/{created_id}/edit")
        if r.status_code == 200 and "Test FAQ" in r.text:
            self.test_result("READ", "FAQ", True, f"FAQ {created_id} loaded")
        else:
            self.test_result("READ", "FAQ", False, f"Status: {r.status_code}")
        
        # UPDATE
        print("\n[UPDATE] Updating FAQ...")
        r = self.get_csrf_token(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/faq/{created_id}/edit")
        
        update_data = {
            "_token": self.csrf_token,
            "_method": "PUT",
            "language_id": self.language_id,
            "title": f"Updated FAQ {datetime.now().strftime('%H%M%S')}",
            "content": "<p>This is an UPDATED test FAQ answer.</p>",
            "status": "1"
        }
        
        r = self.session.post(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/faq/{created_id}", data=update_data, allow_redirects=True)
        
        if r.status_code == 200:
            self.test_result("UPDATE", "FAQ", True, f"FAQ {created_id} updated")
        else:
            self.test_result("UPDATE", "FAQ", False, f"Status: {r.status_code}")
        
        # DELETE
        print("\n[DELETE] Deleting FAQ...")
        r = self.get_csrf_token(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/faq")
        
        # Try direct delete route
        r = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/faq/{created_id}/delete")
        
        # Verify deletion
        r2 = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/faq/{created_id}/edit")
        if r2.status_code == 404 or "Updated FAQ" not in r2.text:
            self.test_result("DELETE", "FAQ", True, f"FAQ {created_id} deleted")
        else:
            self.test_result("DELETE", "FAQ", False, "FAQ still exists")

    # ==================== Blog Category CRUD ====================
    def test_bcategory_crud(self):
        """
        Blog Category form fields: name, language_id, status
        Routes: admin.bcategory.create, admin.bcategory.store
        """
        print("\n" + "="*60)
        print("Testing Blog Category Full CRUD")
        print("="*60)
        
        # CREATE
        print("\n[CREATE] Adding new Blog Category...")
        r = self.get_csrf_token(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/bcategory/create")
        
        cat_data = {
            "_token": self.csrf_token,
            "language_id": self.language_id,
            "name": f"TestCat{datetime.now().strftime('%H%M%S')}",
            "status": "1"
        }
        
        r = self.session.post(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/bcategory/store", data=cat_data, allow_redirects=True)
        
        created_id = None
        r_list = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/bcategory")
        ids = re.findall(r'/admin/bcategory/(\d+)/edit', r_list.text)
        if ids:
            created_id = ids[0]
            self.test_result("CREATE", "Blog Category", True, f"Created ID: {created_id}")
        else:
            self.test_result("CREATE", "Blog Category", False, "Failed to create")
            return
        
        # READ
        print("\n[READ] Reading Blog Category...")
        r = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/bcategory/{created_id}/edit")
        if r.status_code == 200:
            self.test_result("READ", "Blog Category", True, f"Category {created_id} loaded")
        else:
            self.test_result("READ", "Blog Category", False, f"Status: {r.status_code}")
        
        # UPDATE
        print("\n[UPDATE] Updating Blog Category...")
        r = self.get_csrf_token(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/bcategory/{created_id}/edit")
        
        update_data = {
            "_token": self.csrf_token,
            "_method": "PUT",
            "language_id": self.language_id,
            "name": f"UpdatedCat{datetime.now().strftime('%H%M%S')}",
            "status": "1"
        }
        
        r = self.session.post(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/bcategory/{created_id}", data=update_data, allow_redirects=True)
        
        if r.status_code == 200:
            self.test_result("UPDATE", "Blog Category", True, f"Category {created_id} updated")
        else:
            self.test_result("UPDATE", "Blog Category", False, f"Status: {r.status_code}")
        
        # DELETE
        print("\n[DELETE] Deleting Blog Category...")
        r = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/bcategory/{created_id}/delete")
        
        r2 = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/bcategory/{created_id}/edit")
        if r2.status_code == 404 or "UpdatedCat" not in r2.text:
            self.test_result("DELETE", "Blog Category", True, f"Category {created_id} deleted")
        else:
            self.test_result("DELETE", "Blog Category", False, "Category still exists")

    # ==================== Social Links CRUD ====================
    def test_social_crud(self):
        """
        Social Links form fields: icon, url
        Routes: admin.slinks, admin.storeSlinks
        """
        print("\n" + "="*60)
        print("Testing Social Links Full CRUD")
        print("="*60)
        
        # CREATE
        print("\n[CREATE] Adding new Social Link...")
        r = self.get_csrf_token(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/slinks")
        
        social_data = {
            "_token": self.csrf_token,
            "icon": "fab fa-github",
            "url": f"https://github.com/test{datetime.now().strftime('%H%M%S')}"
        }
        
        r = self.session.post(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/slinks/store", data=social_data, allow_redirects=True)
        
        created_id = None
        r_list = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/slinks")
        ids = re.findall(r'/admin/slinks/edit/(\d+)', r_list.text)
        if ids:
            created_id = ids[-1]  # Get the last one (most recently created)
            self.test_result("CREATE", "Social Link", True, f"Created ID: {created_id}")
        else:
            self.test_result("CREATE", "Social Link", False, "Failed to create")
            return
        
        # READ
        print("\n[READ] Reading Social Link...")
        r = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/slinks/edit/{created_id}")
        if r.status_code == 200:
            self.test_result("READ", "Social Link", True, f"Social {created_id} loaded")
        else:
            self.test_result("READ", "Social Link", False, f"Status: {r.status_code}")
        
        # UPDATE
        print("\n[UPDATE] Updating Social Link...")
        r = self.get_csrf_token(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/slinks/edit/{created_id}")
        
        update_data = {
            "_token": self.csrf_token,
            "icon": "fab fa-gitlab",
            "url": f"https://gitlab.com/updated{datetime.now().strftime('%H%M%S')}"
        }
        
        r = self.session.post(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/slinks/update/{created_id}", data=update_data, allow_redirects=True)
        
        if r.status_code == 200:
            self.test_result("UPDATE", "Social Link", True, f"Social {created_id} updated")
        else:
            self.test_result("UPDATE", "Social Link", False, f"Status: {r.status_code}")
        
        # DELETE
        print("\n[DELETE] Deleting Social Link...")
        r = self.get_csrf_token(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/slinks")
        
        delete_data = {
            "_token": self.csrf_token,
            "id": created_id
        }
        r = self.session.post(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/slinks/delete/{created_id}", data=delete_data, allow_redirects=True)
        
        r2 = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/slinks/edit/{created_id}")
        if r2.status_code == 404 or "gitlab" not in r2.text.lower():
            self.test_result("DELETE", "Social Link", True, f"Social {created_id} deleted")
        else:
            self.test_result("DELETE", "Social Link", False, "Social link still exists")

    # ==================== Role CRUD ====================
    def test_role_crud(self):
        """
        Role form fields: role_name, permission[]
        Routes: admin.add_role, admin.store_role (user-role-store)
        """
        print("\n" + "="*60)
        print("Testing Role Full CRUD")
        print("="*60)
        
        # CREATE
        print("\n[CREATE] Adding new Role...")
        r = self.get_csrf_token(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/add-role")
        
        # Find permission IDs from the page
        permissions = re.findall(r'name="permission\[\]"[^>]+value="(\d+)"', r.text)
        if not permissions:
            permissions = ["1"]  # Fallback
        
        role_data = {
            "_token": self.csrf_token,
            "role_name": f"TestRole{datetime.now().strftime('%H%M%S')}",
            "permission[]": permissions[:2] if len(permissions) > 1 else permissions  # Pick first 2 permissions
        }
        
        r = self.session.post(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/user-role-store", data=role_data, allow_redirects=True)
        
        created_id = None
        r_list = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/user-role-manage")
        ids = re.findall(r'/admin/edit_role/(\d+)', r_list.text)
        if ids:
            created_id = ids[-1]
            self.test_result("CREATE", "Role", True, f"Created ID: {created_id}")
        else:
            # Check for error in response
            if "error" in r.text.lower() or "required" in r.text.lower():
                self.test_result("CREATE", "Role", False, "Validation error")
            else:
                self.test_result("CREATE", "Role", False, "Failed to create")
            return
        
        # READ
        print("\n[READ] Reading Role...")
        r = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/edit_role/{created_id}")
        if r.status_code == 200:
            self.test_result("READ", "Role", True, f"Role {created_id} loaded")
        else:
            self.test_result("READ", "Role", False, f"Status: {r.status_code}")
        
        # UPDATE
        print("\n[UPDATE] Updating Role...")
        r = self.get_csrf_token(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/edit_role/{created_id}")
        
        permissions = re.findall(r'name="permission\[\]"[^>]+value="(\d+)"', r.text)
        if not permissions:
            permissions = ["1"]
        
        update_data = {
            "_token": self.csrf_token,
            "role_name": f"UpdatedRole{datetime.now().strftime('%H%M%S')}",
            "permission[]": permissions[:3] if len(permissions) > 2 else permissions
        }
        
        r = self.session.post(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/update_role/{created_id}", data=update_data, allow_redirects=True)
        
        if r.status_code == 200:
            self.test_result("UPDATE", "Role", True, f"Role {created_id} updated")
        else:
            self.test_result("UPDATE", "Role", False, f"Status: {r.status_code}")
        
        # DELETE
        print("\n[DELETE] Deleting Role...")
        r = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/delete_role/{created_id}")
        
        r2 = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/edit_role/{created_id}")
        if r2.status_code == 404 or "UpdatedRole" not in r2.text:
            self.test_result("DELETE", "Role", True, f"Role {created_id} deleted")
        else:
            self.test_result("DELETE", "Role", False, "Role still exists")

    # ==================== Shipping CRUD ====================
    def test_shipping_crud(self):
        """
        Shipping form fields: title, subtitle, cost, language_id, status
        Routes: admin.shipping.index, admin.shipping.store
        """
        print("\n" + "="*60)
        print("Testing Shipping Full CRUD")
        print("="*60)
        
        # CREATE
        print("\n[CREATE] Adding new Shipping Method...")
        r = self.get_csrf_token(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/shipping/create")
        
        shipping_data = {
            "_token": self.csrf_token,
            "language_id": self.language_id,
            "title": f"TestShip{datetime.now().strftime('%H%M%S')}",
            "subtitle": "Test shipping subtitle",
            "cost": "10",
            "status": "1"
        }
        
        r = self.session.post(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/shipping/store", data=shipping_data, allow_redirects=True)
        
        created_id = None
        r_list = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/shipping")
        ids = re.findall(r'/admin/shipping/(\d+)/edit', r_list.text)
        if ids:
            created_id = ids[-1]
            self.test_result("CREATE", "Shipping", True, f"Created ID: {created_id}")
        else:
            self.test_result("CREATE", "Shipping", False, "Failed to create")
            return
        
        # READ
        print("\n[READ] Reading Shipping Method...")
        r = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/shipping/{created_id}/edit")
        if r.status_code == 200:
            self.test_result("READ", "Shipping", True, f"Shipping {created_id} loaded")
        else:
            self.test_result("READ", "Shipping", False, f"Status: {r.status_code}")
        
        # UPDATE
        print("\n[UPDATE] Updating Shipping Method...")
        r = self.get_csrf_token(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/shipping/{created_id}/edit")
        
        update_data = {
            "_token": self.csrf_token,
            "_method": "PUT",
            "language_id": self.language_id,
            "title": f"UpdatedShip{datetime.now().strftime('%H%M%S')}",
            "subtitle": "Updated shipping subtitle",
            "cost": "15",
            "status": "1"
        }
        
        r = self.session.post(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/shipping/{created_id}", data=update_data, allow_redirects=True)
        
        if r.status_code == 200:
            self.test_result("UPDATE", "Shipping", True, f"Shipping {created_id} updated")
        else:
            self.test_result("UPDATE", "Shipping", False, f"Status: {r.status_code}")
        
        # DELETE
        print("\n[DELETE] Deleting Shipping Method...")
        r = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/shipping/{created_id}/delete")
        
        r2 = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/shipping/{created_id}/edit")
        if r2.status_code == 404 or "UpdatedShip" not in r2.text:
            self.test_result("DELETE", "Shipping", True, f"Shipping {created_id} deleted")
        else:
            self.test_result("DELETE", "Shipping", False, "Shipping still exists")

    # ==================== Service CRUD (with file upload) ====================
    def test_service_crud(self):
        """
        Service form fields: name, content, icon (file), image (file), language_id, status
        NOTE: Requires file upload - icon and image are REQUIRED
        """
        print("\n" + "="*60)
        print("Testing Service Full CRUD (with file upload)")
        print("="*60)
        
        # CREATE
        print("\n[CREATE] Adding new Service...")
        r = self.get_csrf_token(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/service/create")
        
        test_image = create_test_image()
        
        files = {
            'icon': ('test_icon.png', test_image, 'image/png'),
            'image': ('test_image.png', test_image, 'image/png')
        }
        
        service_data = {
            "_token": self.csrf_token,
            "language_id": self.language_id,
            "name": f"TestService{datetime.now().strftime('%H%M%S')}",
            "content": "<p>Test service description.</p>",
            "status": "1"
        }
        
        r = self.session.post(
            f"{BASE_URL}/{DEFAULT_LOCALE}/admin/service/store", 
            data=service_data, 
            files=files,
            allow_redirects=True
        )
        
        created_id = None
        r_list = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/service")
        ids = re.findall(r'/admin/service/(\d+)/edit', r_list.text)
        if ids:
            created_id = ids[0]
            self.test_result("CREATE", "Service", True, f"Created ID: {created_id}")
        else:
            if "required" in r.text.lower() or "icon" in r.text.lower() or "image" in r.text.lower():
                self.test_result("CREATE", "Service", False, "File upload validation failed")
            else:
                self.test_result("CREATE", "Service", False, "Failed to create")
            return
        
        # READ
        print("\n[READ] Reading Service...")
        r = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/service/{created_id}/edit")
        if r.status_code == 200:
            self.test_result("READ", "Service", True, f"Service {created_id} loaded")
        else:
            self.test_result("READ", "Service", False, f"Status: {r.status_code}")
        
        # UPDATE (without changing image)
        print("\n[UPDATE] Updating Service...")
        r = self.get_csrf_token(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/service/{created_id}/edit")
        
        update_data = {
            "_token": self.csrf_token,
            "_method": "PUT",
            "language_id": self.language_id,
            "name": f"UpdatedService{datetime.now().strftime('%H%M%S')}",
            "content": "<p>Updated service description.</p>",
            "status": "1"
        }
        
        r = self.session.post(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/service/{created_id}", data=update_data, allow_redirects=True)
        
        if r.status_code == 200:
            self.test_result("UPDATE", "Service", True, f"Service {created_id} updated")
        else:
            self.test_result("UPDATE", "Service", False, f"Status: {r.status_code}")
        
        # DELETE
        print("\n[DELETE] Deleting Service...")
        r = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/service/{created_id}/delete")
        
        r2 = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/service/{created_id}/edit")
        if r2.status_code == 404 or "UpdatedService" not in r2.text:
            self.test_result("DELETE", "Service", True, f"Service {created_id} deleted")
        else:
            self.test_result("DELETE", "Service", False, "Service still exists")

    # ==================== Slider CRUD (with file upload) ====================
    def test_slider_crud(self):
        """
        Slider form fields: name, offer, desc, image (file), language_id, status
        NOTE: Requires file upload - image is REQUIRED
        """
        print("\n" + "="*60)
        print("Testing Slider Full CRUD (with file upload)")
        print("="*60)
        
        # CREATE
        print("\n[CREATE] Adding new Slider...")
        r = self.get_csrf_token(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/slider/create")
        
        test_image = create_test_image()
        
        files = {
            'image': ('test_slider.png', test_image, 'image/png')
        }
        
        slider_data = {
            "_token": self.csrf_token,
            "language_id": self.language_id,
            "name": f"TestSlider{datetime.now().strftime('%H%M%S')}",
            "offer": "50% OFF",
            "desc": "Test slider description",
            "status": "1"
        }
        
        r = self.session.post(
            f"{BASE_URL}/{DEFAULT_LOCALE}/admin/slider/store",
            data=slider_data,
            files=files,
            allow_redirects=True
        )
        
        created_id = None
        r_list = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/slider")
        ids = re.findall(r'/admin/slider/(\d+)/edit', r_list.text)
        if ids:
            created_id = ids[0]
            self.test_result("CREATE", "Slider", True, f"Created ID: {created_id}")
        else:
            self.test_result("CREATE", "Slider", False, "Failed to create")
            return
        
        # READ
        print("\n[READ] Reading Slider...")
        r = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/slider/{created_id}/edit")
        if r.status_code == 200:
            self.test_result("READ", "Slider", True, f"Slider {created_id} loaded")
        else:
            self.test_result("READ", "Slider", False, f"Status: {r.status_code}")
        
        # UPDATE (without changing image)
        print("\n[UPDATE] Updating Slider...")
        r = self.get_csrf_token(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/slider/{created_id}/edit")
        
        update_data = {
            "_token": self.csrf_token,
            "_method": "PUT",
            "language_id": self.language_id,
            "name": f"UpdatedSlider{datetime.now().strftime('%H%M%S')}",
            "offer": "75% OFF",
            "desc": "Updated slider description",
            "status": "1"
        }
        
        r = self.session.post(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/slider/{created_id}", data=update_data, allow_redirects=True)
        
        if r.status_code == 200:
            self.test_result("UPDATE", "Slider", True, f"Slider {created_id} updated")
        else:
            self.test_result("UPDATE", "Slider", False, f"Status: {r.status_code}")
        
        # DELETE
        print("\n[DELETE] Deleting Slider...")
        r = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/slider/{created_id}/delete")
        
        r2 = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/slider/{created_id}/edit")
        if r2.status_code == 404 or "UpdatedSlider" not in r2.text:
            self.test_result("DELETE", "Slider", True, f"Slider {created_id} deleted")
        else:
            self.test_result("DELETE", "Slider", False, "Slider still exists")

    # ==================== Team CRUD (with file upload) ====================
    def test_team_crud(self):
        """
        Team form fields: name, dagenation, image (file), icon1-3, url1-3, language_id, status
        NOTE: Requires file upload - image is REQUIRED
        """
        print("\n" + "="*60)
        print("Testing Team Full CRUD (with file upload)")
        print("="*60)
        
        # CREATE
        print("\n[CREATE] Adding new Team Member...")
        r = self.get_csrf_token(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/team/create")
        
        test_image = create_test_image()
        
        files = {
            'image': ('test_team.png', test_image, 'image/png')
        }
        
        team_data = {
            "_token": self.csrf_token,
            "language_id": self.language_id,
            "name": f"TestMember{datetime.now().strftime('%H%M%S')}",
            "dagenation": "Test Engineer",  # Note: typo in original, keeping as-is
            "icon1": "fab fa-facebook-f",
            "url1": "https://facebook.com",
            "icon2": "fab fa-twitter",
            "url2": "https://twitter.com",
            "icon3": "fab fa-linkedin-in",
            "url3": "https://linkedin.com",
            "status": "1"
        }
        
        r = self.session.post(
            f"{BASE_URL}/{DEFAULT_LOCALE}/admin/team/store",
            data=team_data,
            files=files,
            allow_redirects=True
        )
        
        created_id = None
        r_list = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/team")
        ids = re.findall(r'/admin/team/(\d+)/edit', r_list.text)
        if ids:
            created_id = ids[0]
            self.test_result("CREATE", "Team Member", True, f"Created ID: {created_id}")
        else:
            self.test_result("CREATE", "Team Member", False, "Failed to create")
            return
        
        # READ
        print("\n[READ] Reading Team Member...")
        r = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/team/{created_id}/edit")
        if r.status_code == 200:
            self.test_result("READ", "Team Member", True, f"Team {created_id} loaded")
        else:
            self.test_result("READ", "Team Member", False, f"Status: {r.status_code}")
        
        # UPDATE (without changing image)
        print("\n[UPDATE] Updating Team Member...")
        r = self.get_csrf_token(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/team/{created_id}/edit")
        
        update_data = {
            "_token": self.csrf_token,
            "_method": "PUT",
            "language_id": self.language_id,
            "name": f"UpdatedMember{datetime.now().strftime('%H%M%S')}",
            "dagenation": "Senior Engineer",
            "icon1": "fab fa-facebook-f",
            "url1": "https://facebook.com/updated",
            "status": "1"
        }
        
        r = self.session.post(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/team/{created_id}", data=update_data, allow_redirects=True)
        
        if r.status_code == 200:
            self.test_result("UPDATE", "Team Member", True, f"Team {created_id} updated")
        else:
            self.test_result("UPDATE", "Team Member", False, f"Status: {r.status_code}")
        
        # DELETE
        print("\n[DELETE] Deleting Team Member...")
        r = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/team/{created_id}/delete")
        
        r2 = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/team/{created_id}/edit")
        if r2.status_code == 404 or "UpdatedMember" not in r2.text:
            self.test_result("DELETE", "Team Member", True, f"Team {created_id} deleted")
        else:
            self.test_result("DELETE", "Team Member", False, "Team member still exists")

    # ==================== Funfact CRUD (with file upload) ====================
    def test_funfact_crud(self):
        """
        Funfact form fields: name, value, icon (file), language_id
        NOTE: Requires file upload - icon is REQUIRED
        """
        print("\n" + "="*60)
        print("Testing Funfact Full CRUD (with file upload)")
        print("="*60)
        
        # CREATE
        print("\n[CREATE] Adding new Funfact...")
        r = self.get_csrf_token(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/funfact/create")
        
        test_image = create_test_image()
        
        files = {
            'icon': ('test_funfact.png', test_image, 'image/png')
        }
        
        funfact_data = {
            "_token": self.csrf_token,
            "language_id": self.language_id,
            "name": f"TestFunfact{datetime.now().strftime('%H%M%S')}",
            "value": "999"
        }
        
        r = self.session.post(
            f"{BASE_URL}/{DEFAULT_LOCALE}/admin/funfact/store",
            data=funfact_data,
            files=files,
            allow_redirects=True
        )
        
        created_id = None
        r_list = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/funfact")
        ids = re.findall(r'/admin/funfact/(\d+)/edit', r_list.text)
        if ids:
            created_id = ids[0]
            self.test_result("CREATE", "Funfact", True, f"Created ID: {created_id}")
        else:
            self.test_result("CREATE", "Funfact", False, "Failed to create")
            return
        
        # READ
        print("\n[READ] Reading Funfact...")
        r = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/funfact/{created_id}/edit")
        if r.status_code == 200:
            self.test_result("READ", "Funfact", True, f"Funfact {created_id} loaded")
        else:
            self.test_result("READ", "Funfact", False, f"Status: {r.status_code}")
        
        # UPDATE (without changing image)
        print("\n[UPDATE] Updating Funfact...")
        r = self.get_csrf_token(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/funfact/{created_id}/edit")
        
        update_data = {
            "_token": self.csrf_token,
            "_method": "PUT",
            "language_id": self.language_id,
            "name": f"UpdatedFunfact{datetime.now().strftime('%H%M%S')}",
            "value": "1000"
        }
        
        r = self.session.post(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/funfact/{created_id}", data=update_data, allow_redirects=True)
        
        if r.status_code == 200:
            self.test_result("UPDATE", "Funfact", True, f"Funfact {created_id} updated")
        else:
            self.test_result("UPDATE", "Funfact", False, f"Status: {r.status_code}")
        
        # DELETE
        print("\n[DELETE] Deleting Funfact...")
        r = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/funfact/{created_id}/delete")
        
        r2 = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/funfact/{created_id}/edit")
        if r2.status_code == 404 or "UpdatedFunfact" not in r2.text:
            self.test_result("DELETE", "Funfact", True, f"Funfact {created_id} deleted")
        else:
            self.test_result("DELETE", "Funfact", False, "Funfact still exists")

    def print_summary(self):
        """Print test results summary"""
        print("\n" + "="*60)
        print("FULL CRUD TEST RESULTS SUMMARY")
        print("="*60)
        
        passed = sum(1 for r in self.results if r["success"])
        failed = sum(1 for r in self.results if not r["success"])
        total = len(self.results)
        
        print(f"\nTotal Tests: {total}")
        print(f"Passed: {passed}")
        print(f"Failed: {failed}")
        print(f"Success Rate: {(passed/total*100):.1f}%" if total > 0 else "N/A")
        
        if failed > 0:
            print("\nFailed Tests:")
            for r in self.results:
                if not r["success"]:
                    print(f"  ✗ {r['operation']} {r['entity']}: {r['message']}")
        
        # Group by entity
        print("\nResults by Entity:")
        entities = {}
        for r in self.results:
            if r["entity"] not in entities:
                entities[r["entity"]] = {"create": None, "read": None, "update": None, "delete": None}
            entities[r["entity"]][r["operation"].lower()] = "✓" if r["success"] else "✗"
        
        print(f"{'Entity':<20} {'CREATE':^8} {'READ':^8} {'UPDATE':^8} {'DELETE':^8}")
        print("-" * 60)
        for entity, ops in entities.items():
            c = ops.get("create", "-")
            r = ops.get("read", "-")
            u = ops.get("update", "-")
            d = ops.get("delete", "-")
            print(f"{entity:<20} {c:^8} {r:^8} {u:^8} {d:^8}")
        
        # Save report
        with open("CRUD_FIXED_REPORT.json", "w") as f:
            json.dump({
                "timestamp": datetime.now().isoformat(),
                "total": total,
                "passed": passed,
                "failed": failed,
                "success_rate": f"{(passed/total*100):.1f}%" if total > 0 else "N/A",
                "results": self.results
            }, f, indent=2)
        print("\nReport saved to CRUD_FIXED_REPORT.json")

    def run_all_tests(self):
        """Run all CRUD tests"""
        print("="*60)
        print("Fixed Full CRUD Functionality Test - ISP Admin Panel")
        print(f"Testing: {BASE_URL}")
        print(f"Date: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
        print("="*60)
        
        if not self.login():
            return
        
        # Run tests for entities that DON'T require file uploads first
        self.test_faq_crud()
        self.test_bcategory_crud()
        self.test_social_crud()
        self.test_role_crud()
        self.test_shipping_crud()
        
        # Run tests for entities that require file uploads
        self.test_service_crud()
        self.test_slider_crud()
        self.test_team_crud()
        self.test_funfact_crud()
        
        self.print_summary()


if __name__ == "__main__":
    import urllib3
    urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)
    
    tester = CRUDTester()
    tester.run_all_tests()
