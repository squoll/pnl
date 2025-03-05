// Обработка форм
document.addEventListener('DOMContentLoaded', function() {
    // Валидация форм
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                } else {
                    field.classList.remove('error');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Пожалуйста, заполните все обязательные поля');
            }
        });
    });
    
    // Обработка загрузки файлов
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const fileName = this.files[0]?.name;
            const fileLabel = this.nextElementSibling;
            if (fileLabel) {
                fileLabel.textContent = fileName || 'Выберите файл';
            }
        });
    });
});

// Функции для работы с датами
function formatDate(date) {
    return date.toISOString().split('T')[0];
}

function calculateDaysLeft(endDate) {
    const now = new Date();
    const end = new Date(endDate);
    const diff = end - now;
    return Math.ceil(diff / (1000 * 60 * 60 * 24));
}

// Обновление статусов хостинга
function updateHostingStatuses() {
    const hostingRows = document.querySelectorAll('.hosting-row');
    hostingRows.forEach(row => {
        const endDate = row.dataset.endDate;
        const daysLeft = calculateDaysLeft(endDate);
        const statusCell = row.querySelector('.days-left');
        
        if (daysLeft < 0) {
            row.classList.add('expired');
        } else if (daysLeft < 30) {
            row.classList.add('warning');
        }
        
        if (statusCell) {
            statusCell.textContent = `${daysLeft} дней`;
        }
    });
}

// Функции для модальных окон
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
        
        // Закрытие по клику вне модального окна
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
        
        // Закрытие по Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                modal.style.display = 'none';
            }
        });
    }
}

// Обработка AJAX запросов
async function makeRequest(url, data) {
    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return await response.json();
    } catch (error) {
        console.error('Error:', error);
        alert('Произошла ошибка при выполнении запроса');
        return null;
    }
}

// Функции для работы со счетами
async function markAsPaid(invoiceId) {
    if (confirm('Отметить счет как оплаченный?')) {
        const result = await makeRequest('ajax/mark_paid.php', { invoice_id: invoiceId });
        if (result?.success) {
            location.reload();
        }
    }
}

async function createHostingInvoice(hostingId) {
    const result = await makeRequest('ajax/create_hosting_invoice.php', {
        hosting_id: hostingId
    });
    
    if (result?.success) {
        alert('Счет успешно создан');
    } else {
        alert('Произошла ошибка при создании счета');
    }
} 

  