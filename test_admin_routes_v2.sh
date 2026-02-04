#!/bin/bash

# Admin Route Testing Script v2
# Tests all GET admin routes with proper authentication

BASE_URL="https://isp.mlbbshop.app"
LOCALE="en"
COOKIE_FILE="/tmp/admin_cookies.txt"
OUTPUT_FILE="/tmp/admin_route_test_results.txt"

# Admin credentials
ADMIN_USERNAME="admin"
ADMIN_PASSWORD="Admin@888"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo "============================================"
echo "Admin Route Testing Script v2"
echo "============================================"
echo ""

# Clean up old cookies
rm -f $COOKIE_FILE

# Step 1: Get the login page to get CSRF token
echo "Step 1: Getting login page and CSRF token..."
LOGIN_PAGE=$(curl -s -c $COOKIE_FILE -b $COOKIE_FILE "$BASE_URL/admin")

# Extract CSRF token using sed (macOS compatible)
CSRF_TOKEN=$(echo "$LOGIN_PAGE" | sed -n 's/.*name="_token" value="\([^"]*\)".*/\1/p' | head -1)

if [ -z "$CSRF_TOKEN" ]; then
    echo -e "${RED}Failed to get CSRF token!${NC}"
    exit 1
fi

echo "CSRF Token: ${CSRF_TOKEN:0:20}..."

# Step 2: Login with username (not email)
echo ""
echo "Step 2: Logging in as admin..."
LOGIN_RESPONSE=$(curl -s -c $COOKIE_FILE -b $COOKIE_FILE \
    -X POST "$BASE_URL/admin/login" \
    -H "Content-Type: application/x-www-form-urlencoded" \
    -H "Accept: text/html,application/xhtml+xml,application/xml;q=0.9" \
    -H "Origin: $BASE_URL" \
    -H "Referer: $BASE_URL/admin" \
    -d "_token=$CSRF_TOKEN&username=$ADMIN_USERNAME&password=$ADMIN_PASSWORD" \
    -L -w "%{http_code}" -o /tmp/login_response.html)

echo "Login response code: $LOGIN_RESPONSE"

# Check if login was successful by accessing dashboard
sleep 1
DASHBOARD_CHECK=$(curl -s -b $COOKIE_FILE "$BASE_URL/$LOCALE/admin/dashboard" -o /tmp/dashboard.html -w "%{http_code}|%{size_download}")
DASH_CODE=$(echo $DASHBOARD_CHECK | cut -d'|' -f1)
DASH_SIZE=$(echo $DASHBOARD_CHECK | cut -d'|' -f2)

echo "Dashboard check: HTTP $DASH_CODE, Size: ${DASH_SIZE}B"

# Check if dashboard contains admin content (not login page)
if grep -q "dashboard\|Dashboard\|Admin Panel" /tmp/dashboard.html 2>/dev/null && [ "$DASH_SIZE" -gt 5000 ]; then
    echo -e "${GREEN}✓ Login successful! Dashboard loaded (${DASH_SIZE}B)${NC}"
    LOGIN_SUCCESS=true
