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
        $('.my-message-section').scrollIntoView({ behavior: 'smooth', block: 'start' });
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