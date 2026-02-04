#!/bin/bash

# Admin Route Testing Script
# Tests all GET admin routes with authentication

BASE_URL="https://isp.mlbbshop.app"
LOCALE="en"
COOKIE_FILE="/tmp/admin_cookies.txt"
OUTPUT_FILE="/tmp/admin_route_test_results.txt"

# Admin credentials - update these
ADMIN_EMAIL="admin@gmail.com"
ADMIN_PASSWORD="Admin@888"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "============================================"
echo "Admin Route Testing Script"
echo "============================================"
echo ""

# Clean up old cookies
rm -f $COOKIE_FILE

# Step 1: Get the login page to get CSRF token
echo "Getting login page and CSRF token..."
LOGIN_PAGE=$(curl -s -c $COOKIE_FILE -b $COOKIE_FILE "$BASE_URL/admin")

# Extract CSRF token
CSRF_TOKEN=$(echo "$LOGIN_PAGE" | grep -oP 'name="_token" value="\K[^"]+' | head -1)

if [ -z "$CSRF_TOKEN" ]; then
    CSRF_TOKEN=$(echo "$LOGIN_PAGE" | grep -oP 'csrf-token" content="\K[^"]+' | head -1)
fi

if [ -z "$CSRF_TOKEN" ]; then
    echo "Failed to get CSRF token. Trying alternative method..."
    CSRF_TOKEN=$(echo "$LOGIN_PAGE" | sed -n 's/.*name="_token" value="\([^"]*\)".*/\1/p' | head -1)
fi

echo "CSRF Token: ${CSRF_TOKEN:0:20}..."

# Step 2: Login
echo "Logging in as admin..."
LOGIN_RESPONSE=$(curl -s -c $COOKIE_FILE -b $COOKIE_FILE \
    -X POST "$BASE_URL/admin/login" \
    -H "Content-Type: application/x-www-form-urlencoded" \
    -H "Accept: text/html,application/xhtml+xml" \
    -d "_token=$CSRF_TOKEN&email=$ADMIN_EMAIL&password=$ADMIN_PASSWORD" \
    -L -w "%{http_code}" -o /tmp/login_response.html)

echo "Login response code: $LOGIN_RESPONSE"

# Check if login was successful by accessing dashboard
DASHBOARD_CHECK=$(curl -s -b $COOKIE_FILE -o /dev/null -w "%{http_code}" "$BASE_URL/$LOCALE/admin/dashboard")
if [ "$DASHBOARD_CHECK" == "200" ]; then
    echo -e "${GREEN}✓ Login successful!${NC}"
else
    echo -e "${RED}✗ Login may have failed. Dashboard returned: $DASHBOARD_CHECK${NC}"
    echo "Continuing anyway..."
fi

echo ""
echo "============================================"
echo "Testing Admin Routes..."
echo "============================================"
echo ""

# Clear results file
> $OUTPUT_FILE

# Counter variables
TOTAL=0
SUCCESS=0
REDIRECT=0
ERROR=0

# Function to test a route
test_route() {
    local route="$1"
    local full_url="$BASE_URL/$LOCALE/admin/$route"
    
    # Get response with detailed info
    RESPONSE=$(curl -s -b $COOKIE_FILE -o /tmp/route_response.html -w "%{http_code}|%{size_download}|%{time_total}" "$full_url" -L)
    
    HTTP_CODE=$(echo $RESPONSE | cut -d'|' -f1)
    SIZE=$(echo $RESPONSE | cut -d'|' -f2)
    TIME=$(echo $RESPONSE | cut -d'|' -f3)
    
    TOTAL=$((TOTAL + 1))
    
    # Check for error in response body
    ERROR_CHECK=""
    if [ -f /tmp/route_response.html ]; then
        ERROR_CHECK=$(grep -i "exception\|error\|undefined variable\|missing required" /tmp/route_response.html 2>/dev/null | head -1)
    fi
    
    if [ "$HTTP_CODE" == "200" ] && [ -z "$ERROR_CHECK" ]; then
        echo -e "${GREEN}✓${NC} $route - ${HTTP_CODE} (${SIZE}B, ${TIME}s)"
        SUCCESS=$((SUCCESS + 1))
        echo "PASS|$route|$HTTP_CODE|$SIZE|$TIME" >> $OUTPUT_FILE
    elif [ "$HTTP_CODE" == "302" ] || [ "$HTTP_CODE" == "301" ]; then
        echo -e "${YELLOW}→${NC} $route - ${HTTP_CODE} (redirect)"
        REDIRECT=$((REDIRECT + 1))
        echo "REDIRECT|$route|$HTTP_CODE|$SIZE|$TIME" >> $OUTPUT_FILE
    else
        echo -e "${RED}✗${NC} $route - ${HTTP_CODE} (${SIZE}B)"
        if [ -n "$ERROR_CHECK" ]; then
            echo "  Error: ${ERROR_CHECK:0:100}..."
        fi
        ERROR=$((ERROR + 1))
        echo "FAIL|$route|$HTTP_CODE|$SIZE|$TIME|$ERROR_CHECK" >> $OUTPUT_FILE
    fi
}

# Test all GET routes
# Dashboard & Main
test_route "dashboard"

# About Section
test_route "about"
test_route "about/add"
test_route "about/edit/1"
test_route "about/contact-info"

# Backup
test_route "backup"

# Bank Settings
test_route "bank/settings"

# Basic Info
test_route "basicinfo"

