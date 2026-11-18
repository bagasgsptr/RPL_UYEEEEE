// assets/js/app.js

document.addEventListener('DOMContentLoaded', () => {
    // Initialize Lucide icons
    if (window.lucide) {
        lucide.createIcons();
    }

    // Notification dropdown toggle
    const notifBtn = document.getElementById('notificationBtn');
    const notifDropdown = document.getElementById('notificationDropdown');
    const notifBackdrop = document.getElementById('notificationBackdrop');

    if (notifBtn && notifDropdown && notifBackdrop) {
        notifBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const isHidden = notifDropdown.classList.contains('hidden');
            if (isHidden) {
                notifDropdown.classList.remove('hidden');
                notifDropdown.classList.add('animate-slide-down');
                notifBackdrop.classList.remove('hidden');
            } else {
                notifDropdown.classList.add('hidden');
                notifBackdrop.classList.add('hidden');
            }
        });

        notifBackdrop.addEventListener('click', () => {
            notifDropdown.classList.add('hidden');
            notifBackdrop.classList.add('hidden');
        });
    }

    // Password visibility toggle
    const togglePassBtn = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('passwordInput');
    if (togglePassBtn && passwordInput) {
        togglePassBtn.addEventListener('click', () => {
            const isPass = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPass ? 'text' : 'password');
            const icon = togglePassBtn.querySelector('i');
            if (icon) {
                icon.setAttribute('data-lucide', isPass ? 'eye' : 'eye-off');
                lucide.createIcons();
            }
        });
    }
});

// Global flash alert dismissal with smooth animation
function dismissFlashAlert(id) {
    const el = document.getElementById(id);
    if (el) {
        el.style.opacity = '0';
        el.style.transform = 'translateY(-8px)';
        setTimeout(() => el.remove(), 400);
    }
}

// Mark all notifications read
function markAllNotificationsRead() {
    fetch('api/mark_notifications.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Instant visual removal of unread indicators
            const badge = document.querySelector('#notificationDropdown .bg-blue-50');
            if (badge) badge.remove();

            const markBtn = document.querySelector('#notificationDropdown button[onclick="markAllNotificationsRead()"]');
            if (markBtn) markBtn.remove();

            const bellDots = document.querySelectorAll('#notificationBtn span');
            bellDots.forEach(dot => dot.remove());

            const notifDots = document.querySelectorAll('#notificationDropdown span.bg-blue-600');
            notifDots.forEach(dot => dot.remove());

            const bgHighlights = document.querySelectorAll('#notificationDropdown a.bg-blue-50\\/20');
            bgHighlights.forEach(el => el.classList.remove('bg-blue-50/20'));

            setTimeout(() => {
                window.location.reload();
            }, 250);
        }
    })
    .catch(err => console.error(err));
}

// Category filter for Peminjaman page
function filterCategory(categorySlug, element) {
    const chips = document.querySelectorAll('.category-chip');
    chips.forEach(chip => {
        chip.classList.remove('bg-blue-600', 'text-white', 'shadow-sm');
        chip.classList.add('bg-white', 'text-slate-600', 'border', 'border-slate-200');
    });

    if (element) {
        element.classList.remove('bg-white', 'text-slate-600', 'border', 'border-slate-200');
        element.classList.add('bg-blue-600', 'text-white', 'shadow-sm');
    }

    const items = document.querySelectorAll('.barang-item');
    items.forEach(item => {
        const itemCat = item.getAttribute('data-category');
        if (categorySlug === 'semua' || itemCat === categorySlug) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });
}

// Search bar filter for Peminjaman page
function searchBarang(query) {
    const q = query.toLowerCase().trim();
    const items = document.querySelectorAll('.barang-item');
    let visibleCount = 0;

    items.forEach(item => {
        const name = (item.getAttribute('data-name') || '').toLowerCase();
        const code = (item.getAttribute('data-code') || '').toLowerCase();
        if (name.includes(q) || code.includes(q)) {
            item.style.display = 'flex';
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    });

    const emptyState = document.getElementById('emptySearchState');
    if (emptyState) {
        emptyState.style.display = (visibleCount === 0) ? 'block' : 'none';
    }
}
