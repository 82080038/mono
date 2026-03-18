// Test script untuk memverifikasi navigation rendering
const fs = require('fs');

// Baca navigation config
const navigationConfig = fs.readFileSync('/var/www/html/mono/navigation_config.js', 'utf8');

// Check if renderTopNavigation function exists
if (navigationConfig.includes('function renderTopNavigation')) {
    console.log('✅ renderTopNavigation function found');
} else {
    console.log('❌ renderTopNavigation function Tidak Ditemukan');
}

// Check if navigation config has proper structure
if (navigationConfig.includes('STANDARD_NAVIGATION')) {
    console.log('✅ STANDARD_NAVIGATION found');
} else {
    console.log('❌ STANDARD_NAVIGATION Tidak Ditemukan');
}

// Check collector role navigation
if (navigationConfig.includes('Penagih:')) {
    console.log('✅ Penagih navigation found');
} else {
    console.log('❌ Penagih navigation Tidak Ditemukan');
}

console.log('✅ Navigation config validation Selesai');
