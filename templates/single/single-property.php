<?php
/**
 * Шаблон страницы детального просмотра объекта недвижимости
 *
 * @package WordPress
 * @subpackage Realty Theme
 */
get_header();

// Проверяем статус избранного для текущего объекта
$property_id = get_the_ID();
$is_favorite = false;
if (is_user_logged_in()) {
    $favorites = get_user_meta(get_current_user_id(), '_real_property_favorites', true);
    $favorites = is_array($favorites) ? $favorites : array();
    $is_favorite = in_array($property_id, $favorites, true);
}
?>

<div class="col-full">
    <div class="property-detail" data-property-id="<?php echo get_the_ID(); ?>">
        <?php
        // Loop through the post
        while (have_posts()) :
            the_post();
            $pod = pods('property', get_the_ID());
            $address = $pod->field('address') ?? false;
            $price = $pod->field('price'); // Цена

            // Получаем город из таксономии location
            $locations = get_the_terms(get_the_ID(), 'location');
            $city = !is_wp_error($locations) && !empty($locations) ? $locations[0]->name : '';

            // Формируем отображение: г. Город, Адрес
            $location_display = trim('г. ' . $city . ', ' . $address, ', ');
//            echo '<pre>';
//            print_r($pod);
//            echo '</pre>';
            ?>
            <?php  get_template_part('template-parts/component/breadcrumbs'); ?>
            <div class="property-columns">
                <div class="property-content">
                    <!--Блок галерия-->
                    <?php  get_template_part( 'template-parts/content/single/property-gallery', null, array('pod' => $pod)); ?>
                    <!--./Блок галерия-->
                    <div class="property-details">
                        <div class="entry-header">
                            <h1><?php the_title(); ?></h1>
                            <div class="action">
                                <div class="share js-share"><span class="material-symbols-outlined">share</span></div>
                                <div class="favorite js-favorite <?php echo $is_favorite ? 'active' : ''; ?>" data-property-id="<?php echo esc_attr($property_id); ?>" data-is-favorite="<?php echo $is_favorite ? '1' : '0'; ?>"><span class="material-symbols-outlined"><?php echo $is_favorite ? 'favorite' : 'favorite_border'; ?></span></div>
                            </div>
                        </div>
                        <div class="location">
                            <span class="material-symbols-outlined">location_on</span>
                            <?=esc_html($location_display)?>
                        </div>
                        <?php  get_template_part( 'template-parts/content/single/property-features', null, array('pod' => $pod)); ?>

                        <?php  get_template_part( 'template-parts/content/single/property-rules', null, array('pod' => $pod)); ?>

                        <div class="section-property property-description">
                            <h3 class="title">Описание</h3>
                            <?php the_content(); ?>
                        </div>

                        <?php  get_template_part( 'template-parts/content/single/property-characteristics', null, array('pod' => $pod)); ?>

                        <?php  get_template_part( 'template-parts/content/single/property-map', null, array('pod' => $pod)); ?>
                    </div>
                </div>

                <aside class="property-aside">
                    <?php  get_template_part( 'template-parts/content/aside/property-aside',null, array('pod' => $pod)); ?>
                </aside>
            </div>
        <?php endwhile; ?>
    </div>
    <!--Global detail vue-->
    <div id="vue-property-detail">
        <el-dialog 
            v-model="dialogParams.isVisible" 
            title="Популярные услуги и удобства"
            width="600px"
            :close-on-click-modal="true"
            destroy-on-close
        >
            <div class="characteristics-grid__com" v-loading="characteristics.loading">
                <div class="characteristic-item" v-for="item in characteristics.data">
                    <span class="material-symbols-outlined">{{item.icon}}</span>
                    <span class="label">{{item.title}}</span>
                    <span class="value">{{item.value}}</span>
                </div>
                <div v-if="!characteristics.loading && !characteristics.data?.length" class="characteristics-empty">
                    Нет данных
                </div>
            </div>
            <template #footer>
                <el-button @click="onDialogClose">Закрыть</el-button>
            </template>
        </el-dialog>
    </div>
    <!--./Global detail vue-->
    <!-- Vue Google detail Script -->
    <?php $current_property_id = get_the_ID(); ?>
    <script type="module">
        (function() {
            const { createAppModule, useProperty, useModal, ElDialog } = window.VueAppModule;
            const { ref, onMounted, reactive, nextTick, computed } = Vue;
            const {
                characteristics,
                fetchCharacteristics,
                openCharacteristicsModal,
                dialogParams,
                onDialogClose,
            } = useProperty();
            const AppPropertyDetail = createAppModule({
                setup() {
                    const initClickHandler = () => {
                        if (typeof jQuery === 'undefined') {
                            console.error('jQuery не загружен!');
                            return;
                        }
                        jQuery(document).on('click', '.js-modal-characteristics', async function(e) {
                            e.preventDefault();
                            const groupId = jQuery(this).data('property-id');
                            const lastCount = jQuery(this).data('property-last-count');
                            const propertyId = jQuery('.property-detail').data('property-id') || <?php echo $current_property_id; ?>;
                            openCharacteristicsModal(propertyId, {group_id: groupId, 'last_count': lastCount});
                        });
                    };

                    onMounted(() => {
                        nextTick(initClickHandler);
                    });

                    return {
                        dialogParams,
                        onDialogClose,
                        characteristics,
                        fetchCharacteristics
                    };
                }
            });
            
            AppPropertyDetail.component('el-dialog', ElDialog);
            AppPropertyDetail.mount('#vue-property-detail');
        })();
    </script>
</div>

<?php get_footer(); ?>