else
    echo -e "${YELLOW}⚠ Login might have issues. Checking content...${NC}"
    if grep -q "login\|Login\|Sign in" /tmp/dashboard.html 2>/dev/null; then
        echo -e "${RED}✗ Still showing login page. Testing will continue but routes may redirect.${NC}"
        LOGIN_SUCCESS=false
    else
        echo -e "${GREEN}✓ Dashboard accessible${NC}"
        LOGIN_SUCCESS=true
    fi
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
SMALL=0

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
    
    # Check for error patterns in response body
    ERROR_CHECK=""
    if [ -f /tmp/route_response.html ]; then
        ERROR_CHECK=$(grep -i "exception\|ErrorException\|undefined variable\|missing required\|Call to undefined\|Class .* not found" /tmp/route_response.html 2>/dev/null | head -1)
    fi
    
    # Check if it's showing login page
    IS_LOGIN_PAGE=$(grep -c "Login To Go Your Dashboard" /tmp/route_response.html 2>/dev/null || echo "0")
    
    if [ "$HTTP_CODE" == "200" ] && [ -z "$ERROR_CHECK" ] && [ "$IS_LOGIN_PAGE" == "0" ] && [ "$SIZE" -gt 3000 ]; then
        echo -e "${GREEN}✓${NC} $route - ${HTTP_CODE} (${SIZE}B, ${TIME}s)"
        SUCCESS=$((SUCCESS + 1))
        echo "PASS|$route|$HTTP_CODE|$SIZE|$TIME" >> $OUTPUT_FILE
    elif [ "$HTTP_CODE" == "200" ] && [ "$IS_LOGIN_PAGE" != "0" ]; then
        echo -e "${YELLOW}→${NC} $route - ${HTTP_CODE} (login redirect, ${SIZE}B)"
        REDIRECT=$((REDIRECT + 1))
        echo "LOGIN_REDIRECT|$route|$HTTP_CODE|$SIZE|$TIME" >> $OUTPUT_FILE
    elif [ "$HTTP_CODE" == "302" ] || [ "$HTTP_CODE" == "301" ]; then
        echo -e "${YELLOW}→${NC} $route - ${HTTP_CODE} (redirect)"
        REDIRECT=$((REDIRECT + 1))
        echo "REDIRECT|$route|$HTTP_CODE|$SIZE|$TIME" >> $OUTPUT_FILE
    elif [ "$HTTP_CODE" == "200" ] && [ "$SIZE" -lt 3000 ]; then
        echo -e "${YELLOW}?${NC} $route - ${HTTP_CODE} (small response: ${SIZE}B)"
        SMALL=$((SMALL + 1))
        echo "SMALL|$route|$HTTP_CODE|$SIZE|$TIME" >> $OUTPUT_FILE
    else
        echo -e "${RED}✗${NC} $route - ${HTTP_CODE} (${SIZE}B)"
        if [ -n "$ERROR_CHECK" ]; then
            echo "  Error: ${ERROR_CHECK:0:100}..."
        fi
        ERROR=$((ERROR + 1))
        echo "FAIL|$route|$HTTP_CODE|$SIZE|$TIME|$ERROR_CHECK" >> $OUTPUT_FILE
    fi
}

