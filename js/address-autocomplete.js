// Массив адресов для автодополнения
const addresses = [
    "Исаковского, 2 к.1", "Исаковского, 2 к. 2", "Исаковского, 4 к. 2",
    "Исаковского, 6 к. 1", "Исаковского, 6 к. 3", "Исаковского, 8 к. 1",
    "Исаковского, 8 к. 2", "Исаковского, 8 к. 3", "Исаковского, 10 к. 1",
    "Исаковского, 12 к. 2 п.5-10", "Исаковского, 14 к. 1 под.1-4",
    "Исаковского, 14 к. 2 п.11-14", "Исаковского ул., 16 к.1", "Исаковского, 18",
    "Исаковского, 20 к. 1", "Исаковского, 20 к. 2", "Исаковского, 22 к. 1",
    "Исаковского, 24 к. 1", "Искаковского, 25 к.1", "Искаковского, 25 к.2",
    "Искаковского, 27 к.1", "Искаковского, 27 к.2", "Искаковского, 27 к.3",
    "Исаковского, 28 к. 1", "Исаковского, 28 к. 2", "Исаковского, 29 к. 3",
    "Исаковского, 31", "Исаковского, 33 к. 1", "Исаковского, 33 к. 2",
    "Исаковского, 33 к. 3", "Исаковского, 33 к. 4", "Кулакова, 1 к. 2",
    "Кулакова, 2 к. 1", "Кулакова, 4 к. 1", "Кулакова, 5 к. 1",
    "Кулакова, 5 к. 2", "Кулакова, 6", "Кулакова, 7 под. 1-2",
    "Кулакова, 8", "Кулакова, 9", "Кулакова, 10", "Кулакова, 11 к. 1",
    "Кулакова, 11 к. 2", "Кулакова, 12 к. 1 п.3-8", "Кулакова, 15 к. 1",
    "Кулакова, 18 к. 1", "Кулакова, 19", "М.Катукова, 2 к. 1",
    "М. Катукова, 3 к. 1", "М. Катукова, 4 к. 1", "М. Катукова, 6 к. 2",
    "М. Катукова, 9 к. 1", "М. Катукова, 10 к. 1", "М. Катукова, 10 к. 2",
    "М. Катукова, 11 к. 3", "М. Катукова, 12 к. 1", "М. Катукова, 13 к. 2",
    "М. Катукова, 13 к. 3 под.13-14", "М. Катукова, 14 к. 1",
    "М. Катукова, 15 к. 1", "М. Катукова, 16 к. 2", "М. Катукова, 17 к. 3",
    "М. Катукова, 19 к. 1", "М. Катукова, 19 к. 2", "М. Катукова, 20 к. 2",
    "М. Катукова, 21 к. 1", "М. Катукова, 22 к. 1", "М. Катукова, 25 к. 1",
    "Неманский пр-д, 1 к. 3", "Неманский пр-д, 3", "Неманский пр-д, 5 к. 1",
    "Неманский пр-д, 7 к. 1 под.3-8", "Неманский пр-д, 9", "Неманский пр-д, 11",
    "Неманский пр-д, 13 к.1", "Неманский пр-d, 13 к.2", "Строгинский б-р, 5",
    "Строгинский б-р, 7 к. 1", "Строгинский б-р, 7 к. 2", "Строгинский б-р, 13 к. 3",
    "Строгинский б-р, 17 к. 1", "Строгинский б-р, 22", "Строгинский б-р, 23",
    "Таллинская ул., 5 к.3", "Таллинская ул., 5 к.4", "Таллинская ул., 9 к. 3",
    "Таллинская ул., 9 к. 4", "Таллинская ул., 13 к. 2", "Таллинская ул., 19 к. 1",
    "Таллинская ул., 20 к. 1", "Таллинская ул., 20 к. 2", "Таллинская ул., 20 к. 3",
    "Таллинская ул., 24", "Таллинская ул., 26", "Таллинская ул., 30",
    "Твардовского ул., 1", "Твардовского ул., 3 к.1", "Твардовского ул., 4 к.2",
    "Твардовского ул., 4 к.3", "Твардовского ул., 4 к.4", "Твардовского ул., 5 к.1",
    "Твардовского ул., 5 к.2", "Твардовского ул., 5 к.3", "Твардовского ул., 10 к.2",
    "Твардовского ул., 13 к.2", "Твардовского ул., 19 к.1", "Твардовского ул., 25 к.1",
    "Твардовского ул., 25 к.2", "ул. 2-я лыковская, 23 корп.1",
    "ул. 2-я лыковская, 55", "ул. 2-я лыковская, 55 стр.1", "Туркменский проезд, 20"
];

// Функция для поиска адресов
function searchAddresses(query) {
    if (query.length < 2) return [];
    
    const lowerQuery = query.toLowerCase();
    return addresses.filter(address => 
        address.toLowerCase().includes(lowerQuery)
    );
}

// Функция для обновления полного адреса
function updateFullAddress() {
    const streetInput = document.getElementById('street');
    const fullAddressInput = document.getElementById('full_address');
    const entranceInput = document.getElementById('entrance');
    const floorInput = document.getElementById('floor');
    const apartmentInput = document.getElementById('apartment');
    
    if (!streetInput || !fullAddressInput) return;
    
    const street = streetInput.value.trim();
    const entrance = entranceInput ? entranceInput.value.trim() : '';
    const floor = floorInput ? floorInput.value.trim() : '';
    const apartment = apartmentInput ? apartmentInput.value.trim() : '';
    
    let fullAddress = street;
    
    if (entrance) {
        fullAddress += `, подъезд ${entrance}`;
    }
    
    if (floor) {
        fullAddress += `, этаж ${floor}`;
    }
    
    if (apartment) {
        fullAddress += `, кв. ${apartment}`;
    }
    
    fullAddressInput.value = fullAddress;
}

