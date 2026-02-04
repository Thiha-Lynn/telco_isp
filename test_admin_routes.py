#!/usr/bin/env python3
"""
Admin Route Testing Script
Tests all GET admin routes with proper authentication
"""

import requests
from bs4 import BeautifulSoup
import re
import sys
import time

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
NC = '\033[0m'

# All GET admin routes to test
ADMIN_ROUTES = [
    # Dashboard & Main
    "dashboard",
    
    # About Section
    "about",
    "about/add",
    "about/edit/1",
    "about/contact-info",
    
    # Backup
    "backup",
    
    # Bank Settings
    "bank/settings",
    
    # Basic Info
    "basicinfo",
    
    # Bill Pay
    "bill-add",
    "bill-pay",
    
    # Bind User
    "bind-payment-query-user",
    "bind-user-query",
    
    # Blog
    "blog",
    "blog/add",
    "blog/blog-category",
    "blog/blog-category/add",
    
    # Branch
    "branch",
    "branch/add",
    
    # Cache
    "cache-clear",
    
    # CB Pay
    "cbpay",
    
    # Cookie Alert
    "cookie-alert",
    
    # Currency
    "currency",
    "currency/add",
    
    # Custom CSS
    "custom-css",
    
    # Dynamic Page
    "dynamic-page",
    "dynamic-page/add",
    "dynamic-page/edit/9",
    
    # Email
    "email-config",
    "email-templates",
    
    # Entertainment
    "entertainment",
    "entertainment/add",
    
    # Error Message
    "error-message",
    
    # Extra Months
    "extra-months",
    
    # FAQ
    "faq",
    "faq/add",
    
    # Fault Query
    "fault-query",
    
    # Footer
    "footer",
    
    # Funfact
    "funfact",
    "funfact/add",
    
    # Group Email
    "groupemail",
    
    # Install Query
    "install-query",
    
    # KBZ Pay
    "kbzpay",
    
    # Language
    "languages",
    "language/add",
    
    # Maintenance
    "maintainance-settings",
    "add-maintainance-settings",
    
    # Marketing
    "marketting-information",
    
    # Media
    "media",
    "media/add",
    
    # Mail Subscriber
    "mailsubscriber",
    
    # Offer
    "offer",
    "offer/add",
    
    # Package
    "package",
    "package/add",
    "package/all-order",
    "package/pending-order",
    "package/inprogress-order",
    "package/compleated-order",
    
    # Page Visibility
    "page-visibility",
    
    # Payment
    "payment/gateways",
    "payment-process",
    "payment-process/add",
    "payment-query",
    
    # Preferential
    "preferential-activities",
    
    # Product
    "product",
    "product/add",
    "product/all/orders",
    "product/pending/orders",
    "product/processing/orders",
    "product/completed/orders",
    "product/rejected/orders",
    
    # Profile
    "profile",
    "profile/edit",
    "profile/password/edit",
    
    # Promotion
    "promotion",
    "promotion/add",
    
    # Register Users
    "register/users",
    "register/users/form",
    "register/user/package-buy",
    "register/user/package-not-buy",
    
    # Scripts
    "scripts",
    
    # Search
    "search-bind-user",
    "search-fault-query",
    "search-payment-record",
    "search-query",
    
    # Section Title
    "sectiontitle",
    
    # SEO Info
    "seoinfo",
    
    # Service
    "service",
    "service/add",
    
    # Shipping
    "shipping/methods",
    "shipping/method/add",
    
    # Slider
    "slider",
    "slider/add",
    
    # Social Links
    "slinks",
    
    # Subscriber
    "subscriber",
    "subscriber/add",
    
    # Team
    "team",
    "team/add",
    
    # Testimonial
    "testimonial",
    "testimonial/add",
    
    # User Management
    "user-query",
    "user-disable",
    "user-notification",
    "user-role-manage",
    "user-role-add",
    "user-add-permission",
    "user-update",
    
    # Wave Pay
    "wavepay",
    
    # App Banner
    "app-banner",
]

def login(session):
    """Perform admin login and return True if successful"""
    print("=" * 50)
    print("Admin Route Testing Script")
    print("=" * 50)
    print()
    
    # Get login page
    print("Step 1: Getting login page...")
    login_url = f"{BASE_URL}/admin"
    response = session.get(login_url)
    
    # Extract CSRF token
    soup = BeautifulSoup(response.text, 'html.parser')
    token_input = soup.find('input', {'name': '_token'})
    
    if not token_input:
        print(f"{RED}✗ Could not find CSRF token{NC}")
        return False
    
    csrf_token = token_input.get('value')
    print(f"CSRF Token: {csrf_token[:20]}...")
    
    # Perform login
    print()
    print("Step 2: Logging in as admin...")
    login_data = {
        '_token': csrf_token,
        'username': ADMIN_USERNAME,
        'password': ADMIN_PASSWORD
    }
    
    headers = {
        'Content-Type': 'application/x-www-form-urlencoded',
        'Accept': 'text/html,application/xhtml+xml',
        'Origin': BASE_URL,
        'Referer': login_url
    }
    
    response = session.post(f"{BASE_URL}/admin/login", data=login_data, headers=headers, allow_redirects=True)
    
    # Check if login was successful
    dashboard_url = f"{BASE_URL}/{LOCALE}/admin/dashboard"
    dashboard_response = session.get(dashboard_url)
    
    if "Login To Go Your Dashboard" in dashboard_response.text:
        print(f"{RED}✗ Login failed - still showing login page{NC}")
        return False
    
    if dashboard_response.status_code == 200 and len(dashboard_response.text) > 5000:
        print(f"{GREEN}✓ Login successful!{NC}")
        return True
    
    print(f"{YELLOW}⚠ Login status uncertain (HTTP {dashboard_response.status_code}, {len(dashboard_response.text)} bytes){NC}")
    return True  # Continue anyway

