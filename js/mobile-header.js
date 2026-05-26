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