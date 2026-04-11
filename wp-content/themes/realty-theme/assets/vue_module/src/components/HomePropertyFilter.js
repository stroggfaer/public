import { ref, onMounted, watch, h } from 'vue';
import moment from 'moment';
import usePropertyFilters from '../hooks/usePropertyFilters';

export default {

  template: `
    <form id="property-home-filter-form" class="property-filter-form home-filter" @submit.prevent="handleSubmit">
      <div class="filter-row">
        <!-- Локация -->
        <el-autocomplete
              v-if="activeFilters.includes('location')"
              v-model="locationInput"
              :fetch-suggestions="querySearch"
              placeholder="Город или регион"
              class="location-autocomplete"
              @select="localHandleLocationSelect"
              clearable
          >
            <template #prefix>
              <span class="icon material-symbols-outlined">location_on</span>
            </template>
            <template #default="{ item }">
              <span>{{ item.name }}</span>
            </template>
            <template #empty>
              <span>Ничего не найдено</span>
            </template>
        </el-autocomplete>
        <div class="line"></div>
        <!-- Даты (Date Range) -->
        <el-date-picker
            v-if="activeFilters.includes('checkin_date') && activeFilters.includes('checkout_date')"
            v-model="dateRange"
            type="daterange"
            range-separator="-"
            start-placeholder="Заезд"
            end-placeholder="Выезд"
            format="D MMM"
            value-format="YYYY-MM-DD"
            :disabled-date="disabledCheckinDate"
            @change="handleDateChange"
            :prefix-icon="{ render: DateRangeIcon }"/>
        <div class="line"></div>
        <!-- Гости -->
        <div v-if="activeFilters.includes('adults') || activeFilters.includes('children')" class="input-group__com guests-group">
          <el-popover
            v-model:visible="guestsPopoverVisible"
            placement="bottom-start"
            :width="300"
            trigger="click"
          >
            <template #reference>
              <div class="input-with-icon">
                <span class="icon material-symbols-outlined">group</span>
                <input
                  type="text" 
                  readonly 
                  :value="guestsDisplayText" 
                  class="input__com guests-placeholder" 
                  :placeholder="guestsDisplayText"
                >
              </div>
            </template>
            
            <div class="guest-controls">
              <div v-if="activeFilters.includes('adults')" class="guest-control">
                <div class="flex-label">
                  <span class="guest-label">Взрослые</span>
                  <span class="guest-desc">от 13 лет</span>
                </div>
                <div class="guest-input">
                  <el-input-number
                    v-model="adults"
                    :min="config.guests.adults.min"
                    :max="config.guests.adults.max"
                    :controls="true"
                  />
                </div>
              </div>
              <div v-if="activeFilters.includes('children')" class="guest-control">
                <div class="flex-label">
                  <span class="guest-label">Дети</span>
                  <span class="guest-desc">от 2 до 12 лет</span>
                </div>
                <div class="guest-input">
                  <el-input-number
                    v-model="children"
                    :min="config.guests.children.min"
                    :max="config.guests.children.max"
                    :controls="true"
                  />
                </div>
              </div>
            </div>
          </el-popover>
        </div>
      </div>
      
      <div class="filter-actions">
        <button type="submit" class="button__com lg">
          <span class="icon material-symbols-outlined">search</span>
          <span class="text-b">Искать</span>
        </button>
      </div>
    </form>
  `,

  props: {
    activeFilters: {
      type: Array,
      default: () => ['location', 'checkin_date', 'checkout_date', 'adults', 'children']
    },
    config: {
      type: Object,
      default: () => ({
        guests: {
          adults: { min: 1, max: 10 },
          children: { min: 0, max: 8 }
        }
      })
    },
    ajaxUrl: {
      type: String,
      default: ''
    },
    nonce: {
      type: String,
      default: ''
    }
  },

  setup(props) {
    const DateRangeIcon = () => h('span', {
      class: 'icon material-symbols-outlined',
      style: 'font-size: 24px;'
    }, 'date_range')
    // Переиспользуем хук для autocomplete и валидации дат
    const {
      querySearch,
      handleLocationSelect,
      disabledCheckinDate,
      disabledCheckoutDate,
      filters
    } = usePropertyFilters({
      config: props.config,
      context: 'home',
      initFilters: [],
      ajaxUrl: props.ajaxUrl,
      nonce: props.nonce
    });

    const locationInput = ref('');
    const locationSlug = ref('');
    const dateRange = ref([]);
    const checkinDate = ref('');
    const checkoutDate = ref('');
    const adults = ref(1);
    const children = ref(0);
    const guestsPopoverVisible = ref(false);

    // Локальная обертка для handleLocationSelect
    function localHandleLocationSelect(item) {
      locationInput.value = item.name;
      locationSlug.value = item.slug;
      handleLocationSelect(item);
    }

    // Даты - используем функции из хука
    function handleDateChange(val) {
      if (val && val.length === 2) {
        checkinDate.value = val[0];
        checkoutDate.value = val[1];
      } else {
        checkinDate.value = '';
        checkoutDate.value = '';
      }
    }

    function declension(forms, number) {
      const count = Math.abs(number);
      const cases = [2, 0, 1, 1, 1, 2];
      const chosenCase = (count % 100 > 4 && count % 100 < 20) ? 2 : cases[(count % 10) < 5 ? count % 5 : 5];
      
      return {
        count: number,
        text: forms[chosenCase]
      };
    }

    const guestsDisplayText = ref('Гости');

    function updateGuestsDisplay() {
      const total = adults.value + children.value;
      if (total > 0) {
        // Показываем детальную информацию: "2 взрослых, 1 ребенок"
        const parts = [];
        
        if (adults.value > 0) {
          const adultObj = declension(['взрослых', 'взрослый', 'взрослых'], adults.value);
          parts.push(`${adults.value} ${adultObj.text}`);
        }
        
        if (children.value > 0) {
          const childrenObj = declension(['детей', 'ребенок', 'ребенка'], children.value);
          parts.push(`${children.value} ${childrenObj.text}`);
        }
        guestsDisplayText.value = parts.join(', ');
      } else {
        guestsDisplayText.value = 'Гости';
      }
      console.log('updateGuestsDisplay вызван, adults =', adults.value);
    }

    const guestsPopover = () => {}

    const initFormData = (newVal) => {
      locationInput.value = newVal.locationInput;
      locationSlug.value = newVal.locationSlug;
      checkinDate.value = newVal.checkin_date;
      checkoutDate.value = newVal.checkout_date;
      adults.value = newVal.adults;
      children.value = newVal.children;

      // Синхронизируем dateRange для ElDatePicker
      if (newVal.checkin_date && newVal.checkout_date) {
        dateRange.value = [newVal.checkin_date, newVal.checkout_date];
      } else {
        dateRange.value = [];
      }
    }

    // Автоматически обновляем текст при изменении гостей
    watch([adults, children], () => {
      updateGuestsDisplay();
    });

    watch(filters, (newVal) => {
      initFormData(newVal);
    }, { immediate: true });

    // Отправка формы
    function handleSubmit() {
      const params = new URLSearchParams();
      
      if (locationInput.value) {
        params.append('location', locationInput.value);
      }
      if (locationSlug.value) {
        params.append('location_slug', locationSlug.value);
      }
      
      if (checkinDate.value) {
        params.append('checkin_date', checkinDate.value);
      }
      
      if (checkoutDate.value) {
        params.append('checkout_date', checkoutDate.value);
      }
      
      if (adults.value) {
        params.append('adults', adults.value);
      }
      
      if (children.value) {
        params.append('children', children.value);
      }
      
      const archiveUrl = '/property/' + (params.toString() ? '?' + params.toString() : '');
      window.location.href = archiveUrl;
    }

    onMounted(() => {
      updateGuestsDisplay();
    });

    return {
      locationInput,
      locationSlug,
      dateRange,
      checkinDate,
      checkoutDate,
      adults,
      children,
      guestsPopoverVisible,
      guestsDisplayText,
      querySearch,
      localHandleLocationSelect,
      disabledCheckinDate,
      handleDateChange,
      updateGuestsDisplay,
      handleSubmit,
      DateRangeIcon,
      guestsPopover
    };
  }
};
