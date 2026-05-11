self.addEventListener('push', function (event) {
    console.log('Push message received', event);
    if (!(self.Notification && self.Notification.permission === 'granted')) {
        console.warn('Notifications not granted or supported');
        return;
    }

    let data = {};
    try {
        data = event.data?.json() || {};
    } catch (e) {
        console.warn('Push data is not JSON, treating as text');
        data = { body: event.data?.text() || '' };
    }
    
    console.log('Push data processed:', data);
    const title = data.title || 'Pentapure Notification';
    const options = {
        body: data.body || '',
        icon: 'https://pentapurefoods.com/wp-content/uploads/2025/11/logo.png', 
        badge: 'https://pentapurefoods.com/wp-content/uploads/2025/11/logo.png',
        data: {
            url: data.url || '/'
        }
    };

    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

self.addEventListener('message', function (event) {
    if (event.data && event.data.type === 'SIMULATE_PUSH') {
        const title = event.data.title || 'Simulated Notification';
        const options = {
            body: event.data.body || '',
            icon: 'https://pentapurefoods.com/wp-content/uploads/2025/11/logo.png',
            badge: 'https://pentapurefoods.com/wp-content/uploads/2025/11/logo.png',
            data: { url: '/' }
        };
        self.registration.showNotification(title, options);
    }
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();
    const url = event.notification.data.url;

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
            if (clientList.length > 0) {
                let client = clientList[0];
                for (let i = 0; i < clientList.length; i++) {
                    if (clientList[i].focused) {
                        client = clientList[i];
                    }
                }
                return client.focus().then(c => c.navigate(url));
            }
            return clients.openWindow(url);
        })
    );
});
