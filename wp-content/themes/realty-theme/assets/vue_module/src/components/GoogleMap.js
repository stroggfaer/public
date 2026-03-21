import { watch } from 'vue';
import { useGoogleMap } from '../hooks/useGoogleMap.js';

/**
 * Google Map Vue компонент для страницы архива недвижимости
 * Переиспользуемый компонент для отображения объектов на карте
 * 
 * Использует хук useGoogleMap для бизнес-логики
 */
export default {
  name: 'GoogleMap',

  template: `
    <div 
      :id="mapId" 
      class="property-map"
      :class="{ 'property-map--loading': isLoading }"
    ></div>
  `,

  props: {
    mapId: {
      type: String,
      default: 'property-map'
    },
    coordinates: {
      type: Array,
      default: () => []
    },
    zoom: {
      type: Number,
      default: 6
    },
    center: {
      type: Object,
      default: () => ({ lat: 55.7558, lng: 37.6173 })
    },
    mapTypeId: {
      type: String,
      default: 'roadmap'
    },
    isSingleProperty: {
      type: Boolean,
      default: false
    },
    singleLatitude: {
      type: Number,
      default: null
    },
    singleLongitude: {
      type: Number,
      default: null
    }
  },

  emits: ['marker-click', 'map-ready', 'bounds-changed'],

  setup(props, { emit }) {
    // Используем хук для бизнес-логики
    const {
      map,
      markers,
      infoWindows,
      isLoading,
      isMapReady,
      coordinates,
      initMap,
      updateCoordinates
    } = useGoogleMap({
      mapId: props.mapId,
      defaultZoom: props.zoom,
      defaultCenter: props.center,
      onMarkerClick: (id) => emit('marker-click', id),
      onMapReady: (mapInstance) => emit('map-ready', mapInstance)
    });

    // Синхронизация координат
    coordinates.value = props.coordinates;

    // Watch за координатами
    watch(() => props.coordinates, (newCoords) => {
      coordinates.value = newCoords;
    }, { deep: true });

    return {
      map,
      markers,
      infoWindows,
      isLoading,
      isMapReady,
      mapId: props.mapId,
      initMap,
      updateCoordinates
    };
  }
};
