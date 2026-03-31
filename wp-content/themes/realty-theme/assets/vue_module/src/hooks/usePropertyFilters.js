// composables/usePropertyFilters.js
import { ref, computed, watch, onMounted } from 'vue';
import { ApiService } from '../services/api';
import moment from 'moment';

function hasStore() {
    return typeof window !== 'undefined' && window.store && window.store.state;
}

export default function usePropertyFilters(params = {}) {
    const useStore = hasStore();
    const store = useStore ? Vuex.useStore() : null;

    const {
        config = {},
        context = 'default',
        initFilters: initialFilters = [],
        ajaxUrl = '',
        nonce = ''
    } = params;

    const price_range = config.price_range || { min: 0, max: 1000000, step: 1000 };

    // ---------------- STATE ----------------
    const priceRangeValue = ref([price_range.min, price_range.max]);

    // ---------------- COMPUTED ----------------
    const filters = computed(() =>
        useStore ? store.getters['propertyFilters/getFilters'] : {}
    );

    const isLoading = computed(() =>
        useStore ? store.getters['propertyFilters/getIsLoading'] : false
    );

    const safeStep = computed(() => {
        const s = Number(price_range.step);
        return s > 0 ? s : 1000;
    });

    const formId = computed(() =>
        context === 'archive'
            ? 'property-filter-vue-archive'
            : 'property-filter-vue'
    );

    const formatPriceTooltip = (val) => `${val} ₽`;

    const initFilters = computed(() =>
        useStore ? store.getters['propertyFilters/getInit'] : null
    );

    // ---------------- METHODS ----------------
    function getFiltersFromURL() {
        if (typeof window === 'undefined') return null;

        const params = new URLSearchParams(window.location.search);
        const result = {};

        params.forEach((value, key) => {
            result[key] = decodeURIComponent(value);
        });

        return Object.keys(result).length ? result : null;
    }
    function validate() {
        const f = filters.value;

        // даты
        if (f.checkin_date && f.checkout_date) {
            if (moment(f.checkin_date).isAfter(moment(f.checkout_date))) {
                throw new Error('Дата заезда позже выезда');
            }
        }

        // цена (на будущее)
        if (f.price_min > f.price_max) {
            throw new Error('Некорректный диапазон цены');
        }

        // гости (на будущее)
        if (f.adults < 1) {
            throw new Error('Должен быть хотя бы 1 взрослый');
        }

        return true;
    }
    function setFilter(key, value) {
        if (useStore) {
            store.commit('propertyFilters/SET_FILTER', { key, value });
        }
    }

    function syncPriceInputs() {
        const f = filters.value;
        priceRangeValue.value = [f.price_min, f.price_max];
    }

    watch(priceRangeValue, (val) => {
        if (Array.isArray(val) && val.length === 2) {
            setFilter('price_min', val[0]);
            setFilter('price_max', val[1]);
        }
    });

    // ---------------- INIT ----------------
    function initFromURL() {
        if (typeof window === 'undefined') return null;

        const params = new URLSearchParams(window.location.search);
        const result = {};

        params.forEach((value, key) => {
            result[key] = decodeURIComponent(value);
        });

        return Object.keys(result).length ? result : null;
    }

    function initFiltersFromStore() {
        if (!useStore) return;

        const urlFilters = initFromURL();

        if (urlFilters) {
            store.dispatch('propertyFilters/initializeFilters', urlFilters);
        } else if (Array.isArray(initialFilters)) {
            store.dispatch('propertyFilters/initializeFilters', initialFilters);
        }
    }

    onMounted(initFiltersFromStore);

    // ---------------- AUTOCOMPLETE ----------------
    let timeout = null;

    function querySearch(queryString, cb) {
        if (!ajaxUrl) return cb([]);

        clearTimeout(timeout);

        timeout = setTimeout(async () => {
            try {
                const res = await ApiService.post(ajaxUrl, {
                    action: 'get_locations_autocomplete',
                    term: queryString || '',
                    limit: 20,
                    nonce
                });

                if (res?.success) {
                    cb(res.data.map(item => ({
                        value: item.name,
                        name: item.name,
                        slug: item.slug
                    })));
                } else {
                    cb([]);
                }
            } catch (e) {
                cb([]);
            }
        }, 200); // debounce 200ms
    }

    function handleLocationSelect(item) {
        setFilter('locationInput', item.name);
        setFilter('locationSlug', item.slug);
    }

    // ---------------- API ----------------
    async function fetchProperties() {
        if (useStore) {
            store.commit('propertyFilters/SET_LOADING', true);
        }

        try {
            const f = filters.value;

            const res = await ApiService.post(ajaxUrl, {
                action: 'filter_properties_custom',
                nonce,
                location: f.locationInput,
                location_slug: decodeURIComponent(f.locationSlug),
                price_min: f.price_min,
                price_max: f.price_max,
                checkin_date: f.checkin_date,
                checkout_date: f.checkout_date,
                adults: f.adults,
                children: f.children,
                page: 1
            });

            if (res?.success && useStore) {
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
        if (!useStore) return;

        const urlFilters = getFiltersFromURL();

        if (urlFilters) {
            store.dispatch('propertyFilters/resetFilters', urlFilters);
        } else if (Array.isArray(initialFilters)) {
            store.dispatch('propertyFilters/resetFilters', initialFilters);
        } else {
            store.dispatch('propertyFilters/resetFilters');
        }
    }

    // ---------------- DATE ----------------
    function disabledCheckinDate(date) {
        return moment(date).isBefore(moment(), 'day');
    }

    function disabledCheckoutDate(date) {
        const f = filters.value;

        if (f.checkin_date) {
            return moment(date).isBefore(moment(f.checkin_date), 'day');
        }

        return moment(date).isBefore(moment(), 'day');
    }

    return {
        filters,
        priceRangeValue,
        isLoading,
        safeStep,
        formId,
        formatPriceTooltip,

        querySearch,
        handleLocationSelect,
        fetchProperties,
        reset,
        syncPriceInputs,
        setFilter,

        disabledCheckinDate,
        disabledCheckoutDate,
        validate,
        initFilters,
    };
}