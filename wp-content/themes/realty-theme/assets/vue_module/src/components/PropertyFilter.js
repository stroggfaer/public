import { ref, reactive, computed } from 'vue';
import {
  ElInput,
  ElInputNumber,
  ElDatePicker,
  ElButton,
  ElForm,
  ElFormItem,
  ElAutocomplete,
  ElMessage
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
    ElAutocomplete
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
          <!-- 🔥 PREFIX ICON -->
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

        <input type="hidden" name="location_slug" v-model="locationSlug" />
      </el-form-item>

      <!-- Цена -->
      <el-form-item label="Цена от">
        <el-input
            v-model="filters.price_min"
            :min="priceRange.min"
            :max="priceRange.max"
            :step="priceRange.step"
        />
      </el-form-item>

      <el-form-item label="до">
        <el-input
            v-model="filters.price_max"
            :min="priceRange.min"
            :max="priceRange.max"
            :step="priceRange.step"
        />
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

      <!--ГОСТИ (НОВЫЙ ПОДХОД) -->
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
    ajaxUrl: String,
    nonce: String,
    context: String
  },

  setup(props) {
    const adultsMin = 1;
    const adultsMax = 10;
    const childrenMin = 0;
    const childrenMax = 8;

    const filters = reactive({
      price_min: null,
      price_max: null,
      checkin_date: '',
      checkout_date: '',
      adults: 1,
      children: 0
    });

    const locationInput = ref('');
    const locationSlug = ref('');
    const isLoading = ref(false);

    const formId = computed(() =>
        props.context === 'archive'
            ? 'property-filter-vue-archive'
            : 'property-filter-vue'
    );

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
          } else cb([]);
        });
      }, 300);
    }

    function handleLocationSelect(item) {
      locationInput.value = item.name;
      locationSlug.value = item.slug;
    }

    function validateForm() {
      if (filters.checkin_date && filters.checkout_date) {
        if (new Date(filters.checkin_date) > new Date(filters.checkout_date)) {
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
        location: locationSlug.value || locationInput.value,
        location_slug: locationSlug.value,
        ...filters,
        page: 1
      })
          .then(res => {
            if (res?.success) {
              // Обновляем список объектов недвижимости
              const propertyList = document.querySelector('.property-list');
              if (propertyList) {
                propertyList.innerHTML = res.data.html || '<p>Недвижимость не найдена.</p>';
              }
              console.log('handleSubmit',res.data);
              // Обновляем пагинацию
              const pagination = document.querySelector('.archive-pagination');
              if (pagination) {
                pagination.innerHTML = res.data.pagination || '';
              }
              
              // Обновляем информацию о результатах
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

    // Функция склонения числительных
    function declension(words, number) {
      const num = Math.abs(number) % 100;
      const n = num % 10;
      if (num > 10 && num < 20) return [number, words[2]];
      if (n > 1 && n < 5) return [number, words[1]];
      if (n === 1) return [number, words[0]];
      return [number, words[2]];
    }

    function resetFilter() {
      Object.assign(filters, {
        price_min: null,
        price_max: null,
        checkin_date: '',
        checkout_date: '',
        adults: 1,
        children: 0
      });

      locationInput.value = '';
      locationSlug.value = '';

      handleSubmit();
    }

    function disabledCheckinDate(date) {
      return date < new Date();
    }

    function disabledCheckoutDate(date) {
      if (filters.checkin_date) {
        return date < new Date(filters.checkin_date);
      }
      return date < new Date();
    }

    return {
      filters,
      locationInput,
      locationSlug,
      isLoading,
      formId,

      adultsMin,
      adultsMax,
      childrenMin,
      childrenMax,

      querySearch,
      handleLocationSelect,
      handleSubmit,
      resetFilter,
      disabledCheckinDate,
      disabledCheckoutDate
    };
  }
};