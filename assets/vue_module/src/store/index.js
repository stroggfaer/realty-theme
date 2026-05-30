// index.js
import propertyFiltersModule from './modules/propertyFiltersModule.js';

// Store для Vue 3 с использованием Vuex
const store = Vuex.createStore({
    // Подключаем модули
    modules: {
        propertyFilters: propertyFiltersModule
    },
    
    // Глобальное состояние
    state() {
        return {
            version: '1.0.0'
        };
    },
    
    mutations: {
        setVersion(state, version) {
            state.version = version;
        }
    },
    
    actions: {
        setVersion({ commit }, version) {
            commit('setVersion', version);
        }
    },
    
    getters: {
        getVersion: (state) => state.version
    }
});

// Глобальный доступ к store
window.store = store;

export default store;
