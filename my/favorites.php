<?php
/**
 * Шаблон страницы "Избранное"
 * Модуль "Мой кабинет" для темы Realty Theme
 *
 * @package RealtyTheme
 * @subpackage MyCabinet
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Получаем избранное пользователя
$user_id    = get_current_user_id();
$favorites  = get_user_meta( $user_id, '_real_property_favorites', true );
$favorites  = is_array( $favorites ) ? $favorites : array();

// Пагинация
$paged = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;
$per_page = 12;

get_header();
?>

<div class="my-dashboard-layout col-full">
    <!-- Боковое меню -->
    <?php get_template_part('my/component/my-sidebar'); ?>

    <!-- Основной контент -->
    <main class="my-main-content favorites">
        <div class="my-content-wrapper">
            <h1 class="my-page-title"><?php esc_html_e( 'Избранное', 'realty-theme' ); ?></h1>
            <div class="my-content-grid">
                <div class="my-content-main">
                    <?php if ( empty( $favorites ) ) : ?>
                        <!-- Пустое состояние -->
                        <div class="my-empty-placeholder">
                            <?php
                            get_template_part( 'template-parts/component/empty-block', null, array(
                                    'icon'        => 'favorite_border',
                                    'title'       => __( 'У вас пока нет избранных объектов', 'realty-theme' ),
                                    'description' => __( 'Добавляйте понравившиеся объекты, чтобы не потерять их', 'realty-theme' ),
                                    'button_text' => __( 'Перейти к поиску', 'realty-theme' ),
                                    'button_url'  => '/property/',
                            ) );
                            ?>
                        </div>
                    <?php else : ?>
                        <!-- Список избранных объектов -->
                        <?php
                        $args = array(
                                'post_type'      => 'property',
                                'post__in'       => $favorites,
                                'post_status'    => 'publish',
                                'posts_per_page' => $per_page,
                                'paged'          => $paged,
                                'orderby'        => 'post__in',
                        );

                        $query = new WP_Query( $args );

                        if ( $query->have_posts() ) : ?>
                            <div class="properties-grid">
                                <?php while ( $query->have_posts() ) : $query->the_post();
                                    // Получаем Pods объект
                                    $pod = pods( 'property', get_the_ID() );
                                    get_template_part( 'template-parts/component/property-card', null, array( 'pod' => $pod ) );
                                endwhile; ?>
                            </div>

                            <?php
                            // Пагинация (prev/next)
                            $pagination_args = array(
                                    'prev_text' => '&larr; Предыдущие',
                                    'next_text' => 'Следующие &rarr;',
                                    'type'      => 'plain',
                                    'mid_size'  => 0,
                                    'end_size'  => 0,
                            );
                            echo '<div class="my-pagination">' . paginate_links( array_merge( $pagination_args, array(
                                            'total'   => $query->max_num_pages,
                                            'current' => max( 1, $paged ),
                                    ) ) ) . '</div>';
                            ?>
                        <?php else : ?>
                            <!-- Объекты удалены или не найдены -->
                            <div class="my-empty-placeholder">
                                <?php
                                get_template_part( 'template-parts/component/empty-block', null, array(
                                        'icon'        => 'favorite_border',
                                        'title'       => __( 'Избранные объекты не найдены', 'realty-theme' ),
                                        'description' => __( 'Возможно, некоторые объекты были удалены. Обновите список избранного.', 'realty-theme' ),
                                        'button_text' => __( 'Перейти к поиску', 'realty-theme' ),
                                        'button_url'  => '/property/',
                                ) );
                                ?>
                            </div>
                        <?php endif;

                        wp_reset_postdata();
                        ?>
                    <?php endif; ?>
                </div>
                <!-- Правая колонка: Боковая панель действий -->
                <div class="my-content-sidebar">
                    <?php get_template_part('my/component/info-sidebar'); ?>
                </div>
            </div>
        </div>
    </main>
</div>

<?php
get_footer();
