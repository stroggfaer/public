import store from './src/store/index.js';
import HelloWorld from './src/components/HelloWord.js';
// Стили element-plus нужны для всех компонентов
import 'element-plus/dist/index.css';
// Локализация для русского языка
import ru from 'element-plus/dist/locale/ru.mjs';
import { ElConfigProvider, ElButton, ElDialog } from 'element-plus';
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
  
  app.use(store);
  return app;
}
export {
  HelloWorld,
  ElButton,
  ElDialog,
  ApiService
}
