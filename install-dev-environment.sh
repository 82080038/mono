#!/bin/bash

# KSP Lam Gabe Jaya - Development Environment Setup
# This script installs and configures everything needed for development

echo "🚀 KSP Lam Gabe Jaya - Development Environment Setup"
echo "======================================================"

# Update system packages
echo "📦 Updating system packages..."
sudo apt update && sudo apt upgrade -y

# Install Apache2 Web Server
echo "🌐 Installing Apache2 Web Server..."
sudo apt install -y apache2
sudo systemctl enable apache2
sudo systemctl start apache2

# Install PHP 8.3 and required extensions
echo "🐘 Installing PHP 8.3 and extensions..."
sudo apt install -y php8.3 php8.3-cli php8.3-common php8.3-mysql php8.3-pdo php8.3-pdo-mysql php8.3-mbstring php8.3-json php8.3-curl php8.3-xml php8.3-zip php8.3-gd php8.3-intl php8.3-opcache

# Install MariaDB Database
echo "🗄️ Installing MariaDB Database..."
sudo apt install -y mariadb-server mariadb-client
sudo systemctl enable mariadb
sudo systemctl start mariadb

# Secure MariaDB installation
echo "🔒 Securing MariaDB..."
sudo mysql_secure_installation <<EOF

y
root
root
y
y
y
y
EOF

# Create database and user
echo "🏗️ Creating database and user..."
sudo mysql -u root -proot -e "
CREATE DATABASE IF NOT EXISTS ksp_lamgabejaya CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'root'@'localhost' IDENTIFIED BY 'root';
GRANT ALL PRIVILEGES ON ksp_lamgabejaya.* TO 'root'@'localhost';
FLUSH PRIVILEGES;
"

# Install PHPMyAdmin (optional but recommended)
echo "📊 Installing PHPMyAdmin..."
sudo apt install -y phpmyadmin

# Configure Apache for PHPMyAdmin
echo "⚙️ Configuring Apache for PHPMyAdmin..."
sudo phpenmod mbstring
sudo systemctl restart apache2

# Install Node.js and npm
echo "📦 Installing Node.js and npm..."
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# Install Git
echo "📚 Installing Git..."
sudo apt install -y git

# Install Composer
echo "🎼 Installing Composer..."
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# Install useful development tools
echo "🛠️ Installing development tools..."
sudo apt install -y vim nano htop tree zip unzip

# Configure Apache for the application
echo "🌐 Configuring Apache for KSP Lam Gabe Jaya..."
sudo tee /etc/apache2/sites-available/ksp-lamgabejaya.conf > /dev/null <<EOF
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot /var/www/html/mono
    
    <Directory /var/www/html/mono>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/ksp-lamgabejaya_error.log
    CustomLog \${APACHE_LOG_DIR}/ksp-lamgabejaya_access.log combined
</VirtualHost>
EOF

# Enable the site and modules
sudo a2ensite ksp-lamgabejaya.conf
sudo a2enmod rewrite
sudo systemctl restart apache2

# Import database schema if exists
if [ -f "/var/www/html/mono/database/migrations/003_simple_schema.sql" ]; then
    echo "🗄️ Importing database schema..."
    mysql -u root -proot ksp_lamgabejaya < /var/www/html/mono/database/migrations/003_simple_schema.sql
fi

# Set proper permissions
echo "🔐 Setting proper permissions..."
sudo chown -R www-data:www-data /var/www/html/mono
sudo chmod -R 755 /var/www/html/mono

# Create development startup script
echo "🚀 Creating development startup script..."
cat > /var/www/html/mono/start-dev.sh << 'EOF'
#!/bin/bash

echo "🚀 Starting KSP Lam Gabe Jaya Development Server..."
echo "=================================================="

# Check if Apache is running
if ! systemctl is-active --quiet apache2; then
    echo "🌐 Starting Apache2..."
    sudo systemctl start apache2
fi

# Check if MariaDB is running
if ! systemctl is-active --quiet mariadb; then
    echo "🗄️ Starting MariaDB..."
    sudo systemctl start mariadb
fi

echo "✅ Services started!"
echo "🌐 Application URL: http://localhost/mono"
echo "📊 Database Browser: http://localhost/mono/database_browser.php"
echo "🗄️ PHPMyAdmin: http://localhost/phpmyadmin"
echo ""
echo "👤 Super Admin Login:"
echo "   Email: admin@lamgabejaya.coop"
echo "   Password: admin123"
echo ""
echo "🔧 Development Commands:"
echo "   php -S localhost:8000  # Start PHP development server"
echo "   mysql -u root -proot   # Connect to database"
echo "   tail -f /var/log/apache2/error.log  # View Apache logs"
EOF

sudo chmod +x /var/www/html/mono/start-dev.sh

# Create development utilities
echo "🛠️ Creating development utilities..."
cat > /var/www/html/mono/dev-tools.sh << 'EOF'
#!/bin/bash

case "$1" in
    "start")
        echo "🚀 Starting development environment..."
        sudo systemctl start apache2 mariadb
        echo "✅ Services started!"
        ;;
    "stop")
        echo "🛑 Stopping development environment..."
        sudo systemctl stop apache2 mariadb
        echo "✅ Services stopped!"
        ;;
    "restart")
        echo "🔄 Restarting development environment..."
        sudo systemctl restart apache2 mariadb
        echo "✅ Services restarted!"
        ;;
    "status")
        echo "📊 Service Status:"
        echo "Apache2: $(systemctl is-active apache2)"
        echo "MariaDB: $(systemctl is-active mariadb)"
        ;;
    "logs")
        echo "📋 Viewing logs..."
        echo "Apache Error Log:"
        sudo tail -f /var/log/apache2/error.log
        ;;
    "db")
        echo "🗄️ Connecting to database..."
        mysql -u root -proot ksp_lamabejaya
        ;;
    "test")
        echo "🧪 Running application tests..."
        cd /var/www/html/mono
        php comprehensive_test.php
        ;;
    *)
        echo "Usage: $0 {start|stop|restart|status|logs|db|test}"
        echo ""
        echo "Commands:"
        echo "  start   - Start development services"
        echo "  stop    - Stop development services"
        echo "  restart - Restart development services"
        echo "  status  - Show service status"
        echo "  logs    - View Apache error logs"
        echo "  db      - Connect to database"
        echo "  test    - Run application tests"
        ;;
esac
EOF

sudo chmod +x /var/www/html/mono/dev-tools.sh

echo ""
echo "✅ Development environment setup complete!"
echo ""
echo "🎯 Next Steps:"
echo "1. Start services: ./start-dev.sh"
echo "2. Or use dev tools: ./dev-tools.sh start"
echo "3. Open browser: http://localhost/mono"
echo "4. Login with: admin@lamgabejaya.coop / admin123"
echo ""
echo "🛠️ Development Commands:"
echo "  ./start-dev.sh      - Start all services"
echo "  ./dev-tools.sh start - Start development environment"
echo "  ./dev-tools.sh status - Check service status"
echo "  ./dev-tools.sh test  - Run application tests"
echo "  ./dev-tools.sh db    - Connect to database"
echo ""
echo "📚 Documentation:"
echo "  README.md - Complete application documentation"
echo "  IMPLEMENTATION_CHECKLIST.md - Project status and checklist"
echo ""
echo "🌟 Happy Development with KSP Lam Gabe Jaya!"
