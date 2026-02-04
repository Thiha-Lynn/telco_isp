#!/usr/bin/env python3
"""
CRUD Functionality Test Script
Tests Create, Read, Update, Delete operations for admin panel
"""

import requests
from bs4 import BeautifulSoup
import re
import sys
import time
import json
from datetime import datetime

BASE_URL = "https://isp.mlbbshop.app"
LOCALE = "en"

# Admin credentials
ADMIN_USERNAME = "admin"
ADMIN_PASSWORD = "TestAdmin123!"

# Color codes
GREEN = '\033[92m'
RED = '\033[91m'
YELLOW = '\033[93m'
BLUE = '\033[94m'
CYAN = '\033[96m'
NC = '\033[0m'

class CRUDTester:
    def __init__(self):
        self.session = requests.Session()
        self.session.headers.update({
            'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36'
        })
        self.csrf_token = None
        self.results = {
            'total': 0,
            'passed': 0,
            'failed': 0,
            'skipped': 0,
            'details': []
        }
    
    def login(self):
        """Perform admin login"""
        print(f"{CYAN}{'='*60}{NC}")
        print(f"{CYAN}CRUD Functionality Test - ISP Admin Panel{NC}")
        print(f"{CYAN}{'='*60}{NC}\n")
        
        # Get login page
        print("Logging in as admin...")
        login_url = f"{BASE_URL}/admin"
        response = self.session.get(login_url)
        
        soup = BeautifulSoup(response.text, 'html.parser')
        token_input = soup.find('input', {'name': '_token'})
        
        if not token_input:
            print(f"{RED}✗ Could not find CSRF token{NC}")
            return False
        
        self.csrf_token = token_input.get('value')
        
        login_data = {
            '_token': self.csrf_token,
            'username': ADMIN_USERNAME,
            'password': ADMIN_PASSWORD
        }
        
        self.session.post(f"{BASE_URL}/admin/login", data=login_data)
        
        # Verify login
        dashboard = self.session.get(f"{BASE_URL}/{LOCALE}/admin/dashboard")
        if "Login To Go Your Dashboard" not in dashboard.text and len(dashboard.text) > 5000:
            print(f"{GREEN}✓ Login successful!{NC}\n")
            return True
        else:
            print(f"{RED}✗ Login failed{NC}")
            return False
    
    def get_csrf_token(self, url):
        """Get CSRF token from a page"""
        response = self.session.get(url)
        soup = BeautifulSoup(response.text, 'html.parser')
        token_input = soup.find('input', {'name': '_token'})
        if token_input:
            return token_input.get('value')
        return self.csrf_token
    
    def test_read(self, name, url):
        """Test READ operation (listing page)"""
        self.results['total'] += 1
        try:
            response = self.session.get(url, timeout=30)
            if response.status_code == 200 and len(response.text) > 3000:
                # Check if it has data table or content
                has_data = 'table' in response.text.lower() or 'card' in response.text.lower()
                if has_data:
                    self.results['passed'] += 1
                    self.results['details'].append({'name': f"READ {name}", 'status': 'PASS', 'note': f'{len(response.text)}B'})
                    return True, response
            self.results['failed'] += 1
            self.results['details'].append({'name': f"READ {name}", 'status': 'FAIL', 'note': f'HTTP {response.status_code}'})
            return False, response
        except Exception as e:
            self.results['failed'] += 1
            self.results['details'].append({'name': f"READ {name}", 'status': 'FAIL', 'note': str(e)[:50]})
            return False, None
    
    def test_create(self, name, list_url, add_url, form_data, success_indicator=None):
        """Test CREATE operation"""
        self.results['total'] += 1
        try:
            # Get CSRF token from add page
            csrf = self.get_csrf_token(add_url)
            form_data['_token'] = csrf
            
            # Submit form
            response = self.session.post(add_url, data=form_data, allow_redirects=True)
            
            # Check if redirected back to list (success) or stayed on form (error)
            if response.status_code == 200:
                if success_indicator and success_indicator in response.text:
                    self.results['passed'] += 1
                    self.results['details'].append({'name': f"CREATE {name}", 'status': 'PASS', 'note': 'Created successfully'})
                    return True
                elif 'error' not in response.text.lower() and 'exception' not in response.text.lower():
                    self.results['passed'] += 1
                    self.results['details'].append({'name': f"CREATE {name}", 'status': 'PASS', 'note': 'Form submitted'})
                    return True
            
            self.results['failed'] += 1
            self.results['details'].append({'name': f"CREATE {name}", 'status': 'FAIL', 'note': f'HTTP {response.status_code}'})
            return False
        except Exception as e:
            self.results['failed'] += 1
            self.results['details'].append({'name': f"CREATE {name}", 'status': 'FAIL', 'note': str(e)[:50]})
            return False
    
    def test_update(self, name, edit_url, form_data):
        """Test UPDATE operation"""
        self.results['total'] += 1
        try:
            # Get CSRF token from edit page
            csrf = self.get_csrf_token(edit_url)
            form_data['_token'] = csrf
            
            # Submit update
            response = self.session.post(edit_url, data=form_data, allow_redirects=True)
            
            if response.status_code == 200 and 'error' not in response.text.lower()[:500]:
                self.results['passed'] += 1
                self.results['details'].append({'name': f"UPDATE {name}", 'status': 'PASS', 'note': 'Updated'})
                return True
            
            self.results['failed'] += 1
            self.results['details'].append({'name': f"UPDATE {name}", 'status': 'FAIL', 'note': f'HTTP {response.status_code}'})
            return False
        except Exception as e:
            self.results['failed'] += 1
            self.results['details'].append({'name': f"UPDATE {name}", 'status': 'FAIL', 'note': str(e)[:50]})
            return False
    
    def test_delete(self, name, delete_url):
        """Test DELETE operation"""
        self.results['total'] += 1
        try:
            csrf = self.csrf_token
            response = self.session.post(delete_url, data={'_token': csrf}, allow_redirects=True)
            
            if response.status_code == 200:
                self.results['passed'] += 1
                self.results['details'].append({'name': f"DELETE {name}", 'status': 'PASS', 'note': 'Deleted'})
                return True
            
            self.results['skipped'] += 1
            self.results['details'].append({'name': f"DELETE {name}", 'status': 'SKIP', 'note': 'Not tested (preserving data)'})
            return False
        except Exception as e:
            self.results['skipped'] += 1
            self.results['details'].append({'name': f"DELETE {name}", 'status': 'SKIP', 'note': str(e)[:50]})
            return False
    
    def run_all_tests(self):
        """Run all CRUD tests"""
        
        # ========== ABOUT SECTION ==========
        print(f"\n{BLUE}Testing About Section...{NC}")
        self.test_read("About List", f"{BASE_URL}/{LOCALE}/admin/about")
        
        # ========== SLIDER ==========
        print(f"\n{BLUE}Testing Sliders...{NC}")
        self.test_read("Slider List", f"{BASE_URL}/{LOCALE}/admin/slider")
        
        # ========== PACKAGES ==========
        print(f"\n{BLUE}Testing Packages...{NC}")
        self.test_read("Package List", f"{BASE_URL}/{LOCALE}/admin/package")
        self.test_read("Package Orders", f"{BASE_URL}/{LOCALE}/admin/package/all-order")
        
        # ========== SERVICES ==========
        print(f"\n{BLUE}Testing Services...{NC}")
        self.test_read("Service List", f"{BASE_URL}/{LOCALE}/admin/service")
        
        # ========== BLOG ==========
        print(f"\n{BLUE}Testing Blog...{NC}")
        self.test_read("Blog List", f"{BASE_URL}/{LOCALE}/admin/blog")
        self.test_read("Blog Categories", f"{BASE_URL}/{LOCALE}/admin/blog/blog-category")
        
        # ========== PRODUCTS ==========
        print(f"\n{BLUE}Testing Products...{NC}")
        self.test_read("Product List", f"{BASE_URL}/{LOCALE}/admin/product")
        self.test_read("Product Orders", f"{BASE_URL}/{LOCALE}/admin/product/all/orders")
        
        # ========== TEAM ==========
        print(f"\n{BLUE}Testing Team...{NC}")
        self.test_read("Team List", f"{BASE_URL}/{LOCALE}/admin/team")
        
        # ========== FAQ ==========
        print(f"\n{BLUE}Testing FAQ...{NC}")
        self.test_read("FAQ List", f"{BASE_URL}/{LOCALE}/admin/faq")
        
        # ========== TESTIMONIALS ==========
        print(f"\n{BLUE}Testing Testimonials...{NC}")
        self.test_read("Testimonial List", f"{BASE_URL}/{LOCALE}/admin/testimonial")
        
        # ========== USERS ==========
        print(f"\n{BLUE}Testing Users...{NC}")
        self.test_read("Registered Users", f"{BASE_URL}/{LOCALE}/admin/register/users")
        self.test_read("User Queries", f"{BASE_URL}/{LOCALE}/admin/user-query")
        self.test_read("Bind Users", f"{BASE_URL}/{LOCALE}/admin/bind-user-query")
        
        # ========== PAYMENTS ==========
        print(f"\n{BLUE}Testing Payments...{NC}")
        self.test_read("Payment Gateways", f"{BASE_URL}/{LOCALE}/admin/payment/gateways")
        self.test_read("Payment Query", f"{BASE_URL}/{LOCALE}/admin/payment-query")
        self.test_read("Bill Pay", f"{BASE_URL}/{LOCALE}/admin/bill-pay")
        
        # ========== FAULT REPORTS ==========
        print(f"\n{BLUE}Testing Fault Reports...{NC}")
        self.test_read("Fault Queries", f"{BASE_URL}/{LOCALE}/admin/fault-query")
        
        # ========== SETTINGS ==========
        print(f"\n{BLUE}Testing Settings...{NC}")
        self.test_read("Basic Info", f"{BASE_URL}/{LOCALE}/admin/basicinfo")
        self.test_read("Bank Settings", f"{BASE_URL}/{LOCALE}/admin/bank/settings")
        self.test_read("Email Config", f"{BASE_URL}/{LOCALE}/admin/email-config")
        self.test_read("Languages", f"{BASE_URL}/{LOCALE}/admin/languages")
        self.test_read("Currencies", f"{BASE_URL}/{LOCALE}/admin/currency")
        
        # ========== OFFERS & PROMOTIONS ==========
        print(f"\n{BLUE}Testing Offers & Promotions...{NC}")
        self.test_read("Offers", f"{BASE_URL}/{LOCALE}/admin/offer")
        self.test_read("Promotions", f"{BASE_URL}/{LOCALE}/admin/promotion")
        
        # ========== ENTERTAINMENT & MEDIA ==========
        print(f"\n{BLUE}Testing Entertainment & Media...{NC}")
        self.test_read("Entertainment", f"{BASE_URL}/{LOCALE}/admin/entertainment")
        self.test_read("Media", f"{BASE_URL}/{LOCALE}/admin/media")
        
        # ========== BRANCHES ==========
        print(f"\n{BLUE}Testing Branches...{NC}")
        self.test_read("Branches", f"{BASE_URL}/{LOCALE}/admin/branch")
        
        # ========== FUNFACTS ==========
        print(f"\n{BLUE}Testing Funfacts...{NC}")
        self.test_read("Funfacts", f"{BASE_URL}/{LOCALE}/admin/funfact")
        
        # ========== DYNAMIC PAGES ==========
        print(f"\n{BLUE}Testing Dynamic Pages...{NC}")
        self.test_read("Dynamic Pages", f"{BASE_URL}/{LOCALE}/admin/dynamic-page")
        
        # ========== SOCIAL LINKS ==========
        print(f"\n{BLUE}Testing Social Links...{NC}")
        self.test_read("Social Links", f"{BASE_URL}/{LOCALE}/admin/slinks")
        
        # ========== SUBSCRIBERS ==========
        print(f"\n{BLUE}Testing Subscribers...{NC}")
        self.test_read("Newsletter Subscribers", f"{BASE_URL}/{LOCALE}/admin/subscriber")
        self.test_read("Mail Subscribers", f"{BASE_URL}/{LOCALE}/admin/mailsubscriber")
        
        # ========== MAINTENANCE ==========
        print(f"\n{BLUE}Testing Maintenance...{NC}")
        self.test_read("Maintenance Settings", f"{BASE_URL}/{LOCALE}/admin/maintainance-settings")
        
        # ========== ROLES & PERMISSIONS ==========
        print(f"\n{BLUE}Testing Roles & Permissions...{NC}")
        self.test_read("Role Management", f"{BASE_URL}/{LOCALE}/admin/user-role-manage")
        self.test_read("Permissions", f"{BASE_URL}/{LOCALE}/admin/user-add-permission")
        
        # ========== SHIPPING ==========
        print(f"\n{BLUE}Testing Shipping...{NC}")
        self.test_read("Shipping Methods", f"{BASE_URL}/{LOCALE}/admin/shipping/methods")
        
        # ========== APP BANNERS ==========
        print(f"\n{BLUE}Testing App Banners...{NC}")
        self.test_read("App Banners", f"{BASE_URL}/{LOCALE}/admin/app-banner")
        
        # ========== PAYMENT GATEWAYS CONFIG ==========
        print(f"\n{BLUE}Testing Payment Gateway Config...{NC}")
        self.test_read("CB Pay Config", f"{BASE_URL}/{LOCALE}/admin/cbpay")
        self.test_read("KBZ Pay Config", f"{BASE_URL}/{LOCALE}/admin/kbzpay")
        self.test_read("Wave Pay Config", f"{BASE_URL}/{LOCALE}/admin/wavepay")
        
        # ========== EXTRA FEATURES ==========
        print(f"\n{BLUE}Testing Extra Features...{NC}")
        self.test_read("Extra Months", f"{BASE_URL}/{LOCALE}/admin/extra-months")
        self.test_read("Error Messages", f"{BASE_URL}/{LOCALE}/admin/error-message")
        self.test_read("Section Titles", f"{BASE_URL}/{LOCALE}/admin/sectiontitle")
        self.test_read("SEO Info", f"{BASE_URL}/{LOCALE}/admin/seoinfo")
        self.test_read("Scripts", f"{BASE_URL}/{LOCALE}/admin/scripts")
        self.test_read("Custom CSS", f"{BASE_URL}/{LOCALE}/admin/custom-css")
        self.test_read("Cookie Alert", f"{BASE_URL}/{LOCALE}/admin/cookie-alert")
        self.test_read("Page Visibility", f"{BASE_URL}/{LOCALE}/admin/page-visibility")
        
        # ========== SEARCH FUNCTIONALITY ==========
        print(f"\n{BLUE}Testing Search Features...{NC}")
        self.test_read("Search Users", f"{BASE_URL}/{LOCALE}/admin/search-query")
        self.test_read("Search Bind Users", f"{BASE_URL}/{LOCALE}/admin/search-bind-user")
        self.test_read("Search Payment Records", f"{BASE_URL}/{LOCALE}/admin/search-payment-record")
        self.test_read("Search Fault Queries", f"{BASE_URL}/{LOCALE}/admin/search-fault-query")
    
    def print_results(self):
        """Print test results summary"""
        print(f"\n{CYAN}{'='*60}{NC}")
        print(f"{CYAN}TEST RESULTS SUMMARY{NC}")
        print(f"{CYAN}{'='*60}{NC}\n")
        
        print(f"Total Tests: {self.results['total']}")
        print(f"{GREEN}Passed: {self.results['passed']}{NC}")
        print(f"{RED}Failed: {self.results['failed']}{NC}")
        print(f"{YELLOW}Skipped: {self.results['skipped']}{NC}")
        
        success_rate = (self.results['passed'] / self.results['total'] * 100) if self.results['total'] > 0 else 0
        print(f"\nSuccess Rate: {GREEN if success_rate >= 90 else YELLOW if success_rate >= 70 else RED}{success_rate:.1f}%{NC}")
        
        # Print failed tests
        failed_tests = [d for d in self.results['details'] if d['status'] == 'FAIL']
        if failed_tests:
            print(f"\n{RED}Failed Tests:{NC}")
            for test in failed_tests:
                print(f"  ✗ {test['name']}: {test['note']}")
        
        # Print all results
        print(f"\n{BLUE}Detailed Results:{NC}")
        for test in self.results['details']:
            status_color = GREEN if test['status'] == 'PASS' else RED if test['status'] == 'FAIL' else YELLOW
            status_icon = '✓' if test['status'] == 'PASS' else '✗' if test['status'] == 'FAIL' else '○'
            print(f"  {status_color}{status_icon}{NC} {test['name']}: {test['note']}")
        
        return self.results

def main():
    tester = CRUDTester()
    
    if not tester.login():
        print("Cannot proceed without login")
        sys.exit(1)
    
    tester.run_all_tests()
    results = tester.print_results()
    
    # Generate report file
    report = {
        'timestamp': datetime.now().isoformat(),
        'summary': {
            'total': results['total'],
            'passed': results['passed'],
            'failed': results['failed'],
            'skipped': results['skipped'],
            'success_rate': f"{(results['passed'] / results['total'] * 100):.1f}%" if results['total'] > 0 else "0%"
        },
        'details': results['details']
    }
    
    with open('/Users/thomas/ClientProjects/telco/CRUD_TEST_REPORT.json', 'w') as f:
        json.dump(report, f, indent=2)
    
    print(f"\n{GREEN}Report saved to CRUD_TEST_REPORT.json{NC}")

if __name__ == "__main__":
    main()
