/**
 * Google Maps для страницы архива недвижимости и страницы одиночного объекта
 * 
 * Реализует отображение меток объектов недвижимости на карте
 * и синхронизацию с фильтрами и списком объектов
 */
 
jQuery(document).ready(function($) {
    // Проверяем, загружен ли Google Maps API
    if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
        console.error('Google Maps API не загружен');
        return;
    }

    // Глобальные переменные для карты
    var map = null;
    var markers = [];
    var infoWindows = [];
    var markerMap = {}; // Ассоциативный массив для связи ID объекта и маркера

    // Инициализация карты
    function initPropertyMap(coordinates) {
        // Параметры для центра карты (по умолчанию - центр России)
        var defaultCenter = { lat: 55.7558, lng: 37.6173 }; // Москва
        
        // Если есть координаты, устанавливаем центр на основе первого объекта
        if (coordinates && coordinates.length > 0) {
            defaultCenter = { lat: parseFloat(coordinates[0].lat), lng: parseFloat(coordinates[0].lng) };
        }

        // Создаем карту
        var mapOptions = {
            zoom: 6,
            center: defaultCenter,
            mapTypeId: google.maps.MapTypeId.ROADMAP
        };

        // Находим контейнер для карты
        var mapContainer = document.getElementById('property-map');
        if (!mapContainer) {
            console.error('Контейнер для карты не найден');
            return;
        }

        map = new google.maps.Map(mapContainer, mapOptions);

        // Добавляем метки на карту
        if (coordinates) {
            addMarkersToMap(coordinates);
        }
    }

    // Функция для инициализации карты на странице одиночного объекта
    function initSinglePropertyMap(latitude, longitude) {
        // Преобразуем координаты в числа
        var lat = parseFloat(latitude);
        var lng = parseFloat(longitude);
        
        if (isNaN(lat) || isNaN(lng)) {
            console.error('Некорректные координаты для карты');
            return;
        }
        
        // Создаем карту
        var mapOptions = {
            zoom: 15,
            center: { lat: lat, lng: lng },
            mapTypeId: google.maps.MapTypeId.ROADMAP
        };

        // Находим контейнер для карты
        var mapContainer = document.getElementById('property-map');
        if (!mapContainer) {
            console.error('Контейнер для карты не найден');
            return;
        }

        map = new google.maps.Map(mapContainer, mapOptions);

        // Создаем маркер для объекта
        var marker = new google.maps.Marker({
            position: { lat: lat, lng: lng },
            map: map,
            title: 'Объект недвижимости'
        });
        
        // Сохраняем ссылку на маркер
        markers.push(marker);
    }

    // Функция для добавления меток на карту
    function addMarkersToMap(coordinates) {
        // Очищаем предыдущие метки
        clearMarkers();

        // Добавляем новые метки
        coordinates.forEach(function(coord) {
            var position = { lat: parseFloat(coord.lat), lng: parseFloat(coord.lng) };
            
            // Создаем маркер
            var marker = new google.maps.Marker({
                position: position,
                map: map,
                title: coord.title || 'Объект недвижимости',
                optimized: true // Используем оптимизацию для большого количества маркеров
            });

            // Создаем информационное окно
            var infoWindow = new google.maps.InfoWindow({
                content: '<div class="property-info-window"><h3>' + (coord.title || '') + '</h3><p>ID: ' + coord.id + '</p></div>'
            });

            // Сохраняем ссылки на маркер и инфоокно
            markers.push(marker);
            infoWindows.push(infoWindow);
            markerMap[coord.id] = {
                marker: marker,
                infoWindow: infoWindow
            };

            // Добавляем обработчик клика на маркер
            marker.addListener('click', function() {
                // Закрываем все другие инфоокна
                infoWindows.forEach(function(iw) {
                    iw.close();
                });

                // Открываем инфоокно для этого маркера
                infoWindow.open(map, marker);

                // Выделяем соответствующую карточку в списке
                highlightPropertyCard(coord.id);
            });
        });

        // Автоматически масштабируем карту, чтобы показать все маркеры
        if (markers.length > 1) {
            var bounds = new google.maps.LatLngBounds();
            markers.forEach(function(marker) {
                bounds.extend(marker.getPosition());
            });
            map.fitBounds(bounds);
        } else if (markers.length === 1) {
            map.setCenter(markers[0].getPosition());
        }
    }

    // Функция очистки маркеров
    function clearMarkers() {
        markers.forEach(function(marker) {
            marker.setMap(null);
        });
        markers = [];
        
        infoWindows.forEach(function(iw) {
            iw.close();
        });
        infoWindows = [];
        
        markerMap = {};
    }

    // Функция для выделения карточки объекта в списке
    function highlightPropertyCard(propertyId) {
        // Снимаем выделение со всех карточек
        $('.property-card').removeClass('highlighted');
        
        // Выделяем нужную карточку
        var $card = $('.property-card[data-id="' + propertyId + '"]');
        if ($card.length) {
            $card.addClass('highlighted');
            
            // Плавный скролл к карточке
            $('html, body').animate({
                scrollTop: $card.offset().top - 100
            }, 500);
        }
    }

    // Обновление карты с новыми данными
    function updateMapWithData(data) {
        if (!map) {
            // Если карта еще не инициализирована, инициализируем с новыми данными
            initPropertyMap(data.coordinates);
        } else {
            // Если карта уже существует, просто добавляем новые метки
            addMarkersToMap(data.coordinates);
        }
    }

    // Функция для обновления карты из Vuex store
    function updateMapFromStore() {
        if (typeof window === 'undefined' || !window.store) return;
        
        const coordinates = window.store.getters['propertyFilters/getCoordinates'];
        
        if (coordinates && coordinates.length > 0) {
            if (!map) {
                // Инициализируем карту с координатами из store
                initPropertyMap(coordinates);
            } else {
                // Обновляем маркеры на существующей карте
                addMarkersToMap(coordinates);
            }
        }
    }

    // Обработка клика по кнопке карты - центрирование карты
    $(document).on('click', '.js-map-location', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        if (!map) return;

        var $btn = $(this);
        var id = $btn.data('id');
        var lat = parseFloat($btn.data('lat'));
        var lng = parseFloat($btn.data('lng'));

        if (lat && lng) {
            // Убираем активный класс у всех кнопок карты
            $('.js-map-location').removeClass('active');
            // Добавляем активный класс текущей кнопке
            $btn.addClass('active');

            // Центрируем карту на этом объекте
            var position = new google.maps.LatLng(lat, lng);
            map.panTo(position);
            map.setZoom(14);

            // Открываем информационное окно, если оно связано с этим объектом
            if (markerMap[id]) {
                // Закрываем все другие инфоокна
                infoWindows.forEach(function(iw) {
                    iw.close();
                });

                // Открываем инфоокно для этого маркера
                markerMap[id].infoWindow.open(map, markerMap[id].marker);
            }
        }
    });

    // Подписка на изменения координат в Vuex store
    function subscribeToStoreChanges() {
        if (typeof window === 'undefined' || !window.store) return;
        
        // Подписываемся на мутации store
        window.store.subscribe(function(mutation, state) {
            // Реагируем на изменения координатов
            if (mutation.type === 'propertyFilters/ADD_COORDINATES' || 
                mutation.type === 'propertyFilters/SET_COORDINATES') {
                // Даем Vue время обновить состояние
                setTimeout(function() {
                    updateMapFromStore();
                }, 50);
            }
        });
    }

    // Обновление карты при изменении результатов фильтрации
    $(document).ajaxComplete(function(event, xhr, settings) {
        // Проверяем, является ли это нашим AJAX-запросом фильтрации
        if (settings.data && settings.data.indexOf('action=filter_properties_custom') !== -1) {
            try {
                var response = JSON.parse(xhr.responseText);
                if (response.success && response.data && response.data.coordinates) {
                    updateMapWithData(response.data);
                }
            } catch (e) {
                console.error('Ошибка при обработке ответа AJAX:', e);
            }
        }
    });

    // Инициализация карты при загрузке страницы
    // Ждем немного, чтобы убедиться, что Google Maps API полностью загружен
    setTimeout(function() {
        // Проверяем, на какой странице мы находимся
        if ($('body').hasClass('single-property')) {
            // На странице одиночного объекта
            var mapContainer = document.getElementById('property-map');
            if (mapContainer) {
                var latitude = mapContainer.getAttribute('data-latitude');
                var longitude = mapContainer.getAttribute('data-longitude');
                
                if (latitude && longitude) {
                    initSinglePropertyMap(latitude, longitude);
                }
            }
        } else if ($('body').hasClass('post-type-archive-property')) {
            // На странице архива недвижимости
            // Сначала пытаемся получить координаты из Vuex store
            if (typeof window !== 'undefined' && window.store) {
                var storeCoordinates = window.store.getters['propertyFilters/getCoordinates'];
                
                if (storeCoordinates && storeCoordinates.length > 0) {
                    // Используем координаты из store
                    initPropertyMap(storeCoordinates);
                } else {
                    // Fallback: получаем начальные координаты из карточек
                    var initialCoordinates = [];
                    $('.property-card[data-lat][data-lng]').each(function() {
                        var $card = $(this);
                        initialCoordinates.push({
                            id: $card.data('id'),
                            lat: $card.data('lat'),
                            lng: $card.data('lng'),
                            title: $card.find('.entry-title a').text() || 'Объект недвижимости'
                        });
                    });

                    if (initialCoordinates.length > 0) {
                        initPropertyMap(initialCoordinates);
                    } else {
                        // Инициализируем пустую карту
                        initPropertyMap([]);
                    }
                }
            } else {
                // Fallback для случаев без Vuex store
                var initialCoordinates = [];
                $('.property-card[data-lat][data-lng]').each(function() {
                    var $card = $(this);
                    initialCoordinates.push({
                        id: $card.data('id'),
                        lat: $card.data('lat'),
                        lng: $card.data('lng'),
                        title: $card.find('.entry-title a').text() || 'Объект недвижимости'
                    });
                });

                if (initialCoordinates.length > 0) {
                    initPropertyMap(initialCoordinates);
                } else {
                    // Инициализируем пустую карту
                    initPropertyMap([]);
                }
            }
            
            // Подписываемся на изменения в store
            subscribeToStoreChanges();
        }
    }, 1000);
});