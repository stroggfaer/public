jQuery(document).ready(function($) {
    // Отключаем автозаполнение для поля локации
    $('.location-autocomplete').attr('autocomplete', 'off');
    
    // Логика фильтрации
    $('#property-filter-form').on('submit', function(e) {
        e.preventDefault();

        var formData = $(this).serialize();

        $.ajax({
            url: propertyFilter.ajaxurl,
            type: 'POST',
            data: formData + '&action=filter_properties_custom&nonce=' + propertyFilter.nonce,
            beforeSend: function() {
                $('.property-list').html('<div class="loading-indicator" style="display: block;"><p>Загрузка результатов...</p></div>');
            },
            success: function(data) {
                if (data.success) {
                    $('.property-list').html(data.data.properties_html);
                    // Переинициализируем карту после загрузки новых результатов
                    setTimeout(initMap, 100);
                } else {
                    $('.property-list').html('<p>Ошибка при загрузке результатов: ' + data.data + '</p>');
                }
            },
            error: function() {
                $('.property-list').html('<p>Произошла ошибка.</p>');
            }
        });
    });

    $('.filter-reset').on('click', function(e) {
        e.preventDefault();
        $('#property-filter-form')[0].reset();
        $('#property-filter-form').trigger('submit');
    });

    // Инициализация карты
    var map = null;
    var markers = [];

    // Функция инициализации карты
    function initMap() {
        if (typeof L !== 'undefined' && $('#property-map').length) {
            // Инициализация карты
            map = L.map('property-map').setView([55.7558, 37.6173], 10); // По умолчанию Москва

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            // Добавляем метки на карту
            addMarkersToMap();
        }
    }

    // Функция для добавления меток на карту
    function addMarkersToMap() {
        if (!map) return;

        // Удаляем предыдущие метки
        markers.forEach(function(marker) {
            map.removeLayer(marker);
        });
        markers = [];

        // Добавляем новые метки
        $('.property-card[data-lat][data-lng]').each(function() {
            var $card = $(this);
            var lat = parseFloat($card.data('lat'));
            var lng = parseFloat($card.data('lng'));
            var id = $card.data('id');

            if (lat && lng) {
                var marker = L.marker([lat, lng]).addTo(map);

                // При клике на метку, выделяем карточку и скроллим к ней
                marker.on('click', function() {
                    // Снимаем выделение со всех карточек
                    $('.property-card').removeClass('highlighted');

                    // Выделяем нужную карточку
                    var $targetCard = $('.property-card[data-id="' + id + '"]');
                    if ($targetCard.length) {
                        $targetCard.addClass('highlighted');

                        // Плавный скролл к карточке
                        $('html, body').animate({
                            scrollTop: $targetCard.offset().top - 100
                        }, 500);
                    }
                });

                markers.push(marker);
            }
        });
    }

    // Инициализируем карту при загрузке страницы
    setTimeout(initMap, 500); // Даем время для загрузки Leaflet

    // При клике на карточку, активируем соответствующую метку
    $(document).on('click', '.property-card', function() {
        if (!map) return;

        var id = $(this).data('id');
        var lat = parseFloat($(this).data('lat'));
        var lng = parseFloat($(this).data('lng'));

        if (lat && lng) {
            // Снимаем выделение со всех меток
            markers.forEach(function(marker) {
                if (marker._icon) {
                    $(marker._icon).removeClass('leaflet-marker-highlighted');
                }
            });

            // Активируем нужную метку
            markers.forEach(function(marker) {
                if (Math.abs(marker.getLatLng().lat - lat) < 0.0001 &&
                    Math.abs(marker.getLatLng().lng - lng) < 0.0001) {
                    if (marker._icon) {
                        $(marker._icon).addClass('leaflet-marker-highlighted');
                    }
                    map.setView([lat, lng], 14);
                }
            });
        }
    });
    
    // Автокомплит для локации
    $('.location-autocomplete').on('keyup', function() {
        var input = $(this).val();
        if (input.length >= 2) {
            $.ajax({
                url: propertyFilter.ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_locations_autocomplete',
                    term: input,
                    nonce: propertyFilter.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Показываем результаты автокомплита
                        var suggestionsHtml = '<div class="location-suggestions">';
                        if (response.data.length > 0) {
                            response.data.forEach(function(location) {
                                suggestionsHtml += '<div class="location-suggestion" data-value="' + location.slug + '">' + location.name + '</div>';
                            });
                        } else {
                            suggestionsHtml += '<div class="location-suggestion no-results">Ничего не найдено</div>';
                        }
                        suggestionsHtml += '</div>';
                        
                        $('.location-suggestions').remove(); // Удаляем предыдущие результаты
                        $('.location-group').append(suggestionsHtml);
                        
                        // Обработка клика по варианту
                        $('.location-suggestion').off('click').on('click', function() {
                            var selectedValue = $(this).data('value');
                            var selectedText = $(this).text();
                            if (selectedValue) {
                               $('.location-autocomplete').val(selectedText);
                               $('#filter-location-slug').val(selectedValue);
                            }
                            $('.location-suggestions').remove();
                        });
                    }
                }
            });
        } else {
            $('.location-suggestions').remove();
        }
    });
    
    // Обработчик события focus для отображения подсказок при повторном фокусе
    $('.location-autocomplete').on('focus', function() {
        var input = $(this).val();
        if (input.length >= 2) {
            // Имитируем нажатие клавиши для вызова автозаполнения
            $(this).trigger('keyup');
        }
    });
    
    // Скрытие подсказок при потере фокуса
    $('.location-autocomplete').on('blur', function() {
        // Добавляем небольшую задержку, чтобы успеть кликнуть по подсказке
        setTimeout(function() {
            $('.location-suggestions').remove();
        }, 200);
    });
    
    // Обработка клавиш стрелок и Enter для выбора из списка
    var currentFocus = -1;
    $('.location-autocomplete').on('keydown', function(e) {
        var suggestions = $('.location-suggestion');
        if (suggestions.length === 0) return;
        
        if (e.which === 40) { // Arrow Down
            e.preventDefault();
            currentFocus++;
            addActive(suggestions);
        } else if (e.which === 38) { // Arrow Up
            e.preventDefault();
            currentFocus--;
            addActive(suggestions);
        } else if (e.which === 13) { // Enter
            e.preventDefault();
            if (currentFocus > -1) {
                if (suggestions.eq(currentFocus).hasClass('no-results')) {
                    return;
                }
                var selectedValue = suggestions.eq(currentFocus).data('value');
                var selectedText = suggestions.eq(currentFocus).text();
                $('.location-autocomplete').val(selectedText);
                $('#filter-location-slug').val(selectedValue);
                $('.location-suggestions').remove();
                currentFocus = -1;
            }
        }
    });
    
    function addActive(suggestions) {
        if (!suggestions) return false;
        removeActive(suggestions);
        if (currentFocus >= suggestions.length) currentFocus = 0;
        if (currentFocus < 0) currentFocus = (suggestions.length - 1);
        suggestions.eq(currentFocus).addClass('autocomplete-active');
    }
    
    function removeActive(suggestions) {
        suggestions.removeClass('autocomplete-active');
    }
});

