// PWA Service Worker - DISABLED FOR DEVELOPMENT
// This service worker is disabled during development to prevent caching issues

console.log('Service Worker: DISABLED (Development Mode)');

// Install event - do nothing
self.addEventListener('install', function(event) {
    console.log('Service Worker Install: SKIPPED (Development Mode)');
    self.skipWaiting();
});

// Fetch event - pass through to network
self.addEventListener('fetch', function(event) {
    console.log('Service Worker Fetch: PASSTHROUGH (Development Mode)');
    // Always fetch from network during development
    event.respondWith(fetch(event.request));
});

// Activate event - do nothing
self.addEventListener('activate', function(event) {
    console.log('Service Worker Activate: SKIPPED (Development Mode)');
    event.waitUntil(self.clients.claim());
});
