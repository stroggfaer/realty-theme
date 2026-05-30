/**
 * JavaScript для страницы сообщений в админке
 * Модуль "Мой кабинет" для темы Realty Theme
 */

(function() {
    'use strict';

    let allDialogs = [];

    // Загрузка диалогов при загрузке страницы
    document.addEventListener('DOMContentLoaded', function() {
        // Проверяем наличие элементов фильтров для страницы списка сообщений
        const applyFiltersBtn = document.getElementById('apply-filters');
        const clearFiltersBtn = document.getElementById('clear-filters');
        const messagesList = document.getElementById('messages-list');

        // Если нет элементов фильтров - значит это не страница со списком сообщений
        if (!applyFiltersBtn || !clearFiltersBtn || !messagesList) {
            console.warn('Страница сообщений: элементы фильтров не найдены');
            return;
        }

        loadDialogs();

        // Обработчики фильтров
        applyFiltersBtn.addEventListener('click', function() {
            console.log('Кнопка применить фильтры нажата');
            loadDialogs();
        });
        clearFiltersBtn.addEventListener('click', function() {
            console.log('Кнопка сбросить фильтры нажата');
            clearFilters();
        });

        // Обработчик отправки ответа (если есть на странице)
        const sendReplyBtn = document.getElementById('send-reply');
        if (sendReplyBtn) {
            sendReplyBtn.addEventListener('click', sendReply);
        }
    });

    /**
     * Загрузка списка заявок на бронирование
     */
    async function loadDialogs() {
        // Проверка наличия данных от PHP
        if (typeof myCabinetMessagesData === 'undefined') {
            console.error('myCabinetMessagesData не определена. Данные не переданы из PHP.');
            alert('Ошибка: данные администратора не загружены');
            return;
        }

        const filterDateEl = document.getElementById('filter-date');
        const filterLoginEl = document.getElementById('filter-login');
        const filterStatusEl = document.getElementById('filter-status');

        if (!filterDateEl || !filterLoginEl) {
            console.error('Не найдены элементы фильтров');
            return;
        }

        const filterDate = filterDateEl.value;
        const filterLogin = filterLoginEl.value;
        const filterStatus = filterStatusEl ? filterStatusEl.value : '';

        const messagesList = document.getElementById('messages-list');
        if (messagesList) {
            messagesList.innerHTML = '<tr><td colspan="8" class="loading">Загрузка заявок...</td></tr>';
        }

        const body = new URLSearchParams();
        body.append('action', 'my_cabinet_get_dialogs');
        body.append('nonce', myCabinetMessagesData.getDialogsNonce);
        body.append('filter_date', filterDate);
        body.append('filter_login', filterLogin);
        body.append('filter_status', document.getElementById('filter-status')?.value || '');

        console.log('Отправка AJAX запроса:', {
            url: myCabinetMessagesData.ajaxUrl,
            action: 'my_cabinet_get_dialogs',
            filters: { filterDate, filterLogin, filterStatus }
        });

        try {
            const response = await fetch(myCabinetMessagesData.ajaxUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString(),
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const result = await response.json();
            console.log('Ответ от сервера:', result);

            if (result.success && result.data && result.data.dialogs) {
                allDialogs = result.data.dialogs;
                console.log('Диалоги загружены:', allDialogs.length);
                renderDialogs(allDialogs);
                updateUnreadNotice();
            } else {
                const errorMsg = result.data?.message || 'Неизвестная ошибка';
                console.error('Ошибка загрузки диалогов:', errorMsg);
                if (messagesList) {
                    messagesList.innerHTML = `<tr><td colspan="6" class="error">Ошибка: ${errorMsg}</td></tr>`;
                }
            }
        } catch (error) {
            console.error('Ошибка при загрузке диалогов:', error);
            if (messagesList) {
                messagesList.innerHTML = `<tr><td colspan="6" class="error">Ошибка сети: ${error.message}</td></tr>`;
            }
        }
    }

    /**
     * Отрисовка таблицы заявок на бронирование
     */
    function renderDialogs(dialogs) {
        const tbody = document.getElementById('messages-list');
        
        if (dialogs.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="loading">Нет заявок</td></tr>';
            return;
        }

        // Статусы для отображения
        const statusLabels = {
            'pending': 'Ожидание',
            'new': 'Новая заявка',
            'in_progress': 'В процессе',
            'confirmed': 'Подтверждена',
            'completed': 'Завершена',
            'cancelled': 'Отменена'
        };

        tbody.innerHTML = dialogs.map(dialog => {
            console.log('Booking:', dialog); // Debug
            
            const statusLabel = statusLabels[dialog.status] || dialog.status;
            const statusClass = 'status-' + dialog.status;
            const hasUnread = dialog.unread_count > 0;
            const rowClass = hasUnread ? 'unread-row' : '';
            const newMessageBadge = hasUnread ? `<span class="new-message-badge">!Новое</span>` : '';
            
            return `
            <tr data-thread-id="${dialog.thread_id}" class="${rowClass}">
                <td class="col-id">
                    ${dialog.booking_id}
                    ${newMessageBadge}
                </td>
                <td class="col-login">
                    <a href="${window.location.pathname}?page=booking-messages&view=dialog&thread_id=${dialog.thread_id}" class="login-link">
                        ${dialog.client_login}
                    </a>
                </td>
                <td class="col-property">${dialog.property_title}</td>
                <td class="col-dates">
                    ${dialog.checkin_date ? new Date(dialog.checkin_date).toLocaleDateString('ru-RU') : '—'} / 
                    ${dialog.checkout_date ? new Date(dialog.checkout_date).toLocaleDateString('ru-RU') : '—'}
                </td>
                <td class="col-guests">${dialog.guests_text || '—'}</td>
                <td class="col-status"><span class="status-badge ${statusClass}">${statusLabel}</span></td>
                <td class="col-date">${dialog.created_date}</td>
                <td class="col-actions">
                    <a href="${window.location.pathname}?page=booking-messages&view=dialog&thread_id=${dialog.thread_id}" class="action-link action-view">Просмотр</a> | 
                    <a href="#" class="action-link action-delete" onclick="deleteBooking(${dialog.booking_id}, event); return false;">Удалить</a>
                </td>
            </tr>
            `;
        }).join('');
    }

    /**
     * Обновление уведомления о непрочитанных
     */
    function updateUnreadNotice() {
        const totalUnread = allDialogs.reduce((sum, dialog) => sum + dialog.unread_count, 0);
        const notice = document.querySelector('.unread-notice');
        const count = document.querySelector('.unread-count');
        
        if (!notice || !count) {
            console.warn('Элементы уведомления о непрочитанных не найдены');
            return;
        }

        if (totalUnread > 0) {
            notice.style.display = 'block';
            count.textContent = totalUnread;
        } else {
            notice.style.display = 'none';
        }
    }

    /**
     * Очистка фильтров
     */
    function clearFilters() {
        const filterDateEl = document.getElementById('filter-date');
        const filterLoginEl = document.getElementById('filter-login');
        const filterStatusEl = document.getElementById('filter-status');

        if (filterDateEl) filterDateEl.value = '';
        if (filterLoginEl) filterLoginEl.value = '';
        if (filterStatusEl) filterStatusEl.value = '';
        
        loadDialogs();
    }

    /**
     * Удаление заявки на бронирование
     */
    window.deleteBooking = async function(bookingId, event) {
        if (event) event.stopPropagation();
        
        if (!confirm('Удалить эту заявку на бронирование? Это действие нельзя отменить.')) {
            return;
        }

        const body = new URLSearchParams();
        body.append('action', 'my_cabinet_delete_booking');
        body.append('nonce', myCabinetMessagesData.deleteMessageNonce);
        body.append('booking_id', bookingId);

        try {
            const response = await fetch(myCabinetMessagesData.ajaxUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString(),
            });

            const result = await response.json();

            if (result.success) {
                alert(result.data.message);
                loadDialogs();
            } else {
                alert(result.data.message);
            }
        } catch (error) {
            console.error('Ошибка удаления:', error);
            alert('Ошибка сети');
        }
    };

})();
