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
     * Загрузка списка диалогов
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
        const filterSubjectEl = document.getElementById('filter-subject');

        if (!filterDateEl || !filterLoginEl || !filterSubjectEl) {
            console.error('Не найдены элементы фильтров');
            return;
        }

        const filterDate = filterDateEl.value;
        const filterLogin = filterLoginEl.value;
        const filterSubject = filterSubjectEl.value;

        const messagesList = document.getElementById('messages-list');
        if (messagesList) {
            messagesList.innerHTML = '<tr><td colspan="6" class="loading">Загрузка сообщений...</td></tr>';
        }

        const body = new URLSearchParams();
        body.append('action', 'my_cabinet_get_dialogs');
        body.append('nonce', myCabinetMessagesData.getDialogsNonce);
        body.append('filter_date', filterDate);
        body.append('filter_login', filterLogin);
        body.append('filter_subject', filterSubject);

        console.log('Отправка AJAX запроса:', {
            url: myCabinetMessagesData.ajaxUrl,
            action: 'my_cabinet_get_dialogs',
            filters: { filterDate, filterLogin, filterSubject }
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
     * Отрисовка таблицы диалогов
     */
    function renderDialogs(dialogs) {
        const tbody = document.getElementById('messages-list');
        
        if (dialogs.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="loading">Нет сообщений</td></tr>';
            return;
        }

        tbody.innerHTML = dialogs.map(dialog => {
            console.log('Dialog:', dialog); // Debug
            console.log('Thread ID:', dialog.thread_id); // Debug
            
            return `
            <tr class="${dialog.unread_count > 0 ? 'unread' : ''}" data-thread-id="${dialog.thread_id}">
                <td class="col-id">${dialog.last_message_id}</td>
                <td class="col-login">
                    <a href="${window.location.pathname}?page=booking-messages&view=dialog&thread_id=${dialog.thread_id}" class="login-link">
                        ${dialog.sender_login}
                    </a>
                </td>
                <td class="col-name">${dialog.sender_name}</td>
                <td class="col-subject">
                    ${dialog.last_subject}
                    ${dialog.unread_count > 0 ? `<span class="unread-badge">${dialog.unread_count}</span>` : ''}
                </td>
                <td class="col-date">${dialog.last_date}</td>
                <td class="col-actions">
                    <a href="${window.location.pathname}?page=booking-messages&view=dialog&thread_id=${dialog.thread_id}" class="button action-btn btn-view">Просмотр / Ответить</a>
                    <button type="button" class="button action-btn btn-delete" onclick="deleteDialog('${dialog.thread_id}', event)">Удалить</button>
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
        const filterSubjectEl = document.getElementById('filter-subject');

        if (filterDateEl) filterDateEl.value = '';
        if (filterLoginEl) filterLoginEl.value = '';
        if (filterSubjectEl) filterSubjectEl.value = '';
        
        loadDialogs();
    }

    /**
     * Удаление диалога
     */
    window.deleteDialog = async function(threadId, event) {
        if (event) event.stopPropagation();
        
        if (!confirm('Удалить весь диалог? Это действие нельзя отменить.')) {
            return;
        }

        const body = new URLSearchParams();
        body.append('action', 'my_cabinet_delete_dialog');
        body.append('nonce', myCabinetMessagesData.deleteMessageNonce);
        body.append('thread_id', threadId);

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
