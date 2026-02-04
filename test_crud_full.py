#!/usr/bin/env python3
"""
Full CRUD Functionality Test for ISP Admin Panel
Tests CREATE, UPDATE, DELETE operations (not just READ)
"""

import requests
import json
import re
from datetime import datetime

BASE_URL = "https://isp.mlbbshop.app"
ADMIN_USERNAME = "admin"
ADMIN_PASSWORD = "TestAdmin123!"
DEFAULT_LOCALE = "en"

class CRUDTester:
    def __init__(self):
        self.session = requests.Session()
        self.session.verify = False
        self.results = []
        self.csrf_token = None
        
    def login(self):
        """Login and get CSRF token"""
        print("Logging in as admin...")
        
        # Get login page for CSRF token (login page is at /admin not /admin/login)
        r = self.session.get(f"{BASE_URL}/admin")
        match = re.search(r'name="_token"\s+value="([^"]+)"', r.text)
        if match:
            self.csrf_token = match.group(1)
        else:
            # Try alternate pattern
            match = re.search(r'"_token"[^>]+value="([^"]+)"', r.text)
            if match:
                self.csrf_token = match.group(1)
        
        if not self.csrf_token:
            print("✗ Could not get CSRF token!")
            return False
        
        # Login - POST to /admin/login with username (not email)
        r = self.session.post(f"{BASE_URL}/admin/login", data={
            "_token": self.csrf_token,
            "username": ADMIN_USERNAME,
            "password": ADMIN_PASSWORD
        }, allow_redirects=True)
        
        # After login we should be at en/admin/dashboard
        if "dashboard" in r.url or (f"{DEFAULT_LOCALE}/admin" in r.url):
            print("✓ Login successful!\n")
            return True
        
        # Check if we're logged in by trying to access dashboard
        r2 = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/dashboard")
        if "dashboard" in r2.url.lower() and r2.status_code == 200 and "login" not in r2.url:
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
        print("\n" + "="*60)
        print("Testing FAQ Full CRUD")
        print("="*60)
        
        # CREATE
        print("\n[CREATE] Adding new FAQ...")
        r = self.get_csrf_token(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/faq/create")
        
        faq_data = {
            "_token": self.csrf_token,
            "question": f"Test FAQ Question {datetime.now().strftime('%H%M%S')}",
            "answer": "This is a test answer for the FAQ created by automated testing.",
            "status": "1"
        }
        
        r = self.session.post(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/faq/store", data=faq_data, allow_redirects=True)
        
        created_id = None
        if "success" in r.text.lower() or "faq" in r.url:
            # Try to find the created FAQ ID
            match = re.search(r'/admin/faq/(\d+)/edit', r.text)
            if match:
                created_id = match.group(1)
            else:
                # Get from the list
                r = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/faq")
                ids = re.findall(r'/admin/faq/(\d+)/edit', r.text)
                if ids:
                    created_id = ids[0]
            
            self.test_result("CREATE", "FAQ", True, f"Created ID: {created_id}")
        else:
            self.test_result("CREATE", "FAQ", False, "Failed to create")
            return
        
        if not created_id:
            self.test_result("UPDATE", "FAQ", False, "No ID found for update test")
            return
        
        # READ
        print("\n[READ] Reading FAQ...")
        r = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/faq/{created_id}/edit")
        if r.status_code == 200 and "Test FAQ Question" in r.text:
            self.test_result("READ", "FAQ", True, f"FAQ {created_id} loaded")
        else:
            self.test_result("READ", "FAQ", False, f"Status: {r.status_code}")
        
        # UPDATE
        print("\n[UPDATE] Updating FAQ...")
        r = self.get_csrf_token(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/faq/{created_id}/edit")
        
        update_data = {
            "_token": self.csrf_token,
            "_method": "PUT",
            "question": f"Updated FAQ Question {datetime.now().strftime('%H%M%S')}",
            "answer": "This is an UPDATED test answer.",
            "status": "1"
        }
        
        r = self.session.post(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/faq/{created_id}", data=update_data, allow_redirects=True)
        
        if "success" in r.text.lower() or r.status_code == 200:
            self.test_result("UPDATE", "FAQ", True, f"FAQ {created_id} updated")
        else:
            self.test_result("UPDATE", "FAQ", False, f"Status: {r.status_code}")
        
        # DELETE
        print("\n[DELETE] Deleting FAQ...")
        r = self.get_csrf_token(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/faq")
        
        delete_data = {
            "_token": self.csrf_token,
            "_method": "DELETE"
        }
        
        r = self.session.post(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/faq/{created_id}", data=delete_data, allow_redirects=True)
        
        # Verify deletion
        r2 = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/faq/{created_id}/edit")
        if r2.status_code == 404 or "not found" in r2.text.lower() or "Test FAQ Question" not in r2.text:
            self.test_result("DELETE", "FAQ", True, f"FAQ {created_id} deleted")
        else:
            self.test_result("DELETE", "FAQ", False, "FAQ still exists")

    # ==================== Blog Category CRUD ====================
    def test_bcategory_crud(self):
        print("\n" + "="*60)
        print("Testing Blog Category Full CRUD")
        print("="*60)
        
        # CREATE
        print("\n[CREATE] Adding new Blog Category...")
        r = self.get_csrf_token(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/bcategory/create")
        
        cat_data = {
            "_token": self.csrf_token,
            "name": f"Test Category {datetime.now().strftime('%H%M%S')}",
            "status": "1"
        }
        
        r = self.session.post(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/bcategory/store", data=cat_data, allow_redirects=True)
        
        created_id = None
        r = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/bcategory")
        ids = re.findall(r'/admin/bcategory/(\d+)/edit', r.text)
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
            "name": f"Updated Category {datetime.now().strftime('%H%M%S')}",
            "status": "1"
        }
        
        r = self.session.post(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/bcategory/{created_id}", data=update_data, allow_redirects=True)
        
        if r.status_code == 200:
            self.test_result("UPDATE", "Blog Category", True, f"Category {created_id} updated")
        else:
            self.test_result("UPDATE", "Blog Category", False, f"Status: {r.status_code}")
        
        # DELETE
        print("\n[DELETE] Deleting Blog Category...")
        r = self.get_csrf_token(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/bcategory")
        
        delete_data = {
            "_token": self.csrf_token,
            "_method": "DELETE"
        }
        
        r = self.session.post(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/bcategory/{created_id}", data=delete_data, allow_redirects=True)
        
        r2 = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/bcategory/{created_id}/edit")
        if r2.status_code == 404 or "Updated Category" not in r2.text:
            self.test_result("DELETE", "Blog Category", True, f"Category {created_id} deleted")
        else:
            self.test_result("DELETE", "Blog Category", False, "Category still exists")

    # ==================== Service CRUD ====================
    def test_service_crud(self):
        print("\n" + "="*60)
        print("Testing Service Full CRUD")
        print("="*60)
        
        # CREATE
        print("\n[CREATE] Adding new Service...")
        r = self.get_csrf_token(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/service/create")
        
        service_data = {
            "_token": self.csrf_token,
            "title": f"Test Service {datetime.now().strftime('%H%M%S')}",
            "text": "This is a test service description.",
            "icon": "fas fa-wifi",
            "status": "1"
        }
        
        r = self.session.post(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/service/store", data=service_data, allow_redirects=True)
        
        created_id = None
        r = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/service")
        ids = re.findall(r'/admin/service/(\d+)/edit', r.text)
        if ids:
            created_id = ids[0]
            self.test_result("CREATE", "Service", True, f"Created ID: {created_id}")
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
        
        # UPDATE
        print("\n[UPDATE] Updating Service...")
        r = self.get_csrf_token(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/service/{created_id}/edit")
        
        update_data = {
            "_token": self.csrf_token,
            "_method": "PUT",
            "title": f"Updated Service {datetime.now().strftime('%H%M%S')}",
            "text": "This is an UPDATED service description.",
            "icon": "fas fa-wifi",
            "status": "1"
        }
        
        r = self.session.post(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/service/{created_id}", data=update_data, allow_redirects=True)
        
        if r.status_code == 200:
            self.test_result("UPDATE", "Service", True, f"Service {created_id} updated")
        else:
            self.test_result("UPDATE", "Service", False, f"Status: {r.status_code}")
        
        # DELETE
        print("\n[DELETE] Deleting Service...")
        r = self.get_csrf_token(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/service")
        
        delete_data = {
            "_token": self.csrf_token,
            "_method": "DELETE"
        }
        
        r = self.session.post(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/service/{created_id}", data=delete_data, allow_redirects=True)
        
        r2 = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/service/{created_id}/edit")
        if r2.status_code == 404 or "Updated Service" not in r2.text:
            self.test_result("DELETE", "Service", True, f"Service {created_id} deleted")
        else:
            self.test_result("DELETE", "Service", False, "Service still exists")

    # ==================== Slider CRUD ====================
    def test_slider_crud(self):
        print("\n" + "="*60)
        print("Testing Slider Full CRUD")
        print("="*60)
        
        # CREATE
        print("\n[CREATE] Adding new Slider...")
        r = self.get_csrf_token(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/slider/create")
        
        slider_data = {
            "_token": self.csrf_token,
            "title": f"Test Slider {datetime.now().strftime('%H%M%S')}",
            "text": "Test slider text",
            "button_text": "Learn More",
            "button_link": "#",
            "status": "1"
        }
        
        r = self.session.post(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/slider/store", data=slider_data, allow_redirects=True)
        
        created_id = None
        r = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/slider")
        ids = re.findall(r'/admin/slider/(\d+)/edit', r.text)
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
        
        # UPDATE
        print("\n[UPDATE] Updating Slider...")
        r = self.get_csrf_token(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/slider/{created_id}/edit")
        
        update_data = {
            "_token": self.csrf_token,
            "_method": "PUT",
            "title": f"Updated Slider {datetime.now().strftime('%H%M%S')}",
            "text": "Updated slider text",
            "button_text": "Get Started",
            "button_link": "#about",
            "status": "1"
        }
        
        r = self.session.post(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/slider/{created_id}", data=update_data, allow_redirects=True)
        
        if r.status_code == 200:
            self.test_result("UPDATE", "Slider", True, f"Slider {created_id} updated")
        else:
            self.test_result("UPDATE", "Slider", False, f"Status: {r.status_code}")
        
        # DELETE
        print("\n[DELETE] Deleting Slider...")
        r = self.get_csrf_token(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/slider")
        
        delete_data = {
            "_token": self.csrf_token,
            "_method": "DELETE"
        }
        
        r = self.session.post(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/slider/{created_id}", data=delete_data, allow_redirects=True)
        
        r2 = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/slider/{created_id}/edit")
        if r2.status_code == 404 or "Updated Slider" not in r2.text:
            self.test_result("DELETE", "Slider", True, f"Slider {created_id} deleted")
        else:
            self.test_result("DELETE", "Slider", False, "Slider still exists")

    # ==================== Team CRUD ====================
    def test_team_crud(self):
        print("\n" + "="*60)
        print("Testing Team Full CRUD")
        print("="*60)
        
        # CREATE
        print("\n[CREATE] Adding new Team Member...")
        r = self.get_csrf_token(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/team/create")
        
        team_data = {
            "_token": self.csrf_token,
            "name": f"Test Member {datetime.now().strftime('%H%M%S')}",
            "designation": "Test Engineer",
            "facebook": "https://facebook.com",
            "twitter": "https://twitter.com",
            "linkedin": "https://linkedin.com",
            "instagram": "https://instagram.com",
            "status": "1"
        }
        
        r = self.session.post(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/team/store", data=team_data, allow_redirects=True)
        
        created_id = None
        r = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/team")
        ids = re.findall(r'/admin/team/(\d+)/edit', r.text)
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
        
        # UPDATE
        print("\n[UPDATE] Updating Team Member...")
        r = self.get_csrf_token(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/team/{created_id}/edit")
        
        update_data = {
            "_token": self.csrf_token,
            "_method": "PUT",
            "name": f"Updated Member {datetime.now().strftime('%H%M%S')}",
            "designation": "Senior Engineer",
            "facebook": "https://facebook.com/updated",
            "twitter": "https://twitter.com/updated",
            "linkedin": "https://linkedin.com/updated",
            "instagram": "https://instagram.com/updated",
            "status": "1"
        }
        
        r = self.session.post(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/team/{created_id}", data=update_data, allow_redirects=True)
        
        if r.status_code == 200:
            self.test_result("UPDATE", "Team Member", True, f"Team {created_id} updated")
        else:
            self.test_result("UPDATE", "Team Member", False, f"Status: {r.status_code}")
        
        # DELETE
        print("\n[DELETE] Deleting Team Member...")
        r = self.get_csrf_token(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/team")
        
        delete_data = {
            "_token": self.csrf_token,
            "_method": "DELETE"
        }
        
        r = self.session.post(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/team/{created_id}", data=delete_data, allow_redirects=True)
        
        r2 = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/team/{created_id}/edit")
        if r2.status_code == 404 or "Updated Member" not in r2.text:
            self.test_result("DELETE", "Team Member", True, f"Team {created_id} deleted")
        else:
            self.test_result("DELETE", "Team Member", False, "Team member still exists")

    # ==================== Funfact CRUD ====================
    def test_funfact_crud(self):
        print("\n" + "="*60)
        print("Testing Funfact Full CRUD")
        print("="*60)
        
        # CREATE
        print("\n[CREATE] Adding new Funfact...")
        r = self.get_csrf_token(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/funfact/create")
        
        funfact_data = {
            "_token": self.csrf_token,
            "title": f"Test Funfact {datetime.now().strftime('%H%M%S')}",
            "value": "999",
            "icon": "fas fa-users",
            "status": "1"
        }
        
        r = self.session.post(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/funfact/store", data=funfact_data, allow_redirects=True)
        
        created_id = None
        r = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/funfact")
        ids = re.findall(r'/admin/funfact/(\d+)/edit', r.text)
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
        
        # UPDATE
        print("\n[UPDATE] Updating Funfact...")
        r = self.get_csrf_token(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/funfact/{created_id}/edit")
        
        update_data = {
            "_token": self.csrf_token,
            "_method": "PUT",
            "title": f"Updated Funfact {datetime.now().strftime('%H%M%S')}",
            "value": "1000",
            "icon": "fas fa-trophy",
            "status": "1"
        }
        
        r = self.session.post(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/funfact/{created_id}", data=update_data, allow_redirects=True)
        
        if r.status_code == 200:
            self.test_result("UPDATE", "Funfact", True, f"Funfact {created_id} updated")
        else:
            self.test_result("UPDATE", "Funfact", False, f"Status: {r.status_code}")
        
        # DELETE
        print("\n[DELETE] Deleting Funfact...")
        r = self.get_csrf_token(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/funfact")
        
        delete_data = {
            "_token": self.csrf_token,
            "_method": "DELETE"
        }
        
        r = self.session.post(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/funfact/{created_id}", data=delete_data, allow_redirects=True)
        
        r2 = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/funfact/{created_id}/edit")
        if r2.status_code == 404 or "Updated Funfact" not in r2.text:
            self.test_result("DELETE", "Funfact", True, f"Funfact {created_id} deleted")
        else:
            self.test_result("DELETE", "Funfact", False, "Funfact still exists")

    # ==================== Social Links CRUD ====================
    def test_social_crud(self):
        print("\n" + "="*60)
        print("Testing Social Links Full CRUD")
        print("="*60)
        
        # CREATE
        print("\n[CREATE] Adding new Social Link...")
        r = self.get_csrf_token(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/social/create")
        
        social_data = {
            "_token": self.csrf_token,
            "icon": "fab fa-github",
            "link": f"https://github.com/test{datetime.now().strftime('%H%M%S')}",
            "status": "1"
        }
        
        r = self.session.post(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/social/store", data=social_data, allow_redirects=True)
        
        created_id = None
        r = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/social")
        ids = re.findall(r'/admin/social/(\d+)/edit', r.text)
        if ids:
            created_id = ids[0]
            self.test_result("CREATE", "Social Link", True, f"Created ID: {created_id}")
        else:
            self.test_result("CREATE", "Social Link", False, "Failed to create")
            return
        
        # READ
        print("\n[READ] Reading Social Link...")
        r = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/social/{created_id}/edit")
        if r.status_code == 200:
            self.test_result("READ", "Social Link", True, f"Social {created_id} loaded")
        else:
            self.test_result("READ", "Social Link", False, f"Status: {r.status_code}")
        
        # UPDATE
        print("\n[UPDATE] Updating Social Link...")
        r = self.get_csrf_token(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/social/{created_id}/edit")
        
        update_data = {
            "_token": self.csrf_token,
            "_method": "PUT",
            "icon": "fab fa-gitlab",
            "link": f"https://gitlab.com/updated{datetime.now().strftime('%H%M%S')}",
            "status": "1"
        }
        
        r = self.session.post(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/social/{created_id}", data=update_data, allow_redirects=True)
        
        if r.status_code == 200:
            self.test_result("UPDATE", "Social Link", True, f"Social {created_id} updated")
        else:
            self.test_result("UPDATE", "Social Link", False, f"Status: {r.status_code}")
        
        # DELETE
        print("\n[DELETE] Deleting Social Link...")
        r = self.get_csrf_token(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/social")
        
        delete_data = {
            "_token": self.csrf_token,
            "_method": "DELETE"
        }
        
        r = self.session.post(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/social/{created_id}", data=delete_data, allow_redirects=True)
        
        r2 = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/social/{created_id}/edit")
        if r2.status_code == 404 or "gitlab" not in r2.text.lower():
            self.test_result("DELETE", "Social Link", True, f"Social {created_id} deleted")
        else:
            self.test_result("DELETE", "Social Link", False, "Social link still exists")

    # ==================== Role CRUD ====================
    def test_role_crud(self):
        print("\n" + "="*60)
        print("Testing Role Full CRUD")
        print("="*60)
        
        # CREATE
        print("\n[CREATE] Adding new Role...")
        r = self.get_csrf_token(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/role/create")
        
        role_data = {
            "_token": self.csrf_token,
            "name": f"TestRole{datetime.now().strftime('%H%M%S')}",
        }
        
        r = self.session.post(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/role/store", data=role_data, allow_redirects=True)
        
        created_id = None
        r = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/role")
        ids = re.findall(r'/admin/role/(\d+)/edit', r.text)
        if ids:
            created_id = ids[0]
            self.test_result("CREATE", "Role", True, f"Created ID: {created_id}")
        else:
            self.test_result("CREATE", "Role", False, "Failed to create")
            return
        
        # READ
        print("\n[READ] Reading Role...")
        r = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/role/{created_id}/edit")
        if r.status_code == 200:
            self.test_result("READ", "Role", True, f"Role {created_id} loaded")
        else:
            self.test_result("READ", "Role", False, f"Status: {r.status_code}")
        
        # UPDATE
        print("\n[UPDATE] Updating Role...")
        r = self.get_csrf_token(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/role/{created_id}/edit")
        
        update_data = {
            "_token": self.csrf_token,
            "_method": "PUT",
            "name": f"UpdatedRole{datetime.now().strftime('%H%M%S')}",
        }
        
        r = self.session.post(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/role/{created_id}", data=update_data, allow_redirects=True)
        
        if r.status_code == 200:
            self.test_result("UPDATE", "Role", True, f"Role {created_id} updated")
        else:
            self.test_result("UPDATE", "Role", False, f"Status: {r.status_code}")
        
        # DELETE
        print("\n[DELETE] Deleting Role...")
        r = self.get_csrf_token(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/role")
        
        delete_data = {
            "_token": self.csrf_token,
            "_method": "DELETE"
        }
        
        r = self.session.post(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/role/{created_id}", data=delete_data, allow_redirects=True)
        
        r2 = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/role/{created_id}/edit")
        if r2.status_code == 404 or "UpdatedRole" not in r2.text:
            self.test_result("DELETE", "Role", True, f"Role {created_id} deleted")
        else:
            self.test_result("DELETE", "Role", False, "Role still exists")

    # ==================== Shipping CRUD ====================
    def test_shipping_crud(self):
        print("\n" + "="*60)
        print("Testing Shipping Full CRUD")
        print("="*60)
        
        # CREATE
        print("\n[CREATE] Adding new Shipping Method...")
        r = self.get_csrf_token(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/shipping/create")
        
        shipping_data = {
            "_token": self.csrf_token,
            "title": f"Test Shipping {datetime.now().strftime('%H%M%S')}",
            "charge": "10.00",
            "status": "1"
        }
        
        r = self.session.post(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/shipping/store", data=shipping_data, allow_redirects=True)
        
        created_id = None
        r = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/shipping")
        ids = re.findall(r'/admin/shipping/(\d+)/edit', r.text)
        if ids:
            created_id = ids[0]
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
            "title": f"Updated Shipping {datetime.now().strftime('%H%M%S')}",
            "charge": "15.00",
            "status": "1"
        }
        
        r = self.session.post(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/shipping/{created_id}", data=update_data, allow_redirects=True)
        
        if r.status_code == 200:
            self.test_result("UPDATE", "Shipping", True, f"Shipping {created_id} updated")
        else:
            self.test_result("UPDATE", "Shipping", False, f"Status: {r.status_code}")
        
        # DELETE
        print("\n[DELETE] Deleting Shipping Method...")
        r = self.get_csrf_token(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/shipping")
        
        delete_data = {
            "_token": self.csrf_token,
            "_method": "DELETE"
        }
        
        r = self.session.post(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/shipping/{created_id}", data=delete_data, allow_redirects=True)
        
        r2 = self.session.get(f"{BASE_URL}/{DEFAULT_LOCALE}/admin/shipping/{created_id}/edit")
        if r2.status_code == 404 or "Updated Shipping" not in r2.text:
            self.test_result("DELETE", "Shipping", True, f"Shipping {created_id} deleted")
        else:
            self.test_result("DELETE", "Shipping", False, "Shipping still exists")

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
        with open("CRUD_FULL_REPORT.json", "w") as f:
            json.dump({
                "total": total,
                "passed": passed,
                "failed": failed,
                "success_rate": f"{(passed/total*100):.1f}%" if total > 0 else "N/A",
                "results": self.results
            }, f, indent=2)
        print("\nReport saved to CRUD_FULL_REPORT.json")

    def run_all_tests(self):
        """Run all CRUD tests"""
        print("="*60)
        print("Full CRUD Functionality Test - ISP Admin Panel")
        print("="*60)
        
        if not self.login():
            return
        
        # Run all CRUD tests
        self.test_faq_crud()
        self.test_bcategory_crud()
        self.test_service_crud()
        self.test_slider_crud()
        self.test_team_crud()
        self.test_funfact_crud()
        self.test_social_crud()
        self.test_role_crud()
        self.test_shipping_crud()
        
        self.print_summary()


if __name__ == "__main__":
    import urllib3
    urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)
    
    tester = CRUDTester()
    tester.run_all_tests()
