// composables/usePropertyFilters.js
import { ref, computed, watch, onMounted } from 'vue';
import { ApiService } from '../services/api';
import moment from 'moment';

// CONFIG - только здесь, не в store
const CONFIG = {
    guests: {
        adults: { min: 1, max: 10 },
        children: { min: 0, max: 8 }
    },
    price: {
        min: 0,
        max: 1000000,
        step: 1000
    }
};

// Проверка наличия store
function hasStore() {
    return typeof window !== 'undefined' && window.store && window.store.state;
}

export default function usePropertyFilters(params = {}) {

    // ---------------- PARAMS ----------------
    const {
        priceRange: {
            min = CONFIG.price.min,
            max = CONFIG.price.max,
            step = CONFIG.price.step
        } = {},
        context = 'default',
        initFilters: initialFilters = [],
        ajaxUrl = '',
        nonce = ''
    } = params;

    // ---------------- STORE ----------------
    const useStore = hasStore();
    let store = null;
    if (useStore) {
        store = window.store;
    }

    // ---------------- STATE (reactive для слайдера) ----------------
    // Цена для слайдера - реактивная, чтобы работал v-model
    const priceRangeValue = ref([Number(min), Number(max)]);

    // ---------------- INIT FROM URL ----------------
    function initFromURL() {
        if (typeof window === 'undefined') return null;
        
        const params = new URLSearchParams(window.location.search);
        const urlFilters = {};
        
        // Читаем URL параметры
        const location = params.get('location');
        const location_slug = params.get('location_slug');
        
        if (location) {
            urlFilters.location = decodeURIComponent(location);
        }
        if (location_slug) {
            urlFilters.location_slug = decodeURIComponent(location_slug);
        }
        
        const price_min = params.get('price_min');
        const price_max = params.get('price_max');
        if (price_min) urlFilters.price_min = Number(price_min);
        if (price_max) urlFilters.price_max = Number(price_max);
        
        const checkin_date = params.get('checkin_date');
        const checkout_date = params.get('checkout_date');
        if (checkin_date) urlFilters.checkin_date = checkin_date;
        if (checkout_date) urlFilters.checkout_date = checkout_date;
        
        const adults = params.get('adults');
        const children = params.get('children');
        if (adults) urlFilters.adults = Number(adults);
        if (children) urlFilters.children = Number(children);
        
        return Object.keys(urlFilters).length > 0 ? urlFilters : null;
    }

    // ---------------- COMPUTED ----------------
    const safeStep = computed(() => {
        const s = Number(step);
        return s > 0 ? s : CONFIG.price.step;
    });

    const formId = computed(() =>
        context === 'archive'
            ? 'property-filter-vue-archive'
            : 'property-filter-vue'
    );

    const formatPriceTooltip = (val) => `${val} ₽`;

    // ---------------- FILTERS FROM STORE ----------------
    const filters = computed(() => {
        if (useStore) {
            return store.getters['propertyFilters/getFilters'];
        }
        // Fallback - локальное состояние без store
        return {
            locationInput: '',
            locationSlug: '',
            price_min: Number(min),
            price_max: Number(max),
            checkin_date: moment().format('YYYY-MM-DD'),
            checkout_date: moment().add(7, 'days').format('YYYY-MM-DD'),
            adults: CONFIG.guests.adults.min,
            children: CONFIG.guests.children.min
        };
    });

    // priceRangeValue - синхронизируется с filters
    // Слайдер управляет фильтрами

    const isLoading = computed(() => {
        if (useStore) {
            return store.getters['propertyFilters/getIsLoading'];
        }
        return false;
    });

    // ---------------- METHODS ----------------
    function setFilter(key, value) {
        if (useStore) {
            store.commit('propertyFilters/SET_FILTER', { key, value });
        }
    }

    function getFilters() {
        if (useStore) {
            return store.getters['propertyFilters/getFilters'];
        }
        return {
            locationInput: '',
            locationSlug: '',
            price_min: Number(min),
            price_max: Number(max),
            checkin_date: moment().format('YYYY-MM-DD'),
            checkout_date: moment().add(7, 'days').format('YYYY-MM-DD'),
            adults: CONFIG.guests.adults.min,
            children: CONFIG.guests.children.min
        };
    }

    function syncPriceInputs() {
        const f = getFilters();
        // Синхронизируем слайдер с фильтрами
        priceRangeValue.value = [f.price_min, f.price_max];
    }

    // Синхронизация слайдера -> filters
    watch(priceRangeValue, (val) => {
        if (Array.isArray(val) && val.length === 2) {
            setFilter('price_min', val[0]);
            setFilter('price_max', val[1]);
        }
    });

    // ---------------- INIT ----------------
    function initFiltersFromStore() {
        if (useStore) {
            // Читаем URL параметры
            const urlFilters = initFromURL();
            if (urlFilters) {
                // Применяем URL параметры в store
                store.dispatch('propertyFilters/initializeFilters', urlFilters);
            } else if (Array.isArray(initialFilters)) {
                // Инициализируем из initialFilters (PHP)
                store.dispatch('propertyFilters/initializeFilters', initialFilters);
            }
        }
    }

    onMounted(initFiltersFromStore);

    // ---------------- AUTOCOMPLETE ----------------
    let timeout = null;

    function querySearch(queryString, cb) {
        if (queryString.length < 2) return cb([]);
        if (!ajaxUrl) return cb([]);

        clearTimeout(timeout);

        timeout = setTimeout(() => {
            ApiService.post(ajaxUrl, {
                action: 'get_locations_autocomplete',
                term: queryString,
                nonce: nonce
            }).then(res => {
                if (res?.success) {
                    cb(res.data.map(item => ({
                        value: item.name,
                        name: item.name,
                        slug: item.slug
                    })));
                } else {
                    cb([]);
                }
            });
        }, 300);
    }

    function handleLocationSelect(item) {
        setFilter('locationInput', item.name);
        setFilter('locationSlug', item.slug);
    }

    // ---------------- VALIDATION ----------------
    function validate() {
        const f = getFilters();
        if (f.checkin_date && f.checkout_date) {
            if (moment(f.checkin_date).isAfter(moment(f.checkout_date))) {
                throw new Error('Дата заезда позже выезда');
            }
        }
    }

    // ---------------- API ----------------
    async function fetchProperties() {
        if (useStore) {
            store.commit('propertyFilters/SET_LOADING', true);
        }

        try {
            validate();

            const f = getFilters();
            
            const res = await ApiService.post(ajaxUrl, {
                action: 'filter_properties_custom',
                nonce: nonce,
                location: f.locationInput,
                price_min: f.price_min,
                price_max: f.price_max,
                checkin_date: f.checkin_date,
                checkout_date: f.checkout_date,
                adults: f.adults,
                children: f.children,
                page: 1
            });

            if (res?.success && useStore) {
                // Сохраняем data и meta в store
                store.commit('propertyFilters/SET_DATA', res.data.properties || []);
                store.commit('propertyFilters/SET_META', {
                    page: 1,
                    total: res.data.total || 0
                });
            }

            return res;

        } finally {
            if (useStore) {
                store.commit('propertyFilters/SET_LOADING', false);
            }
        }
    }

    // ---------------- RESET ----------------
    function reset() {
        if (useStore) {
            store.dispatch('propertyFilters/resetFilters');
        }
    }

    // ---------------- DATE HELPERS ----------------
    function disabledCheckinDate(date) {
        return moment(date).isBefore(moment(), 'day');
    }

    function disabledCheckoutDate(date) {
        const f = getFilters();
        if (f.checkin_date) {
            return moment(date).isBefore(moment(f.checkin_date), 'day');
        }
        return moment(date).isBefore(moment(), 'day');
    }

    // ---------------- EXPORT ----------------
    return {
        // Computed
        filters,
        priceRangeValue,
        isLoading,
        safeStep,
        formId,
        formatPriceTooltip,

        // Methods
        querySearch,
        handleLocationSelect,
        fetchProperties,
        reset,
        syncPriceInputs,
        setFilter,
        getFilters,

        // Date helpers
        disabledCheckinDate,
        disabledCheckoutDate,

        // Config
        CONFIG
    };
}