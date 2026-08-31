// news-slider.js – кликабельные карточки + свайп на мобильных
document.addEventListener('DOMContentLoaded', function() {
    console.log('Слайдер запущен');

    let currentIndex = 0;
    let newsData = [];
    let autoSlideInterval;
    let isAnimating = false;

    // Переменные для свайпа
    let startX = 0;
    let currentX = 0;
    let isDragging = false;

    const track = document.getElementById('newsSliderTrack');
    if (!track) {
        console.error('Слайдер не найден!');
        return;
    }

    // Загружаем новости
    loadNews();

    async function loadNews() {
        try {
            const response = await fetch('includes/get_news_home.php');
            const data = await response.json();

            if (!data || data.length === 0) {
                track.innerHTML = '<div class="no-news">Новостей пока нет</div>';
                return;
            }

            newsData = data;
            createSlider();

            // Автопрокрутка только на ПК
            if (!isMobile()) {
                startAutoSlide();
                setupAutoSlideControls();
            }
        } catch (error) {
            console.error('Ошибка загрузки новостей:', error);
            track.innerHTML = '<div class="error">Ошибка загрузки новостей</div>';
        }
    }

    function isMobile() {
        return window.innerWidth <= 768;
    }

    function getSlideParams() {
        const width = window.innerWidth;
        if (width <= 480) {
            return { slideWidth: 260, gap: 20, visibleSlides: 1 };
        } else if (width <= 768) {
            return { slideWidth: 300, gap: 20, visibleSlides: 1.2 };
        } else if (width <= 992) {
            return { slideWidth: 380, gap: 40, visibleSlides: 2.5 };
        } else {
            return { slideWidth: 380, gap: 40, visibleSlides: 3.5 };
        }
    }

    function createSlider() {
        if (newsData.length === 0) return;

        const params = getSlideParams();
        const isMobileDevice = isMobile();

        let slidesHTML = '';

        if (isMobileDevice) {
            // Мобильная версия: без клонирования
            newsData.forEach((item, index) => {
                slidesHTML += createSlideHTML(item, index);
            });
            track.innerHTML = slidesHTML;

            const slides = document.querySelectorAll('.news-slide');
            slides.forEach(slide => {
                slide.style.flex = `0 0 ${params.slideWidth}px`;
                // Добавляем клик по всей карточке
                slide.addEventListener('click', function(e) {
                    // Игнорируем клик по ссылке внутри (чтобы не было двойного перехода)
                    if (e.target.closest('.news-slide-link')) return;
                    const id = this.dataset.id;
                    if (id) window.location.href = 'news_details.php?id=' + id;
                });
            });

            currentIndex = 0;
            track.style.transform = 'translateX(0px)';

            // Добавляем обработчики свайпа для мобильных
            initSwipe();

        } else {
            // ПК версия: бесконечный слайдер с клонированием
            const visibleSlides = params.visibleSlides;
            const cloneCount = Math.ceil(visibleSlides / 2);
            const clonedSlides = [
                ...newsData.slice(-cloneCount),
                ...newsData,
                ...newsData.slice(0, cloneCount)
            ];

            clonedSlides.forEach((item, index) => {
                slidesHTML += createSlideHTML(item, index);
            });

            track.innerHTML = slidesHTML;

            // Добавляем клик на все слайды (кроме ссылки внутри)
            document.querySelectorAll('.news-slide').forEach(slide => {
                slide.addEventListener('click', function(e) {
                    if (e.target.closest('.news-slide-link')) return;
                    const id = this.dataset.id;
                    if (id) window.location.href = 'news_details.php?id=' + id;
                });
            });

            const fullWidth = params.slideWidth + params.gap;
            currentIndex = cloneCount * fullWidth;
            track.style.transform = `translateX(-${currentIndex}px)`;
        }

        // Дополнительно: если слайдов меньше, чем видимых, центрируем
        updateSlideWidths();
    }

    function createSlideHTML(item, index) {
        const defaultImage = 'images/default-news.jpg';
        const imageSrc = item.image ? 'uploads/news/' + item.image : defaultImage;
        const date = new Date(item.created_at).toLocaleDateString('ru-RU', {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });

        let shortDescription = item.description;
        if (shortDescription.length > 120) {
            shortDescription = shortDescription.substring(0, 120) + '...';
        }

        return `
        <div class="news-slide" data-id="${item.id}" data-index="${index}">
            <div class="news-slide-image">
                <img src="${imageSrc}" alt="${item.title}" 
                     onerror="this.src='${defaultImage}'; this.onerror=null;">
            </div>
            <div class="news-slide-content">
                <div class="news-slide-date">${date}</div>
                <h3 class="news-slide-title">${escapeHtml(item.title)}</h3>
                <p class="news-slide-text">${escapeHtml(shortDescription)}</p>
                <a href="news_details.php?id=${item.id}" class="news-slide-link">
                    Читать подробнее
                </a>
            </div>
        </div>`;
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }

    // Обновление ширины слайдов при изменении окна
    function updateSlideWidths() {
        const params = getSlideParams();
        document.querySelectorAll('.news-slide').forEach(slide => {
            slide.style.flex = `0 0 ${params.slideWidth}px`;
        });
    }

    // ---------- СВАЙП (только для мобильных) ----------
    function initSwipe() {
        const wrapper = document.querySelector('.news-slider-wrapper');
        if (!wrapper) return;

        wrapper.addEventListener('touchstart', handleTouchStart, { passive: true });
        wrapper.addEventListener('touchmove', handleTouchMove, { passive: false });
        wrapper.addEventListener('touchend', handleTouchEnd, { passive: true });
    }

    function handleTouchStart(e) {
        if (isAnimating) return;
        const touch = e.touches[0];
        startX = touch.clientX;
        currentX = startX;
        isDragging = true;
        // Останавливаем автопрокрутку на время свайпа
        clearInterval(autoSlideInterval);
    }

    function handleTouchMove(e) {
        if (!isDragging || isAnimating) return;
        const touch = e.touches[0];
        currentX = touch.clientX;
        const diff = startX - currentX;
        // Показываем смещение (можно добавить визуальный отклик)
        const params = getSlideParams();
        const fullWidth = params.slideWidth + params.gap;
        const offset = currentIndex * fullWidth + diff;
        track.style.transition = 'none';
        track.style.transform = `translateX(-${offset}px)`;
        e.preventDefault(); // предотвращаем вертикальный скролл при горизонтальном свайпе
    }

    function handleTouchEnd(e) {
        if (!isDragging) return;
        isDragging = false;
        const diff = startX - currentX;
        const threshold = 50; // минимальное расстояние для смены слайда

        if (Math.abs(diff) > threshold) {
            const direction = diff > 0 ? 1 : -1;
            slideNews(direction);
        } else {
            // Возвращаем на место
            const params = getSlideParams();
            const fullWidth = params.slideWidth + params.gap;
            const offset = currentIndex * fullWidth;
            track.style.transition = 'transform 0.3s ease';
            track.style.transform = `translateX(-${offset}px)`;
        }

        // Возобновляем автопрокрутку, если не мобильное устройство
        if (!isMobile()) {
            startAutoSlide();
        }
    }

    // ---------- НАВИГАЦИЯ (кнопки и свайп) ----------
    function slideNews(direction) {
        if (isAnimating || newsData.length === 0) return;

        isAnimating = true;
        const params = getSlideParams();
        const fullWidth = params.slideWidth + params.gap;

        if (isMobile()) {
            // Мобильная версия: простое листание с ограничением
            const slides = document.querySelectorAll('.news-slide');
            const maxIndex = slides.length - 1;
            let newIndex = currentIndex + direction;

            if (newIndex < 0) newIndex = 0;
            if (newIndex > maxIndex) newIndex = maxIndex;

            if (newIndex === currentIndex) {
                isAnimating = false;
                return;
            }

            currentIndex = newIndex;
            const offset = currentIndex * fullWidth;
            track.style.transition = 'transform 0.4s ease';
            track.style.transform = `translateX(-${offset}px)`;

            setTimeout(() => {
                isAnimating = false;
            }, 400);
        } else {
            // ПК: бесконечный слайдер
            const totalSlides = newsData.length;
            const visibleSlides = params.visibleSlides;
            const cloneCount = Math.ceil(visibleSlides / 2);
            const minIndex = cloneCount * fullWidth;
            const maxIndex = (cloneCount + totalSlides) * fullWidth - fullWidth;

            currentIndex += direction * fullWidth;

            track.style.transition = 'transform 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
            track.style.transform = `translateX(-${currentIndex}px)`;

            setTimeout(() => {
                // Проверка на выход за границы
                if (currentIndex >= maxIndex) {
                    currentIndex = minIndex;
                    track.style.transition = 'none';
                    track.style.transform = `translateX(-${currentIndex}px)`;
                    void track.offsetWidth;
                    track.style.transition = 'transform 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                }
                if (currentIndex < minIndex) {
                    currentIndex = maxIndex - fullWidth;
                    track.style.transition = 'none';
                    track.style.transform = `translateX(-${currentIndex}px)`;
                    void track.offsetWidth;
                    track.style.transition = 'transform 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                }
                isAnimating = false;
            }, 600);
        }
    }

    // ---------- АВТОПРОКРУТКА ----------
    function startAutoSlide() {
        if (isMobile()) return;
        clearInterval(autoSlideInterval);
        autoSlideInterval = setInterval(() => {
            slideNews(1);
        }, 4000);
    }

    function setupAutoSlideControls() {
        if (isMobile()) return;
        const wrapper = document.querySelector('.news-slider-wrapper');
        wrapper.addEventListener('mouseenter', () => clearInterval(autoSlideInterval));
        wrapper.addEventListener('mouseleave', () => startAutoSlide());
    }

    // ---------- ПЕРЕСЧЁТ ПРИ ИЗМЕНЕНИИ РАЗМЕРА ----------
    let resizeTimeout;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            clearInterval(autoSlideInterval);
            // Пересоздаём слайдер
            createSlider();
            if (!isMobile()) {
                startAutoSlide();
                setupAutoSlideControls();
            }
            if (isMobile()) {
                currentIndex = 0;
            }
        }, 300);
    });

    // Делаем функцию глобальной для кнопок
    window.slideNews = slideNews;
});