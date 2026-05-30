jQuery(document).ready(function($) {
    // Добавляем стрелки для пунктов с подкатегориями
    // Скрыть подкатегории по умолчанию
    const is_parent_active = $('.menu-item.parent-active ul').length;
    if(!is_parent_active) {
        $('.menu-item ul').hide();
    } else {
        $('.toggle-arrow').toggleClass('open');
    }

    // Событие клика на стрелке
    $('.toggle-arrow').on('click', function(e) {
        e.preventDefault(); // Останавливаем действия по умолчанию
        var $submenu = $(this).siblings('ul');
        if ($submenu.length) {
            $submenu.slideToggle(); // Раскрываем/скрываем подкатегории
            $(this).toggleClass('open'); // Меняем стиль стрелки
        }
    });

    $(document).on('click','.my-btn-write-host', function(e) {
        e.preventDefault(); // Останавливаем действия по умолчанию
        const element = $('.my-message-section')[0];
        if (element) {
            element.scrollIntoView({ behavior: 'smooth', block: 'start' });
            
            // Фокус на textarea после завершения скролла
            setTimeout(() => {
                if (typeof window.focusMessageTextarea === 'function') {
                    window.focusMessageTextarea();
                }
            }, 500);
        }
    });

    // ============================================================
    // Избранное (Favorites)
    // ============================================================

    /**
     * Обработчик клика на кнопку избранного
     */
    function handleFavoriteClick(e) {
        e.preventDefault();

        const btn = e.target.closest('.js-favorite');
        if (!btn) return;

        const propertyId = btn.dataset.propertyId;
        const isLoggedIn = window.RealtyData?.isLoggedIn || false;
        const ajaxUrl = window.RealtyData?.ajaxUrl || '/wp-admin/admin-ajax.php';
        const nonce = window.RealtyData?.favoriteNonce || '';

        // Если не авторизован — показать dialog
        if (!isLoggedIn) {
            if (typeof ElementPlus !== 'undefined' && ElementPlus.ElMessageBox) {
                ElementPlus.ElMessageBox.confirm(
                    'Для добавления в избранное необходимо войти',
                    'Авторизация',
                    {
                        confirmButtonText: 'Войти',
                        cancelButtonText: 'Отмена',
                        type: 'warning'
                    }
                ).then(() => {
                    window.location.href = '/my-auth/?redirect_to=' + encodeURIComponent(window.location.href);
                }).catch(() => {
                    // Пользователь отменил
                });
            } else {
                // Fallback без Element Plus
                if (confirm('Для добавления в избранное необходимо войти. Перейти на страницу входа?')) {
                    window.location.href = '/my-auth/?redirect_to=' + encodeURIComponent(window.location.href);
                }
            }
            return;
        }

        // Блокируем кнопку на время запроса
        if (btn.tagName.toLowerCase() === 'button') {
            btn.disabled = true;
        } else {
            // Для div и других элементов используем класс loading
            btn.classList.add('loading');
        }
        btn.style.opacity = '0.5';

        // AJAX toggle запрос
        const formData = new FormData();
        formData.append('action', 'toggle_favorite');
        formData.append('property_id', propertyId);
        formData.append('nonce', nonce);

        fetch(ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Если на странице /my/favorites/ — reload
                if (window.location.pathname.includes('/my/favorites/')) {
                    window.location.reload();
                } else {
                    // На других страницах — обновляем иконку
                    const iconSpan = btn.querySelector('.material-symbols-outlined');
                    if (data.data.is_favorite) {
                        btn.classList.add('active');
                        if (iconSpan) iconSpan.textContent = 'favorite';
                    } else {
                        btn.classList.remove('active');
                        if (iconSpan) iconSpan.textContent = 'favorite_border';
                    }
                }
            } else {
                console.error('Favorite toggle error:', data.data?.message);
            }
        })
        .catch(error => {
            console.error('Favorite toggle error:', error);
        })
        .finally(() => {
            // Разблокируем кнопку
            if (btn.tagName.toLowerCase() === 'button') {
                btn.disabled = false;
            } else {
                // Для div и других элементов убираем класс loading
                btn.classList.remove('loading');
            }
            btn.style.opacity = '';
        });
    }

    // Инициализация обработчиков для существующих кнопок
    document.addEventListener('click', function(e) {
        if (e.target.closest('.js-favorite')) {
            handleFavoriteClick(e);
        }
    });

    // ============================================================
    // Поделиться (Share)
    // ============================================================

    /**
     * Обработчик клика на кнопку "Поделиться"
     */
    function handleShareClick(e) {
        e.preventDefault();

        const btn = e.target.closest('.js-share');
        if (!btn) return;

        const shareData = {
            title: document.title,
            url: window.location.href
        };

        // Проверяем поддержку Web Share API
        if (navigator.share) {
            navigator.share(shareData)
                .then(() => {
                    console.log('Успешно поделились');
                })
                .catch((error) => {
                    if (error.name !== 'AbortError') {
                        console.error('Ошибка при попытке поделиться:', error);
                        // Fallback: копируем URL в буфер обмена
                        copyToClipboard(window.location.href);
                    }
                });
        } else {
            // Fallback для браузеров без поддержки Web Share API
            copyToClipboard(window.location.href);
        }
    }

    /**
     * Копирует текст в буфер обмена и показывает уведомление
     */
    function copyToClipboard(text) {
        // Проверяем поддержку Clipboard API
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text)
                .then(() => {
                    // Показываем уведомление о успешном копировании
                    showShareNotification('Ссылка скопирована в буфер обмена');
                })
                .catch(err => {
                    console.error('Не удалось скопировать:', err);
                    // Fallback для старых браузеров
                    fallbackCopyToClipboard(text);
                });
        } else {
            // Clipboard API не поддерживается, используем fallback
            fallbackCopyToClipboard(text);
        }
    }

    /**
     * Fallback метод копирования для старых браузеров
     */
    function fallbackCopyToClipboard(text) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        document.body.appendChild(textArea);
        textArea.select();
        
        try {
            document.execCommand('copy');
            showShareNotification('Ссылка скопирована в буфер обмена');
        } catch (err) {
            console.error('Не удалось скопировать:', err);
            showShareNotification('Не удалось скопировать ссылку', 'error');
        }
        
        document.body.removeChild(textArea);
    }

    /**
     * Показывает уведомление о результате операции "Поделиться"
     */
    function showShareNotification(message, type = 'success') {
        // Проверяем наличие ElementPlus
        if (typeof ElementPlus !== 'undefined' && ElementPlus.ElMessage) {
            ElementPlus.ElMessage({
                message: message,
                type: type === 'error' ? 'error' : 'success',
                duration: 2000
            });
        } else {
            // Fallback: простое alert
            alert(message);
        }
    }

    // Инициализация обработчиков для кнопки "Поделиться"
    document.addEventListener('click', function(e) {
        if (e.target.closest('.js-share')) {
            handleShareClick(e);
        }
    });
});



