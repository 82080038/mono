#!/bin/bash
# PWA Development Control Script

echo "🔧 PWA Development Control"
echo "=========================="

case "$1" in
    --disable-development)
        echo "Disabling PWA for development..."
        python3 pwa_dev_controller.py --disable-development
        ;;
    --enable-production)
        echo "Enabling PWA for production..."
        python3 pwa_dev_controller.py --enable-production
        ;;
    --status)
        echo "Checking PWA status..."
        if [ -f "DEVELOPMENT_MODE.md" ]; then
            echo "🔧 Development Mode: ACTIVE"
            echo "📱 PWA Features: DISABLED"
        else
            echo "🚀 Production Mode: ACTIVE"
            echo "📱 PWA Features: ENABLED"
        fi
        ;;
    *)
        echo "Usage: $0 [--disable-development|--enable-production|--status]"
        echo ""
        echo "Options:"
        echo "  --disable-development  Disable PWA features for development"
        echo "  --enable-production    Enable PWA features for production"
        echo "  --status               Check current PWA status"
        exit 1
        ;;
esac
