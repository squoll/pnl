// Основной файл JavaScript для IPTV Dashboard
'use strict'

// Функции для работы с DOM
function $(selector) {
    return document.querySelector(selector);
}

function findAll(selector) {
    return document.querySelectorAll(selector);
}

// Основная функциональность боковой панели
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация боковой панели
    initSidebar();
    
    // Инициализация поиска
    initSearch();
    
    // Инициализация аккордеонов
    initAccordions();
    
    // Инициализация тултипов
    initTooltips();
    initCopyButtons();
});

// Инициализация боковой панели
function initSidebar() {
    const showAsideBtn = $('.show-side-btn');
    const sidebar = $('.sidebar');
    const wrapper = $('#wrapper');
    const closeAsideBtn = $('.close-aside');
    
    if (showAsideBtn && sidebar) {
        showAsideBtn.addEventListener('click', function() {
            const targetId = this.dataset.show;
            const targetSidebar = $(`#${targetId}`);
            
            if (targetSidebar) {
                targetSidebar.classList.toggle('show-sidebar');
                if (wrapper) {
                    wrapper.classList.toggle('fullwidth');
                }
            }
        });
    }
    
    if (closeAsideBtn) {
        closeAsideBtn.addEventListener('click', function() {
            const targetId = this.dataset.close;
            const targetSidebar = $(`#${targetId}`);
            
            if (targetSidebar) {
                targetSidebar.classList.remove('show-sidebar');
                if (wrapper) {
                    wrapper.classList.remove('margin');
                }
            }
        });
    }
    
    // Адаптация для мобильных устройств
    if (window.innerWidth < 768 && sidebar) {
        sidebar.classList.remove('show-sidebar');
    }
    
    window.addEventListener('resize', function() {
        if (window.innerWidth > 767 && sidebar) {
            sidebar.classList.remove('show-sidebar');
        }
    });
}

// Инициализация поиска
function initSearch() {
    const searchInput = $('#global-search');
    
    if (searchInput) {
        // Очищаем старые обработчики
        searchInput.removeEventListener('keypress', handleSearch);
        searchInput.addEventListener('keypress', handleSearch);
        
        function handleSearch(e) {
            if (e.key === 'Enter') {
                const query = this.value.trim();
                if (query) {
                    const inPages = location.pathname.includes('/pages/');
                    const base = inPages ? '' : 'pages/';
                    window.location.href = base + 'tv_clients.php?search=' + encodeURIComponent(query);
                }
            }
        }
    }
}

// Инициализация аккордеонов
function initAccordions() {
    const accordions = findAll('.accordiontv');
    
    accordions.forEach(accordion => {
        accordion.removeEventListener('click', toggleAccordion);
        accordion.addEventListener('click', toggleAccordion);
    });
    
    function toggleAccordion() {
        this.classList.toggle('active');
        const panel = this.nextElementSibling;
        
        if (panel && panel.classList.contains('panel')) {
            if (panel.style.display === 'block') {
                panel.style.display = 'none';
            } else {
                panel.style.display = 'block';
            }
        }
    }
}

