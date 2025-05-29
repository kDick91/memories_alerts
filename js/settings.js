import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";

document.addEventListener('DOMContentLoaded', () => {
    flatpickr("#alert_time", {
        enableTime: true,
        noCalendar: true,
        dateFormat: "H:i",
        time_24hr: true
    });

    const timeInput = document.getElementById('alert_time');
    timeInput.addEventListener('change', () => {
        fetch(OC.generateUrl('/apps/memories_alerts/settings/save-time'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ time: timeInput.value })
        });
    });

    document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
        checkbox.addEventListener('change', () => {
            const [_, albumId, userId] = checkbox.id.split('_');
            fetch(OC.generateUrl('/apps/memories_alerts/settings/save-alert'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    albumId: albumId,
                    userId: userId,
                    enabled: checkbox.checked
                })
            });
        });
    });
});