def test_route(session, route):
    """Test a single route and return result"""
    url = f"{BASE_URL}/{LOCALE}/admin/{route}"
    
    try:
        response = session.get(url, timeout=30)
        
        # Check for various conditions
        is_login_page = "Login To Go Your Dashboard" in response.text
        has_error = any(err in response.text for err in [
            "Exception", "ErrorException", "undefined variable",
            "Missing required", "Call to undefined", "Class not found"
        ])
        
        size = len(response.text)
        status = response.status_code
        
        if status == 200 and not is_login_page and not has_error and size > 3000:
            return ("PASS", status, size, None)
        elif is_login_page:
            return ("LOGIN", status, size, "Redirected to login")
        elif has_error:
            # Extract error message
            error_match = re.search(r'(Exception|Error|undefined variable)[^<]{0,100}', response.text)
            error_msg = error_match.group(0) if error_match else "Unknown error"
            return ("ERROR", status, size, error_msg)
        elif status != 200:
            return ("HTTP_ERROR", status, size, f"HTTP {status}")
        else:
            return ("SMALL", status, size, "Small response")
            
    except Exception as e:
        return ("EXCEPTION", 0, 0, str(e))

def main():
    # Create session with persistent cookies
    session = requests.Session()
    session.headers.update({
        'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36'
    })
    
    # Login
    if not login(session):
        print()
        print("Continuing with tests anyway (routes may redirect to login)...")
    
    print()
    print("=" * 50)
    print("Testing Admin Routes...")
    print("=" * 50)
    print()
    
    # Results tracking
    results = {
        'PASS': [],
        'LOGIN': [],
        'ERROR': [],
        'HTTP_ERROR': [],
        'SMALL': [],
        'EXCEPTION': []
    }
    
    total = len(ADMIN_ROUTES)
    
    for i, route in enumerate(ADMIN_ROUTES, 1):
        status, http_code, size, message = test_route(session, route)
        results[status].append((route, http_code, size, message))
        
        if status == "PASS":
            print(f"{GREEN}✓{NC} {route} - {http_code} ({size}B)")
        elif status == "LOGIN":
            print(f"{YELLOW}→{NC} {route} - {http_code} (login redirect)")
        elif status == "ERROR":
            print(f"{RED}✗{NC} {route} - {http_code} ({message[:50]}...)")
        elif status == "HTTP_ERROR":
            print(f"{RED}✗{NC} {route} - {message}")
        elif status == "SMALL":
            print(f"{YELLOW}?{NC} {route} - {http_code} ({size}B - small)")
        else:
            print(f"{RED}✗{NC} {route} - Exception: {message[:50]}")
        
        # Small delay to avoid overwhelming the server
        if i % 20 == 0:
            time.sleep(0.5)
    
    # Print summary
    print()
    print("=" * 50)
    print("Test Summary")
    print("=" * 50)
    print(f"Total Routes Tested: {total}")
    print(f"{GREEN}Successful (200 with content): {len(results['PASS'])}{NC}")
    print(f"{YELLOW}Login Required: {len(results['LOGIN'])}{NC}")
    print(f"{RED}Errors: {len(results['ERROR'])}{NC}")
    print(f"{RED}HTTP Errors: {len(results['HTTP_ERROR'])}{NC}")
    print(f"{YELLOW}Small Responses: {len(results['SMALL'])}{NC}")
    print(f"{RED}Exceptions: {len(results['EXCEPTION'])}{NC}")
    
    # Print failed routes
    if results['ERROR']:
        print()
        print("=" * 50)
        print(f"{RED}Error Routes:{NC}")
        print("=" * 50)
        for route, code, size, msg in results['ERROR']:
            print(f"  {RED}✗{NC} {route}: {msg[:80]}")
    
    if results['HTTP_ERROR']:
        print()
        print("=" * 50)
        print(f"{RED}HTTP Error Routes:{NC}")
        print("=" * 50)
        for route, code, size, msg in results['HTTP_ERROR']:
            print(f"  {RED}✗{NC} {route}: {msg}")

if __name__ == "__main__":
    main()
