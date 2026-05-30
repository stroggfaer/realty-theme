/**
 * JavaScript для страницы просмотра диалога
 * Модуль "Мой кабинет" для темы Realty Theme
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', async function() {
        const dialogSendBtn = document.getElementById('dialog-send-btn');
        if (dialogSendBtn) {
            await loadDialogMessages();
            // Обработчик отправки ответа
            dialogSendBtn?.addEventListener('click', sendReply);
        }
        
        // Инициализация редактирования бронирования
        initBookingEdit();
    });

    /**
     * Загрузка сообщений диалога
     */
    async function loadDialogMessages() {
        const container = document.getElementById('dialog-messages-container');
        
        const body = new URLSearchParams();
        body.append('action', 'my_cabinet_get_dialog');
        body.append('nonce', dialogViewData.getDialogNonce);
        body.append('thread_id', dialogViewData.threadId);

        try {
            const response = await fetch(dialogViewData.ajaxUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString(),
            });

            const result = await response.json();
            if (result.success) {
                renderMessages(result.data.messages);
            } else {
                container.innerHTML = '<div class="error">Ошибка загрузки: ' + result.data.message + '</div>';
            }
        } catch (error) {
            console.error('Ошибка сети:', error);
            container.innerHTML = '<div class="error">Ошибка сети</div>';
        }
    }

    /**
     * Отрисовка сообщений
     */
    function renderMessages(messages) {
        const container = document.getElementById('dialog-messages-container');
        
        if (messages.length === 0) {
            container.innerHTML = '<p>Нет сообщений</p>';
            return;
        }

        container.innerHTML = messages.map(msg => {
            return `
            <div class="message-thread-item ${msg.sender_role}">
                <div class="message-bubble">${msg.content}</div>
                <div class="message-meta">
                    ${msg.sender_name} • ${msg.date}
                </div>
            </div>
            `;
        }).join('');

        // Прокрутка вниз после рендера сообщений
        setTimeout(() => {
            container.scrollTop = container.scrollHeight;
        }, 50);
    }

    /**
     * Отправка ответа
     */
    async function sendReply() {
        const textarea = document.getElementById('dialog-reply-textarea');
        const statusSpan = document.getElementById('dialog-send-status');
        const sendBtn = document.getElementById('dialog-send-btn');
        const content = textarea.value.trim();

        // Проверяем, не заблокирована ли форма
        if (textarea.disabled || sendBtn.disabled) {
            alert('Отправка сообщений недоступна до подтверждения бронирования');
            return;
        }

        if (!content) {
            alert('Введите сообщение');
            return;
        }

        // Показываем статус отправки
        statusSpan.textContent = 'Отправка...';
        statusSpan.className = 'send-status';

        const body = new URLSearchParams();
        body.append('action', 'my_cabinet_send_reply');
        body.append('nonce', dialogViewData.sendReplyNonce);
        body.append('thread_id', dialogViewData.threadId);
        body.append('content', content);

        try {
            const response = await fetch(dialogViewData.ajaxUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString(),
            });

            const result = await response.json();

            if (result.success) {
                textarea.value = '';
                statusSpan.textContent = '✓ Отправлено!';
                statusSpan.className = 'send-status success';
                            
                // Перезагружаем сообщения (CSS автоматически разместит снизу)
                loadDialogMessages();
                            
                // Очищаем статус через 3 секунды
                setTimeout(() => {
                    statusSpan.textContent = '';
                }, 3000);
            } else {
                statusSpan.textContent = '✗ Ошибка: ' + result.data.message;
                statusSpan.className = 'send-status error';
            }
        } catch (error) {
            console.error('Ошибка отправки:', error);
            statusSpan.textContent = '✗ Ошибка сети';
            statusSpan.className = 'send-status error';
        }
    }

    // ============================================================
    // Редактирование бронирования
    // ============================================================

    /**
     * Инициализация редактирования бронирования
     */
    function initBookingEdit() {
        const editBtn = document.getElementById('booking-edit-btn');
        const saveBtn = document.getElementById('booking-save-btn');
        const cancelBtn = document.getElementById('booking-cancel-btn');
        
        if (editBtn) {
            editBtn.addEventListener('click', toggleEditMode);
        }
        if (saveBtn) {
            saveBtn.addEventListener('click', saveBookingDetails);
        }
        if (cancelBtn) {
            cancelBtn.addEventListener('click', cancelEdit);
        }
    }

    /**
     * Переключение в режим редактирования
     */
    function toggleEditMode() {
        const viewMode = document.getElementById('booking-view-mode');
        const editMode = document.getElementById('booking-edit-mode');
        
        if (viewMode && editMode) {
            viewMode.style.display = 'none';
            editMode.style.display = 'block';
        }
    }

    /**
     * Отмена редактирования
     */
    function cancelEdit() {
        const viewMode = document.getElementById('booking-view-mode');
        const editMode = document.getElementById('booking-edit-mode');
        
        if (viewMode && editMode) {
            editMode.style.display = 'none';
            viewMode.style.display = 'block';
        }
    }

    /**
     * Сохранение деталей бронирования
     */
    async function saveBookingDetails() {
        // Подтверждение
        const confirmed = confirm('Вы точно хотите сохранить изменения?\n\nКлиент увидит обновлённые данные в личном кабинете.');
        if (!confirmed) {
            return;
        }
        
        const bookingId = document.getElementById('booking-message-id')?.value;
        const checkinDate = document.getElementById('edit-checkin-date')?.value;
        const checkoutDate = document.getElementById('edit-checkout-date')?.value;
        const bookingStatus = document.getElementById('edit-booking-status')?.value;
        
        // Собираем все поля гостей динамически
        const guestFields = document.querySelectorAll('.booking-edit-field input[type="number"][id^="edit-"]');
        const guestsData = {};
        guestFields.forEach(field => {
            const guestName = field.id.replace('edit-', '');
            guestsData[guestName] = field.value;
        });
        
        if (!bookingId) {
            alert('Ошибка: не найден ID бронирования');
            return;
        }
        
        // Валидация дат
        if (checkinDate && checkoutDate && checkinDate >= checkoutDate) {
            alert('Дата заезда должна быть раньше даты выезда');
            return;
        }
        
        const saveBtn = document.getElementById('booking-save-btn');
        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.textContent = 'Сохранение...';
        }
        
        const body = new URLSearchParams();
        body.append('action', 'my_cabinet_update_booking_details');
        body.append('nonce', dialogViewData.updateBookingNonce);
        body.append('booking_id', bookingId);
        body.append('thread_id', dialogViewData.threadId);
        body.append('checkin_date', checkinDate);
        body.append('checkout_date', checkoutDate);
        body.append('booking_status', bookingStatus);
        
        // Добавляем данные гостей динамически
        for (const [guestName, guestValue] of Object.entries(guestsData)) {
            body.append(guestName, guestValue);
        }
        
        try {
            const response = await fetch(dialogViewData.ajaxUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString(),
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Обновляем UI
                updateBookingUI(result.data);
                
                // Показываем уведомление
                showNotification('Изменения сохранены', 'success');
                
                // Возвращаемся в режим просмотра
                cancelEdit();
            } else {
                alert('Ошибка: ' + (result.data.message || 'Не удалось сохранить изменения'));
            }
        } catch (error) {
            console.error('Ошибка сохранения:', error);
            alert('Ошибка сети при сохранении');
        } finally {
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<span class="dashicons dashicons-saved"></span> Сохранить';
            }
        }
    }

    /**
     * Обновление UI после сохранения
     */
    function updateBookingUI(data) {
        // Обновляем даты в режиме просмотра
        const viewMode = document.getElementById('booking-view-mode');
        if (!viewMode) return;
        
        // Обновляем дату заезда
        const checkinValue = viewMode.querySelector('.booking-date-row:nth-child(1) .date-value');
        if (checkinValue && data.checkin_date_formatted) {
            checkinValue.textContent = data.checkin_date_formatted;
        }
        
        // Обновляем дату выезда
        const checkoutValue = viewMode.querySelector('.booking-date-row:nth-child(2) .date-value');
        if (checkoutValue && data.checkout_date_formatted) {
            checkoutValue.textContent = data.checkout_date_formatted;
        }
        
        // Обновляем гостей динамически
        const guestsValue = viewMode.querySelector('.booking-guests-row .guests-value');
        if (guestsValue && data.guests_values && data.guests_labels) {
            const guestsParts = [];
            for (const [guestName, guestValue] of Object.entries(data.guests_values)) {
                if (guestValue > 0) {
                    guestsParts.push(guestValue + ' ' + data.guests_labels[guestName]);
                }
            }
            guestsValue.textContent = guestsParts.join(', ');
        }
        
        // Обновляем статус
        const statusBadge = viewMode.querySelector('.booking-status-badge');
        if (statusBadge && data.status_label && data.status_class) {
            statusBadge.textContent = data.status_label;
            // Убираем старые классы статусов и добавляем новый
            statusBadge.className = 'booking-status-badge booking-status--' + data.status_class;
        }
    }

    /**
     * Показ уведомления
     */
    function showNotification(message, type) {
        // Создаём элемент уведомления
        const notification = document.createElement('div');
        notification.className = 'booking-notification booking-notification-' + type;
        notification.innerHTML = '<span class="dashicons dashicons-yes-alt"></span> ' + message;
        
        // Вставляем после заголовка
        const pageTitle = document.querySelector('.wp-heading-inline');
        if (pageTitle && pageTitle.parentNode) {
            pageTitle.parentNode.insertBefore(notification, pageTitle.nextSibling);
        }
        
        // Автоматически скрываем через 3 секунды
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

})();
