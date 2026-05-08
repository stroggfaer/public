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
        const content = textarea.value.trim();

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

})();
