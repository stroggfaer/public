import store from './src/store/index.js';
import HelloWorld from './src/components/HelloWord.js';
import PropertyFilter from './src/components/PropertyFilter.js';
import HomePropertyFilter from './src/components/HomePropertyFilter.js';
import useProperty from './src/hooks/useProperty.js';
import usePropertyFilters from './src/hooks/usePropertyFilters';
import GoogleMap from './src/components/GoogleMap.js';
import ElementPlus from 'element-plus'
// Локализация для русского языка
import ru from 'element-plus/es/locale/lang/ru';
import moment from 'moment';
import 'moment/locale/ru';
moment.locale('ru');
import {
  ElConfigProvider,
  ElButton,
  ElDialog,
  ElForm,
  ElFormItem,
  ElInput,
  ElInputNumber,
  ElDatePicker,
  ElAutocomplete,
  ElMessage,
  ElPopover
} from 'element-plus';
import {ApiService} from './src/services/api';
export function createAppModule(options = {}) {
  const app = Vue.createApp(options);
  app.config.compilerOptions =  {
     isCustomElement: (tag) => tag === 'noindex',
  };
  
  // Настройка русской локализации для ElementPlus
  app.use(ElementPlus, {
    locale: ru,
  })

  // Глобальная регистрация moment
  app.provide('moment', moment);

  app.use(store);
  return app;
}
export {
  HelloWorld,
  PropertyFilter,
  HomePropertyFilter,
  GoogleMap,
  ElButton,
  ElDialog,
  ElForm,
  ElFormItem,
  ElInput,
  ElInputNumber,
  ElDatePicker,
  ElAutocomplete,
  ElPopover,
  ElMessage,
  ApiService,
  useProperty,
  usePropertyFilters
}
