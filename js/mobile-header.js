document.addEventListener('DOMContentLoaded', function() {
    const hamburger = document.getElementById('hamburgerBtn');
    const sideMenu = document.getElementById('sideMenu');
    const overlay = document.getElementById('overlay');
    const closeBtn = document.getElementById('closeMenuBtn');

    if (hamburger && sideMenu && overlay) {
        function openMenu() {
            sideMenu.classList.add('open');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        function closeMenu() {
            sideMenu.classList.remove('open');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
        hamburger.addEventListener('click', openMenu);
        if (closeBtn) closeBtn.addEventListener('click', closeMenu);
        overlay.addEventListener('click', closeMenu);
    }
});

// ===== Обработка свайпов для бокового меню =====
(function() {
    const sideMenu = document.getElementById('sideMenu');
    const overlay = document.getElementById('overlay');
    let startX = 0;
    let currentX = 0;
    let isDragging = false;
    let menuOpen = false;

    // Проверяем, открыто ли меню (по классу)
    function isMenuOpen() {
        return sideMenu.classList.contains('open');
    }

    // Открыть меню
    function openMenu() {
        if (!isMenuOpen()) {
            sideMenu.classList.add('open');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
            menuOpen = true;
        }
    }

    // Закрыть меню
    function closeMenu() {
        if (isMenuOpen()) {
            sideMenu.classList.remove('open');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
            menuOpen = false;
            // Сброс позиции при закрытии
            sideMenu.style.transform = '';
        }
    }

    // Начало касания
    document.addEventListener('touchstart', function(e) {
        const touch = e.touches[0];
        startX = touch.clientX;
        currentX = startX;
        isDragging = true;

        // Если меню открыто и касание не внутри меню – отслеживаем для закрытия
        if (isMenuOpen()) {
            const menuRect = sideMenu.getBoundingClientRect();
            // Если касание вне меню (справа от него) – помечаем, что можем закрыть
            if (touch.clientX > menuRect.right) {
                // ничего не делаем, просто запоминаем
            }
        }
    }, { passive: true });

    // Перемещение пальца
    document.addEventListener('touchmove', function(e) {
        if (!isDragging) return;
        const touch = e.touches[0];
        currentX = touch.clientX;
        const deltaX = currentX - startX;

        // Если меню закрыто и свайп вправо более чем на 50px – открываем
        if (!isMenuOpen() && deltaX > 50 && startX < 50) { // свайп от левого края
            openMenu();
            // После открытия сбрасываем начальную точку, чтобы не дергалось
            startX = currentX;
            return;
        }

        // Если меню открыто
        if (isMenuOpen()) {
            // Если свайп влево (deltaX < 0) – закрываем
            if (deltaX < -50) {
                closeMenu();
                isDragging = false;
                return;
            }

            // Дополнительно: если касание началось на оверлее или справа от меню,
            // и тянем влево – закрываем
            const menuRect = sideMenu.getBoundingClientRect();
            if (startX > menuRect.right && deltaX < -30) {
                closeMenu();
                isDragging = false;
                return;
            }

            // Плавное следование за пальцем (если хотите)
            // Можно ограничить движение, чтобы меню не уходило слишком далеко
            if (deltaX < 0) {
                const offset = Math.max(deltaX, -menuRect.width); // не больше ширины меню
                sideMenu.style.transform = `translateX(${offset}px)`;
                // Затемнение оверлея тоже можно менять пропорционально
                const opacity = 1 + (offset / menuRect.width);
                overlay.style.opacity = Math.min(1, Math.max(0, opacity));
            }
        }
    }, { passive: true });

    // Завершение касания
    document.addEventListener('touchend', function(e) {
        if (!isDragging) return;
        isDragging = false;

        // Если меню открыто и мы его двигали, решаем оставить открытым или закрыть
        if (isMenuOpen()) {
            const menuRect = sideMenu.getBoundingClientRect();
            const currentOffset = parseInt(sideMenu.style.transform.replace('translateX(', '')) || 0;
            // Если сдвинули более чем на 30% ширины влево – закрываем
            if (currentOffset < -menuRect.width * 0.3) {
                closeMenu();
            } else {
                // Иначе возвращаем на место
                sideMenu.style.transform = '';
                overlay.style.opacity = '';
            }
        }

        // Сброс
        startX = 0;
        currentX = 0;
    }, { passive: true });

    // Закрытие по клику на оверлей (уже есть, но продублируем)
    if (overlay) {
        overlay.addEventListener('click', closeMenu);
    }

    // Также закрываем по Escape (можно добавить)
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && isMenuOpen()) {
            closeMenu();
        }
    });

    // Открытие через кнопку-гамбургер (существующее, оставляем)
    const hamburger = document.getElementById('hamburgerBtn');
    if (hamburger) {
        hamburger.addEventListener('click', function() {
            if (isMenuOpen()) {
                closeMenu();
            } else {
                openMenu();
            }
        });
    }

    // Закрытие по клику на ссылки внутри меню (опционально)
    sideMenu.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', closeMenu);
    });

    // Экспортируем функции, если нужно
    window.openMenu = openMenu;
    window.closeMenu = closeMenu;
})();