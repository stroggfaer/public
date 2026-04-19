import {onMounted, ref, watch} from 'vue';
import moment from 'moment';
import usePropertyFilters from '../hooks/usePropertyFilters';
export default {

  template: `
    <el-form :id="formId" @submit.prevent="handleSubmit" label-position="top">
      <!-- Цена – слайдер с двумя ползунками и инпутами -->
      <el-form-item label="Цена">
        <div class="price-range-wrapper">
          <div class="price-inputs-row">
            <el-input
                v-model.number="localFilters.price_min"
                type="number"
                :min="Number(priceRange.min) || 0"
                :max="localFilters.price_max"
                placeholder="{{ priceRange.min || 0 }}"
                size="small"
                class="price-input"
                @input="onPriceInput"
            />
            <el-input
                v-model.number="localFilters.price_max"
                type="number"
                :min="localFilters.price_min"
                :max="Number(priceRange.max)"
                placeholder="{{ priceRange.max }}"
                size="small"
                class="price-input"
                @input="onPriceInput"
            />
          </div>
          <el-slider
              v-model="localPriceRangeValue"
              range
              :min="Number(priceRange.min) || 0"
              :max="Number(priceRange.max) || 1000000"
              :step="safeStep"
              :format-tooltip="formatPriceTooltip"
          />
        </div>
      </el-form-item>



      <!-- Actions -->
      <el-form-item>
        <el-button type="primary" native-type="submit" :loading="loadingBtn">
          Применить
        </el-button>

        <el-button @click="resetFilter">
          Сбросить
        </el-button>
      </el-form-item>
      

    </el-form>
  `,
  props: {
    activeFilters: Array,
    priceRange: Object,
    config: {
      type: Object,
      default: null
    },
    initFilters: Array,
    ajaxUrl: String,
    nonce: String,
    context: String
  },

  setup(props) {
    const {
      filters,
      priceRangeValue,
      safeStep,
      formId,
      formatPriceTooltip,
      querySearch,
      reset,
      initFilters,
      validate,
      fetchPropertiesRender,
      initFiltersFromStore
    } = usePropertyFilters({
      config: props.config,
      context: props.context,
      initFilters: props.initFilters,
      ajaxUrl: props.ajaxUrl,
      nonce: props.nonce
    });

    const store = Vuex.useStore();

    const loadingBtn = ref(false);

    // Локальные фильтры для формы
    const localFilters = ref({ ...filters.value });
    const localPriceRangeValue = ref([...priceRangeValue.value]);

    // Синхронизация локальных фильтров с store
    watch(filters, (newVal) => {
      localFilters.value = { ...newVal };
    }, { immediate: true });

    watch(priceRangeValue, (newVal) => {
      localPriceRangeValue.value = [...newVal];
    }, { immediate: true });

    // Локальная синхронизация цены
    function localSyncPriceInputs() {
      localPriceRangeValue.value = [localFilters.value.price_min, localFilters.value.price_max];
    }

    // Watch для локального слайдера
    watch(localPriceRangeValue, (val) => {
      if (Array.isArray(val) && val.length === 2) {
        localFilters.value.price_min = val[0];
        localFilters.value.price_max = val[1];
      }
    });

    // Фильтры запуск;
    async function handleSubmit() {
      try {
        // Обновляем store только при сабмите
        store.commit('propertyFilters/SET_FILTERS', localFilters.value);
        loadingBtn.value = true;
        await fetchPropertiesRender();
        loadingBtn.value = false;
      } catch (e) {
        // Можно добавить уведомление об ошибке
        console.error(e.message);
      }
    }

    function onPriceInput() {
      localSyncPriceInputs();
    }

    async function resetFilter() {
      reset();
      // Обновляем локальные фильтры после обновления store
      localFilters.value = { ...filters.value };
      localPriceRangeValue.value = [localFilters.value.price_min, localFilters.value.price_max];
      await handleSubmit();
    }

    onMounted(()=>{
        initFiltersFromStore();
        store.commit('propertyFilters/SET_INIT', {
          config: props.config,
          initFilters: props.initFilters,
          activeFilter: props.activeFilters,
        });
    });

    return {
      localFilters,
      localPriceRangeValue,
      loadingBtn,
      safeStep,
      formId,
      formatPriceTooltip,
      querySearch,
      handleSubmit,
      resetFilter,
      onPriceInput,
      priceRange: props.config.price_range,
      initFilters
    };
  }
};