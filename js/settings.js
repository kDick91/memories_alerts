(function (OC, window, $, undefined) {
    'use strict';

    $(document).ready(function () {
        // Save alert time when the input changes
        $('#alert_time').on('change', function () {
            var time = $(this).val();
            $.ajax({
                url: OC.generateUrl('/apps/memories_alerts/settings/save-time'),
                method: 'POST',
                data: { time: time },
                success: function (response) {
                    if (response.success) {
                        OC.Notification.showTemporary('Alert time saved successfully');
                    } else {
                        OC.Notification.showTemporary('Error saving alert time: ' + (response.error || 'Unknown error'));
                    }
                },
                error: function () {
                    OC.Notification.showTemporary('Failed to save alert time');
                }
            });
        });

        // Toggle album alert settings
        $('.album-checkbox').on('change', function () {
            var albumId = $(this).data('album-id');
            var enabled = $(this).is(':checked');
            $.ajax({
                url: OC.generateUrl('/apps/memories_alerts/settings/save-alert'),
                method: 'POST',
                data: {
                    albumId: albumId,
                    enabled: enabled
                },
                success: function (response) {
                    if (response.success) {
                        OC.Notification.showTemporary('Alert settings updated for album ' + albumId);
                    } else {
                        OC.Notification.showTemporary('Error updating alert settings: ' + (response.error || 'Unknown error'));
                    }
                },
                error: function () {
                    OC.Notification.showTemporary('Failed to update alert settings');
                }
            });
        });

        // Send test alert
        $('#test_alert_button').on('click', function () {
            $.ajax({
                url: OC.generateUrl('/apps/memories_alerts/settings/send-test-alert'),
                method: 'POST',
                success: function (response) {
                    if (response.success) {
                        OC.Notification.showTemporary('Test alert sent successfully');
                    } else {
                        OC.Notification.showTemporary('Error sending test alert: ' + (response.error || 'Unknown error'));
                    }
                },
                error: function () {
                    OC.Notification.showTemporary('Failed to send test alert');
                }
            });
        });
    });
})(OC, window, jQuery);