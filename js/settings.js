import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";

document.addEventListener('DOMContentLoaded', () => {
    // Initialize time picker
    flatpickr("#alert_time", {
        enableTime: true,
        noCalendar: true,
        dateFormat: "H:i",
        time_24hr: true
    });

    // Save alert time
    const timeInput = document.getElementById('alert_time');
    timeInput.addEventListener('change', () => {
        fetch(OC.generateUrl('/apps/memories_alerts/settings/save-time'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ time: timeInput.value })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('Alert time saved!', 'success');
            } else {
                showMessage('Error saving alert time: ' + (data.error || 'Unknown error'), 'error');
            }
        })
        .catch(error => showMessage('Network error: ' + error, 'error'));
    });

    // Handle album tick boxes
    document.querySelectorAll('.album-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', () => {
            const albumId = checkbox.getAttribute('data-album-id');
            fetch(OC.generateUrl('/apps/memories_alerts/settings/save-alert'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    albumId: albumId,
                    enabled: checkbox.checked
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('Alert settings saved for album ' + albumId, 'success');
                } else {
                    showMessage('Error saving alert settings: ' + (data.error || 'Unknown error'), 'error');
                }
            })
            .catch(error => showMessage('Network error: ' + error, 'error'));
        });
    });

    // Handle test alert button
    const testAlertButton = document.getElementById('test_alert_button');
    testAlertButton.addEventListener('click', () => {
        testAlertButton.disabled = true;
        testAlertButton.textContent = 'Sending...';
        
        fetch(OC.generateUrl('/apps/memories_alerts/settings/send-test-alert'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        })
        .then(response => response.json())
        .then(data => {
            testAlertButton.disabled = false;
            testAlertButton.textContent = 'Send Test Alert';
            if (data.success) {
                showMessage(data.message, 'success');
            } else {
                showMessage('Error: ' + (data.error || 'Unknown error'), 'error');
            }
        })
        .catch(error => {
            testAlertButton.disabled = false;
            testAlertButton.textContent = 'Send Test Alert';
            showMessage('Network error: ' + error, 'error');
        });
    });

    // Helper function to show messages
    function showMessage(message, type) {
        const msgDiv = document.createElement('div');
        msgDiv.className = `alert-message ${type}`;
        msgDiv.textContent = message;
        document.querySelector('#memories_alerts').prepend(msgDiv);
        setTimeout(() => msgDiv.remove(), 3000);
    }
});