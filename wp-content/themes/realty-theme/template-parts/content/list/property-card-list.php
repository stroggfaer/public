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

    <div class="more">
        <el-button v-if="hasMore" @click="loadMore" type="primary" size="large" :loading="isLoading">Еще загрузить</el-button>
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

            const loadMore = async () => {
                if (isLoading.value || !hasMore.value) return;
                isLoading.value = true;
                try {
                    const nextPage = currentPage.value + 1;
                    const res = await fetchProperties({page: nextPage});

                    if (res?.success) {
                        const propertyList = document.querySelector('.properties-grid');
                        if (propertyList) {
                            propertyList.insertAdjacentHTML('beforeend', res.data.html || '');
                        }
                        currentPage.value = res.data.meta.current_page;
                        hasMore.value = res.data.meta.current_page < res.data.meta.page_count;
                    }
                } catch (e) {
                    console.error('Load more error:', e);
                } finally {
                    isLoading.value = false;
                }
            }
            return {
                appData,
                isLoading,
                hasMore,
                getFilters,
                loadMore
            }
        }
    });
    AppProperties.mount('#appProperties');
</script>
