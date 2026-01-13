<template>
  <!-- Address Field -->
  <div
    v-if="googleMapsLoaded()"
    class="am-input-wrapper"
    :style="cssVars"
  >
    <div class="el-input am-input am-input--default">
      <div
        v-click-outside="() => isFocus = false"
        class="el-input__wrapper"
        :class="{'is-focus': isFocus}"
        @click="() => isFocus = true"
      >
        <vue-google-autocomplete
          :id="`amelia-address-autocomplete-${props.id}`"
          ref="addressCustomFields"
          types=""
          classname="el-input__inner"
          :placeholder="props.placeholder"
          :aria-label="props.ariaLabel"
          @placechanged="setAddressCF"
        />
      </div>
    </div>
  </div>
  <AmInput
    v-else
    v-model="model"
    :placeholder="props.placeholder"
  />
  <!-- /Address Field -->
</template>

<script setup>
// * Vue Google Maps Autocomplete
import VueGoogleAutocomplete from "vue-google-autocomplete";
import { ClickOutside as vClickOutside} from "element-plus";

// * Components
import AmInput from "../input/AmInput.vue";

// * Import from Vue
import {
  computed,
  ref,
  toRefs,
  onMounted,
  inject
} from "vue";

// * Composables
import { useColorTransparency} from "../../../assets/js/common/colorManipulation";

// * Import from Vuex
import { useStore } from "vuex";

// * Store
let store = useStore()

// * Component Props
const props = defineProps({
  modelValue: {
    type: [String, Array, Object, Number],
    default: '',
    required: true
  },
  id: {
    type: [String, Number],
    required: true
  },
  placeholder: {
    type: String,
    default: ''
  },
  ariaLabel: {
    type: String,
    default: 'address input'
  },
})

// * Define Emits
const emits = defineEmits(['update:modelValue', 'address-selected'])

// * Component Refs
let addressCustomFields = ref()

// * Component model
let { modelValue } = toRefs(props)
let model = computed({
  get: () => modelValue.value,
  set: (val) => {
    emits('update:modelValue', val)
  }
})

let isFocus = ref(false)

function googleMapsLoaded () {
  return window.google && window.google.maps?.places && store.state.settings.general.gMapApiKey
}

function setAddressCF (val, obj) {
  emits('update:modelValue', obj.formatted_address)
  emits('address-selected', obj.address_components)
}

onMounted(() => {
  if (model.value && addressCustomFields.value) {
    addressCustomFields.value.update(model.value)
  }
})

// * Color Vars
let amColors = inject(
  'amColors',
  ref({
    colorPrimary: '#1246D6',
    colorSuccess: '#019719',
    colorError: '#B4190F',
    colorWarning: '#CCA20C',
    colorMainBgr: '#FFFFFF',
    colorMainHeadingText: '#33434C',
    colorMainText: '#1A2C37',
    colorSbBgr: '#17295A',
    colorSbText: '#FFFFFF',
    colorInpBgr: '#FFFFFF',
    colorInpBorder: '#D1D5D7',
    colorInpText: '#1A2C37',
    colorInpPlaceHolder: '#808A90',
    colorDropBgr: '#FFFFFF',
    colorDropBorder: '#D1D5D7',
    colorDropText: '#0E1920',
    colorBtnPrim: '#265CF2',
    colorBtnPrimText: '#FFFFFF',
    colorBtnSec: '#1A2C37',
    colorBtnSecText: '#FFFFFF',
  })
)

// * Css Variables
let cssVars = computed(() => {
  return {
    '--am-c-inp-bgr': amColors.value.colorInpBgr,
    '--am-c-inp-border': amColors.value.colorInpBorder,
    '--am-c-inp-text': amColors.value.colorInpText,
    '--am-c-inp-text-op03': useColorTransparency(
      amColors.value.colorInpText,
      0.03
    ),
    '--am-c-inp-text-op05': useColorTransparency(
      amColors.value.colorInpText,
      0.05
    ),
    '--am-c-inp-text-op40': useColorTransparency(
      amColors.value.colorInpText,
      0.4
    ),
    '--am-c-inp-text-op60': useColorTransparency(
      amColors.value.colorInpText,
      0.6
    ),
    '--am-c-inp-placeholder': amColors.value.colorInpPlaceHolder,
  }
})
</script>