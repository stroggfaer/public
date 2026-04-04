jQuery(document).ready(function($) {
    // =============================================================
    // Константы и основные селекторы для архива недвижимости
    // =============================================================
    const AJAX_URL = propertyFilter?.ajaxurl || '/wp-admin/admin-ajax.php';
    const NONCE    = propertyFilter?.nonce    || '';

    const SELECTORS = {
        locationInput:    '.location-autocomplete',
        locationGroup:    '.location-group',
        locationSlug:     '#filter-location-slug',
        suggestions:      '.location-suggestions',
        suggestion:       '.location-suggestion',
        propertyList:     '.property-list',
        propertyCard:     '.property-card',
        mapContainer:     '#property-map',
        archiveForm:      '#property-archive-filter-form',  // Форма фильтрации на странице архива
        resetBtn:         '.filter-reset',
        paginationLinks:  '.archive-pagination a',         // Ссылки пагинации
        dateRange:        '#daterange',
        checkin:          '#filter-checkin',
        checkout:         '#filter-checkout',
        adults:           '#filter-adults',
        children:         '#filter-children',
        priceMin:         '#filter-price-min',
        priceMax:         '#filter-price-max'
    };

    // =============================================================
    // Глобальные переменные карты
    // =============================================================
    let map     = null;
    let markers = [];

    // =============================================================
    // Автокомплит локации
    // =============================================================
    let keyboardFocus = -1;

    function hideLocationSuggestions() {
        $(SELECTORS.suggestions).remove();
        keyboardFocus = -1;
    }

    function renderLocationSuggestions(locations) {
        const icon = $('.input-group__com').data('name-icon') || '';

        let html = '<div class="location-suggestions">';
        if (!locations?.length) {
            html += '<div class="location-suggestion no-results">Ничего не найдено</div>';
        } else {
            locations.forEach(loc => {
                html += '<div class="location-suggestion" ' +
                    `data-value="${loc.slug}" ` +
                    `data-location-value="${loc.name}">`;
                if (icon) {
                    html += `<span class="icon material-symbols-outlined">${icon}</span>`;
                }
                html += `${loc.name}</div>`;
            });
        }
        html += '</div>';

        hideLocationSuggestions();
        $(SELECTORS.locationGroup).append(html);
    }

    function fetchLocationSuggestions(term) {
        if (term.length < 2) {
            hideLocationSuggestions();
            return;
        }

        $.ajax({
            url: AJAX_URL,
            type: 'POST',
            data: {
                action: 'get_locations_autocomplete',
                term: term,
                nonce: NONCE
            },
            success(response) {
                if (response?.success) {
                    renderLocationSuggestions(response.data);
                }
            }
        });
    }

    // События поля автокомплита
    $(SELECTORS.locationInput)
        .attr('autocomplete', 'off')
        .on('input', function() {
            fetchLocationSuggestions($(this).val().trim());
        })
        .on('focus', function() {
            const val = $(this).val().trim();
            if (val.length >= 2) fetchLocationSuggestions(val);
        })
        .on('blur', () => setTimeout(hideLocationSuggestions, 200))
        .on('keydown', function(e) {
            const $items = $(SELECTORS.suggestion);
            if (!$items.length) return;

            if (e.which === 40) {         // Arrow Down
                e.preventDefault();
                keyboardFocus++;
            } else if (e.which === 38) {  // Arrow Up
                e.preventDefault();
                keyboardFocus--;
            } else if (e.which === 13) {  // Enter
                e.preventDefault();
                if (keyboardFocus >= 0 && !$items.eq(keyboardFocus).hasClass('no-results')) {
                    const $active = $items.eq(keyboardFocus);
                    $(SELECTORS.locationInput).val($active.data('location-value'));
                    $(SELECTORS.locationSlug).val($active.data('value'));
                    hideLocationSuggestions();
                }
                return;
            } else {
                return;
            }

            keyboardFocus = Math.max(0, Math.min($items.length - 1, keyboardFocus));
            $items.removeClass('autocomplete-active');
            $items.eq(keyboardFocus).addClass('autocomplete-active');
        });

    // Клик по подсказке (делегирование)
    $(document).on('click', SELECTORS.suggestion, function() {
        if ($(this).hasClass('no-results')) return;
        $(SELECTORS.locationInput).val($(this).data('location-value'));
        $(SELECTORS.locationSlug).val($(this).data('value'));
        hideLocationSuggestions();
    });

    // =============================================================
    // Фильтрация объектов на странице архива (AJAX)
    // =============================================================
    function applyFilters(formData, page = 1) {
        $.ajax({
            url: AJAX_URL,
            type: 'POST',
            data: formData + `&action=filter_properties_custom&nonce=${NONCE}&page=${page}`,
            beforeSend() {
                $(SELECTORS.propertyList).html(
                    '<div class="loading-indicator" style="display:block;"><p>Загрузка результатов...</p></div>'
                );
            },
            success(data) {
                if (data?.success) {
                    $(SELECTORS.propertyList).html(data.data.html || '<p>Недвижимость не найдена.</p>');
                    
                    // Обновляем пагинацию, если она есть
                    if (data.data.pagination) {
                        $('.archive-pagination').html(data.data.pagination);
                    } else {
                        $('.archive-pagination').empty();
                    }
                    
                    // Обновляем общее количество результатов
                    if (data.data.total !== undefined) {
                        updateResultsInfo(data.data.total);
                    }
                    
                    setTimeout(initMap, 120);
                } else {
                    $(SELECTORS.propertyList).html('<p>Ошибка при загрузке результатов: ' + (data?.data || '—') + '</p>');
                }
            },
            error() {
                $(SELECTORS.propertyList).html('<p>Произошла ошибка.</p>');
            }
        });
    }

    // Обновление информации о результатах
    function updateResultsInfo(totalCount) {
        let resultsHtml = '';
        if (totalCount > 0) {
            const obj = declension(['объект', 'объекта', 'объектов'], totalCount);
            resultsHtml = `<p>Всего ${totalCount} ${obj.text}</p>`;
        } else {
            resultsHtml = '<p>Недвижимость не найдена.</p>';
        }
        
        $('.results-info').html(resultsHtml);
    }

    // Обработка отправки формы фильтрации
    $(SELECTORS.archiveForm).on('submit', function(e) {
        e.preventDefault();
        
        // Добавляем страницу 1 при новом поиске
        const formData = $(this).serialize() + '&page=1';
        applyFilters(formData, 1);
    });

    // Сброс фильтра
    $(SELECTORS.resetBtn).on('click', function(e) {
        e.preventDefault();
        $(SELECTORS.archiveForm)[0]?.reset();
        
        // Также очищаем скрытое поле location slug
        $(SELECTORS.locationSlug).val('');
        $(SELECTORS.locationInput).val('');
        
        // Применяем фильтры с пустыми значениями (получаем все объекты)
        const formData = $(SELECTORS.archiveForm).serialize() + '&page=1';
        applyFilters(formData, 1);
    });

    // Обработка кликов по ссылкам пагинации
    $(document).on('click', SELECTORS.paginationLinks, function(e) {
        e.preventDefault();
        
        const pageUrl = $(this).attr('href');
        const pageMatch = pageUrl.match(/\/page\/(\d+)/);
        const page = pageMatch ? parseInt(pageMatch[1]) : 1;
        
        // Собираем текущие значения фильтров
        const formData = $(SELECTORS.archiveForm).serialize() + `&page=${page}`;
        applyFilters(formData, page);
    });

    // =============================================================
    // Карта и маркеры
    // =============================================================
    function initMap() {
        if (typeof L === 'undefined' || !$(SELECTORS.mapContainer).length) return;

        // Если карта уже инициализирована, уничтожаем её
        if (map) {
            map.remove();
            map = null;
        }

        map = L.map('property-map').setView([55.7558, 37.6173], 10);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        refreshMarkers();
    }

    function refreshMarkers() {
        if (!map) return;

        // Очистка старых маркеров
        markers.forEach(marker => map.removeLayer(marker));
        markers = [];

        // Добавление новых
        $(SELECTORS.propertyCard + '[data-lat][data-lng]').each(function() {
            const $card = $(this);
            const lat = parseFloat($card.data('lat'));
            const lng = parseFloat($card.data('lng'));
            const id  = $card.data('id');

            if (isNaN(lat) || isNaN(lng)) return;

            const marker = L.marker([lat, lng]).addTo(map);

            marker.on('click', () => {
                $(SELECTORS.propertyCard).removeClass('highlighted');
                const $target = $(`${SELECTORS.propertyCard}[data-id="${id}"]`);
                if ($target.length) {
                    $target.addClass('highlighted');
                    $('html, body').animate({
                        scrollTop: $target.offset().top - 100
                    }, 500);
                }
            });

            markers.push(marker);
        });
    }

    // Клик по кнопке карты → подсветка и центрирование маркера
    $(document).on('click', '.js-map-location', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        if (!map) return;

        const $btn = $(this);
        const id = $btn.data('id');
        const lat = parseFloat($btn.data('lat'));
        const lng = parseFloat($btn.data('lng'));

        if (isNaN(lat) || isNaN(lng)) return;

        // Убираем активный класс у всех кнопок карты
        $('.js-map-location').removeClass('active');
        // Добавляем активный класс текущей кнопке
        $btn.addClass('active');

        markers.forEach(m => $(m._icon).removeClass('leaflet-marker-highlighted'));

        markers.forEach(m => {
            const pos = m.getLatLng();
            if (Math.abs(pos.lat - lat) < 0.0001 && Math.abs(pos.lng - lng) < 0.0001) {
                $(m._icon).addClass('leaflet-marker-highlighted');
                map.setView([lat, lng], 14);
            }
        });
    });

    // Запуск карты с небольшой задержкой
    setTimeout(initMap, 600);

    // =============================================================
    // Date Range Picker (если подключен)
    // =============================================================
    if ($(SELECTORS.dateRange).length && typeof moment !== 'undefined' && $.fn.daterangepicker) {
        // Сокращения месяцев на русском
        const monthNamesShort = ['янв', 'февр', 'март', 'апр', 'май', 'июнь', 'июль', 'авг', 'сент', 'окт', 'нояб', 'дек'];

        // Функция форматирования даты в русском формате
        function formatDateRU(momentDate) {
            const day = momentDate.format('D');
            const month = monthNamesShort[momentDate.month()];
            return day + ' ' + month;
        }

        // Функция формирования красивой строки диапазона
        function formatDateRange(start, end) {
            if (!start || !end) return '';

            const startMonth = start.month();
            const endMonth = end.month();

            // Если один месяц: "5 – 12 март"
            if (startMonth === endMonth) {
                return start.format('D') + ' – ' + end.format('D') + ' ' + monthNamesShort[startMonth];
            }
            // Разные месяцы: "19 февр. – 21 февр."
            return formatDateRU(start) + '. – ' + formatDateRU(end) + '.';
        }

        // Функция обновления видимого поля
        function updateDateRangeInput(start, end) {
            const $input = $(SELECTORS.dateRange);
            if (start && end) {
                $input.val(formatDateRange(start, end));
            } else {
                $input.val('');
            }
        }

        // Функция инициализации daterangepicker
        function initDateRangePicker() {
            // Проверяем значения в hidden полях
            const checkinVal  = $(SELECTORS.checkin).val();
            const checkoutVal = $(SELECTORS.checkout).val();

            let startDate, endDate;

            if (checkinVal && checkoutVal) {
                startDate = moment(checkinVal);
                endDate = moment(checkoutVal);
            } else {
                // Значения по умолчанию - завтра и послезавтра
                startDate = moment().add(1, 'day');
                endDate = moment().add(2, 'day');
            }

            $(SELECTORS.dateRange).daterangepicker({
                locale: {
                    format: 'YYYY-MM-DD',
                    separator: ' - ',
                    applyLabel: 'Применить',
                    cancelLabel: 'Отмена',
                    fromLabel: 'С',
                    toLabel: 'По',
                    customRangeLabel: 'Свой диапазон',
                    weekLabel: 'Н',
                    daysOfWeek: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
                    monthNames: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь']
                },
                startDate: startDate,
                endDate: endDate,
                minDate: moment(),
                opens: 'center',
                autoUpdateInput: false
            }, function(start, end) {
                // Обновляем hidden поля
                $(SELECTORS.checkin).val(start.format('YYYY-MM-DD'));
                $(SELECTORS.checkout).val(end.format('YYYY-MM-DD'));
                // Обновляем видимое поле
                updateDateRangeInput(start, end);
            });

            // Если даты уже выбраны - показываем их
            if (checkinVal && checkoutVal) {
                updateDateRangeInput(startDate, endDate);
            } else {
                // Показываем placeholder
                $(SELECTORS.dateRange).attr('placeholder', 'В любое время');
            }
        }

        // Обработка кнопки "Отмена" - сброс выбора
        $(SELECTORS.dateRange).on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
            $(SELECTORS.checkin).val('');
            $(SELECTORS.checkout).val('');
            $(this).attr('placeholder', 'В любое время');
        });

        // Инициализация
        initDateRangePicker();
    }


    // =============================================================
    // Счётчик гостей — переиспользуемый компонент
    // =============================================================
    function initGuestCounter() {

        // Находим все блоки с классом .guest-input
        $('.guest-input').each(function() {
            const $container = $(this);
            const $input     = $container.find('input[type="text"][readonly]');
            const $decrease  = $container.find('.guest-decrease');
            const $increase  = $container.find('.guest-increase');

            // Минимальное и максимальное значение (можно переопределять через data-атрибуты)
            let min = $container.data('min') || 0;
            let max = $container.data('max') || 20;

            // Для взрослых почти всегда min = 1
            if ($input.attr('name') === 'adults') {
                min = 1;
            }

            // Текущее значение
            let value = parseInt($input.val()) || min;

            // Функция обновления значения и UI
            function updateValue(newVal) {
                if (newVal < min) newVal = min;
                if (newVal > max) newVal = max;

                value = newVal;
                $input.val(value);

                // Отключаем кнопку -, если достигли минимума
                $decrease.prop('disabled', value <= min);
                // Отключаем кнопку +, если достигли максимума
                $increase.prop('disabled', value >= max);

                // Обновляем общее отображение гостей
                updateGuestsPlaceholder();
            }

            // Обработчики кликов
            $decrease.on('click', function() {
                updateValue(value - 1);
            });

            $increase.on('click', function() {
                updateValue(value + 1);
            });

            // Инициализация состояния кнопок при загрузке
            updateValue(value);
        });
    }

    // =============================================================
    // Обновление текста в поле "Гости" (3 гостя, 2 взрослых, 1 ребёнок и т.д.)
    // =============================================================
    function updateGuestsPlaceholder() {
        const adults   = parseInt($('input[name="adults"]').val())   || 0;
        const children = parseInt($('input[name="children"]').val()) || 0;
        const total    = adults + children;

        let text = 'Гости';

        if (total > 0) {
            const  objTotal = declension(['гостей', 'гость', 'гостя'], total);
            text = `${objTotal.count} ${objTotal.text}`;
        }

        $('.js-guests-val').val(text);
    }

    // Функция склонения числительных
    function declension(forms, number) {
        const count = Math.abs(number);
        const cases = [2, 0, 1, 1, 1, 2];
        const chosenCase = (count % 100 > 4 && count % 100 < 20) ? 2 : cases[(count % 10) < 5 ? count % 5 : 5];
        
        return {
            count: number,
            text: forms[chosenCase]
        };
    }

    // =============================================================
    // Запуск
    // =============================================================
    initGuestCounter();
    updateGuestsPlaceholder();   // начальное значение

    // При изменении любым способом (в т.ч. если где-то ещё поменяют)
    $(document).on('change input', 'input[name="adults"], input[name="children"]', function() {
        updateGuestsPlaceholder();
    });
});