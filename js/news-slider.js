// news-slider.js
document.addEventListener('DOMContentLoaded', function() {
    console.log('Бесконечный слайдер запущен');
    
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
            
            // Создаем бесконечный слайдер
            createInfiniteSlider();
            
            // Запускаем автопрокрутку
            startAutoSlide();
            
            // Обработчики для автопрокрутки
            setupAutoSlideControls();
            
        } catch (error) {
            console.error('Ошибка загрузки новостей:', error);
            track.innerHTML = '<div class="error">Ошибка загрузки новостей</div>';
        }
    }
    
    // Создание бесконечного слайдера
    function createInfiniteSlider() {
        if (newsData.length === 0) return;
        
        // Клонируем слайды для бесконечности
        const slidesCount = newsData.length;
        const visibleSlides = getVisibleSlidesCount();
        
        // Создаем массив: [копия последних] + [все слайды] + [копия первых]
        const clonedSlides = [
            ...newsData.slice(-Math.ceil(visibleSlides / 2)), // Последние слайды
            ...newsData,                                     // Все слайды
            ...newsData.slice(0, Math.ceil(visibleSlides / 2)) // Первые слайды
        ];
        
        // Генерируем HTML
        let slidesHTML = '';
        clonedSlides.forEach((item, index) => {
            slidesHTML += createSlideHTML(item, index);
        });
        
        track.innerHTML = slidesHTML;
        
        // Устанавливаем начальную позицию
        currentIndex = Math.ceil(visibleSlides / 2) * (380 + 40); // ширина слайда + gap
        track.style.transform = `translateX(-${currentIndex}px)`;
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
        
        // Обрезаем описание
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
                <h3 class="news-slide-title">${item.title}</h3>
                <p class="news-slide-text">${shortDescription}</p>
                <a href="news-details.php?id=${item.id}" class="news-slide-link">
                    Читать подробнее
                </a>
            </div>
        </div>`;
    }
    
    // Получение количества видимых слайдов
    function getVisibleSlidesCount() {
        const width = window.innerWidth;
        if (width <= 576) return 1;
        if (width <= 768) return 1.5;
        if (width <= 992) return 2.5;
        return 3.5; // Показываем 3.5 слайда как вы хотели
    }
    
    // Перемещение слайдера
    function slideNews(direction) {
        if (isAnimating || newsData.length === 0) return;
        
        isAnimating = true;
        
        const slideWidth = 380 + 40; // ширина слайда + gap
        const totalSlides = newsData.length;
        const visibleSlides = getVisibleSlidesCount();
        
        currentIndex += direction * slideWidth;
        
        // Плавное перемещение
        track.style.transition = 'transform 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
        track.style.transform = `translateX(-${currentIndex}px)`;
        
        // Проверяем границы для бесконечной прокрутки
        const maxIndex = (totalSlides + Math.ceil(visibleSlides / 2)) * slideWidth;
        const minIndex = Math.ceil(visibleSlides / 2) * slideWidth;
        
        setTimeout(() => {
            // Если вышли за пределы в конце - переходим к началу
            if (currentIndex >= maxIndex) {
                currentIndex = minIndex;
                track.style.transition = 'none';
                track.style.transform = `translateX(-${currentIndex}px)`;
                
                // Принудительный рефлоу для сброса анимации
                void track.offsetWidth;
                
                track.style.transition = 'transform 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
            }
            
            // Если вышли за пределы в начале - переходим к концу
            if (currentIndex < minIndex) {
                currentIndex = maxIndex - slideWidth;
                track.style.transition = 'none';
                track.style.transform = `translateX(-${currentIndex}px)`;
                
                void track.offsetWidth;
                
                track.style.transition = 'transform 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
            }
            
            isAnimating = false;
        }, 600);
    }
    
    // Автопрокрутка
    function startAutoSlide() {
        clearInterval(autoSlideInterval);
        autoSlideInterval = setInterval(() => {
            slideNews(1);
        }, 4000); // Каждые 4 секунды
    }
    
    // Управление автопрокруткой
    function setupAutoSlideControls() {
        // Пауза при наведении
        track.addEventListener('mouseenter', () => {
            clearInterval(autoSlideInterval);
        });
        
        // Возобновление при уходе мыши
        track.addEventListener('mouseleave', () => {
            startAutoSlide();
        });
        
        // Пауза при фокусе на слайде
        track.addEventListener('focusin', () => {
            clearInterval(autoSlideInterval);
        });
        
        // Возобновление при потере фокуса
        track.addEventListener('focusout', () => {
            startAutoSlide();
        });
    }
    
    // Обновление при изменении размера окна
    window.addEventListener('resize', () => {
        // Пересоздаем слайдер для нового количества слайдов
        createInfiniteSlider();
    });
    
    // Делаем функции глобальными для кнопок
    window.slideNews = slideNews;
});