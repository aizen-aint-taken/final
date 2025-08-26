fetch('getSession.php')
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return response.text();
    })
    .then(text => {
        console.log('Raw Response:', text);
        try {
            return JSON.parse(text);
        } catch (error) {
            throw new Error('Invalid JSON: ' + text);
        }
    })
    .then(data => console.log('Session Data:', data))
    .catch(error => console.error('Error fetching session data:', error));

// client mqtt
// wss://broker.hivemq.com:8884/mqtt
// wss://broker.emqx.io:8084/mqtt
const client = mqtt.connect('wss://broker.emqx.io:8084/mqtt', {
    reconnectPeriod: 5000,
    clean: true,
    clientId: 'libraryAdmin_' + Math.random().toString(16).substr(2, 8),
    username: '',
    password: '',
})
let notificationCount = 0;

client.on('connect', () => {
    console.log('Connected to MQTT broker');
    client.subscribe('library/admin/notifications', {
        qos: 1
    }, (err) => {
        if (!err) {
            console.log('Subscribed to library/admin/notifications');
        } else {
            console.error('Subscription error:', err);
        }
    });
});

client.on('message', (topic, message) => {
    if (topic === 'library/admin/notifications') {
        try {
            const data = JSON.parse(message.toString());
            notificationCount++;
            document.getElementById('notification-count').textContent = notificationCount;

            const notification = `
            <div class="notification-item">
                <strong>New Reservation:</strong><br> Book Reserved: <strong> ${data.title}</strong> by ${data.author}<br>
                <small>Reserved by: ${data.name}</small>
            </div>`;
            document.getElementById('notifications').insertAdjacentHTML('afterbegin', notification);
        } catch (e) {
            console.error('Error parsing message:', e);
        }
    }
});

client.on('error', (err) => {
    console.error('MQTT error:', err);
});

client.on('close', () => {
    console.log('MQTT connection closed');
});

client.on('offline', () => {
    console.log('MQTT client is offline');
});

client.on('reconnect', () => {
    console.log('Reconnecting to MQTT broker...');
});

function toggleNotifications() {
    const dropdown = document.getElementById('notification-dropdown');
    dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    if (dropdown.style.display === 'block') {
        notificationCount = 0;
        document.getElementById('notification-count').textContent = notificationCount;
    }
}

document.addEventListener('click', (event) => {
    const dropdown = document.getElementById('notification-dropdown');
    const bell = document.querySelector('.notification-bell');
    if (!bell.contains(event.target) && !dropdown.contains(event.target)) {
        dropdown.style.display = 'none';
    }
});

window.addEventListener('beforeunload', () => {
    client.end();
}); 