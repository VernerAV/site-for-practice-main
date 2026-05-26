// news-slider.js - единая версия для ПК и мобильных
document.addEventListener('DOMContentLoaded', function() {
    console.log('Слайдер запущен');
    
    let currentIndex = 0;
    let newsData = [];
    let autoSlideInterval;
    let isAnimating = false;
    
    const track = document.getElementById('newsSliderTrack');
    if (!track) {
        console.error('Слайдер не найден!');
        return;
    }
    
    // Загружаем новости
    loadNews();
    
    // Функция загрузки новостей
    async function loadNews() {
        try {
            const response = await fetch('includes/get_news_home.php');
            const data = await response.json();
            
            if (!data || data.length === 0) {
                track.innerHTML = '<div class="no-news">Новостей пока нет</div>';
                return;
            }
            
            newsData = data;
            
            // Создаем слайдер (без бесконечности на мобильных)
            createSlider();
            
            // Запускаем автопрокрутку только на ПК
            if (!isMobile()) {
                startAutoSlide();
                setupAutoSlideControls();
            }
            
        } catch (error) {
            console.error('Ошибка загрузки новостей:', error);
            track.innerHTML = '<div class="error">Ошибка загрузки новостей</div>';
        }
    }
    
    // Проверка на мобильное устройство
    function isMobile() {
        return window.innerWidth <= 768;
    }
    
    // Получение параметров слайда в зависимости от экрана
    function getSlideParams() {
        const width = window.innerWidth;
        if (width <= 480) {
            return { slideWidth: 240, gap: 20, visibleSlides: 1 };
        } else if (width <= 768) {
            return { slideWidth: 260, gap: 20, visibleSlides: 1 };
        } else if (width <= 992) {
            return { slideWidth: 380, gap: 40, visibleSlides: 2.5 };
        } else {
            return { slideWidth: 380, gap: 40, visibleSlides: 3.5 };
        }
    }
    
    // Создание слайдера (с бесконечностью только на ПК)
    function createSlider() {
        if (newsData.length === 0) return;
        
        const params = getSlideParams();
        const isMobileDevice = isMobile();
        
        let slidesHTML = '';
        
        if (isMobileDevice) {
            // На мобильных: без клонирования, простой список
            newsData.forEach((item, index) => {
                slidesHTML += createSlideHTML(item, index);
            });
            track.innerHTML = slidesHTML;
            
            // Устанавливаем ширину слайдам
            const slides = document.querySelectorAll('.news-slide');
            slides.forEach(slide => {
                slide.style.flex = `0 0 ${params.slideWidth}px`;
            });
            
            currentIndex = 0;
            track.style.transform = 'translateX(0px)';
        } else {
            // На ПК: бесконечный слайдер с клонированием
            const visibleSlides = params.visibleSlides;
            const clonedSlides = [
                ...newsData.slice(-Math.ceil(visibleSlides / 2)),
                ...newsData,
                ...newsData.slice(0, Math.ceil(visibleSlides / 2))
            ];
            
            clonedSlides.forEach((item, index) => {
                slidesHTML += createSlideHTML(item, index);
            });
            
            track.innerHTML = slidesHTML;
            
            const fullWidth = params.slideWidth + params.gap;
            currentIndex = Math.ceil(visibleSlides / 2) * fullWidth;
            track.style.transform = `translateX(-${currentIndex}px)`;
        }
    }
    
    // Создание HTML для одного слайда
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
        <div class="news-slide" data-index="${index}">
            <div class="news-slide-image">
                <img src="${imageSrc}" alt="${item.title}" 
                     onerror="this.src='${defaultImage}'; this.onerror=null;">
            </div>
            <div class="news-slide-content">
                <div class="news-slide-date">${date}</div>
                <h3 class="news-slide-title">${escapeHtml(item.title)}</h3>
                <p class="news-slide-text">${escapeHtml(shortDescription)}</p>
                <a href="news-details.php?id=${item.id}" class="news-slide-link">
                    Читать подробнее
                </a>
            </div>
        </div>`;
    }
    
    // Защита от XSS
    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }
    
    // Перемещение слайдера
    function slideNews(direction) {
        if (isAnimating || newsData.length === 0) return;
        
        isAnimating = true;
        const params = getSlideParams();
        const fullWidth = params.slideWidth + params.gap;
        
        if (isMobile()) {
            // Мобильная версия: простое листание
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
            // ПК версия: бесконечный слайдер
            const totalSlides = newsData.length;
            const visibleSlides = params.visibleSlides;
            
            currentIndex += direction * fullWidth;
            
            track.style.transition = 'transform 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
            track.style.transform = `translateX(-${currentIndex}px)`;
            
            const maxIndex = (totalSlides + Math.ceil(visibleSlides / 2)) * fullWidth;
            const minIndex = Math.ceil(visibleSlides / 2) * fullWidth;
            
            setTimeout(() => {
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
    
    // Автопрокрутка (только для ПК)
    function startAutoSlide() {
        if (isMobile()) return;
        clearInterval(autoSlideInterval);
        autoSlideInterval = setInterval(() => {
            slideNews(1);
        }, 4000);
    }
    
    // Управление автопрокруткой (только для ПК)
    function setupAutoSlideControls() {
        if (isMobile()) return;
        
        track.addEventListener('mouseenter', () => {
            clearInterval(autoSlideInterval);
        });
        
        track.addEventListener('mouseleave', () => {
            startAutoSlide();
        });
        
        track.addEventListener('focusin', () => {
            clearInterval(autoSlideInterval);
        });
        
        track.addEventListener('focusout', () => {
            startAutoSlide();
        });
    }
    
    // Обновление при изменении размера окна
    let resizeTimeout;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            // Останавливаем автопрокрутку
            clearInterval(autoSlideInterval);
            
            // Пересоздаем слайдер
            createSlider();
            
            // Если не мобильное - перезапускаем автопрокрутку
            if (!isMobile()) {
                startAutoSlide();
                setupAutoSlideControls();
            }
            
            // Обновляем переменную currentIndex для мобильных
            if (isMobile()) {
                currentIndex = 0;
            }
        }, 200);
    });
    
    // Делаем функцию глобальной для кнопок
    window.slideNews = slideNews;
});