





// ================= NOTIFICATIONS MODULE =================

(function () {
    function safeInit() {
        if (typeof $ === 'undefined') {  // FIXED: 'undefined' not ''
            console.error("jQuery is not loaded");
            return;
        }
        initNotifications();
    }

    $(document).ready(function () {
        try {
            safeInit();
        } catch (err) {
            console.error("Notification init failed:", err);
        }
    });
})();


// ================= MAIN INIT =================

function initNotifications() {

    if (!$('#notifBell').length || !$('#notifDropdown').length) {
        console.warn("Notification elements not found on this page");
        return;
    }

    // toggle dropdown
    $('#notifBell')
        .off('click')
        .on('click', function (e) {
            e.preventDefault();
            $('#notifDropdown').toggle();
            markAllAsRead();
        });

    // prevent closing when clicking inside dropdown
    $('#notifDropdown')
        .off('click')
        .on('click', function (e) {
            e.stopPropagation();
        });

    // close when clicking outside
    $(document)
        .off('click.notif')
        .on('click.notif', function (e) {
            if (!$(e.target).closest('#notifBell, #notifDropdown').length) {
                $('#notifDropdown').hide();
            }
        });

    // click single notification
    $(document)
        .off('click.notifItem')
        .on('click.notifItem', '.notif-item', function () {
            let id = $(this).data('id');
            markAsRead(id);
        });

    // first load
    loadNotifications();

    // auto refresh every 5 sec
    setInterval(loadNotifications, 5000);
}


// ================= LOAD =================

function loadNotifications() {

    $.ajax({
        url: '../../backend/php/notifications.php?action=get_notifications',
        method: 'GET',
        dataType: 'json',
        success: function (res) {

            if (!res || res.status !== 'success') return;

            let count = 0;
            let html = '';

            if (!Array.isArray(res.data) || res.data.length === 0) {
                html = `<p style="padding:10px; text-align:center; color:black;">No notifications</p>`;
            } else {

                res.data.forEach(n => {

                    const type = (n.type || '').toLowerCase();
                    const message = n.message || 'No message';
                    const createdAt = n.created_at || '';

                    let bg = '';

                    if (type === 'due_bill') {
                        bg = 'background:#ffe5e5; border-left:4px solid #e74c3c;';
                    }
                    else if (type === 'reminder') {
                        bg = 'background:#fff8d6; border-left:4px solid #f1c40f;';
                    }
                    else if (n.is_read == 0) {
                        bg = 'background:#f0f9ff;';
                    }

                    if (n.is_read == 0) count++;

                    html += `
                        <div class="notif-item" data-id="${n.id}"
                            style="padding:10px; border-bottom:1px solid #eee; cursor:pointer; ${bg}">
                            <p style="margin:0; color:black;">${message}</p>
                            <small style="color:gray;">${createdAt}</small>
                        </div>
                    `;
                });
            }

            $('#notifDropdown').html(html);

            // badge
            if (count > 0) {
                $('#notifCount').text(count).show();
            } else {
                $('#notifCount').hide();
            }
        },
        error: function (err) {
            console.error("Notification load error:", err);
        }
    });
}


// ================= ACTIONS =================

function markAsRead(id) {

    if (!id) return;

    $.ajax({
        url: '../../backend/php/notifications.php',
        method: 'POST',
        dataType: 'json',
        data: {
            action: 'mark_read',
            id: id
        },
        success: function () {
            loadNotifications();
        }
    });
}

function markAllAsRead() {

    $.ajax({
        url: '../../backend/php/notifications.php',
        method: 'POST',
        dataType: 'json',
        data: {
            action: 'mark_all_read'
        },
        success: function () {
            loadNotifications();
        }
    });
}

// LOG OUT NOTIFICATIONS MODULE
$(document)
    .off('click.logout')
    .on('click.logout', 'a[href$="auth.php?action=logout"]', function(e) {
        e.preventDefault();

        const logoutUrl = $(this).attr('href');

        if (confirm("Are you sure you want to logout?")) {
            window.location.href = logoutUrl;
        }
    });