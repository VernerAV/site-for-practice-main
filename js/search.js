const texts = [
    "График отключения горячей воды",
    "Записаться на прием к директору", 
    "Узнать задолженность по ЖКУ"
];

let textIndex = 0;
let charIndex = 0;
let isDeleting = false;
let timeout;
let isActive = false; // true когда пользователь взаимодействует

function typeAnimation() {
    const input = document.getElementById('searchInput');
    if (!input || isActive) return;
    
    const text = texts[textIndex];
    
    if (isDeleting) {
        charIndex--;
    } else {
        charIndex++;
    }
    
    input.placeholder = text.substring(0, charIndex) + '|';
    
    if (!isDeleting && charIndex === text.length) {
        isDeleting = true;
        timeout = setTimeout(typeAnimation, 2000);
        return;
    }
    
    if (isDeleting && charIndex === 0) {
        isDeleting = false;
        textIndex = (textIndex + 1) % texts.length;
    }
    
    timeout = setTimeout(typeAnimation, isDeleting ? 50 : 100);
}

function startAnimation() {
    if (isActive) return;
    
    textIndex = 0;
    charIndex = 0;
    isDeleting = false;
    
    const input = document.getElementById('searchInput');
    if (input && !input.value) {
        clearTimeout(timeout);
        input.placeholder = '|';
        setTimeout(typeAnimation, 300);
    }
}

function stopAnimation() {
    clearTimeout(timeout);
    const input = document.getElementById('searchInput');
    if (input) {
        input.placeholder = '';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('searchInput');
    
    if (input) {
        // Запускаем анимацию при загрузке
        setTimeout(startAnimation, 500);
        
        // При фокусе - останавливаем анимацию
        input.addEventListener('focus', function() {
            isActive = true;
            stopAnimation();
        });
        
        input.addEventListener('input', function() {
            isActive = true;
            this.placeholder = '';
        });
        
        // При потере фокуса - возвращаем анимацию если поле пустое
        input.addEventListener('blur', function() {
            isActive = false; // Пользователь перестал взаимодействовать
            
            // Ждем немного и запускаем анимацию если поле пустое
            if (!this.value) {
                setTimeout(startAnimation, 800);
            }
        });
        
        // Если пользователь очистил поле - запускаем анимацию
        input.addEventListener('change', function() {
            if (!this.value && document.activeElement !== input) {
                isActive = false;
                setTimeout(startAnimation, 300);
            }
        });
    }
});