// IIFE для изоляции переменных
(function() {
    // Предотвращаем повторную инициализацию
    if (window.__accessibilityInitialized) {
        console.warn('Accessibility mode already initialized');
        return;
    }
    window.__accessibilityInitialized = true;

    const accessibilityToggle = document.getElementById('accessibilityToggle');
    if (!accessibilityToggle) {
        console.warn('Accessibility toggle button not found');
        return;
    }

    // Проверяем, есть ли сохраненные настройки
    const isAccessibilityMode = localStorage.getItem('accessibilityMode') === 'true';

    // Функция включения режима для слабовидящих (ГОСТ)
    function enableAccessibilityMode() {
        document.documentElement.style.setProperty('--font-size', '22px');
        document.documentElement.style.setProperty('--line-height', '1.8');
        document.documentElement.style.setProperty('--letter-spacing', '0.12em');
        
        // Основные стили по ГОСТ
        document.body.classList.add('accessibility-mode');
        
        // Сохраняем настройки
        localStorage.setItem('accessibilityMode', 'true');
        accessibilityToggle.textContent = '👁 Обычная версия';
        accessibilityToggle.setAttribute('aria-pressed', 'true');
    }

    // Функция выключения режима
    function disableAccessibilityMode() {
        document.body.classList.remove('accessibility-mode');
        
        // Сбрасываем CSS переменные
        document.documentElement.style.removeProperty('--font-size');
        document.documentElement.style.removeProperty('--line-height');
        document.documentElement.style.removeProperty('--letter-spacing');
        
        localStorage.setItem('accessibilityMode', 'false');
        accessibilityToggle.textContent = '👁 Версия для слабовидящих';
        accessibilityToggle.setAttribute('aria-pressed', 'false');
    }

    // Применяем сохраненные настройки при загрузке
    if (isAccessibilityMode) {
        enableAccessibilityMode();
    }

    // Обработчик клика
    accessibilityToggle.addEventListener('click', function() {
        if (document.body.classList.contains('accessibility-mode')) {
            disableAccessibilityMode();
        } else {
            enableAccessibilityMode();
        }
    });

    // Инициализация ARIA атрибутов
    accessibilityToggle.setAttribute('role', 'button');
    accessibilityToggle.setAttribute('aria-pressed', isAccessibilityMode.toString());

    // Дополнительные функции для полного соответствия ГОСТ

    // Функция для увеличения/уменьшения шрифта
    window.changeFontSize = function(change) {
        const currentSize = parseFloat(
            getComputedStyle(document.documentElement)
                .getPropertyValue('--font-size')
        ) || 22;
        
        const newSize = Math.max(16, Math.min(36, currentSize + change));
        document.documentElement.style.setProperty('--font-size', `${newSize}px`);
    };

    // Функция изменения цветовой схемы
    window.changeColorScheme = function(scheme) {
        const schemes = {
            'black-on-white': { bg: '#FFFFFF', text: '#000000' },
            'white-on-black': { bg: '#000000', text: '#FFFFFF' },
            'sepia': { bg: '#FBF0D9', text: '#5B4636' },
            'blue-on-yellow': { bg: '#FFFF00', text: '#0000FF' }
        };
        
        const selected = schemes[scheme];
        if (selected) {
            document.documentElement.style.setProperty('--bg-color', selected.bg);
            document.documentElement.style.setProperty('--text-color', selected.text);
        }
    };

    // Создание панели расширенных настроек доступности
    function createAccessibilityPanel() {
        // Проверяем, не существует ли уже панель
        if (document.getElementById('accessibilityPanel')) return;
        
        const panel = document.createElement('div');
        panel.id = 'accessibilityPanel';
        panel.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border: 3px solid black;
            padding: 15px;
            z-index: 10000;
            box-shadow: 0 0 20px rgba(0,0,0,0.3);
        `;
        
        panel.innerHTML = `
            <h3 style="margin-top:0">Настройки доступности</h3>
            <div>
                <button onclick="changeFontSize(2)">А+</button>
                <button onclick="changeFontSize(-2)">А-</button>
                <button onclick="changeColorScheme('black-on-white')">Ч/Б</button>
                <button onclick="changeColorScheme('white-on-black')">Б/Ч</button>
                <button onclick="changeColorScheme('sepia')">Сепия</button>
            </div>
            <button onclick="this.closest('#accessibilityPanel').remove()">Закрыть</button>
        `;
        
        document.body.appendChild(panel);
    }

    // Инициализация при загрузке страницы
    document.addEventListener('DOMContentLoaded', function() {
        // Добавляем панель управления для расширенных настроек
        if (isAccessibilityMode) {
            createAccessibilityPanel();
        }
        
        // Добавляем обработчик клавиатуры
        document.addEventListener('keydown', function(e) {
            // Alt+1 для быстрого переключения
            if (e.altKey && e.key === '1') {
                accessibilityToggle.click();
            }
        });
    });

    // Экспортируем функции для использования в HTML (onclick)
    window.enableAccessibilityMode = enableAccessibilityMode;
    window.disableAccessibilityMode = disableAccessibilityMode;
    window.createAccessibilityPanel = createAccessibilityPanel;

})();