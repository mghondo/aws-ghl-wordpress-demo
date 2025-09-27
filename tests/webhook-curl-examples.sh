#!/bin/bash

# GoHighLevel Webhook Testing with cURL
# Test the webhook endpoint manually

WEBHOOK_URL="http://localhost:8000/wp-json/clarity-ghl/v1/webhook"
WEBHOOK_SECRET="test_secret_key_12345"

echo "=== GoHighLevel Webhook cURL Tests ==="
echo "Webhook URL: $WEBHOOK_URL"
echo ""

# Function to generate signature
generate_signature() {
    local payload="$1"
    local secret="$2"
    echo -n "$payload" | openssl dgst -sha256 -hmac "$secret" | sed 's/^.* //'
}

# Test 1: Contact Created Event
echo "1. Testing Contact Created Event..."
PAYLOAD=$(cat mock-ghl-contact_created.json)
SIGNATURE=$(generate_signature "$PAYLOAD" "$WEBHOOK_SECRET")

curl -X POST "$WEBHOOK_URL" \
  -H "Content-Type: application/json" \
  -H "X-GHL-Signature: sha256=$SIGNATURE" \
  -H "User-Agent: GoHighLevel-Webhook/1.0" \
  -d "$PAYLOAD" \
  -w "\nHTTP Status: %{http_code}\n" \
  -v

echo -e "\n---\n"

# Test 2: Opportunity Created Event  
echo "2. Testing Opportunity Created Event..."
PAYLOAD=$(cat mock-ghl-opportunity_created.json)
SIGNATURE=$(generate_signature "$PAYLOAD" "$WEBHOOK_SECRET")

curl -X POST "$WEBHOOK_URL" \
  -H "Content-Type: application/json" \
  -H "X-GHL-Signature: sha256=$SIGNATURE" \
  -H "User-Agent: GoHighLevel-Webhook/1.0" \
  -d "$PAYLOAD" \
  -w "\nHTTP Status: %{http_code}\n"

echo -e "\n---\n"

# Test 3: Form Submitted Event
echo "3. Testing Form Submitted Event..."
PAYLOAD=$(cat mock-ghl-form_submitted.json)
SIGNATURE=$(generate_signature "$PAYLOAD" "$WEBHOOK_SECRET")

curl -X POST "$WEBHOOK_URL" \
  -H "Content-Type: application/json" \
  -H "X-GHL-Signature: sha256=$SIGNATURE" \
  -H "User-Agent: GoHighLevel-Webhook/1.0" \
  -d "$PAYLOAD" \
  -w "\nHTTP Status: %{http_code}\n"

echo -e "\n---\n"

# Test 4: Invalid Signature (should fail)
echo "4. Testing Invalid Signature (should return 401)..."
PAYLOAD=$(cat mock-ghl-contact_created.json)
INVALID_SIGNATURE="sha256=invalid_signature_here"

curl -X POST "$WEBHOOK_URL" \
  -H "Content-Type: application/json" \
  -H "X-GHL-Signature: $INVALID_SIGNATURE" \
  -H "User-Agent: GoHighLevel-Webhook/1.0" \
  -d "$PAYLOAD" \
  -w "\nHTTP Status: %{http_code}\n"

echo -e "\n---\n"

# Test 5: No Signature (should work if webhook secret not configured)
echo "5. Testing No Signature..."
PAYLOAD=$(cat mock-ghl-form_submitted.json)

curl -X POST "$WEBHOOK_URL" \
  -H "Content-Type: application/json" \
  -H "User-Agent: GoHighLevel-Webhook/1.0" \
  -d "$PAYLOAD" \
  -w "\nHTTP Status: %{http_code}\n"

echo -e "\n---\n"

# Test 6: Invalid JSON (should return 400)
echo "6. Testing Invalid JSON (should return 400)..."

curl -X POST "$WEBHOOK_URL" \
  -H "Content-Type: application/json" \
  -H "User-Agent: GoHighLevel-Webhook/1.0" \
  -d '{"invalid": json}' \
  -w "\nHTTP Status: %{http_code}\n"

echo -e "\n---\n"

# Test 7: Wrong Content Type (should return 400)
echo "7. Testing Wrong Content Type (should return 400)..."
PAYLOAD=$(cat mock-ghl-contact_created.json)

curl -X POST "$WEBHOOK_URL" \
  -H "Content-Type: text/plain" \
  -H "User-Agent: GoHighLevel-Webhook/1.0" \
  -d "$PAYLOAD" \
  -w "\nHTTP Status: %{http_code}\n"

echo ""
echo "=== Test Summary ==="
echo "✓ Test webhook endpoint with valid GHL data"
echo "✓ Test signature verification (both valid and invalid)"
echo "✓ Test error conditions (invalid JSON, wrong content type)"
echo ""
echo "Expected Results:"
echo "- Tests 1-3: HTTP 200 (successful webhook processing)"
echo "- Test 4: HTTP 401 (signature verification failed)" 
echo "- Test 5: HTTP 200 (no signature required if secret not set)"
echo "- Tests 6-7: HTTP 400 (bad request errors)"
echo ""
echo "Check WordPress logs and S3 bucket for stored webhook data."