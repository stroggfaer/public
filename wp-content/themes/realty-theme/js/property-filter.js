jQuery(document).ready(function($) {
    // Логика фильтрации
    $('#property-filter-form').on('submit', function(e) {
        e.preventDefault();

        var formData = $(this).serialize();

        $.ajax({
            url: propertyFilter.ajaxurl,
            type: 'POST',
            data: formData + '&action=filter_properties_custom&nonce=' + propertyFilter.nonce,
            beforeSend: function() {
                $('.results-content').html('<div class="loading-indicator" style="display: block;"><p>Загрузка результатов...</p></div>');
            },
            success: function(data) {
                if (data.success) {
                    $('.results-content').html(data.data.properties_html);
                    // Переинициализируем карту после загрузки новых результатов
                    setTimeout(initMap, 100);
                } else {
                    $('.results-content').html('<p>Ошибка при загрузке результатов: ' + data.data + '</p>');
                }
            },
            error: function() {
                $('.results-content').html('<p>Произошла ошибка.</p>');
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
});