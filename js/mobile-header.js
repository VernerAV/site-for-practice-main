document.addEventListener('DOMContentLoaded', function() {
    const hamburger = document.getElementById('hamburgerBtn');
    const sideMenu = document.getElementById('sideMenu');
    const overlay = document.getElementById('overlay');
    const closeBtn = document.getElementById('closeMenuBtn');

    function openMenu() {
        sideMenu.classList.add('open');
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
        console.log('Меню открыто');
    }

    function closeMenu() {
        sideMenu.classList.remove('open');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
        console.log('Меню закрыто');
    }

    if (hamburger) {
        hamburger.addEventListener('click', openMenu);
    }
    if (closeBtn) {
        closeBtn.addEventListener('click', closeMenu);
    }
    if (overlay) {
        overlay.addEventListener('click', closeMenu);
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sideMenu.classList.contains('open')) {
            closeMenu();
        }
    });

    // ===== СВАЙП =====
    let touchStartX = 0;
    let touchStartY = 0;

    document.addEventListener('touchstart', function(e) {
        touchStartX = e.changedTouches[0].screenX;
        touchStartY = e.changedTouches[0].screenY;
        console.log('touchstart X:', touchStartX);
    }, { passive: true });

    document.addEventListener('touchend', function(e) {
        const deltaX = e.changedTouches[0].screenX - touchStartX;
        const deltaY = e.changedTouches[0].screenY - touchStartY;
        console.log('touchend deltaX:', deltaX, 'deltaY:', deltaY);

        // Проверяем, что движение горизонтальное и достаточно длинное
        if (Math.abs(deltaX) > Math.abs(deltaY) * 1.5 && Math.abs(deltaX) > 30) {
            if (sideMenu.classList.contains('open')) {
                if (deltaX < -30) {
                    closeMenu();
                }
            } else {
                if (touchStartX < 70 && deltaX > 30) {
                    openMenu();
                }
            }
        }
    }, { passive: true });
});