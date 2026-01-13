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
});
document.addEventListener('DOMContentLoaded', function () {
    const menu = document.querySelector('.menu');
    const toggle = document.querySelector('.menu-toggle');

    toggle.addEventListener('click', () => {
        menu.classList.toggle('active');

        // Переключение значка
        if (menu.classList.contains('active')) {
            toggle.textContent = '×'; // Крестик
            toggle.setAttribute('aria-label', 'Закрыть меню');
        } else {
            toggle.textContent = '☰'; // Гамбургер
            toggle.setAttribute('aria-label', 'Открыть меню');
        }
    });
});

