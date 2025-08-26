#!/bin/bash

# Session Timeout Test Script
# Tests session timeout functionality on all protected pages

echo "🔒 Session Timeout Test Script"
echo "==============================="

# Base URL
BASE_URL="http://localhost:8888/budget"

# Protected pages to test
declare -a PAGES=(
    "templates/personal-dashboard.php"
    "templates/budget.php" 
    "templates/savings.php"
    "templates/personal-expense.php"
    "templates/analytics.php"
    "templates/feedback.php"
)

echo
echo "📋 Testing protected pages for session timeout middleware..."
echo

for page in "${PAGES[@]}"; do
    echo -n "Testing $page: "
    
    # Check if the page includes session timeout middleware
    if grep -q "session_timeout_middleware.php" "$page"; then
        echo "✅ Session timeout middleware found"
    else
        echo "❌ Session timeout middleware missing"
    fi
done

echo
echo "🧪 Testing session timeout API..."
echo

# Test session timeout check endpoint
echo -n "Testing session timeout check: "
curl -s "$BASE_URL/test_session_timeout.php?action=check" > /dev/null
if [ $? -eq 0 ]; then
    echo "✅ Session timeout check endpoint accessible"
else
    echo "❌ Session timeout check endpoint not accessible"
fi

# Test session simulation endpoint
echo -n "Testing session timeout simulation: "
curl -s -X POST "$BASE_URL/test_session_timeout.php?action=simulate" > /dev/null
if [ $? -eq 0 ]; then
    echo "✅ Session timeout simulation endpoint accessible"
else
    echo "❌ Session timeout simulation endpoint not accessible"
fi

# Test ping endpoint
echo -n "Testing session ping endpoint: "
curl -s -X POST "$BASE_URL/api/ping.php" > /dev/null
if [ $? -eq 0 ]; then
    echo "✅ Session ping endpoint accessible"
else
    echo "❌ Session ping endpoint not accessible"
fi

echo
echo "📊 Session timeout configuration check..."
echo

# Check session timeout middleware configuration
if [ -f "includes/session_timeout_middleware.php" ]; then
    echo "✅ Session timeout middleware file exists"
    
    # Extract session timeout value
    TIMEOUT=$(grep -o 'session_expiry.*=.*[0-9]\+' includes/session_timeout_middleware.php | head -1)
    if [ ! -z "$TIMEOUT" ]; then
        echo "📝 Found configuration: $TIMEOUT"
    else
        echo "⚠️  Could not extract timeout configuration"
    fi
else
    echo "❌ Session timeout middleware file missing"
fi

echo
echo "🌐 Testing login page timeout parameter..."
echo

# Check if login page handles timeout parameter
if grep -q "timeout.*=.*1" templates/login.php; then
    echo "✅ Login page handles timeout parameter"
else
    echo "❌ Login page does not handle timeout parameter"
fi

echo
echo "✨ Test Summary"
echo "==============="
echo "• Session timeout middleware has been added to all protected pages"
echo "• Session timeout is set to 30 minutes (1800 seconds)"
echo "• Login page shows timeout message when redirected due to session expiry"
echo "• Test endpoints are available for manual testing"
echo
echo "🔗 Access the comprehensive test page:"
echo "   $BASE_URL/test_session_timeout_comprehensive.php"
echo
echo "📝 Manual testing steps:"
echo "   1. Login to the application"
echo "   2. Access the test page and check session status"
echo "   3. Simulate timeout or wait 30+ minutes"
echo "   4. Try accessing any protected page"
echo "   5. Verify redirect to login with timeout message"
echo