// Инициализация тултипов
function initTooltips() {
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(findAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(tooltipTriggerEl => {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
}

// Функция для копирования в буфер обмена
function copyToClipboard(text, button = null) {
    navigator.clipboard.writeText(text).then(() => {
        if (button) {
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="uil uil-check"></i>';
            button.classList.remove('btn-outline-secondary');
            button.classList.add('btn-success');
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.classList.remove('btn-success');
                button.classList.add('btn-outline-secondary');
            }, 2000);
        }
        
        showNotification('Текст скопирован в буфер обмена', 'success');
    }).catch(err => {
        console.error('Ошибка копирования: ', err);
        showNotification('Ошибка копирования', 'danger');
    });
}

// Функция для показа уведомлений
function showNotification(message, type = 'info') {
    // Создаем контейнер для уведомлений если его нет
    let notificationContainer = $('#notification-container');
    
    if (!notificationContainer) {
        notificationContainer = document.createElement('div');
        notificationContainer.id = 'notification-container';
        notificationContainer.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 300px;
        `;
        document.body.appendChild(notificationContainer);
    }
    
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show`;
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    notificationContainer.appendChild(notification);
    
    // Автоматическое скрытие через 5 секунд
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Функция для подтверждения действий
function confirmAction(message, callback) {
    if (confirm(message)) {
        if (typeof callback === 'function') {
            callback();
        }
        return true;
    }
    return false;
}

// Форматирование даты
function formatDate(dateString) {
    if (!dateString) return '';
    
    const date = new Date(dateString);
    return date.toLocaleDateString('ru-RU', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}

// Форматирование валюты
function formatCurrency(amount) {
    return '€' + parseFloat(amount || 0).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

// Подсчет дней до окончания подписки
function calculateDaysRemaining(subscriptionDate, months) {
    const endDate = new Date(subscriptionDate);
    endDate.setMonth(endDate.getMonth() + parseInt(months));
    const now = new Date();
    const diffTime = endDate - now;
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
}

// Инициализация всех кнопок копирования
function initCopyButtons() {
    const copyButtons = findAll('.copy-link');
    
    copyButtons.forEach(button => {
        button.removeEventListener('click', handleCopyClick);
        button.addEventListener('click', handleCopyClick);
    });
    
    function handleCopyClick() {
        const text = this.getAttribute('data-link') || 
                    this.parentElement.querySelector('input')?.value;
        
        if (text) {
            copyToClipboard(text, this);
        }
    }
}

// Автоматический расчет заработка
function initEarningsCalculator() {
    const calculators = findAll('[data-auto-calculate]');
    
    calculators.forEach(container => {
        const paidInput = container.querySelector('[name="paid"]');
        const costInput = container.querySelector('[name="provider_cost"]');
        const earnedInput = container.querySelector('[name="earned"]');
        
        if (paidInput && costInput && earnedInput) {
            function calculate() {
                const paid = parseFloat(paidInput.value) || 0;
                const cost = parseFloat(costInput.value) || 0;
                earnedInput.value = (paid - cost).toFixed(2);
            }
            
            paidInput.removeEventListener('input', calculate);
            costInput.removeEventListener('input', calculate);
            
            paidInput.addEventListener('input', calculate);
            costInput.addEventListener('input', calculate);
            
            // Инициализируем начальный расчет
            calculate();
        }
    });
}

// Инициализация при загрузке
document.addEventListener('DOMContentLoaded', function() {
    initCopyButtons();
    initEarningsCalculator();
    
    // Обработчик для всех ссылок в боковой панели
    const sidebarLinks = findAll('.sidebar a[href]:not(.has-dropdown > a)');
    
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Проверяем, не обрабатывается ли уже ссылка другим обработчиком
            if (!e.defaultPrevented) {
                // Для мобильных устройств скрываем боковую панель после клика
                if (window.innerWidth < 768) {
                    const sidebar = $('.sidebar');
                    if (sidebar) {
                        // Только если это не выпадающее меню
                        if (!this.closest('.sidebar-dropdown')) {
                            sidebar.classList.remove('show-sidebar');
                        }
                    }
                }
                
                // Разрешаем стандартное поведение ссылки
                return true;
            }
        });
    });
    
    // Обработчик для меню навигации
    const navLinks = findAll('.navbar-nav a[href]');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Разрешаем стандартное поведение
            return true;
        });
    });
});

// Функция для загрузки контента через AJAX (если нужно)
function loadContent(url, containerId) {
    const container = document.getElementById(containerId);
    
    if (!container) return;
    
    container.innerHTML = '<div class="text-center"><div class="spinner-border"></div></div>';
    
    fetch(url)
        .then(response => response.text())
        .then(html => {
            container.innerHTML = html;
        })
        .catch(error => {
            console.error('Ошибка загрузки:', error);
            container.innerHTML = '<div class="alert alert-danger">Ошибка загрузки контента</div>';
        });
}

// Экспорт функций в глобальную область видимости
window.copyToClipboard = copyToClipboard;
window.showNotification = showNotification;
window.confirmAction = confirmAction;
window.formatDate = formatDate;
window.formatCurrency = formatCurrency;
window.calculateDaysRemaining = calculateDaysRemaining;
window.loadContent = loadContent;
