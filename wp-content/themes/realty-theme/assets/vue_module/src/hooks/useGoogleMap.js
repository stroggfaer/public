import { ref, onMounted, onUnmounted, watch } from 'vue';

/**
 * Хук для работы с Google Maps
 * Содержит бизнес-логику: инициализация, маркеры, события
 */
export function useGoogleMap(options = {}) {
    const {
        mapId = 'property-map',
        defaultZoom = 6,
        defaultCenter = { lat: 55.7558, lng: 37.6173 },
        onMarkerClick = null,
        onMapReady = null
    } = options;

    // Состояние
    const map = ref(null);
    const markers = ref([]);
    const infoWindows = ref([]);
    const markerMap = ref({});
    const isLoading = ref(true);
    const isMapReady = ref(false);
    const coordinates = ref([]);

    // Проверка загрузки Google Maps API
    function isGoogleMapsLoaded() {
        return typeof google !== 'undefined' && typeof google.maps !== 'undefined';
    }

    // Инициализация карты
    function initMap(mapCoordinates = [], singleProperty = false, lat = null, lng = null) {
        if (!isGoogleMapsLoaded()) {
            console.error('Google Maps API не загружен');
            isLoading.value = false;
            return;
        }

        const mapContainer = document.getElementById(mapId);
        if (!mapContainer) {
            console.error('Контейнер для карты не найден:', mapId);
            isLoading.value = false;
            return;
        }

        let mapCenter = { ...defaultCenter };
        let mapZoom = defaultZoom;

        // Если это одиночный объект
        if (singleProperty && lat && lng) {
            mapCenter = {
                lat: parseFloat(lat),
                lng: parseFloat(lng)
            };
            mapZoom = 15;
        } else if (mapCoordinates && mapCoordinates.length > 0) {
            mapCenter = {
                lat: parseFloat(mapCoordinates[0].lat),
                lng: parseFloat(mapCoordinates[0].lng)
            };
        }

        const mapOptions = {
            zoom: mapZoom,
            center: mapCenter,
            mapTypeId: google.maps.MapTypeId.ROADMAP
        };

        map.value = new google.maps.Map(mapContainer, mapOptions);

        // Добавляем маркеры
        if (singleProperty && lat && lng) {
            addSingleMarker(lat, lng);
        } else if (mapCoordinates && mapCoordinates.length > 0) {
            addMarkers(mapCoordinates);
        }

        isMapReady.value = true;
        isLoading.value = false;

        if (onMapReady) {
            onMapReady(map.value);
        }
    }

    // Добавить маркер для одиночного объекта
    function addSingleMarker(lat, lng) {
        const marker = new google.maps.Marker({
            position: {
                lat: parseFloat(lat),
                lng: parseFloat(lng)
            },
            map: map.value,
            title: 'Объект недвижимости'
        });
        markers.value.push(marker);
    }

    // Добавить маркеры для списка объектов
    function addMarkers(newCoordinates) {
        clearMarkers();

        newCoordinates.forEach(coord => {
            const position = {
                lat: parseFloat(coord.lat),
                lng: parseFloat(coord.lng)
            };

            const marker = new google.maps.Marker({
                position: position,
                map: map.value,
                title: coord.title || 'Объект недвижимости',
                optimized: true
            });

            // Информационное окно
            const infoWindow = new google.maps.InfoWindow({
                content: `
                    <div class="property-info-window">
                        <h3>${coord.title || ''}</h3>
                        ${coord.price ? `<p class="price">${coord.price}</p>` : ''}
                        ${coord.address ? `<p class="address">${coord.address}</p>` : ''}
                        <p class="id">ID: ${coord.id}</p>
                    </div>
                `
            });

            markers.value.push(marker);
            infoWindows.value.push(infoWindow);
            markerMap.value[coord.id] = {
                marker: marker,
                infoWindow: infoWindow
            };

            // Обработчик клика на маркер
            marker.addListener('click', () => {
                infoWindows.value.forEach(iw => iw.close());
                infoWindow.open(map.value, marker);
                
                if (onMarkerClick) {
                    onMarkerClick(coord.id);
                }
                
                highlightPropertyCard(coord.id);
            });
        });

        // Автоматическое масштабирование
        if (markers.value.length > 1) {
            const bounds = new google.maps.LatLngBounds();
            markers.value.forEach(marker => bounds.extend(marker.getPosition()));
            map.value.fitBounds(bounds);
        } else if (markers.value.length === 1) {
            map.value.setCenter(markers.value[0].getPosition());
        }
    }

    // Очистка маркеров
    function clearMarkers() {
        markers.value.forEach(marker => marker.setMap(null));
        markers.value = [];
        infoWindows.value.forEach(iw => iw.close());
        infoWindows.value = [];
        markerMap.value = {};
    }

    // Выделение карточки в списке
    function highlightPropertyCard(propertyId) {
        document.querySelectorAll('.property-card').forEach(card => {
            card.classList.remove('highlighted');
        });

        const card = document.querySelector(`.property-card[data-id="${propertyId}"]`);
        if (card) {
            card.classList.add('highlighted');
            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    // Центрирование карты на объекте
    function panToProperty(propertyId, lat, lng) {
        if (!map.value) return;

        const position = new google.maps.LatLng(parseFloat(lat), parseFloat(lng));
        map.value.panTo(position);
        map.value.setZoom(14);

        if (markerMap.value[propertyId]) {
            infoWindows.value.forEach(iw => iw.close());
            markerMap.value[propertyId].infoWindow.open(
                map.value,
                markerMap.value[propertyId].marker
            );
        }
    }

    // Обновление координат
    function updateCoordinates(newCoordinates) {
        if (!map.value) {
            if (isGoogleMapsLoaded()) {
                initMap(newCoordinates);
            }
            return;
        }

        addMarkers(newCoordinates);
    }

    // Обработчик клика по карточке
    function handleCardClick(event) {
        const card = event.target.closest('.property-card');
        if (!card || !map.value) return;

        const id = card.dataset.id;
        const lat = card.dataset.lat;
        const lng = card.dataset.lng;

        if (lat && lng) {
            panToProperty(id, lat, lng);
        }
    }

    // Обработчик AJAX завершения фильтрации
    function handleAjaxComplete(event, xhr, settings) {
        if (!settings.data || settings.data.indexOf('action=filter_properties_custom') === -1) {
            return;
        }

        try {
            const response = JSON.parse(xhr.responseText);
            if (response.success && response.data && response.data.coordinates) {
                updateCoordinates(response.data.coordinates);
            }
        } catch (e) {
            console.error('Ошибка при обработке ответа AJAX:', e);
        }
    }

    // Lifecycle
    onMounted(() => {
        const checkGoogleMaps = setInterval(() => {
            if (isGoogleMapsLoaded()) {
                clearInterval(checkGoogleMaps);
                initMap(coordinates.value);
            }
        }, 100);

        setTimeout(() => {
            clearInterval(checkGoogleMaps);
            if (!isMapReady.value) {
                isLoading.value = false;
            }
        }, 5000);

        document.addEventListener('click', handleCardClick);
        jQuery(document).ajaxComplete(handleAjaxComplete);
    });

    onUnmounted(() => {
        clearMarkers();
        document.removeEventListener('click', handleCardClick);
    });

    // Watch за координатами
    watch(coordinates, (newCoords) => {
        if (newCoords && newCoords.length > 0 && isMapReady.value) {
            updateCoordinates(newCoords);
        }
    }, { deep: true });

    return {
        // State
        map,
        markers,
        infoWindows,
        isLoading,
        isMapReady,
        coordinates,
        
        // Methods
        initMap,
        addMarkers,
        clearMarkers,
        updateCoordinates,
        panToProperty,
        highlightPropertyCard
    };
}
