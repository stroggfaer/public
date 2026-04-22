import {onMounted, ref, watch, computed} from 'vue';
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

      <!-- Характеристики: Первые 5 групп -->
      <template v-for="group in visibleGroups" :key="group.group_id">
        <el-form-item :label="group.group_name">
          
          <!-- Checkbox -->
          <el-checkbox-group v-if="group.type_ui === 'checkbox'" v-model="localFilters.characteristics">
            <el-checkbox v-for="char in group.characteristics" :key="char.id" :label="char.id">
              {{ char.title }}
            </el-checkbox>
          </el-checkbox-group>

          <!-- Radio -->
          <el-radio-group v-else-if="group.type_ui === 'radio'" v-model="selectedRadioChar[group.group_id]" @change="onRadioChange(group.group_id)">
            <el-radio v-for="char in group.characteristics" :key="char.id" :label="char.id">
              {{ char.title }}
            </el-radio>
          </el-radio-group>

          <!-- Select -->
          <el-select v-else-if="group.type_ui === 'select'" v-model="selectedRadioChar[group.group_id]" @change="onRadioChange(group.group_id)" placeholder="Выберите">
            <el-option v-for="char in group.characteristics" :key="char.id" :label="char.title" :value="char.id" />
          </el-select>

          <!-- Multi Select -->
          <el-select v-else-if="group.type_ui === 'multi_select'" v-model="localFilters.characteristics" multiple placeholder="Выберите">
            <el-option v-for="char in group.characteristics" :key="char.id" :label="char.title" :value="char.id" />
          </el-select>

          <!-- Chips -->
          <div v-else-if="group.type_ui === 'chips'" class="chips-container">
            <el-tag 
              v-for="char in group.characteristics" 
              :key="char.id"
              :type="localFilters.characteristics.includes(char.id) ? 'primary' : 'info'"
              @click="toggleCharacteristic(char.id)"
              style="cursor: pointer; margin: 4px;"
            >
              {{ char.title }}
            </el-tag>
          </div>

          <!-- Switch -->
          <div v-else-if="group.type_ui === 'switch'">
            <div v-for="char in group.characteristics" :key="char.id" style="display: flex; align-items: center; margin-bottom: 8px;">
              <el-switch 
                :model-value="localFilters.characteristics.includes(char.id)"
                @change="(val) => toggleCharacteristic(char.id, val)"
              />
              <span style="margin-left: 8px;">{{ char.title }}</span>
            </div>
          </div>

        </el-form-item>
      </template>

      <!-- Характеристики: Остальные группы в collapse -->
      <el-collapse v-if="hiddenGroups.length > 0" v-model="expandedGroups">
        <el-collapse-item 
          v-for="group in hiddenGroups" 
          :key="group.group_id"
          :name="group.group_id" 
          :title="group.group_name"
        >
          
          <!-- Checkbox -->
          <el-checkbox-group v-if="group.type_ui === 'checkbox'" v-model="localFilters.characteristics">
            <el-checkbox v-for="char in group.characteristics" :key="char.id" :label="char.id">
              {{ char.title }}
            </el-checkbox>
          </el-checkbox-group>

          <!-- Radio -->
          <el-radio-group v-else-if="group.type_ui === 'radio'" v-model="selectedRadioChar[group.group_id]" @change="onRadioChange(group.group_id)">
            <el-radio v-for="char in group.characteristics" :key="char.id" :label="char.id">
              {{ char.title }}
            </el-radio>
          </el-radio-group>

          <!-- Select -->
          <el-select v-else-if="group.type_ui === 'select'" v-model="selectedRadioChar[group.group_id]" @change="onRadioChange(group.group_id)" placeholder="Выберите">
            <el-option v-for="char in group.characteristics" :key="char.id" :label="char.title" :value="char.id" />
          </el-select>

          <!-- Multi Select -->
          <el-select v-else-if="group.type_ui === 'multi_select'" v-model="localFilters.characteristics" multiple placeholder="Выберите">
            <el-option v-for="char in group.characteristics" :key="char.id" :label="char.title" :value="char.id" />
          </el-select>

          <!-- Chips -->
          <div v-else-if="group.type_ui === 'chips'" class="chips-container">
            <el-tag 
              v-for="char in group.characteristics" 
              :key="char.id"
              :type="localFilters.characteristics.includes(char.id) ? 'primary' : 'info'"
              @click="toggleCharacteristic(char.id)"
              style="cursor: pointer; margin: 4px;"
            >
              {{ char.title }}
            </el-tag>
          </div>

          <!-- Switch -->
          <div v-else-if="group.type_ui === 'switch'">
            <div v-for="char in group.characteristics" :key="char.id" style="display: flex; align-items: center; margin-bottom: 8px;">
              <el-switch 
                :model-value="localFilters.characteristics.includes(char.id)"
                @change="(val) => toggleCharacteristic(char.id, val)"
              />
              <span style="margin-left: 8px;">{{ char.title }}</span>
            </div>
          </div>

        </el-collapse-item>
      </el-collapse>

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

    // Характеристики
    const filterCharacteristics = ref(props.config?.filter_characteristics || []);
    const expandedGroups = ref([]);
    
    // Для radio и select (одиночный выбор)
    const selectedRadioChar = ref({});
    
    // Для checkbox: показать/скрыть дополнительные элементы (лимит 10)
    const expandedCheckboxGroups = ref({});

    // Первые 5 групп видимы, остальные в collapse
    const visibleGroups = computed(() => filterCharacteristics.value.slice(0, 5));
    const hiddenGroups = computed(() => filterCharacteristics.value.slice(5));
    
    // Функция для получения видимых характеристик в checkbox группе (лимит 10)
    function getVisibleCharacteristics(group) {
      if (group.type_ui !== 'checkbox' || !group.characteristics) {
        return group.characteristics || [];
      }
      const isExpanded = expandedCheckboxGroups.value[group.group_id];
      return isExpanded ? group.characteristics : group.characteristics.slice(0, 10);
    }
    
    // Проверка, нужна ли кнопка "Показать еще"
    function needsShowMoreButton(group) {
      return group.type_ui === 'checkbox' && group.characteristics && group.characteristics.length > 10;
    }
    
    // Переключение состояния "Показать еще" / "Скрыть"
    function toggleShowMore(groupId) {
      expandedCheckboxGroups.value[groupId] = !expandedCheckboxGroups.value[groupId];
    }

    // Toggle для chips и switch
    function toggleCharacteristic(charId, value = null) {
      const index = localFilters.value.characteristics.indexOf(charId);
      if (value === false || (value === null && index > -1)) {
        localFilters.value.characteristics.splice(index, 1);
      } else if (value === true || (value === null && index === -1)) {
        localFilters.value.characteristics.push(charId);
      }
    }

    // Обработка radio и select (одиночный выбор)
    function onRadioChange(groupId) {
      const charId = selectedRadioChar.value[groupId];
      if (charId) {
        // Удаляем все характеристики этой группы
        const group = filterCharacteristics.value.find(g => g.group_id === groupId);
        if (group) {
          const groupCharIds = group.characteristics.map(c => c.id);
          localFilters.value.characteristics = localFilters.value.characteristics.filter(
            id => !groupCharIds.includes(id)
          );
        }
        // Добавляем выбранную
        if (!localFilters.value.characteristics.includes(charId)) {
          localFilters.value.characteristics.push(charId);
        }
      }
    }

    // Обновление URL
    function updateURL() {
      const params = new URLSearchParams(window.location.search);
      
      if (localFilters.value.characteristics.length > 0) {
        params.set('characteristics', JSON.stringify(localFilters.value.characteristics));
      } else {
        params.delete('characteristics');
      }
      
      window.history.pushState({}, '', window.location.pathname + '?' + params.toString());
    }

    // Чтение из URL
    function initFromURL() {
      const params = new URLSearchParams(window.location.search);
      const characteristics = params.get('characteristics');
      
      if (characteristics) {
        try {
          const parsed = JSON.parse(characteristics);
          if (Array.isArray(parsed)) {
            store.commit('propertyFilters/SET_FILTER', { key: 'characteristics', value: parsed });
            localFilters.value.characteristics = parsed;
            
            // Инициализируем selectedRadioChar для radio/select
            filterCharacteristics.value.forEach(group => {
              if (['radio', 'select'].includes(group.type_ui)) {
                const selectedInGroup = parsed.find(id => 
                  group.characteristics.some(c => c.id === id)
                );
                if (selectedInGroup) {
                  selectedRadioChar.value[group.group_id] = selectedInGroup;
                }
              }
            });
          }
        } catch (e) {
          console.error('Error parsing characteristics from URL:', e);
        }
      }
    }

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
        updateURL(); // Обновляем URL
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
      // Полностью очищаем локальные данные
      localFilters.value = { ...filters.value };
      localFilters.value.characteristics = [];
      localFilters.value.characteristics_logic = 'OR';
      localPriceRangeValue.value = [localFilters.value.price_min, localFilters.value.price_max];
      selectedRadioChar.value = {};
      expandedGroups.value = [];
      expandedCheckboxGroups.value = {}; // Сбрасываем состояние "Показать еще"
      
      // Обновляем store
      store.commit('propertyFilters/SET_FILTER', { key: 'characteristics', value: [] });
      store.commit('propertyFilters/SET_FILTER', { key: 'characteristics_logic', value: 'OR' });
      
      // Очищаем URL
      const params = new URLSearchParams(window.location.search);
      params.delete('characteristics');
      params.delete('characteristics_logic');
      window.history.pushState({}, '', window.location.pathname + (params.toString() ? '?' + params.toString() : ''));
      
      await handleSubmit();
    }

    onMounted(()=>{
        initFromURL(); // Читаем из URL
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
      initFilters,
      filterCharacteristics,
      visibleGroups,
      hiddenGroups,
      expandedGroups,
      selectedRadioChar,
      toggleCharacteristic,
      onRadioChange,
      expandedCheckboxGroups,
      getVisibleCharacteristics,
      needsShowMoreButton,
      toggleShowMore
    };
  }
};