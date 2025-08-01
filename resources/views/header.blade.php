<nav class="navbar navbar-expand navbar-light bg-white shadow-sm px-4" style="min-height:64px;">
    <div class="container-fluid">
        <button class="navbar-toggler d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar" aria-controls="sidebar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="ms-auto d-flex align-items-center gap-3">
            <span class="text-secondary fw-bold">Admin</span>
            
            <!-- Notifikasi Dropdown -->
            <div class="dropdown">
                <button class="btn btn-link position-relative" type="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-bell fs-5"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notificationCount">
                        {{ \App\Services\NotificationService::getUnreadCount() }}
                    </span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown" style="width: 350px; max-height: 400px; overflow-y: auto;">
                    <li><h6 class="dropdown-header">Notifikasi</h6></li>
                    <li><hr class="dropdown-divider"></li>
                    <div id="notificationList">
                        @foreach(\App\Services\NotificationService::getUnread(5) as $notification)
                        <li>
                            <a class="dropdown-item notification-item" href="#" data-id="{{ $notification->id }}">
                                <div class="d-flex align-items-start">
                                    <div class="flex-shrink-0">
                                        @if($notification->type == 'success')
                                            <i class="bi bi-check-circle-fill text-success"></i>
                                        @elseif($notification->type == 'warning')
                                            <i class="bi bi-exclamation-triangle-fill text-warning"></i>
                                        @elseif($notification->type == 'error')
                                            <i class="bi bi-x-circle-fill text-danger"></i>
                                        @else
                                            <i class="bi bi-info-circle-fill text-info"></i>
                                        @endif
                                    </div>
                                    <div class="flex-grow-1 ms-2">
                                        <div class="fw-bold small">{{ $notification->title }}</div>
                                        <div class="small text-muted">{{ $notification->message }}</div>
                                        <div class="small text-muted">{{ $notification->created_at->diffForHumans() }}</div>
                                    </div>
                                </div>
                            </a>
                        </li>
                        @endforeach
                    </div>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-center" href="{{ route('notifications.index') }}">Lihat Semua</a></li>
                    <li><a class="dropdown-item text-center" href="#" id="markAllRead">Tandai Semua Dibaca</a></li>
                </ul>
            </div>
            
            <i class="bi bi-person-circle" style="font-size: 36px; color: #6c757d;"></i>
            <form action="{{ route('logout') }}" method="POST" style="display:inline; margin-left: 8px;">
                @csrf
                <button type="submit" class="btn btn-outline-danger btn-sm" style="vertical-align: middle;">
                    <i class="bi bi-box-arrow-right"></i> {{ __('messages.logout') }}
                </button>
            </form>
        </div>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update notifikasi setiap 30 detik
    setInterval(updateNotifications, 30000);
    
    // Tandai notifikasi sebagai dibaca saat diklik
    document.addEventListener('click', function(e) {
        if (e.target.closest('.notification-item')) {
            e.preventDefault();
            const notificationId = e.target.closest('.notification-item').dataset.id;
            markAsRead(notificationId);
        }
    });
    
    // Tandai semua sebagai dibaca
    document.getElementById('markAllRead').addEventListener('click', function(e) {
        e.preventDefault();
        markAllAsRead();
    });
});

function updateNotifications() {
    fetch('{{ route("notifications.get") }}')
        .then(response => response.json())
        .then(data => {
            const notificationList = document.getElementById('notificationList');
            const notificationCount = document.getElementById('notificationCount');
            
            // Update counter
            notificationCount.textContent = data.unread_count;
            if (data.unread_count == 0) {
                notificationCount.style.display = 'none';
            } else {
                notificationCount.style.display = 'block';
            }
            
            // Update notification list
            notificationList.innerHTML = '';
            data.notifications.forEach(notification => {
                const icon = getNotificationIcon(notification.type);
                const timeAgo = getTimeAgo(notification.created_at);
                
                notificationList.innerHTML += `
                    <li>
                        <a class="dropdown-item notification-item" href="#" data-id="${notification.id}">
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0">
                                    ${icon}
                                </div>
                                <div class="flex-grow-1 ms-2">
                                    <div class="fw-bold small">${notification.title}</div>
                                    <div class="small text-muted">${notification.message}</div>
                                    <div class="small text-muted">${timeAgo}</div>
                                </div>
                            </div>
                        </a>
                    </li>
                `;
            });
        })
        .catch(error => {
            console.error('Error updating notifications:', error);
        });
}

function markAsRead(id) {
    fetch(`{{ url('notifications') }}/${id}/read`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateNotifications();
        }
    })
    .catch(error => {
        console.error('Error marking notification as read:', error);
    });
}

function markAllAsRead() {
    fetch('{{ route("notifications.readAll") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateNotifications();
        }
    })
    .catch(error => {
        console.error('Error marking all notifications as read:', error);
    });
}

function getNotificationIcon(type) {
    switch(type) {
        case 'success':
            return '<i class="bi bi-check-circle-fill text-success"></i>';
        case 'warning':
            return '<i class="bi bi-exclamation-triangle-fill text-warning"></i>';
        case 'error':
            return '<i class="bi bi-x-circle-fill text-danger"></i>';
        default:
            return '<i class="bi bi-info-circle-fill text-info"></i>';
    }
}

function getTimeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);
    
    if (diffInSeconds < 60) return 'Baru saja';
    if (diffInSeconds < 3600) return Math.floor(diffInSeconds / 60) + ' menit yang lalu';
    if (diffInSeconds < 86400) return Math.floor(diffInSeconds / 3600) + ' jam yang lalu';
    return Math.floor(diffInSeconds / 86400) + ' hari yang lalu';
}
</script>