# Define all GET admin routes
ROUTES=(
    # Dashboard & Main
    "dashboard"
    
    # About Section
    "about"
    "about/add"
    "about/edit/1"
    "about/contact-info"
    
    # Backup
    "backup"
    
    # Bank Settings
    "bank/settings"
    
    # Basic Info
    "basicinfo"
    
    # Bill Pay
    "bill-add"
    "bill-pay"
    "bill-pay/view/1"
    
    # Bind User
    "bind-payment-query-user"
    "bind-user-details/1"
    "bind-user-query"
    
    # Blog
    "blog"
    "blog/add"
    "blog/edit/1"
    "blog/blog-category"
    "blog/blog-category/add"
    "blog/blog-category/edit/1"
    
    # Branch
    "branch"
    "branch/add"
    "branch/edit/1"
    
    # Cache
    "cache-clear"
    
    # CB Pay
    "cbpay"
    
    # Cookie Alert
    "cookie-alert"
    
    # Currency
    "currency"
    "currency/add"
    "currency/edit/1"
    
    # Custom CSS
    "custom-css"
    
    # Dynamic Page
    "dynamic-page"
    "dynamic-page/add"
    "dynamic-page/edit/1"
    "dynamic-page/edit/9"
    
    # Email
    "email-config"
    "email-templates"
    "email-templates/1"
    
    # Entertainment
    "entertainment"
    "entertainment/add"
    "entertainment/edit/1"
    
    # Error Message
    "error-message"
    
    # Export
    "export-binduser-excel"
    "export-fault-excel"
    "export-install-excel"
    "export-payment-excel"
    "export-users-excel"
    
    # Extra Months
    "extra-months"
    "extra-months-edit/1"
    
    # FAQ
    "faq"
    "faq/add"
    "faq/edit/1"
    
    # Fault Query
    "fault-query"
    
    # Footer
    "footer"
    
    # Funfact
    "funfact"
    "funfact/add"
    "funfact/edit/1"
    
    # Group Email
    "groupemail"
    
    # Install Query
    "install-query"
    
    # KBZ Pay
    "kbzpay"
    
    # Language
    "languages"
    "language/add"
    "language/1/edit"
    "language/1/edit/keyword"
    
    # Maintenance
    "maintainance-settings"
    "add-maintainance-settings"
    
    # Marketing
    "marketting-information"
    
    # Media
    "media"
    "media/add"
    "media/edit/1"
    
    # Mail Subscriber
    "mailsubscriber"
    
    # Offer
    "offer"
    "offer/add"
    "offer/edit/1"
    
    # Package
    "package"
    "package/add"
    "package/edit/1"
    "package/all-order"
    "package/pending-order"
    "package/inprogress-order"
    "package/compleated-order"
    
    # Page Visibility
    "page-visibility"
    
    # Payment
    "payment/gateways"
    "payment/gateways/edit/1"
    "payment-process"
    "payment-process/add"
    "payment-process/edit/1"
    "payment-query"
    "payment-detail-query/1"
    "payment-user-detail/1"
    
    # Preferential
    "preferential-activities"
    
    # Product
    "product"
    "product/add"
    "product/edit/1"
    "product/all/orders"
    "product/pending/orders"
    "product/processing/orders"
    "product/completed/orders"
    "product/rejected/orders"
    "product/orders/detais/1"
    
    # Profile
    "profile"
    "profile/edit"
    "profile/password/edit"
    
    # Promotion
    "promotion"
    "promotion/add"
    "promotion/edit/1"
    
    # Register Users
    "register/users"
    "register/users/form"
    "register/user/details/1"
    "register/user/package-buy"
    "register/user/package-not-buy"
    
    # Scripts
    "scripts"
    
    # Search
    "search-bind-user"
    "search-fault-query"
    "search-payment-record"
    "search-query"
    
    # Section Title
    "sectiontitle"
    
    # SEO Info
    "seoinfo"
    
    # Service
    "service"
    "service/add"
    "service/edit/1"
    
    # Shipping
    "shipping/methods"
    "shipping/method/add"
    "shipping/method/edit/1"
    
    # Slider
    "slider"
    "slider/add"
    "slider/edit/1"
    
    # Social Links
    "slinks"
    "slinks/edit/1"
    
    # Subscriber
    "subscriber"
    "subscriber/add"
    "subscriber/edit/1"
    
    # Team
    "team"
    "team/add"
    "team/edit/1"
    
    # Testimonial
    "testimonial"
    "testimonial/add"
    "testimonial/edit/1"
    
    # User Management
    "user-query"
    "user-details/1"
    "user-disable"
    "user-notification"
    "user-role-manage"
    "user-role-add"
    "user-add-permission"
    "user-update"
    
    # Wave Pay
    "wavepay"
    
    # App Banner
    "app-banner"
    
    # Maintenance Edit
    "edit-maintainance-settings/1"
)

# Test all routes
for route in "${ROUTES[@]}"; do
    test_route "$route"
done

echo ""
echo "============================================"
echo "Test Summary"
echo "============================================"
echo -e "Total Routes Tested: $TOTAL"
echo -e "${GREEN}Successful (200 with content): $SUCCESS${NC}"
echo -e "${YELLOW}Redirects/Login Required: $REDIRECT${NC}"
echo -e "${YELLOW}Small Responses (possible issue): $SMALL${NC}"
echo -e "${RED}Errors: $ERROR${NC}"
echo ""
echo "Detailed results saved to: $OUTPUT_FILE"

# Show failed routes
if [ $ERROR -gt 0 ]; then
    echo ""
    echo "============================================"
    echo -e "${RED}Failed Routes:${NC}"
    echo "============================================"
    grep "^FAIL" $OUTPUT_FILE | while IFS='|' read -r status route code size time error; do
        echo -e "  ${RED}✗${NC} $route (HTTP $code) - $error"
    done
fi

# Show small response routes
if [ $SMALL -gt 0 ]; then
    echo ""
    echo "============================================"
    echo -e "${YELLOW}Small Response Routes (review needed):${NC}"
    echo "============================================"
    grep "^SMALL" $OUTPUT_FILE | while IFS='|' read -r status route code size time; do
        echo -e "  ${YELLOW}?${NC} $route (${size}B)"
    done
fi

# Cleanup
rm -f /tmp/route_response.html /tmp/login_response.html /tmp/dashboard.html
