jQuery(document).ready(function($) {
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

    // Обработчик фильтров
    $(document).on('click', '#apply_filters', applyFilters);
    $(document).on('click', '#reset_filters', resetFilters);
    
    // Также обрабатываем изменение селектов и полей ввода для более удобного UX
    $(document).on('change', '#price_min, #price_max, #property_type, #location, #sort_order', function() {
        // Добавляем задержку, чтобы дать время на ввод
        clearTimeout(window.filterTimeout);
        window.filterTimeout = setTimeout(applyFilters, 500);
    });

    function applyFilters() {
        // Получаем значения фильтров
        var priceMin = $('#price_min').val();
        var priceMax = $('#price_max').val();
        var propertyType = $('#property_type').val();
        var location = $('#location').val();
        var sortOrder = $('#sort_order').val();
        
        // Показываем индикатор загрузки
        $('#properties-list').html('<div class="loading">Загрузка...</div>');
        
        // Отправляем AJAX-запрос
        $.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'filter_properties',
                price_min: priceMin,
                price_max: priceMax,
                property_type: propertyType,
                location: location,
                sort_order: sortOrder,
                nonce: ajax_object.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#properties-list').html(response.data.properties_html);
                    // Обновляем метки на карте
                    setTimeout(addMarkersToMap, 500);
                } else {
                    $('#properties-list').html('<p>Произошла ошибка при загрузке данных.</p>');
                }
            },
            error: function() {
                $('#properties-list').html('<p>Произошла ошибка при загрузке данных.</p>');
            }
        });
    }
    
    function resetFilters() {
        $('#price_min').val('');
        $('#price_max').val('');
        $('#property_type').prop('selectedIndex', 0);
        $('#location').prop('selectedIndex', 0);
        $('#sort_order').prop('selectedIndex', 0);
        
        // Сбрасываем фильтры
        applyFilters();
    }
    
    // Обработка пагинации
    $(document).on('click', '.property-pagination a', function(e) {
        e.preventDefault();
        var page = $(this).attr('href').match(/paged=(\d+)/);
        if (page) {
            page = parseInt(page[1]);
        } else {
            page = 1;
        }
        
        // Получаем текущие значения фильтров
        var priceMin = $('#price_min').val();
        var priceMax = $('#price_max').val();
        var propertyType = $('#property_type').val();
        var location = $('#location').val();
        var sortOrder = $('#sort_order').val();
        
        // Показываем индикатор загрузки
        $('#properties-list').html('<div class="loading">Загрузка...</div>');
        
        // Отправляем AJAX-запрос с номером страницы
        $.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'filter_properties',
                price_min: priceMin,
                price_max: priceMax,
                property_type: propertyType,
                location: location,
                sort_order: sortOrder,
                page: page,
                nonce: ajax_object.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#properties-list').html(response.data.properties_html);
                    // Обновляем метки на карте
                    setTimeout(addMarkersToMap, 500);
                    
                    // Прокручиваем к началу списка
                    $('html, body').animate({
                        scrollTop: $('#properties-list').offset().top - 100
                    }, 500);
                } else {
                    $('#properties-list').html('<p>Произошла ошибка при загрузке данных.</p>');
                }
            },
            error: function() {
                $('#properties-list').html('<p>Произошла ошибка при загрузке данных.</p>');
            }
        });
    });
});