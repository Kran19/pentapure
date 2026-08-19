@extends('layouts.app')

@section('content')
<div class="container" style="padding: 1rem;">
    <div class="card" style="background: var(--card-bg); border-radius: 15px; padding: 1.5rem; box-shadow: 0 4px 20px rgba(0,0,0,0.2);">
        <h2 style="color: var(--primary-light); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
            Notification Debugger
        </h2>

        <div style="display: grid; gap: 1rem;">
            {{-- Status Section --}}
            <div style="padding: 1rem; background: rgba(255,255,255,0.05); border-radius: 10px;">
                <h3 style="font-size: 1rem; margin-bottom: 0.5rem;">Current Status</h3>
                <div style="display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.9rem;">
                    <div>Permission: <span id="perm-status" style="font-weight: bold;">Checking...</span></div>
                    <div>Service Worker: <span id="sw-status" style="font-weight: bold;">Checking...</span></div>
                    <div>VAPID Key: <code style="background: #000; padding: 0.2rem 0.4rem; border-radius: 4px;">{{ config('webpush.vapid.public_key') }}</code></div>
                </div>
            </div>

            {{-- Actions Section --}}
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <button onclick="testLocalNotification()" class="btn btn-secondary" style="padding: 1rem;">
                    Test Local Notification
                </button>
                <button onclick="requestPermission()" class="btn" style="padding: 1rem;">
                    Request/Refresh Permission
                </button>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                <button onclick="sendServerPush()" class="btn" style="padding: 1rem; background: var(--success); color: white; border: none;">
                    Send Server Push (Real)
                </button>
                <button onclick="simulatePush()" class="btn" style="padding: 1rem; background: var(--info); color: white; border: none;">
                    Simulate Push (Local)
                </button>
                <button onclick="checkSubscriptionOnServer()" class="btn btn-secondary" style="padding: 1rem;">
                    Check Server Record
                </button>
            </div>
        </div>

        <div id="debug-log" style="margin-top: 1.5rem; padding: 1rem; background: #000; border-radius: 10px; color: #0f0; font-family: monospace; font-size: 0.8rem; max-height: 200px; overflow-y: auto;">
            <div>> Debugger initialized...</div>
        </div>
    </div>
</div>

<script>
    function log(msg) {
        const div = document.createElement('div');
        div.innerText = `> ${new Date().toLocaleTimeString()}: ${msg}`;
        const container = document.getElementById('debug-log');
        container.appendChild(div);
        container.scrollTop = container.scrollHeight;
    }

    // 1. Initial Checks
    document.getElementById('perm-status').innerText = Notification.permission;
    document.getElementById('perm-status').style.color = Notification.permission === 'granted' ? 'var(--success)' : 'var(--danger)';

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.getRegistration().then(reg => {
            if (reg) {
                document.getElementById('sw-status').innerText = 'Active (Ready)';
                document.getElementById('sw-status').style.color = 'var(--success)';
                log('Service Worker found and active.');
            } else {
                document.getElementById('sw-status').innerText = 'Missing (Not Registered)';
                document.getElementById('sw-status').style.color = 'var(--danger)';
                log('No Service Worker found!');
            }
        });
    }

    // 2. Test Local Notification
    function testLocalNotification() {
        log('Attempting local notification...');
        if (Notification.permission === 'granted') {
            const n = new Notification('Pentapure Local Test', {
                body: 'This is a local browser notification. If you see this, your browser supports notifications!',
                icon: 'https://pentapurefoods.com/wp-content/uploads/2025/11/logo.png'
            });
            log('Local notification sent to browser API.');
        } else {
            log('FAILED: Permission not granted.');
            Swal.fire('Permission Error', 'You must grant permission first.', 'error');
        }
    }

    // 3. Request/Refresh Permission
    async function requestPermission() {
        log('Requesting permission...');
        const res = await app.requestNotificationPermission();
        if (res) {
            log('Permission granted and subscription saved.');
            location.reload();
        } else {
            log('Permission denied or subscription failed.');
        }
    }

    // 4. Send Server Push
    async function sendServerPush() {
        log('Triggering server-side push notification...');
        try {
            const res = await fetch(window.baseUrl + '/' + window.userSlug + '/notifications/test');
            const text = await res.text();
            log('Server Response: ' + text);
            Swal.fire('Push Triggered', text, 'success');
        } catch (e) {
            log('Error triggering push: ' + e.message);
        }
    }

    // 4b. Simulate Push (Internal)
    async function simulatePush() {
        log('Simulating push event via postMessage...');
        if (!navigator.serviceWorker.controller) {
            log('FAILED: No active service worker controller found.');
            return;
        }
        navigator.serviceWorker.controller.postMessage({
            type: 'SIMULATE_PUSH',
            title: 'Simulated Push',
            body: 'This notification was triggered locally to test the Service Worker.'
        });
        log('Message sent to Service Worker.');
    }

    // 5. Check Server Record
    async function checkSubscriptionOnServer() {
        log('Checking if server has your token...');
        try {
            const res = await fetch(window.baseUrl + '/' + window.userSlug + '/debug-check-sub');
            const data = await res.json();
            if (data.exists) {
                log('SUCCESS: Server has ' + data.count + ' subscription(s) for you.');
                log('Endpoint: ' + data.endpoint.substring(0, 30) + '...');
            } else {
                log('FAILED: Server has NO record of your subscription.');
            }
        } catch (e) {
            log('Error checking record: ' + e.message);
        }
    }
</script>
@endsection
