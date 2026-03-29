<div id="appProperties" class="property-list js-properties-list">
    <?php echo property_render_list(); // Вывод начального списка недвижимости ?>
</div>
<script type="module">
    const { createAppModule, useProperty } = window.VueAppModule;
    const { ref } = Vue;
    const AppProperties = createAppModule({
        setup() {
            return {}
        }
    });
    AppProperties.mount('#appProperties');
</script>
