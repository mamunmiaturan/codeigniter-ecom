$(document).ready(function() {
    var shownNotificationIds = [];
    var isFirstLoad = true;

    function fetchNotifications() {
        if (!window.NotificationConfig || !window.NotificationConfig.isLoggedIn) {
            return;
        }
        $.ajax({
            url: window.NotificationConfig.getUnreadUrl,
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response && typeof response.count !== 'undefined') {
                    // Update badge counts and visibility of "Mark All As Read"
                    if (response.count > 0) {
                        $('#notification-badge-count').text(response.count).show();
                        $('#notification-text-count')
                            .text(response.count + ' New')
                            .removeClass('label-default')
                            .addClass('label-danger');
                        $('#mark-notifications-read-btn').show();
                    } else {
                        $('#notification-badge-count').hide();
                        $('#notification-text-count')
                            .text('0 New')
                            .removeClass('label-danger')
                            .addClass('label-default');
                        $('#mark-notifications-read-btn').hide();
                    }

                    // Generate notification elements
                    var html = '';
                    if (response.notifications && response.notifications.length > 0) {
                        response.notifications.forEach(function(item) {
                            html += '<li>' +
                                        '<a href="' + window.NotificationConfig.baseUrl + '" data-id="' + item.id + '" class="clearfix navbar-notification-item">' +
                                            '<div class="image"><i class="fas fa-info-circle bg-primary" style="background:#5956ea; color:#fff; padding:6px; border-radius:50%;"></i></div>' +
                                            '<div style="padding-left: 45px;">' +
                                                '<span class="title" style="font-weight:600; display:block; font-size:12px; color:#333;">' + item.title + '</span>' +
                                                '<span class="message" style="display:block; font-size:11px; color:#666;">' + item.message + '</span>' +
                                            '</div>' +
                                        '</a>' +
                                    '</li>';

                            // Real-time message popup if not already shown in this session
                            if (shownNotificationIds.indexOf(item.id) === -1) {
                                shownNotificationIds.push(item.id);
                                
                                if (!isFirstLoad) {
                                    // Trigger SweetAlert popup
                                    swal({
                                        toast: true,
                                        position: 'top-end',
                                        type: 'info',
                                        title: item.title,
                                        text: item.message,
                                        showConfirmButton: false,
                                        timer: 500
                                    });

                                    // Play a subtle notification sound using Web Audio API
                                    try {
                                        var audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                                        var oscillator = audioCtx.createOscillator();
                                        var gainNode = audioCtx.createGain();
                                        oscillator.connect(gainNode);
                                        gainNode.connect(audioCtx.destination);
                                        oscillator.type = 'sine';
                                        oscillator.frequency.setValueAtTime(587.33, audioCtx.currentTime); // D5 note
                                        gainNode.gain.setValueAtTime(0.1, audioCtx.currentTime);
                                        oscillator.start();
                                        oscillator.stop(audioCtx.currentTime + 0.15);
                                    } catch (e) {}
                                }
                            }
                        });
                    } else {
                        html = '<li class="no-notifications-placeholder">' +
                                    (window.NotificationConfig.noNewTranslation || 'No new notifications') +
                                '</li>';
                    }
                    isFirstLoad = false;
                    $('#notification-list-items').html(html);
                }
            }
        });
    }

    // Expose fetchNotifications globally so that other scripts can call it
    window.fetchNotifications = fetchNotifications;

    // Poll every 5 seconds
    if (typeof jQuery !== 'undefined') {
        fetchNotifications();
        setInterval(fetchNotifications, 5000);
    }

    // Mark notifications as read click handler
    $(document).on('click', '#mark-notifications-read-btn', function(e) {
        e.preventDefault();
        if (!window.NotificationConfig) return;
        $.ajax({
            url: window.NotificationConfig.markAllReadUrl,
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#notification-badge-count').hide();
                    $('#notification-text-count')
                        .text('0 New')
                        .removeClass('label-danger')
                        .addClass('label-default');
                    $('#notification-list-items').html(
                        '<li class="no-notifications-placeholder">' +
                            (window.NotificationConfig.noNewTranslation || 'No new notifications') +
                        '</li>'
                    );
                    $('#mark-notifications-read-btn').hide();
                }
            }
        });
    });

    // Mark single notification as read and redirect when clicking a navbar notification list item
    $(document).on('click', '.navbar-notification-item', function(e) {
        e.preventDefault();
        if (!window.NotificationConfig) return;
        var $link = $(this);
        var id = $link.data('id');
        var url = $link.attr('href');
        
        $.ajax({
            url: window.NotificationConfig.markSingleReadUrl + id,
            method: 'GET',
            dataType: 'json',
            complete: function() {
                window.location.href = url;
            }
        });
    });
});