// Добавляем валидацию для формы на главной странице
jQuery(document).ready(function($) {
    // Обработка инпута для гостей
    $('.guest-increase').on('click', function() {
        var target = $(this).data('target');
        var input = $('#' + 'filter-' + target);
        var currentVal = parseInt(input.val());
        var maxVal = target === 'adults' ? 10 : 8;
        
        if (!isNaN(currentVal) && currentVal < maxVal) {
            input.val(currentVal + 1);
        } else if (isNaN(currentVal)) {
            input.val(1);
        }
    });
    
    $('.guest-decrease').on('click', function() {
        var target = $(this).data('target');
        var input = $('#' + 'filter-' + target);
        var currentVal = parseInt(input.val());
        var minVal = target === 'adults' ? 1 : 0;
        
        if (!isNaN(currentVal) && currentVal > minVal) {
            input.val(currentVal - 1);
        } else if (isNaN(currentVal)) {
            input.val(minVal);
        }
    });
    
    // Автокомплит для локации
    $('.location-autocomplete').on('keyup', function() {
        var input = $(this).val();
        if (input.length >= 2) { // Изменено с >2 на >=2, чтобы "Ка" уже показывало результаты
            $.ajax({
                url: propertyFilter.ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_locations_autocomplete',
                    term: input,
                    nonce: propertyFilter.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Показываем результаты автокомплита
                        var suggestionsHtml = '<div class="location-suggestions">';
                        if (response.data.length > 0) {
                            response.data.forEach(function(location) {
                                suggestionsHtml += '<div class="location-suggestion" data-value="' + location.slug + '">' + location.name + '</div>';
                            });
                        } else {
                            suggestionsHtml += '<div class="location-suggestion no-results">Ничего не найдено</div>';
                        }
                        suggestionsHtml += '</div>';
                        
                        $('.location-suggestions').remove(); // Удаляем предыдущие результаты
                        $('.location-group').append(suggestionsHtml);
                        
                        // Обработка клика по варианту
                        $('.location-suggestion').off('click').on('click', function() {
                            var selectedValue = $(this).data('value');
                            var selectedText = $(this).text();
                            if (selectedValue) {
                               $('.location-autocomplete').val(selectedText);
                               $('#filter-location-slug').val(selectedValue);
                            }
                            $('.location-suggestions').remove();
                        });
                    }
                }
            });
        } else {
            $('.location-suggestions').remove();
        }
    });
    
    // Скрытие подсказок при потере фокуса
    $('.location-autocomplete').on('blur', function() {
        // Добавляем небольшую задержку, чтобы успеть кликнуть по подсказке
        setTimeout(function() {
            $('.location-suggestions').remove();
        }, 200);
    });
    
    // Обработка клавиш стрелок и Enter для выбора из списка
    var currentFocus = -1;
    $('.location-autocomplete').on('keydown', function(e) {
        var suggestions = $('.location-suggestion');
        if (suggestions.length === 0) return;
        
        if (e.which === 40) { // Arrow Down
            e.preventDefault();
            currentFocus++;
            addActive(suggestions);
        } else if (e.which === 38) { // Arrow Up
            e.preventDefault();
            currentFocus--;
            addActive(suggestions);
        } else if (e.which === 13) { // Enter
            e.preventDefault();
            if (currentFocus > -1) {
                if (suggestions.eq(currentFocus).hasClass('no-results')) {
                    return;
                }
                var selectedValue = suggestions.eq(currentFocus).data('value');
                var selectedText = suggestions.eq(currentFocus).text();
                $('.location-autocomplete').val(selectedText);
                $('#filter-location-slug').val(selectedValue);
                $('.location-suggestions').remove();
                currentFocus = -1;
            }
        }
    });
    
    function addActive(suggestions) {
        if (!suggestions) return false;
        removeActive(suggestions);
        if (currentFocus >= suggestions.length) currentFocus = 0;
        if (currentFocus < 0) currentFocus = (suggestions.length - 1);
        suggestions.eq(currentFocus).addClass('autocomplete-active');
    }
    
    function removeActive(suggestions) {
        suggestions.removeClass('autocomplete-active');
    }
    
    // Обработка отправки формы
    $('#property-home-filter-form').on('submit', function(e) {
        var checkinInput = $('#filter-checkin');
        var checkoutInput = $('#filter-checkout');
        var adultsInput = $('#filter-adults');
        var childrenInput = $('#filter-children');
        var locationInput = $('.location-autocomplete');
        var locationSlug = $('#filter-location-slug');
        
        var checkinDate = checkinInput.val();
        var checkoutDate = checkoutInput.val();
        var adults = parseInt(adultsInput.val());
        var children = parseInt(childrenInput.val());
        var location = locationSlug.val(); // Используем slug для отправки

        // Проверяем даты
        if (checkinDate && checkoutDate) {
            var checkin = new Date(checkinDate);
            var checkout = new Date(checkoutDate);
            
            if (checkin > checkout) {
                e.preventDefault();
                alert('Дата заезда не может быть позже даты выезда.');
                return false;
            }
        }
        
        // Проверяем текущую дату
        var currentDate = new Date();
        currentDate.setHours(0, 0, 0, 0); // Устанавливаем время на начало дня
        
        if (checkinDate) {
            var checkin = new Date(checkinDate);
            if (checkin < currentDate) {
                e.preventDefault();
                alert('Дата заезда не может быть в прошлом.');
                return false;
            }
        }
        
        // Проверяем количество гостей
        if (adults && (isNaN(adults) || adults < 1 || adults > 10)) {
            e.preventDefault();
            alert('Количество взрослых должно быть от 1 до 10.');
            return false;
        }
        
        if (children && (isNaN(children) || children < 0 || children > 8)) {
            e.preventDefault();
            alert('Количество детей должно быть от 0 до 8.');
            return false;
        }
    });
});

