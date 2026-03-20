import store from './src/store/index.js';
import HelloWorld from './src/components/HelloWord.js';
import PropertyFilter from './src/components/PropertyFilter.js';
// Локализация для русского языка
import ru from 'element-plus/dist/locale/ru.mjs';
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
  ElMessage
} from 'element-plus';
import {ApiService} from './src/services/api';
export function createAppModule(options = {}) {
  const app = Vue.createApp(options);
  app.config.compilerOptions =  {
     isCustomElement: (tag) => tag === 'noindex',
  };
  
  // Настройка русской локализации для ElementPlus
  app.use(ElConfigProvider, {
    locale: ru,
  });
  
  // Регистрация компонентов Element Plus глобально
  app.component('ElButton', ElButton);
  app.component('ElDialog', ElDialog);
  app.component('ElForm', ElForm);
  app.component('ElFormItem', ElFormItem);
  app.component('ElInput', ElInput);
  app.component('ElInputNumber', ElInputNumber);
  app.component('ElDatePicker', ElDatePicker);
  app.component('ElAutocomplete', ElAutocomplete);

  app.use(store);
  return app;
}
export {
  HelloWorld,
  PropertyFilter,
  ElButton,
  ElDialog,
  ElForm,
  ElFormItem,
  ElInput,
  ElInputNumber,
  ElDatePicker,
  ElAutocomplete,
  ElMessage,
  ApiService
}
