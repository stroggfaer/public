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
// document.addEventListener('DOMContentLoaded', function () {
//     const menu = document.querySelector('.menu');
//     const toggle = document.querySelector('.menu-toggle');

//     toggle.addEventListener('click', () => {
//         menu.classList.toggle('active');

//         // Переключение значка
//         if (menu.classList.contains('active')) {
//             toggle.textContent = '×'; // Крестик
//             toggle.setAttribute('aria-label', 'Закрыть меню');
//         } else {
//             toggle.textContent = '☰'; // Гамбургер
//             toggle.setAttribute('aria-label', 'Открыть меню');
//         }
//     });
// });

// $(function () {
//
//     const $groups = $('.guests-group');
//
//     // Обработка инпута для гостей
//     $('.guest-increase').on('click', function() {
//         var target = $(this).data('target');
//         var input = $('#' + 'filter-' + target);
//         var currentVal = parseInt(input.val());
//         var maxVal = target === 'adults' ? 10 : 8;
//
//         if (!isNaN(currentVal) && currentVal < maxVal) {
//             input.val(currentVal + 1);
//         } else if (isNaN(currentVal)) {
//             input.val(1);
//         }
//     });
//
//     $('.guest-decrease').on('click', function() {
//         var target = $(this).data('target');
//         var input = $('#' + 'filter-' + target);
//         var currentVal = parseInt(input.val());
//         var minVal = target === 'adults' ? 1 : 0;
//
//         if (!isNaN(currentVal) && currentVal > minVal) {
//             input.val(currentVal - 1);
//         } else if (isNaN(currentVal)) {
//             input.val(minVal);
//         }
//     });
//
//     function getVal($el) {
//         return $el.length ? parseInt($el.val(), 10) || 0 : 0;
//     }
//
//     function updatePlaceholder($group) {
//         const adults = getVal($group.find('#filter-adults'));
//         const children = getVal($group.find('#filter-children'));
//
//         const total = adults + children;
//
//         $group.find('.guests-placeholder')
//             .val(total ? `Гостей: ${total}` : 'Гости');
//     }
//
//     $groups.each(function () {
//
//         const $group = $(this);
//         const $wrapper = $group.find('.guest-controls-wrapper');
//         const $placeholder = $group.find('.guests-placeholder');
//
//         /* открыть */
//         $placeholder.on('click', function (e) {
//             e.stopPropagation();
//             $wrapper.toggleClass('open');
//         });
//
//         /* кнопки */
//         $group.find('button[data-target]').on('click', function (e) {
//             e.preventDefault();
//             e.stopPropagation();
//
//             const target = $(this).data('target');
//             const $input = $group.find(`#filter-${target}`);
//
//             let value = getVal($input);
//
//             if ($(this).hasClass('guest-increase')) value++;
//             if ($(this).hasClass('guest-decrease')) value--;
//
//             value = target === 'adults'
//                 ? Math.max(1, value)
//                 : Math.max(0, value);
//
//             $input.val(value);
//
//             updatePlaceholder($group);
//         });
//
//         /* старт */
//         updatePlaceholder($group);
//     });
//
//     /* закрытие вне */
//     $(document).on('click', function () {
//         $('.guest-controls-wrapper').removeClass('open');
//     });
// });


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