<div id="appProperties" class="property-list js-properties-list">
    <?php echo property_render_list(); // Вывод начального списка недвижимости ?>
</div>
<script type="module">
    const { createAppModule, useProperty } = window.VueAppModule;
    const { ref } = Vue;
    const AppProperties = createAppModule({
        setup() {
            const store = Vuex.useStore();
            const getFilters = store.getters['propertyFilters/getData'];
            return {
                getFilters
            }
        }
    });
    AppProperties.mount('#appProperties');
</script>
