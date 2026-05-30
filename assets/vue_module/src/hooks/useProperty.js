// composables/useProperty.js
import { reactive } from 'vue';
import { ApiService } from '../services/api';
import useModal from "@assets/vue_module/src/hooks/useModal";

export default function useProperty() {
    const { dialogParams, onDialogClose } = useModal();

    const dataGrid = reactive({
        data: [],
    });

    const characteristics = reactive({
        loading: false,
        data: [],
        meta: {},
        filters: [],
    });

    async function fetchCharacteristics(propertyId, params = {}) {
        if (!propertyId) {
            characteristics.error = 'ID объекта недвижимости не указан';
            return;
        }

        characteristics.loading = true;

        try {
            const { data = [], meta = {}, filters = [] } = await ApiService.get(`/wp-json/property/v1/characteristics/${propertyId}`, params) || {};
            characteristics.data = data;
            characteristics.meta = meta;
            characteristics.filters = filters;
        } catch (e) {
            console.error('Error fetching characteristics:', e);
            Object.assign(characteristics, {
                data: [],
                meta: {},
                filters: [],
            });

        } finally {
            characteristics.loading = false;
        }
    }

    async function openCharacteristicsModal(propertyId, params = {}) {
        dialogParams.isVisible = true;
        await fetchCharacteristics(propertyId, params);
    }

    function closeCharacteristicsModal() {
        dialogParams.isVisible = false;
    }

    return {
        dataGrid,
        characteristics,
        dialogParams,
        onDialogClose,
        fetchCharacteristics,
        openCharacteristicsModal,
        closeCharacteristicsModal
    };
}