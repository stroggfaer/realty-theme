<?php
extract($args ?? []);

$result = property_render_list();

$data = [
        'ajaxUrl' => $ajaxUrl ?? admin_url('admin-ajax.php'),
        'nonce' => $nonce ?? wp_create_nonce('property_filter_nonce'),
];

$appData = wp_json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
$initialResult = wp_json_encode($result, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);

?>

<div id="appProperties" class="property-list js-properties-list">
    <div class="properties-grid">
        <?php echo $result['html']; // Вывод начального списка недвижимости ?>
    </div>

    <div class="more">
        <el-button class="shadow-lg" v-if="hasMore" @click="loadMore" type="primary" size="large" :loading="isLoading">
            Еще загрузить
            <span class="material-symbols-outlined">refresh</span>
        </el-button>
    </div>

</div>
<script type="module">
    const { createAppModule, usePropertyFilters } = window.VueAppModule;
    const { computed, ref, onMounted } = Vue;
    const AppProperties = createAppModule({
        setup() {
            const store = Vuex.useStore();
            const appData = ref(<?=$appData?>);
            const initialResult = ref(<?=$initialResult?>);

            const {
                fetchProperties
            } = usePropertyFilters({
                ajaxUrl: appData.value.ajaxUrl,
                nonce: appData.value.nonce
            });

            const meta = computed(() => store.getters['propertyFilters/getMeta']);
            const hasMore = computed(() => meta.value.current_page < meta.value.page_count);
            const isLoading = computed(() => store.getters['propertyFilters/getIsLoading']);

            onMounted(() => {
                store.commit('propertyFilters/SET_META', initialResult.value.meta);
                // Initialize coordinates from initial result if available
                if (initialResult.value.coordinates) {
                    store.commit('propertyFilters/SET_COORDINATES', initialResult.value.coordinates);
                }
            });

            const loadMore = async () => {
                if (isLoading.value || !hasMore.value) {
                    return;
                }
                try {
                    const nextPage = meta.value.current_page + 1;
                    const res = await fetchProperties({page: nextPage});

                    if (res?.success) {
                        const propertyList = document.querySelector('.properties-grid');
                        if (propertyList) {
                            propertyList.insertAdjacentHTML('beforeend', res.data.html || '');
                        } else {
                        }
                    } else {
                    }
                } catch (e) {
                    console.error('[LoadMore] Error:', e);
                }
            }

            return {
                appData,
                hasMore,
                isLoading,
                loadMore
            }
        }
    });
    AppProperties.mount('#appProperties');
</script>