// Функция для автоматического заполнения полей при выборе адреса
function autoFillAddressDetails(selectedAddress) {
    const streetInput = document.getElementById('street');
    const entranceInput = document.getElementById('entrance');
    const floorInput = document.getElementById('floor');
    const apartmentInput = document.getElementById('apartment');
    
    if (!streetInput) return;
    
    // Заполняем поле улицы
    streetInput.value = selectedAddress;
    
    // Очищаем детали при выборе нового адреса
    if (entranceInput) entranceInput.value = '';
    if (floorInput) floorInput.value = '';
    if (apartmentInput) apartmentInput.value = '';
    
    // Обновляем полный адрес
    updateFullAddress();
}

// Функция для подготовки адреса перед отправкой формы
function prepareAddress() {
    updateFullAddress();
    console.log('Отправляется полный адрес:', document.getElementById('full_address').value);
    return true;
}

// Инициализация автодополнения
document.addEventListener('DOMContentLoaded', function() {
    console.log('Скрипт автодополнения загружен');
    
    const streetInput = document.getElementById('street');
    
    if (!streetInput) {
        console.error('Поле street не найдено!');
        return;
    }
    
    console.log('Поле street найдено:', streetInput);
    
    // Создаем dropdown для подсказок
    const dropdown = document.createElement('div');
    dropdown.className = 'address-dropdown';
    dropdown.style.cssText = `
        position: absolute;
        background: white;
        border: 1px solid #ddd;
        border-radius: 5px;
        max-height: 200px;
        overflow-y: auto;
        z-index: 1000;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        display: none;
        width: 100%;
        top: 100%;
        left: 0;
        margin-top: 5px;
    `;
    
    const parentContainer = streetInput.parentNode;
    parentContainer.style.position = 'relative';
    parentContainer.appendChild(dropdown);
    
    // Назначаем обработчики на все поля адреса для обновления полного адреса
    const addressInputs = ['street', 'entrance', 'floor', 'apartment'];
    addressInputs.forEach(id => {
        const input = document.getElementById(id);
        if (input) {
            input.addEventListener('input', updateFullAddress);
        }
    });
    
    // Очищаем детали адреса при ручном изменении улицы
    streetInput.addEventListener('input', function() {
        const query = this.value;
        
        // Если пользователь начал вводить новый адрес, очищаем детали
        if (query.length < 2) {
            const entranceInput = document.getElementById('entrance');
            const floorInput = document.getElementById('floor');
            const apartmentInput = document.getElementById('apartment');
            
            if (entranceInput) entranceInput.value = '';
            if (floorInput) floorInput.value = '';
            if (apartmentInput) apartmentInput.value = '';
        }
        
        dropdown.innerHTML = '';
        dropdown.style.display = 'none';
        
        if (query.length >= 2) {
            const results = searchAddresses(query);
            
            if (results.length > 0) {
                results.forEach(address => {
                    const item = document.createElement('div');
                    item.className = 'dropdown-item';
                    item.textContent = address;
                    item.style.cssText = `
                        padding: 10px 15px;
                        cursor: pointer;
                        border-bottom: 1px solid #f0f0f0;
                        transition: background 0.2s;
                    `;
                    
                    item.addEventListener('mouseenter', function() {
                        this.style.background = '#f8f9fa';
                    });
                    
                    item.addEventListener('mouseleave', function() {
                        this.style.background = 'white';
                    });
                    
                    item.addEventListener('click', function() {
                        console.log('Выбран адрес:', address);
                        autoFillAddressDetails(address);
                        dropdown.style.display = 'none';
                        
                        // Фокус на следующее поле
                        const entranceInput = document.getElementById('entrance');
                        if (entranceInput) {
                            entranceInput.focus();
                        }
                    });
                    
                    dropdown.appendChild(item);
                });
                dropdown.style.display = 'block';
            }
        }
        
        // Обновляем полный адрес при ручном вводе
        updateFullAddress();
    });
    
    // Скрываем dropdown при клике вне его
    document.addEventListener('click', function(e) {
        if (!streetInput.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });
    
    // Закрываем dropdown по Escape
    streetInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            dropdown.style.display = 'none';
        }
        
        // Навигация по подсказкам стрелками
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            const items = dropdown.querySelectorAll('.dropdown-item');
            if (items.length > 0 && dropdown.style.display === 'block') {
                let currentIndex = -1;
                items.forEach((item, index) => {
                    if (item.style.background === 'rgb(248, 249, 250)') {
                        currentIndex = index;
                    }
                });
                
                if (e.key === 'ArrowDown') {
                    const nextIndex = (currentIndex + 1) % items.length;
                    items[nextIndex].style.background = '#f8f9fa';
                    if (currentIndex >= 0) {
                        items[currentIndex].style.background = 'white';
                    }
                } else if (e.key === 'ArrowUp') {
                    const prevIndex = currentIndex <= 0 ? items.length - 1 : currentIndex - 1;
                    items[prevIndex].style.background = '#f8f9fa';
                    if (currentIndex >= 0) {
                        items[currentIndex].style.background = 'white';
                    }
                }
            }
        }
        
        // Выбор подсказки по Enter
        if (e.key === 'Enter') {
            const selectedItem = dropdown.querySelector('.dropdown-item[style*="background: rgb(248, 249, 250)"]');
            if (selectedItem && dropdown.style.display === 'block') {
                e.preventDefault();
                selectedItem.click();
            }
        }
    });
    
    // Инициализируем полный адрес при загрузке
    updateFullAddress();
    
    // Экспортируем функцию для формы
    window.prepareAddress = prepareAddress;
    
    console.log('Автодополнение инициализировано');
});