// JavaScript для страницы прайс-листа
document.addEventListener('DOMContentLoaded', function() {
    // Элементы страницы
    const searchForm = document.getElementById('search-form');
    const filtersForm = document.getElementById('filters-form');
    const categorySelect = document.getElementById('category');
    const sortSelect = document.getElementById('sort');
    const minPriceInput = document.querySelector('input[name="min_price"]');
    const maxPriceInput = document.querySelector('input[name="max_price"]');
    const searchInput = document.querySelector('input[name="search"]');
    const tableBody = document.getElementById('prices-table-body');
    const servicesCount = document.getElementById('services-count');
    
    // Загрузка данных при загрузке страницы
    loadPrices();
    
    // Обработка отправки формы поиска
    searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        updateFiltersFromSearch();
        loadPrices();
    });
    
    // Обработка отправки формы фильтров
    filtersForm.addEventListener('submit', function(e) {
        e.preventDefault();
        loadPrices();
    });
    
    // Динамическая фильтрация при изменении значений
    categorySelect.addEventListener('change', function() {
        loadPrices();
    });
    
    sortSelect.addEventListener('change', function() {
        loadPrices();
    });
    
    // Фильтрация по цене с задержкой
    let priceTimeout;
    function handlePriceChange() {
        clearTimeout(priceTimeout);
        priceTimeout = setTimeout(function() {
            loadPrices();
        }, 800);
    }
    
    minPriceInput.addEventListener('input', handlePriceChange);
    maxPriceInput.addEventListener('input', handlePriceChange);
    
    // Функция для обновления фильтров из поиска
    function updateFiltersFromSearch() {
        const hiddenSearchInput = filtersForm.querySelector('input[name="search"]');
        hiddenSearchInput.value = searchInput.value;
    }
    
    // Функция загрузки данных
    function loadPrices() {
        // Показываем индикатор загрузки
        tableBody.innerHTML = `
            <tr>
                <td colspan="5" class="loading-cell">
                    <div class="loading-spinner">
                        <i class="fas fa-spinner fa-spin"></i>
                        <span>Загрузка данных...</span>
                    </div>
                </td>
            </tr>
        `;
        
        // Собираем параметры фильтрации
        const params = new URLSearchParams();
        
        if (searchInput.value) {
            params.append('search', searchInput.value);
        }
        
        if (categorySelect.value) {
            params.append('category', categorySelect.value);
        }
        
        if (minPriceInput.value && parseFloat(minPriceInput.value) > 0) {
            params.append('min_price', minPriceInput.value);
        }
        
        if (maxPriceInput.value && parseFloat(maxPriceInput.value) > 0) {
            params.append('max_price', maxPriceInput.value);
        }
        
        if (sortSelect.value) {
            params.append('sort', sortSelect.value);
        }
        
        // Загружаем данные через AJAX
        fetch('includes/get_prices.php?' + params.toString())
            .then(response => response.text())
            .then(data => {
                tableBody.innerHTML = data;
                updateServicesCount();
                applyRowStyles();
            })
            .catch(error => {
                console.error('Ошибка при загрузке данных:', error);
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="5" style="text-align: center; color: #dc3545; padding: 40px 20px;">
                            <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 15px;"></i>
                            <p>Произошла ошибка при загрузке данных. Пожалуйста, попробуйте позже.</p>
                        </td>
                    </tr>
                `;
            });
    }
    
    // Функция обновления счетчика услуг
    function updateServicesCount() {
        const rows = tableBody.querySelectorAll('tr');
        let count = 0;
        
        rows.forEach(row => {
            if (!row.classList.contains('loading-cell') && row.cells.length > 1) {
                count++;
            }
        });
        
        servicesCount.textContent = count;
    }
    
    // Функция применения стилей к строкам таблицы
    function applyRowStyles() {
        const rows = tableBody.querySelectorAll('tr');
        
        rows.forEach((row, index) => {
            // Пропускаем строки с ошибками или загрузкой
            if (row.cells.length < 2) return;
            
            // Чередование цветов строк
            if (index % 2 === 0) {
                row.style.backgroundColor = '#fafafa';
            } else {
                row.style.backgroundColor = '#ffffff';
            }
            
            // Применение стилей для премиум услуг (цена > 5000)
            const priceCell = row.cells[3];
            if (priceCell) {
                const priceText = priceCell.textContent.trim();
                const priceMatch = priceText.match(/[\d\s,]+/);
                
                if (priceMatch) {
                    const price = parseFloat(priceMatch[0].replace(/\s/g, '').replace(',', '.'));
                    
                    if (price > 5000) {
                        row.classList.add('premium-row');
                    }
                }
            }
        });
    }
    
    // Валидация цены
    function validatePriceRange() {
        const minPrice = parseFloat(minPriceInput.value) || 0;
        const maxPrice = parseFloat(maxPriceInput.value) || 0;
        
        if (minPrice > maxPrice && maxPrice > 0) {
            alert('Минимальная цена не может быть больше максимальной');
            minPriceInput.focus();
            return false;
        }
        
        return true;
    }
    
    // Добавляем валидацию перед отправкой формы
    filtersForm.addEventListener('submit', function(e) {
        if (!validatePriceRange()) {
            e.preventDefault();
            return false;
        }
    });
    
    // Сохранение состояния фильтров в localStorage
    function saveFiltersToStorage() {
        const filters = {
            search: searchInput.value,
            category: categorySelect.value,
            min_price: minPriceInput.value,
            max_price: maxPriceInput.value,
            sort: sortSelect.value
        };
        
        localStorage.setItem('price_filters', JSON.stringify(filters));
    }
    
    function loadFiltersFromStorage() {
        const savedFilters = localStorage.getItem('price_filters');
        
        if (savedFilters) {
            const filters = JSON.parse(savedFilters);
            
            searchInput.value = filters.search || '';
            categorySelect.value = filters.category || '';
            minPriceInput.value = filters.min_price || 0;
            maxPriceInput.value = filters.max_price || 100000;
            sortSelect.value = filters.sort || 'name_asc';
            
            // Обновляем скрытое поле поиска в форме фильтров
            const hiddenSearchInput = filtersForm.querySelector('input[name="search"]');
            hiddenSearchInput.value = filters.search || '';
        }
    }
    
    // Сохраняем фильтры при изменении
    const filterInputs = [searchInput, categorySelect, minPriceInput, maxPriceInput, sortSelect];
    filterInputs.forEach(input => {
        input.addEventListener('change', saveFiltersToStorage);
        input.addEventListener('input', saveFiltersToStorage);
    });
    
    // Загружаем сохраненные фильтры при загрузке страницы
    loadFiltersFromStorage();
});