// Инициализация date range picker
jQuery(document).ready(function($) {
    if ($('#daterange').length > 0) {
        // Загружаем библиотеку date range picker если она доступна
        if (typeof moment !== 'undefined' && typeof $.fn.daterangepicker !== 'undefined') {
            $('#daterange').daterangepicker({
                locale: {
                    format: 'YYYY-MM-DD',
                    separator: ' - ',
                    applyLabel: "Применить",
                    cancelLabel: "Отмена",
                    fromLabel: "С",
                    toLabel: "По",
                    customRangeLabel: "Свой диапазон",
                    weekLabel: "Н",
                    daysOfWeek: [
                        "Вс",
                        "Пн",
                        "Вт",
                        "Ср",
                        "Чт",
                        "Пт",
                        "Сб"
                    ],
                    monthNames: [
                        "Январь",
                        "Февраль",
                        "Март",
                        "Апрель",
                        "Май",
                        "Июнь",
                        "Июль",
                        "Август",
                        "Сентябрь",
                        "Октябрь",
                        "Ноябрь",
                        "Декабрь"
                    ],
                },
                startDate: moment().add(1, 'day'), // Завтра
                endDate: moment().add(2, 'day'),   // Послезавтра
                minDate: moment(), // Минимум сегодня
                opens: 'center'
            }, function(start, end, label) {
                // Обновляем скрытые поля
                $('#filter-checkin').val(start.format('YYYY-MM-DD'));
                $('#filter-checkout').val(end.format('YYYY-MM-DD'));
            });
        }
    }
});