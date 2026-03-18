#!/bin/bash

# Test navigation content generation
echo "Testing navigation content generation..."

# Test collector role navigation
echo "Testing collector role navigation..."
curl -s "http://localhost/mono/navigation_config.js" | grep -A 20 "collector:" | head -15

echo ""
echo "✅ Navigation content test completed"