document.addEventListener('DOMContentLoaded', function () {
    // Проверяем существует ли элемент thumbnail-slider на странице
    if (!document.getElementById('thumbnail-slider')) {
        return; // Если элемента нет, выходим
    }

    const thumbnails = new Splide('#thumbnail-slider', {
        fixedWidth: 150,      // Ширина миниатюры
        fixedHeight: 100,     // Высота миниатюры
        gap: 10,              // Отступ между ними
        rewind: true,
        pagination: false,
        isNavigation: true,   // Делает слайды кликабельными
        direction: 'ttb',     // Вертикальное направление
        height: '500px',      // Общая высота блока (подстройте под основной слайдер)
        arrows: true,         // Стрелки вверх/вниз
        breakpoints: {
            768: {
                fixedWidth: 100,
                fixedHeight: 70,
                direction: 'ltr', // На мобильных лучше сделать горизонтально
                height: 'auto',
            },
        },
    });

    // 2. Инициализация основного слайдера
    const mainSlider = new Splide('#main-slider', {
        type: 'fade',      // Плавный переход
        rewind: true,
        pagination: false,
        arrows: false,     // Стрелки можно скрыть, если управление через миниатюры
    });
    mainSlider.sync(thumbnails);
    mainSlider.mount();
    thumbnails.mount();
});

