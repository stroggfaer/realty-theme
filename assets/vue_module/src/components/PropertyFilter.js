import {onMounted, ref, watch, computed} from 'vue';
import usePropertyFilters from '../hooks/usePropertyFilters';
export default {

  template: `
    <div class="property-filter-sidebar-wrap">
      <el-form :id="formId" @submit.prevent label-position="top">

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
                  @change="onPriceChange"
              />
              <el-input
                  v-model.number="localFilters.price_max"
                  type="number"
                  :min="localFilters.price_min"
                  :max="Number(priceRange.max)"
                  placeholder="{{ priceRange.max }}"
                  size="small"
                  class="price-input"
                  @change="onPriceChange"
              />
            </div>
            <el-slider
                v-model="localPriceRangeValue"
                range
                :min="Number(priceRange.min) || 0"
                :max="Number(priceRange.max) || 1000000"
                :step="safeStep"
                :format-tooltip="formatPriceTooltip"
                @change="applyFilters"
            />
          </div>
        </el-form-item>

        <!-- Тип жилья -->
        <el-form-item v-if="propertyTypes.length > 0" label="Тип жилья">
          <el-checkbox-group v-model="localFilters.property_types" @change="applyFilters">
            <el-checkbox
              v-for="type in visiblePropertyTypes"
              :key="type.id"
              :value="type.id"
            >
              {{ type.name }}
            </el-checkbox>
          </el-checkbox-group>
          <div v-if="propertyTypes.length > 10" class="show-more-btn-wrap">
            <el-button type="text" size="small" @click="togglePropertyTypesExpanded">
              {{ propertyTypesExpanded ? 'Скрыть' : 'Показать все' }}
            </el-button>
          </div>
        </el-form-item>

        <!-- Характеристики: Первые 5 групп -->
        <template v-for="group in visibleGroups" :key="group.group_id">
          <el-form-item :label="group.group_name">

            <!-- Checkbox -->
            <template v-if="group.type_ui === 'checkbox'">
              <el-checkbox-group v-model="localFilters.characteristics" @change="applyFilters">
                <el-checkbox
                  v-for="char in getVisibleCharacteristics(group)"
                  :key="char.id"
                  :value="char.id"
                >
                  {{ char.title }}
                </el-checkbox>
              </el-checkbox-group>
              <div v-if="needsShowMoreButton(group)" class="show-more-btn-wrap">
                <el-button type="text" size="small" @click="toggleShowMore(group.group_id)">
                  {{ expandedCheckboxGroups[group.group_id] ? 'Скрыть' : 'Показать все' }}
                </el-button>
              </div>
            </template>

            <!-- Radio -->
            <el-radio-group v-else-if="group.type_ui === 'radio'" v-model="selectedRadioChar[group.group_id]" @change="onRadioChange(group.group_id)">
              <el-radio v-for="char in group.characteristics" :key="char.id" :value="char.id">
                {{ char.title }}
              </el-radio>
            </el-radio-group>

            <!-- Select -->
            <el-select v-else-if="group.type_ui === 'select'" v-model="selectedRadioChar[group.group_id]" @change="onRadioChange(group.group_id)" placeholder="Выберите">
              <el-option v-for="char in group.characteristics" :key="char.id" :label="char.title" :value="char.id" />
            </el-select>

            <!-- Multi Select -->
            <el-select v-else-if="group.type_ui === 'multi_select'" v-model="localFilters.characteristics" multiple placeholder="Выберите" @change="applyFilters">
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
            <template v-if="group.type_ui === 'checkbox'">
              <el-checkbox-group v-model="localFilters.characteristics" @change="applyFilters">
                <el-checkbox
                  v-for="char in getVisibleCharacteristics(group)"
                  :key="char.id"
                  :value="char.id"
                >
                  {{ char.title }}
                </el-checkbox>
              </el-checkbox-group>
              <div v-if="needsShowMoreButton(group)" class="show-more-btn-wrap">
                <el-button type="text" size="small" @click="toggleShowMore(group.group_id)">
                  {{ expandedCheckboxGroups[group.group_id] ? 'Скрыть' : 'Показать все' }}
                </el-button>
              </div>
            </template>

            <!-- Radio -->
            <el-radio-group v-else-if="group.type_ui === 'radio'" v-model="selectedRadioChar[group.group_id]" @change="onRadioChange(group.group_id)">
              <el-radio v-for="char in group.characteristics" :key="char.id" :value="char.id">
                {{ char.title }}
              </el-radio>
            </el-radio-group>

            <!-- Select -->
            <el-select v-else-if="group.type_ui === 'select'" v-model="selectedRadioChar[group.group_id]" @change="onRadioChange(group.group_id)" placeholder="Выберите">
              <el-option v-for="char in group.characteristics" :key="char.id" :label="char.title" :value="char.id" />
            </el-select>

            <!-- Multi Select -->
            <el-select v-else-if="group.type_ui === 'multi_select'" v-model="localFilters.characteristics" multiple placeholder="Выберите" @change="applyFilters">
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

      </el-form>

      <!-- Фиксированная кнопка сброса — видна только если есть активные фильтры -->
      <transition name="filter-reset-fade">
        <div v-if="hasActiveFilters" class="filter-reset-sticky">
          <el-button
            :loading="loadingBtn"
            class="filter-reset-btn shadow-lg"
            @click="resetFilter"
          >
            Сбросить все фильтры
          </el-button>
        </div>
      </transition>
    </div>
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
      fetchPropertiesRender,
      initFiltersFromStore,
      setFilter
    } = usePropertyFilters({
      config: props.config,
      context: props.context,
      initFilters: props.initFilters,
      ajaxUrl: props.ajaxUrl,
      nonce: props.nonce
    });
    const limitMore = 6;
    const store = Vuex.useStore();

    const loadingBtn = ref(false);

    // Локальные фильтры для формы
    const localFilters = ref({
      ...filters.value,
      property_types: Array.isArray(filters.value?.property_types) ? [...filters.value.property_types] : [],
    });
    const localPriceRangeValue = ref([...priceRangeValue.value]);

    // Характеристики
    const filterCharacteristics = ref(props.config?.filter_characteristics || []);
    const expandedGroups = ref([]);

    // Типы жилья
    const propertyTypes = ref(Array.isArray(props.config?.property_types) ? props.config.property_types : []);
    const propertyTypesExpanded = ref(false);
    const visiblePropertyTypes = computed(() =>
      propertyTypesExpanded.value ? propertyTypes.value : propertyTypes.value.slice(0, 10)
    );
    function togglePropertyTypesExpanded() {
      propertyTypesExpanded.value = !propertyTypesExpanded.value;
    }

    // Для radio и select (одиночный выбор)
    const selectedRadioChar = ref({});

    // Для checkbox: показать/скрыть дополнительные элементы (лимит 10)
    const expandedCheckboxGroups = ref({});

    // Первые 5 групп видимы, остальные в collapse
    const visibleGroups = computed(() => filterCharacteristics.value.slice(0, 5));
    const hiddenGroups = computed(() => filterCharacteristics.value.slice(5));

    // Есть ли активные фильтры
    const priceMin = computed(() => Number(props.config?.price_range?.min) || 0);
    const priceMax = computed(() => Number(props.config?.price_range?.max) || 1000000);
    const hasActiveFilters = computed(() => {
      const f = localFilters.value;
      const priceChanged =
        (f.price_min != null && f.price_min !== priceMin.value) ||
        (f.price_max != null && f.price_max !== priceMax.value);
      const hasChars = Array.isArray(f.characteristics) && f.characteristics.length > 0;
      const hasTypes = Array.isArray(f.property_types) && f.property_types.length > 0;
      return priceChanged || hasChars || hasTypes;
    });

    // Функция для получения видимых характеристик в checkbox группе (лимит 10)
    function getVisibleCharacteristics(group) {
      if (group.type_ui !== 'checkbox' || !group.characteristics) {
        return group.characteristics || [];
      }
      const isExpanded = expandedCheckboxGroups.value[group.group_id];
      return isExpanded ? group.characteristics : group.characteristics.slice(0, limitMore);
    }

    // Проверка, нужна ли кнопка "Показать все"
    function needsShowMoreButton(group) {
      return group.type_ui === 'checkbox' && group.characteristics && group.characteristics.length > limitMore;
    }

    // Переключение состояния "Показать все" / "Скрыть"
    function toggleShowMore(groupId) {
      expandedCheckboxGroups.value[groupId] = !expandedCheckboxGroups.value[groupId];
    }

    // Toggle для chips и switch — применяем сразу
    function toggleCharacteristic(charId, value = null) {
      const index = localFilters.value.characteristics.indexOf(charId);
      if (value === false || (value === null && index > -1)) {
        localFilters.value.characteristics.splice(index, 1);
      } else if (value === true || (value === null && index === -1)) {
        localFilters.value.characteristics.push(charId);
      }
      applyFilters();
    }

    // Обработка radio и select (одиночный выбор) — применяем сразу
    function onRadioChange(groupId) {
      const charId = selectedRadioChar.value[groupId];
      if (charId) {
        const group = filterCharacteristics.value.find(g => g.group_id === groupId);
        if (group) {
          const groupCharIds = group.characteristics.map(c => c.id);
          localFilters.value.characteristics = localFilters.value.characteristics.filter(
            id => !groupCharIds.includes(id)
          );
        }
        if (!localFilters.value.characteristics.includes(charId)) {
          localFilters.value.characteristics.push(charId);
        }
      }
      applyFilters();
    }

    // Обновление URL
    function updateURL() {
      const params = new URLSearchParams(window.location.search);

      if (localFilters.value.characteristics.length > 0) {
        params.set('characteristics', JSON.stringify(localFilters.value.characteristics));
      } else {
        params.delete('characteristics');
      }

      if (Array.isArray(localFilters.value.property_types) && localFilters.value.property_types.length > 0) {
        params.set('property_types', JSON.stringify(localFilters.value.property_types));
      } else {
        params.delete('property_types');
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

      const propertyTypesParam = params.get('property_types');
      if (propertyTypesParam) {
        try {
          const parsed = JSON.parse(propertyTypesParam);
          if (Array.isArray(parsed)) {
            store.commit('propertyFilters/SET_FILTER', { key: 'property_types', value: parsed });
            localFilters.value.property_types = parsed;
          }
        } catch (e) {
          console.error('Error parsing property_types from URL:', e);
        }
      }
    }

    // Синхронизация локальных фильтров с store
    watch(filters, (newVal) => {
      localFilters.value = {
        ...newVal,
        property_types: Array.isArray(newVal?.property_types) ? newVal.property_types : [],
      };
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

    // Применить фильтры (по change)
    async function applyFilters() {
      try {
        store.commit('propertyFilters/SET_FILTERS', localFilters.value);
        updateURL();
        loadingBtn.value = true;
        await fetchPropertiesRender();
      } catch (e) {
        console.error(e.message);
      } finally {
        loadingBtn.value = false;
      }
    }

    function onPriceChange() {
      localSyncPriceInputs();
      applyFilters();
    }

    async function resetFilter() {
      reset();
      localFilters.value = {
        ...filters.value,
        characteristics: [],
        characteristics_logic: 'OR',
        property_types: [],
        price_min: priceMin.value,
        price_max: priceMax.value,
      };
      localPriceRangeValue.value = [priceMin.value, priceMax.value];
      selectedRadioChar.value = {};
      expandedGroups.value = [];
      expandedCheckboxGroups.value = {};
      propertyTypesExpanded.value = false;

      store.commit('propertyFilters/SET_FILTER', { key: 'characteristics', value: [] });
      store.commit('propertyFilters/SET_FILTER', { key: 'characteristics_logic', value: 'OR' });
      store.commit('propertyFilters/SET_FILTER', { key: 'property_types', value: [] });

      const params = new URLSearchParams(window.location.search);
      params.delete('characteristics');
      params.delete('characteristics_logic');
      params.delete('property_types');
      window.history.pushState({}, '', window.location.pathname + (params.toString() ? '?' + params.toString() : ''));

      await applyFilters();
    }

    onMounted(() => {
      initFromURL();
      initFiltersFromStore();
      store.commit('propertyFilters/SET_INIT', {
        config: props.config,
        initFilters: props.initFilters,
        activeFilter: props.activeFilters,
      });

      if (props.config.location) setFilter('locationInput', props.config.location);
    });

    return {
      localFilters,
      localPriceRangeValue,
      loadingBtn,
      safeStep,
      formId,
      formatPriceTooltip,
      querySearch,
      applyFilters,
      resetFilter,
      onPriceChange,
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
      toggleShowMore,
      hasActiveFilters,
      propertyTypes,
      visiblePropertyTypes,
      propertyTypesExpanded,
      togglePropertyTypesExpanded,
    };
  }
};
