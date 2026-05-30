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
        characteristics: [],
        characteristics_logic: 'OR', // Всегда OR для фильтров
        property_types: [],
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
    // adults и children удалены отсюда - они теперь обрабатываются через isGuestKey()
    'characteristics',
    'characteristics_logic',
    'property_types',
];

// Проверка является ли ключ динамическим типом гостя
function isGuestKey(key) {
    // Разрешаем любые ключи которые не являются стандартными полями
    const nonGuestKeys = [
        'locationInput', 'locationSlug', 'location', 'location_slug',
        'price_min', 'price_max',
        'checkin_date', 'checkout_date',
        // adults и children удалены - теперь они обрабатываются как динамические guest keys
        'characteristics', 'characteristics_logic',
        'property_types',
        'page', 'per_page', 'paged', 'orderby', 'order'
    ];
    return !nonGuestKeys.includes(key);
}
function normalizeFilters(input = {}) {
    const map = {
        location: 'locationInput',
        location_slug: 'locationSlug',
    };
    const result = {};

    Object.keys(input).forEach(key => {
        const newKey = map[key] || key;

        // Разрешаем стандартные ключи ИЛИ динамические guest типы
        if (!ALLOWED_KEYS.includes(newKey) && !isGuestKey(newKey)) return;
        let value = input[key];

        if (['price_min', 'price_max', 'adults', 'children'].includes(newKey) || isGuestKey(newKey)) {
            value = Number(value) || 0;
        }
        
        // Обработка массивов (characteristics)
        if (newKey === 'characteristics') {
            if (typeof value === 'string') {
                try {
                    value = JSON.parse(value);
                } catch (e) {
                    value = [];
                }
            }
            if (!Array.isArray(value)) {
                value = [];
            }
        }

        // Обработка массивов (property_types)
        if (newKey === 'property_types') {
            if (typeof value === 'string') {
                try {
                    value = JSON.parse(value);
                } catch (e) {
                    value = [];
                }
            }
            if (!Array.isArray(value)) {
                value = [];
            }
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
            coordinates: [], // Accumulated coordinates for map markers
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

        ADD_COORDINATES(state, coordinates) {
            // Append new coordinates to existing ones (for pagination)
            if (Array.isArray(coordinates)) {
                state.coordinates = [...state.coordinates, ...coordinates];
            }
        },

        SET_COORDINATES(state, coordinates) {
            // Replace all coordinates (for initial load or filter reset)
            state.coordinates = Array.isArray(coordinates) ? coordinates : [];
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

        addCoordinates({ commit }, coordinates) {
            commit('ADD_COORDINATES', coordinates);
        },

        setCoordinates({ commit }, coordinates) {
            commit('SET_COORDINATES', coordinates);
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
        getCoordinates: (state) => state.coordinates,
        getMeta: (state) => state.meta,
        getIsLoading: (state) => state.isLoading,
        getPage: (state) => state.meta.current_page,
        getTotal: (state) => state.meta.total,
        getPageCount: (state) => state.meta.page_count,
        getInit: (state) => state.initFilters,
    }
};

export default propertyFiltersModule;