# Bill Pay
test_route "bill-add"
test_route "bill-pay"
test_route "bill-pay/view/1"

# Bind User
test_route "bind-payment-query-user"
test_route "bind-user-details/1"
test_route "bind-user-query"

# Blog
test_route "blog"
test_route "blog/add"
test_route "blog/edit/1"
test_route "blog/blog-category"
test_route "blog/blog-category/add"
test_route "blog/blog-category/edit/1"

# Branch
test_route "branch"
test_route "branch/add"
test_route "branch/edit/1"

# Cache
test_route "cache-clear"

# CB Pay
test_route "cbpay"

# Cookie Alert
test_route "cookie-alert"

# Currency
test_route "currency"
test_route "currency/add"
test_route "currency/edit/1"

# Custom CSS
test_route "custom-css"

# Dynamic Page
test_route "dynamic-page"
test_route "dynamic-page/add"
test_route "dynamic-page/edit/1"

# Email
test_route "email-config"
test_route "email-templates"
test_route "email-templates/1"

# Entertainment
test_route "entertainment"
test_route "entertainment/add"
test_route "entertainment/edit/1"

# Error Message
test_route "error-message"

# Export
test_route "export-binduser-excel"
test_route "export-fault-excel"
test_route "export-install-excel"
test_route "export-payment-excel"
test_route "export-users-excel"

# Extra Months
test_route "extra-months"
test_route "extra-months-edit/1"

# FAQ
test_route "faq"
test_route "faq/add"
test_route "faq/edit/1"

# Fault Query
test_route "fault-query"

# Footer
test_route "footer"

# Funfact
test_route "funfact"
test_route "funfact/add"
test_route "funfact/edit/1"

# Group Email
test_route "groupemail"

# Install Query
test_route "install-query"

# KBZ Pay
test_route "kbzpay"

# Language
test_route "languages"
test_route "language/add"
test_route "language/1/edit"
test_route "language/1/edit/keyword"

# Maintenance
test_route "maintainance-settings"
test_route "add-maintainance-settings"

# Marketing
test_route "marketting-information"

# Media
test_route "media"
test_route "media/add"
test_route "media/edit/1"

# Mail Subscriber
test_route "mailsubscriber"

# Offer
test_route "offer"
test_route "offer/add"
test_route "offer/edit/1"

# Package
test_route "package"
test_route "package/add"
test_route "package/edit/1"
test_route "package/all-order"
test_route "package/pending-order"
test_route "package/inprogress-order"
test_route "package/compleated-order"

# Page Visibility
test_route "page-visibility"

# Payment
test_route "payment/gateways"
test_route "payment/gateways/edit/1"
test_route "payment-process"
test_route "payment-process/add"
test_route "payment-process/edit/1"
test_route "payment-query"

# Preferential
test_route "preferential-activities"

# Product
test_route "product"
test_route "product/add"
test_route "product/edit/1"
test_route "product/all/orders"
test_route "product/pending/orders"
test_route "product/processing/orders"
test_route "product/completed/orders"
test_route "product/rejected/orders"

# Profile
test_route "profile"
test_route "profile/edit"
test_route "profile/password/edit"

# Promotion
test_route "promotion"
test_route "promotion/add"
test_route "promotion/edit/1"

# Register Users
test_route "register/users"
test_route "register/users/form"
test_route "register/user/details/1"
test_route "register/user/package-buy"
test_route "register/user/package-not-buy"

# Scripts
test_route "scripts"

# Search
test_route "search-bind-user"
test_route "search-fault-query"
test_route "search-payment-record"
test_route "search-query"

# Section Title
test_route "sectiontitle"

# SEO Info
test_route "seoinfo"

# Service
test_route "service"
test_route "service/add"
test_route "service/edit/1"

# Shipping
test_route "shipping/methods"
test_route "shipping/method/add"
test_route "shipping/method/edit/1"

# Slider
test_route "slider"
test_route "slider/add"
test_route "slider/edit/1"

# Social Links
test_route "slinks"
test_route "slinks/edit/1"

# Subscriber
test_route "subscriber"
test_route "subscriber/add"
test_route "subscriber/edit/1"

# Team
test_route "team"
test_route "team/add"
test_route "team/edit/1"

# Testimonial
test_route "testimonial"
test_route "testimonial/add"
test_route "testimonial/edit/1"

# User Management
test_route "user-query"
test_route "user-details/1"
test_route "user-disable"
test_route "user-notification"
test_route "user-role-manage"
test_route "user-role-add"
test_route "user-add-permission"
test_route "user-update"

# Wave Pay
test_route "wavepay"

# App Banner
test_route "app-banner"

echo ""
echo "============================================"
echo "Test Summary"
echo "============================================"
echo -e "Total Routes Tested: $TOTAL"
echo -e "${GREEN}Successful (200): $SUCCESS${NC}"
echo -e "${YELLOW}Redirects (301/302): $REDIRECT${NC}"
echo -e "${RED}Errors: $ERROR${NC}"
echo ""
echo "Detailed results saved to: $OUTPUT_FILE"

# Show failed routes
if [ $ERROR -gt 0 ]; then
    echo ""
    echo "============================================"
    echo "Failed Routes:"
    echo "============================================"
    grep "^FAIL" $OUTPUT_FILE | while IFS='|' read -r status route code size time error; do
        echo "  - $route (HTTP $code)"
    done
fi

# Cleanup
rm -f /tmp/route_response.html /tmp/login_response.html
