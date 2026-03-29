// composables/useProperty.js
import { ref, reactive, computed, watch, onMounted } from 'vue';
import { ApiService } from '../services/api';
import usePropertyFilters from "@assets/vue_module/src/hooks/usePropertyFilters";


export default function useProperty() {
    const {} = usePropertyFilters();
    const dataGrid = reactive({
        data: [],
    })
    return {
        dataGrid
    };
}