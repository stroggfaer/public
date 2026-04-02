<?php
extract($args ?? []);

$data = [
        'ajaxUrl' => $ajaxUrl ?? admin_url('admin-ajax.php'),
        'nonce' => $nonce ?? wp_create_nonce('property_filter_nonce'),
];

$appData = wp_json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);

?>

<div id="appProperties" class="property-list js-properties-list">
    <?php echo property_render_list(); // Вывод начального списка недвижимости ?>
    <pre>{{appData}}</pre>
    <div class="more">
        <el-button @click="loadMore" type="primary" size="large" :loading="isLoading">Еще загрузить</el-button>
    </div>
</div>
<script type="module">
    const { createAppModule, useProperty, usePropertyFilters } = window.VueAppModule;
    const { ref } = Vue;
    const AppProperties = createAppModule({
        setup() {
            const store = Vuex.useStore();
            const getFilters = store.getters['propertyFilters/getData'];
            const appData = ref(<?=$appData?>);

            const {
                fetchPropertiesRender,
                fetchProperties,
                filters
            } = usePropertyFilters({
                ajaxUrl: appData.value.ajaxUrl,
                nonce: appData.value.nonce
            });

            const currentPage = ref(1);
            const hasMore = ref(true);
            const isLoading = ref(false);
            const perPage = ref(20);

            const loadMore = async () => {
                if (isLoading.value || !hasMore.value) return;
                try {
                  const res = await fetchProperties({page: 2});

                    if (res?.success) {
                        const propertyList = document.querySelector('.properties-grid');
                        if (propertyList) {
                            propertyList.innerHTML = res.data.html || '<div class="no-results">Недвижимость не найдена.</div>';
                        }
                    }
                  console.log('loadMore',res);
                }catch (e){

                }
            }
            return {
                appData,
                isLoading,
                getFilters,
                loadMore
            }
        }
    });
    AppProperties.mount('#appProperties');
</script>
