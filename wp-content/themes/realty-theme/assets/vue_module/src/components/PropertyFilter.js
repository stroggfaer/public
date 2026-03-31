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

import { ref } from 'vue';
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
            v-model="filters.locationInput"
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
    const {
      filters,
      priceRangeValue,
      isLoading,
      safeStep,
      formId,
      formatPriceTooltip,
      querySearch,
      handleLocationSelect,
      fetchProperties,
      reset,
      syncPriceInputs,
      setFilter,

      disabledCheckinDate,
      disabledCheckoutDate,

      CONFIG
    } = usePropertyFilters({
      priceRange: props.priceRange,
      context: props.context,
      initFilters: props.initFilters,
      ajaxUrl: props.ajaxUrl,
      nonce: props.nonce
    });

    function handleSubmit() {
      fetchProperties()
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
                resultsInfo.innerHTML = total > 0
                    ? `<p>Всего ${total}</p>`
                    : '<p>Недвижимость не найдена.</p>';
              }
            }
          })
          .catch(error => {
            console.error(error);
          });
    }

    function onPriceInput() {
      syncPriceInputs();
    }

    function resetFilter() {
      reset();
      handleSubmit();
    }

    return {
      filters,
      priceRangeValue,
      isLoading,
      safeStep,
      formId,
      formatPriceTooltip,

      querySearch,
      handleLocationSelect,
      handleSubmit,
      resetFilter,
      onPriceInput,

      disabledCheckinDate,
      disabledCheckoutDate,

      adultsMin: CONFIG.guests.adults.min,
      adultsMax: CONFIG.guests.adults.max,
      childrenMin: CONFIG.guests.children.min,
      childrenMax: CONFIG.guests.children.max
    };
  }
};