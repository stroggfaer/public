````
<!-- Vue 3 Hello World Test -->
<div id="vue-app">
test: {{test}}
<hello-word></hello-word>
</div>
<script type="module">
    const {HelloWorld, createAppModule} = window.VueAppModule;
    const { ref } = Vue;
    const AppTestWp = createAppModule({
        setup() {
            const test = ref('test');
            return {
                test
            }
        }
    });
    AppTestWp.component('hello-word', HelloWorld);
    AppTestWp.mount('#vue-app');
</script>

````