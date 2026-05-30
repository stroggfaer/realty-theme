<?php
// Определяем текущую страницу
$paged = (get_query_var('paged')) ? get_query_var('paged') : ((get_query_var('page')) ? get_query_var('page') : 1);
$per_page = !empty($args['per_page']) ? $args['per_page'] : null;
$is_pagination = $args['is_pagination'] ?? true;
$query = query_post(['paged' => $paged], $per_page);

$arg_list = array(
    'query' => $query,
    'paged' => $paged,
    'per_page' => $per_page,
    'is_pagination' => $is_pagination,
);
?>
<div class="rubric_list">
    <div class="el-row" id="posts-container">
        <?php
          if (!empty($query) && $query->have_posts()) {
              include locate_template('template-parts/component/post-list.php', true, false, $arg_list);
          }
        ?>
    </div>
</div>
