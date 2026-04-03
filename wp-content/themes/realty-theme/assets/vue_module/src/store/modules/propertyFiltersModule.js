// propertyFiltersModule.js
import moment from 'moment';
function createDefaultFilters(overrides = {}) {
    return {
        locationInput: '',
        locationSlug: '',
        price_min: 0,
        price_max: 1000000,
        checkin_date: moment().format('YYYY-MM-DD'),
        checkout_date: moment().add(7, 'days').format('YYYY-MM-DD'),
        adults: 1,
        children: 0,
        ...overrides
    };
}
const ALLOWED_KEYS = [
    'locationInput',
    'locationSlug',
    'price_min',
    'price_max',
    'checkin_date',
    'checkout_date',
    'adults',
    'children'
];
function normalizeFilters(input = {}) {
    const map = {
        location: 'locationInput',
        location_slug: 'locationSlug',
    };

    const result = {};

    Object.keys(input).forEach(key => {
        const newKey = map[key] || key;

        if (!ALLOWED_KEYS.includes(newKey)) return;
        let value = input[key];

        if (['price_min', 'price_max', 'adults', 'children'].includes(newKey)) {
            value = Number(value);
        }

        result[newKey] = value;
    });

    return result;
}

const propertyFiltersModule = {
    namespaced: true,

    state() {
        return {
            filters: createDefaultFilters(),

            initFilters: {
                config: null,
                initFilters: null,
                activeFilter: null,
            },

            data: [],
            meta: {
                current_page: 1,
                page_count: 1,
                per_page: 4,
                total: 0
            },
            isLoading: false,
        };
    },

    mutations: {
        SET_FILTERS(state, filters) {
            state.filters = { ...state.filters, ...filters };
        },

        SET_FILTER(state, { key, value }) {
            state.filters[key] = value;
        },

        SET_DATA(state, data) {
            state.data = data;
        },

        SET_META(state, meta) {
            state.meta = { ...state.meta, ...meta };
        },

        SET_LOADING(state, isLoading) {
            state.isLoading = isLoading;
        },

        RESET_FILTERS(state, payload = {}) {
            state.filters = createDefaultFilters(payload);
            state.meta.current_page = 1;
        },

        SET_INIT(state, data) {
            state.initFilters = data;
        },
    },

    actions: {
        setFilter({ commit }, payload) {
            commit('SET_FILTER', payload);
        },

        setFilters({ commit }, filters) {
            commit('SET_FILTERS', filters);
        },

        resetFilters({ commit }, payload = {}) {
            const normalized = normalizeFilters(payload);
            commit('RESET_FILTERS', normalized);
        },

        setLoading({ commit }, val) {
            commit('SET_LOADING', val);
        },

        setData({ commit }, data) {
            commit('SET_DATA', data);
        },

        setMeta({ commit }, meta) {
            commit('SET_META', meta);
        },

        setInit({ commit }, data) {
            commit('SET_INIT', data);
        },

        initializeFilters({ commit }, filters = {}) {
            const normalized = normalizeFilters(filters);
            commit('SET_FILTERS', normalized);
        }
    },

    getters: {
        getFilters: (state) => state.filters,
        getData: (state) => state.data,
        getMeta: (state) => state.meta,
        getIsLoading: (state) => state.isLoading,
        getPage: (state) => state.meta.current_page,
        getTotal: (state) => state.meta.total,
        getPageCount: (state) => state.meta.page_count,
        getInit: (state) => state.initFilters,
    }
};

export default propertyFiltersModule;