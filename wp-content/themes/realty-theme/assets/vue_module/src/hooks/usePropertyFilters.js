// composables/usePropertyFilters.js
import { ref, reactive, computed, watch, onMounted } from 'vue';
import { ApiService } from '../services/api';

// ---- CONFIG ----
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

export function usePropertyFilters(props, moment) {

    // ---------------- STATE ----------------
    const filters = reactive({
        price_min: Number(props.priceRange?.min) || CONFIG.price.min,
        price_max: Number(props.priceRange?.max) || CONFIG.price.max,
        checkin_date: moment().format('YYYY-MM-DD'),
        checkout_date: moment().add(7, 'days').format('YYYY-MM-DD'),
        adults: CONFIG.guests.adults.min,
        children: CONFIG.guests.children.min
    });

    const priceRangeValue = ref([
        filters.price_min,
        filters.price_max
    ]);

    const locationInput = ref('');
    const locationSlug = ref('');
    const isLoading = ref(false);

    // ---------------- COMPUTED ----------------
    const safeStep = computed(() => {
        const step = Number(props.priceRange?.step ?? CONFIG.price.step);
        return step > 0 ? step : CONFIG.price.step;
    });

    const formId = computed(() =>
        props.context === 'archive'
            ? 'property-filter-vue-archive'
            : 'property-filter-vue'
    );

    const formatPriceTooltip = (val) => `${val} ₽`;

    // ---------------- WATCH ----------------
    watch(priceRangeValue, (val) => {
        [filters.price_min, filters.price_max] = val;
    }, { deep: true });

    function syncPriceInputs() {
        priceRangeValue.value = [filters.price_min, filters.price_max];
    }

    // ---------------- INIT ----------------
    function initFilters() {
        if (!Array.isArray(props.initFilters)) return;

        props.initFilters.forEach(filter => {
            if (!filter?.key) return;

            const { key, value } = filter;

            switch (key) {
                case 'location':
                    locationInput.value = value || '';
                    locationSlug.value = value || '';
                    break;

                case 'checkin_date':
                    if (value && moment(value, 'YYYY-MM-DD', true).isValid()) {
                        filters.checkin_date = value;
                    }
                    break;

                case 'checkout_date':
                    if (value && moment(value, 'YYYY-MM-DD', true).isValid()) {
                        filters.checkout_date = value;
                    }
                    break;

                case 'adults':
                    filters.adults = Number(value) || CONFIG.guests.adults.min;
                    break;

                case 'children':
                    filters.children = Number(value) || CONFIG.guests.children.min;
                    break;
            }
        });

        syncPriceInputs();
    }

    onMounted(initFilters);

    // ---------------- AUTOCOMPLETE ----------------
    let timeout = null;

    function querySearch(query, cb) {
        if (query.length < 2) return cb([]);

        clearTimeout(timeout);

        timeout = setTimeout(() => {
            ApiService.post(props.ajaxUrl, {
                action: 'get_locations_autocomplete',
                term: query,
                nonce: props.nonce
            }).then(res => {
                if (res?.success) {
                    cb(res.data.map(i => ({
                        value: i.name,
                        name: i.name,
                        slug: i.slug
                    })));
                } else {
                    cb([]);
                }
            });
        }, 300);
    }

    function handleLocationSelect(item) {
        locationInput.value = item.name;
        locationSlug.value = item.slug;
    }

    // ---------------- VALIDATION ----------------
    function validate() {
        if (filters.checkin_date && filters.checkout_date) {
            if (moment(filters.checkin_date).isAfter(moment(filters.checkout_date))) {
                throw new Error('Дата заезда позже выезда');
            }
        }
    }

    // ---------------- API ----------------
    async function fetchProperties() {
        isLoading.value = true;

        try {
            validate();

            const res = await ApiService.post(props.ajaxUrl, {
                action: 'filter_properties_custom',
                nonce: props.nonce,
                location: locationInput.value,
                ...filters,
                page: 1
            });

            return res;

        } finally {
            isLoading.value = false;
        }
    }

    // ---------------- RESET ----------------
    function reset() {
        filters.price_min = Number(props.priceRange?.min) || CONFIG.price.min;
        filters.price_max = Number(props.priceRange?.max) || CONFIG.price.max;

        filters.checkin_date = moment().format('YYYY-MM-DD');
        filters.checkout_date = moment().add(7, 'days').format('YYYY-MM-DD');

        filters.adults = CONFIG.guests.adults.min;
        filters.children = CONFIG.guests.children.min;

        locationInput.value = '';
        locationSlug.value = '';

        syncPriceInputs();
    }

    // ---------------- DATE HELPERS ----------------
    function disabledCheckinDate(date) {
        return moment(date).isBefore(moment(), 'day');
    }

    function disabledCheckoutDate(date) {
        if (filters.checkin_date) {
            return moment(date).isBefore(moment(filters.checkin_date), 'day');
        }
        return moment(date).isBefore(moment(), 'day');
    }

    return {
        filters,
        priceRangeValue,
        locationInput,
        locationSlug,
        isLoading,

        safeStep,
        formId,
        formatPriceTooltip,

        querySearch,
        handleLocationSelect,
        fetchProperties,
        reset,
        syncPriceInputs,

        disabledCheckinDate,
        disabledCheckoutDate,

        CONFIG
    };
}