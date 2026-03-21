import { ref, reactive, computed, onMounted, watch, inject } from 'vue';

import {
  ElInput,
  ElInputNumber,
  ElDatePicker,
  ElButton,
  ElForm,
  ElFormItem,
  ElAutocomplete,
  ElMessage,
  ElSlider
} from 'element-plus';

import {ApiService} from '../services/api';

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
            v-model="locationInput"
            :fetch-suggestions="querySearch"
            placeholder="Город или регион"
            @select="handleLocationSelect"
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
                v-model.number="filters.price_min"
                type="number"
                :min="Number(priceRange.min) || 0"
                :max="filters.price_max"
                placeholder="{{ priceRange.min || 0 }}"
                size="small"
                class="price-input"
                @input="onPriceInput"
            />
            <el-input
                v-model.number="filters.price_max"
                type="number"
                :min="filters.price_min"
                :max="Number(priceRange.max) || 1000000"
                placeholder="{{ priceRange.max || 1000000 }}"
                size="small"
                class="price-input"
                @input="onPriceInput"
            />
          </div>
          <el-slider
              v-model="priceRangeValue"
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
            v-model="filters.checkin_date"
            type="date"
            value-format="YYYY-MM-DD"
            :disabled-date="disabledCheckinDate"
        />
      </el-form-item>

      <el-form-item v-if="activeFilters.includes('checkout_date')" label="Дата выезда">
        <el-date-picker
            v-model="filters.checkout_date"
            type="date"
            value-format="YYYY-MM-DD"
            :disabled-date="disabledCheckoutDate"
        />
      </el-form-item>

      <!-- ГОСТИ -->
      <el-form-item v-if="activeFilters.includes('adults')" label="Взрослые">
        <el-input-number
            v-model="filters.adults"
            :min="adultsMin"
            :max="adultsMax"
        />
      </el-form-item>

      <el-form-item v-if="activeFilters.includes('children')" label="Дети">
        <el-input-number
            v-model="filters.children"
            :min="childrenMin"
            :max="childrenMax"
        />
      </el-form-item>

      <!-- Actions -->
      <el-form-item>
        <el-button type="primary" native-type="submit">
          Применить
        </el-button>

        <el-button @click="resetFilter">
          Сбросить
        </el-button>
      </el-form-item>

      <!-- Loading -->
      <div v-if="isLoading">
        Загрузка...
      </div>

    </el-form>
  `,

  props: {
    activeFilters: Array,
    priceRange: Object,
    initFilters: Array,
    ajaxUrl: String,
    nonce: String,
    context: String
  },

  setup(props) {
    const moment = inject('moment');
    const adultsMin = 1;
    const adultsMax = 10;
    const childrenMin = 0;
    const childrenMax = 8;

    const filters = reactive({
      price_min: Number(props.priceRange?.min) || 0,
      price_max: Number(props.priceRange?.max) || 1000000,
      checkin_date: moment().format('YYYY-MM-DD'),
      checkout_date: moment().add(7, 'days').format('YYYY-MM-DD'),
      adults: 1,
      children: 0
    });

    const priceRangeValue = ref([
      filters.price_min,
      filters.price_max
    ]);

    const safeStep = computed(() => {
      const step = Number(props.priceRange?.step ?? 1000);
      return step > 0 ? step : 1000; // защита от step = 0
    });

    // Синхронизация слайдера → filters
    watch(priceRangeValue, (newVal) => {
      [filters.price_min, filters.price_max] = newVal;
    }, { deep: true });

    // Обработчик ввода в инпуты цены
    function onPriceInput() {
      priceRangeValue.value = [filters.price_min, filters.price_max];
    }

    const locationInput = ref('');
    const locationSlug = ref('');
    const isLoading = ref(false);

    const formId = computed(() =>
        props.context === 'archive'
            ? 'property-filter-vue-archive'
            : 'property-filter-vue'
    );

    const formatPriceTooltip = (val) => `${val} ₽`;

    // Инициализация из GET-параметров
    onMounted(() => {
      console.log('[DEBUG] initFilters received:', props.initFilters);
      if (!Array.isArray(props.initFilters) || props.initFilters.length === 0) {
        console.warn('[DEBUG] Нет активных фильтров для инициализации');
      } else {
        props.initFilters.forEach(filter => {
          if (!filter || typeof filter !== 'object' || !filter.key) return;

          const key = filter.key;
          const value = filter.value;

          console.log(`[DEBUG] Applying filter:`, filter);

          switch (key) {
            case 'location':
              locationSlug.value = value || '';
              locationInput.value = value || '';
              break;

            case 'checkin_date':
              if (value && moment(value, 'YYYY-MM-DD', true).isValid()) {
                filters.checkin_date = moment(value).format('YYYY-MM-DD');
              }
              break;

            case 'checkout_date':
              if (value && moment(value, 'YYYY-MM-DD', true).isValid()) {
                filters.checkout_date = moment(value).format('YYYY-MM-DD');
              }
              break;

            case 'adults':
              filters.adults = Number(value) || 1;
              break;

            case 'children':
              filters.children = Number(value) || 0;
              break;
              // case 'price_min': filters.price_min = Number(value) || props.priceRange.min; break;
              // case 'price_max': filters.price_max = Number(value) || props.priceRange.max; break;
          }
        });
      }

      // Обязательно синхронизируем слайдер после всех изменений
      priceRangeValue.value = [filters.price_min, filters.price_max];
    });

    // AUTOCOMPLETE
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

    function validateForm() {
      if (filters.checkin_date && filters.checkout_date) {
        if (moment(filters.checkin_date).isAfter(moment(filters.checkout_date))) {
          ElMessage.error('Дата заезда позже выезда');
          return false;
        }
      }
      return true;
    }

    function handleSubmit() {
      if (!validateForm()) return;

      isLoading.value = true;

      ApiService.post(props.ajaxUrl, {
        action: 'filter_properties_custom',
        nonce: props.nonce,
        location: locationInput.value,
       // location_slug: locationSlug.value,
        ...filters,
        page: 1
      })
          .then(res => {
            if (res?.success) {
              const propertyList = document.querySelector('.property-list');
              if (propertyList) {
                propertyList.innerHTML = res.data.html || '<p>Недвижимость не найдена.</p>';
              }

              const pagination = document.querySelector('.archive-pagination');
              if (pagination) {
                pagination.innerHTML = res.data.pagination || '';
              }

              const resultsInfo = document.querySelector('.results-info');
              if (resultsInfo && res.data.total !== undefined) {
                const total = res.data.total;
                if (total > 0) {
                  const obj = declension(['объект', 'объекта', 'объектов'], total);
                  resultsInfo.innerHTML = `<p>Всего ${total} ${obj[1]}</p>`;
                } else {
                  resultsInfo.innerHTML = '<p>Недвижимость не найдена.</p>';
                }
              }
            }
          })
          .catch(error => {
            console.error('Ошибка фильтрации:', error);
            const propertyList = document.querySelector('.property-list');
            if (propertyList) {
              propertyList.innerHTML = '<p>Произошла ошибка при загрузке результатов.</p>';
            }
          })
          .finally(() => {
            isLoading.value = false;
          });
    }

    function declension(words, number) {
      const num = Math.abs(number) % 100;
      const n = num % 10;
      if (num > 10 && num < 20) return [number, words[2]];
      if (n > 1 && n < 5) return [number, words[1]];
      if (n === 1) return [number, words[0]];
      return [number, words[2]];
    }

    function resetFilter() {
      filters.price_min = Number(props.priceRange?.min) || 0;
      filters.price_max = Number(props.priceRange?.max) || 1000000;
      priceRangeValue.value = [filters.price_min, filters.price_max];

      // Дефолтные даты: сегодня + неделя
      filters.checkin_date = moment().format('YYYY-MM-DD');
      filters.checkout_date = moment().add(7, 'days').format('YYYY-MM-DD');
      filters.adults = 1;
      filters.children = 0;

      locationInput.value = '';
      locationSlug.value = '';

      handleSubmit();
    }

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
      safeStep,
      locationInput,
      locationSlug,
      isLoading,
      formId,
      formatPriceTooltip,

      adultsMin,
      adultsMax,
      childrenMin,
      childrenMax,

      querySearch,
      handleLocationSelect,
      handleSubmit,
      resetFilter,
      disabledCheckinDate,
      disabledCheckoutDate,
      onPriceInput
    };
  }
};