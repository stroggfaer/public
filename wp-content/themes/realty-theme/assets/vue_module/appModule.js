import store from './src/store/index.js';
import HelloWorld from './components/helloWord.js';
export function createAppModule(options = {}) {
  const app = Vue.createApp(options);

  app.config.complerOptions =  {
     isCustomElement: (tag) => tag === 'noindex',
  }
  app.use(store);

  return app;

}
export {
  HelloWorld
}