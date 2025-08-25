#!/bin/bash

echo "Testing complete_step.php via curl..."

# Test the API endpoint directly
curl -X POST \
  http://localhost/budget/public/walkthrough/complete_step.php \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID=test_session" \
  -d '{"step_name":"setup_income"}' \
  -v

echo -e "\n\nDone."
