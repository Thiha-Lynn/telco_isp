#!/usr/bin/env python3
"""
Quick fix verification for the specific paths
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

def test_url(name, path):
    url = f"{ADMIN_URL}{path}"
    try:
        r = session.get(url, allow_redirects=True, timeout=30)
        if r.status_code == 200:
            return True, "OK"
        elif r.status_code == 500:
            return False, "500 Error"
        elif r.status_code == 404:
            return False, "404 Not Found"
        return False, f"Status {r.status_code}"
    except Exception as e:
        return False, str(e)[:50]

# Verify the correct URLs
CORRECT_PATHS = [
    ('Social Links', '/slinks'),
    ('Roles', '/user-role-manage'),
    ('Shipping', '/shipping/methods'),
    ('Payment Query', '/payment-query'),
]

if __name__ == "__main__":
    print("Verifying correct paths...\n")
    
    if not login():
        print("Login failed!")
        exit(1)
    
    print("Logged in!\n")
    
    for name, path in CORRECT_PATHS:
        success, msg = test_url(name, path)
        if success:
            print(f"✅ {name} ({path}): {msg}")
        else:
            print(f"❌ {name} ({path}): {msg}")
