<?php
/**
 * Шаблон главной страницы
 *
 * Отображает визуальный баннер с формой поиска недвижимости
 */

get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">
        <div class="col-full">
            <!-- Визуальный баннер с формой поиска -->
            <section class="hero-banner">
                <div class="hero-content">
                    <h1 class="hero-title">Найдите идеальное жилье<br> для вашего отдыха</h1>
                    <div class="hero-subtitle">Более 10,000 вариантов аренды по всему миру</div>

                    <!-- Форма поиска недвижимости -->
                    <div class="main-search-form-container">
                        <?php echo property_filter_render_home_form(); ?>
                    </div>
                </div>
            </section>
        </div>

        <?php
        $popular_tag_ids  = realty_get_home_popular_tags();
        $archive_base_url = get_post_type_archive_link( 'property' );
        if ( ! empty( $popular_tag_ids ) ) : ?>
            <section class="chips py-10">
                <div class="col-full">
                    <div class="chip-container">
                        <?php foreach ( $popular_tag_ids as $tag_id ) :
                            $icon      = get_post_meta( $tag_id, 'icon', true );
                            $icon_type = get_post_meta( $tag_id, 'icon_type', true );
                            $media_id  = (int) get_post_meta( $tag_id, 'media_id', true );
                            $label     = get_post_meta( $tag_id, 'label', true ) ?: get_the_title( $tag_id );
                            if ( ! $label ) continue;
                            $chip_url = add_query_arg(
                                [ 'characteristics' => wp_json_encode( [ $tag_id ] ) ],
                                $archive_base_url
                            );
                        ?>
                        <a href="<?php echo esc_url( $chip_url ); ?>" class="chip__com">
                            <?php if ( $icon_type === 'upload' && $media_id ) : ?>
                                <?php echo wp_get_attachment_image( $media_id, [ 24, 24 ] ); ?>
                            <?php else : ?>
                                <span class="material-symbols-outlined"><?php echo esc_html( $icon ); ?></span>
                            <?php endif; ?>
                            <span class="chip__com-text"><?php echo esc_html( $label ); ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <!-- Дополнительный контент -->
        <section class="featured-properties__com py-10">
            <div class="col-full">
                <div class="featured-header">
                    <h2 class="section-title font-bold">Популярные направления</h2>
                    <a href="/property/" class="bold border">Посмотреть все</a>
                </div>

                <?php
                // Выводим несколько популярных объектов недвижимости
                $featured_properties = pods('property', array(
                    'limit' => 4,
                    'orderby' => 'RAND()',
                    'where' => 't.post_status = "publish"'
                ));
                
                if ($featured_properties->total() > 0) {
                    echo '<div class="properties-grid">';
                    while ($featured_properties->fetch()) {
                        get_template_part('template-parts/component/property-card', null, array('pod' => $featured_properties));
                    }
                    echo '</div>';
                } else {
                    echo '<p>Популярные предложения временно отсутствуют.</p>';
                }
                ?>
            </div>
        </section>


        <section class="map__block py-10">
            <div class="col-full">
                <div class="map-card__com ">
                    <div class="map-card__com-content">
                        <h2 class="map-card__com-title">Найдите своё идеальное пространство</h2>
                        <p class="map-card__com-description">
                            От уютных квартир до роскошных отелей — всё в одном месте
                        </p>
                        <a href="/property/" class="button__com lg">
                            <span class="material-symbols-outlined">map</span>
                            Смотреть на карте
                        </a>
                    </div>

                    <div class="map-card__com-image-wrapper">
                        <div class="point animate-pulse">
                            <span class="material-symbols-outlined">location_on</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>

<?php get_footer(); ?>