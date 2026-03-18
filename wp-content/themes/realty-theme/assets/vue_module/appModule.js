import store from './src/store/index.js';
import HelloWorld from './src/components/helloWord.js';

export function createAppModule(options = {}) {
  const app = Vue.createApp(options);

  app.config.compilerOptions =  {
     isCustomElement: (tag) => tag === 'noindex',
  };
  app.use(store);

  return app;

}
export {
  HelloWorld
}