// ============================================================
// Уведомления в шапке: обновление счетчика непрочитанных сообщений
// ============================================================
(function() {
    'use strict';

    // Проверяем, есть ли элемент уведомлений на странице
    const notificationCounter = document.querySelector('[data-unread-count]');
    if (!notificationCounter) {
        return; // Элеента нет, выходим
    }

    // Получаем данные из RealtyData (должны быть локализованы в functions.php)
    const ajaxUrl = window.RealtyData?.ajaxUrl || '/wp-admin/admin-ajax.php';
    
    /**
     * Загружаем количество непрочитанных сообщений через AJAX
     */
    function loadUnreadCount() {
        const formData = new FormData();
        formData.append('action', 'my_cabinet_get_unread_count');

        fetch(ajaxUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && typeof data.data.unread_count !== 'undefined') {
                updateCounter(data.data.unread_count);
            }
        })
        .catch(error => {
            console.error('Ошибка загрузки счетчика уведомлений:', error);
        });
    }

    /**
     * Обновляем счетчик на странице
     * @param {number} count - количество непрочитанных сообщений
     */
    function updateCounter(count) {
        const counter = document.querySelector('[data-unread-count]');
        if (!counter) return;

        const countNum = parseInt(count) || 0;
        
        // Обновляем текст
        counter.textContent = countNum > 99 ? '99+' : countNum;
        
        // Управляем видимостью
        if (countNum > 0) {
            counter.style.display = 'block';
            counter.setAttribute('data-count', countNum);
            counter.setAttribute('data-count-active', '');
        } else {
            counter.style.display = 'none';
            counter.removeAttribute('data-count');
            counter.removeAttribute('data-count-active');
        }
    }

    // Загружаем сразу при загрузке страницы
    loadUnreadCount();

    // Обновляем каждые 30 секунд
    setInterval(loadUnreadCount, 30000);

    // Также обновляем при возвращении на вкладку (visibility change)
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            loadUnreadCount();
        }
    });

    // Обновляем при возвращении фокуса на окно
    window.addEventListener('focus', function() {
        loadUnreadCount();
    });

    // Обновляем счетчик когда Vue компонент отмечает сообщения как прочитанные
    window.addEventListener('messages-marked-read', function() {
        // Небольшая задержка чтобы БД успела обновиться
        setTimeout(loadUnreadCount, 500);
    });
})();



/**
 * Универсальная функция склонения для русского языка
 * @param {string[]} forms - массив из 3 вариантов: [мн.ч. род. падеж, им. падеж ед.ч., род. падеж ед.ч. после 2–4]
 *                           Пример: ['гостей', 'гость', 'гостя']
 * @param {number} n - число, для которого нужно склонение
 * @returns {{count: number, text: string}} объект с числом и правильной формой слова
 */
function declension(forms, n) {
    if (!Array.isArray(forms) || forms.length !== 3) {
        throw new Error('Ожидается массив из ровно 3 строк: [мн.ч., 1, 2-4]');
    }

    const num = Math.abs(Math.round(n)) || 0; // приводим к целому неотрицательному

    let form;

    if (num === 0) {
        form = forms[0]; // обычно множественное число для 0
    } else {
        const lastTwo = num % 100;
        const lastOne  = num % 10;

        if (lastTwo >= 11 && lastTwo <= 14) {
            form = forms[0];           // 11–14 гостей
        } else if (lastOne === 1) {
            form = forms[1];           // 1 гость, 21 гость...
        } else if (lastOne >= 2 && lastOne <= 4) {
            form = forms[2];           // 2 гостя, 23 гостя...
        } else {
            form = forms[0];           // 0,5,6,7,8,9,10,15,25... гостей
        }
    }

    return {
        count: num,
        text: form
    };
}