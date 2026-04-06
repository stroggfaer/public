import {
  ElInput,
  ElInputNumber,
  ElDatePicker,
  ElButton,
  ElForm,
  ElFormItem,
  ElAutocomplete,
  ElSlider
} from 'element-plus';
import {onMounted, ref, watch} from 'vue';
import moment from 'moment';
import usePropertyFilters from '../hooks/usePropertyFilters';
export default {
  components: {
    ElInput,
    ElInputNumber,
    ElDatePicker,
    ElButton,
    ElForm,
    ElFormItem,
    ElAutocomplete,
    ElSlider
  },

  template: `
    <el-form :id="formId" @submit.prevent="handleSubmit" label-position="top">
      <!-- Локация -->
      <el-form-item v-if="activeFilters.includes('location')" label="Город">
        <el-autocomplete
            ref="autocompleteRef"
            v-model="localFilters.locationInput"
            :fetch-suggestions="querySearch"
            placeholder="Город или регион"
            @select="localHandleLocationSelect"
            clearable
        >
          <template #prefix>
            <span class="material-symbols-outlined">location_on</span>
          </template>

          <template #default="{ item }">
            <span>{{ item.name }}</span>
          </template>

          <template #empty>
            <span>Ничего не найдено</span>
          </template>
        </el-autocomplete>
      </el-form-item>
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

      <!-- Даты -->
      <el-form-item v-if="activeFilters.includes('checkin_date')" label="Дата заезда">
        <el-date-picker
            v-model="localFilters.checkin_date"
            type="date"
            value-format="YYYY-MM-DD"
            :disabled-date="disabledCheckinDate"
        />
      </el-form-item>

      <el-form-item v-if="activeFilters.includes('checkout_date')" label="Дата выезда">
        <el-date-picker
            v-model="localFilters.checkout_date"
            type="date"
            value-format="YYYY-MM-DD"
            :disabled-date="disabledCheckoutDate"
        />
      </el-form-item>

      <!-- ГОСТИ -->
      <el-form-item v-if="activeFilters.includes('adults')" label="Взрослые">
        <el-input-number
            v-model="localFilters.adults"
            :min="adultsMin"
            :max="adultsMax"
        />
      </el-form-item>

      <el-form-item v-if="activeFilters.includes('children')" label="Дети">
        <el-input-number
            v-model="localFilters.children"
            :min="childrenMin"
            :max="childrenMax"
        />
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
      handleLocationSelect,
      fetchProperties,
      reset,
      syncPriceInputs,
      initFilters,
      disabledCheckinDate,
      disabledCheckoutDate,
      validate,
      fetchPropertiesRender
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

    // Локальная функция для выбора локации
    function localHandleLocationSelect(item) {
      localFilters.value.locationInput = item.name;
      localFilters.value.locationSlug = item.slug;
    }

    // Локальная валидация
    function localValidate() {
      const f = localFilters.value;

      if (f.checkin_date && f.checkout_date) {
        if (moment(f.checkin_date).isAfter(moment(f.checkout_date))) {
          throw new Error('Дата заезда позже выезда');
        }
      }

      if (f.price_min > f.price_max) {
        throw new Error('Некорректный диапазон цены');
      }

      if (f.adults < 1) {
        throw new Error('Должен быть хотя бы 1 взрослый');
      }

      return true;
    }

    // Фильтры запуск;
    async function handleSubmit() {
      try {
        localValidate();
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
      localHandleLocationSelect,
      formatPriceTooltip,
      querySearch,
      handleSubmit,
      resetFilter,
      onPriceInput,
      disabledCheckinDate,
      disabledCheckoutDate,
      priceRange: props.config.price_range,
      adultsMin: props.config.guests.adults.min,
      adultsMax: props.config.guests.adults.max,
      childrenMin: props.config.guests.children.min,
      childrenMax: props.config.guests.children.max,
      initFilters
    };
  }
};