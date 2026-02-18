jQuery(document).ready(function($) {
    // =============================================================
    // Константы и основные селекторы
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
        mainForm:         '#property-filter-form',
        homeForm:         '#property-home-filter-form',
        resetBtn:         '.filter-reset',
        dateRange:        '#daterange',
        checkin:          '#filter-checkin',
        checkout:         '#filter-checkout',
        adults:           '#filter-adults',
        children:         '#filter-children'
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
    // Фильтрация объектов (основная форма)
    // =============================================================
    $(SELECTORS.mainForm).on('submit', function(e) {
        e.preventDefault();

        const formData = $(this).serialize();

        $.ajax({
            url: AJAX_URL,
            type: 'POST',
            data: formData + '&action=filter_properties_custom&nonce=' + NONCE,
            beforeSend() {
                $(SELECTORS.propertyList).html(
                    '<div class="loading-indicator" style="display:block;"><p>Загрузка результатов...</p></div>'
                );
            },
            success(data) {
                if (data?.success) {
                    $(SELECTORS.propertyList).html(data.data.properties_html);
                    setTimeout(initMap, 120);
                } else {
                    $(SELECTORS.propertyList).html('<p>Ошибка при загрузке результатов: ' + (data?.data || '—') + '</p>');
                }
            },
            error() {
                $(SELECTORS.propertyList).html('<p>Произошла ошибка.</p>');
            }
        });
    });

    // Сброс фильтра
    $(SELECTORS.resetBtn).on('click', function(e) {
        e.preventDefault();
        $(SELECTORS.mainForm)[0]?.reset();
        $(SELECTORS.mainForm).trigger('submit');
    });

    // =============================================================
    // Карта и маркеры
    // =============================================================
    function initMap() {
        if (typeof L === 'undefined' || !$(SELECTORS.mapContainer).length) return;

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

    // Клик по карточке → подсветка и центрирование маркера
    $(document).on('click', SELECTORS.propertyCard, function() {
        if (!map) return;

        const lat = parseFloat($(this).data('lat'));
        const lng = parseFloat($(this).data('lng'));

        if (isNaN(lat) || isNaN(lng)) return;

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
    // Валидация формы на главной странице
    // =============================================================
    $(SELECTORS.homeForm).on('submit', function(e) {
        const checkinDate  = $(SELECTORS.checkin).val();
        const checkoutDate = $(SELECTORS.checkout).val();
        const adults   = parseInt($('input[name="adults"]').val()   || 0, 10);
        const children = parseInt($('input[name="children"]').val() || 0, 10);

        const today = new Date();
        today.setHours(0, 0, 0, 0);

        // Проверка дат
        if (checkinDate && checkoutDate) {
            const checkin  = new Date(checkinDate);
            const checkout = new Date(checkoutDate);
            if (checkin > checkout) {
                e.preventDefault();
                alert('Дата заезда не может быть позже даты выезда.');
                return;
            }
        }

        if (checkinDate) {
            const checkin = new Date(checkinDate);
            if (checkin < today) {
                e.preventDefault();
                alert('Дата заезда не может быть в прошлом.');
                return;
            }
        }

        // Проверка количества гостей
        if (isNaN(adults) || adults < 1 || adults > 10) {
            e.preventDefault();
            alert('Количество взрослых должно быть от 1 до 10.');
            return;
        }

        if (isNaN(children) || children < 0 || children > 8) {
            e.preventDefault();
            alert('Количество детей должно быть от 0 до 8.');
            return;
        }
    });

    // =============================================================
    // Date Range Picker (если подключен)
    // =============================================================
    if ($(SELECTORS.dateRange).length && typeof moment !== 'undefined' && $.fn.daterangepicker) {
        $(SELECTORS.dateRange).daterangepicker({
            locale: {
                format: 'YYYY-MM-DD',
                separator: ' - ',
                applyLabel: "Применить",
                cancelLabel: "Отмена",
                fromLabel: "С",
                toLabel: "По",
                customRangeLabel: "Свой диапазон",
                weekLabel: "Н",
                daysOfWeek: ["Вс", "Пн", "Вт", "Ср", "Чт", "Пт", "Сб"],
                monthNames: ["Январь", "Февраль", "Март", "Апрель", "Май", "Июнь", "Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь"]
            },
            startDate: moment().add(1, 'day'),
            endDate: moment().add(2, 'day'),
            minDate: moment(),
            opens: 'center'
        }, function(start, end) {
            $(SELECTORS.checkin).val(start.format('YYYY-MM-DD'));
            $(SELECTORS.checkout).val(end.format('YYYY-MM-DD'));
        });
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