document.addEventListener('DOMContentLoaded', function() {
    // Фильтрация новостей
    const filterButtons = document.querySelectorAll('.filter-btn');
    const newsCards = document.querySelectorAll('.news-card');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Убираем активный класс у всех кнопок
            filterButtons.forEach(btn => btn.classList.remove('active'));
            // Добавляем активный класс текущей кнопке
            this.classList.add('active');
            
            const filter = this.dataset.filter;
            
            // Показываем/скрываем карточки
            newsCards.forEach(card => {
                if (filter === 'all' || card.dataset.category === filter) {
                    card.style.display = 'flex';
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 100);
                } else {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    setTimeout(() => {
                        card.style.display = 'none';
                    }, 300);
                }
            });
        });
    });
    
    // Кнопки действий
    const shareButtons = document.querySelectorAll('.share-btn');
    const saveButtons = document.querySelectorAll('.save-btn');
    
    shareButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            const newsId = this.dataset.id;
            const url = window.location.origin + '/news-details.php?id=' + newsId;
            const title = this.closest('.news-card').querySelector('.card-title').textContent;
            
            // Простая копия ссылки в буфер
            navigator.clipboard.writeText(url).then(() => {
                const originalHTML = this.innerHTML;
                this.innerHTML = '<i class="fas fa-check"></i>';
                this.style.color = '#10b981';
                
                setTimeout(() => {
                    this.innerHTML = originalHTML;
                    this.style.color = '';
                }, 2000);
            });
        });
    });
    
    saveButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            const newsId = this.dataset.id;
            
            if (this.classList.contains('saved')) {
                this.classList.remove('saved');
                this.innerHTML = '<i class="far fa-bookmark"></i>';
                this.style.color = '';
                // Удалить из сохраненных
                localStorage.removeItem('saved_news_' + newsId);
            } else {
                this.classList.add('saved');
                this.innerHTML = '<i class="fas fa-bookmark"></i>';
                this.style.color = '#f59e0b';
                // Сохранить
                localStorage.setItem('saved_news_' + newsId, 'true');
            }
        });
    });
    
    // Проверяем сохраненные новости при загрузке
    saveButtons.forEach(button => {
        const newsId = button.dataset.id;
        if (localStorage.getItem('saved_news_' + newsId)) {
            button.classList.add('saved');
            button.innerHTML = '<i class="fas fa-bookmark"></i>';
            button.style.color = '#f59e0b';
        }
    });
    
    // Подписка на рассылку
    const subscriptionForm = document.querySelector('.subscription-form');
    if (subscriptionForm) {
        subscriptionForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const email = this.querySelector('input[type="email"]').value;
            const submitBtn = this.querySelector('.subscribe-btn');
            
            // Валидация email
            if (!isValidEmail(email)) {
                showMessage('Введите корректный email', 'error');
                return;
            }
            
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = 'Отправка...';
            submitBtn.disabled = true;
            
            // Имитация отправки
            setTimeout(() => {
                showMessage('Вы успешно подписались на рассылку!', 'success');
                this.reset();
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 1500);
        });
    }
    
    // Вспомогательные функции
    function isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    function showMessage(text, type) {
        const message = document.createElement('div');
        message.className = `alert-message alert-${type}`;
        message.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            ${text}
        `;
        
        document.body.appendChild(message);
        
        setTimeout(() => {
            message.classList.add('show');
        }, 10);
        
        setTimeout(() => {
            message.classList.remove('show');
            setTimeout(() => message.remove(), 300);
        }, 3000);
    }
    
    // Добавляем стили для сообщений
    const style = document.createElement('style');
    style.textContent = `
        .alert-message {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 12px;
            color: white;
            font-weight: 500;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 12px;
            transform: translateX(150%);
            transition: transform 0.3s ease;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .alert-message.show {
            transform: translateX(0);
        }
        
        .alert-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        
        .alert-error {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }
        
        .alert-message i {
            font-size: 1.2rem;
        }
    `;
    document.head.appendChild(style);
});