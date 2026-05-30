<?php
// Показываем ошибки валидации, если они есть
$validation_errors = get_property_search_errors();
if (!empty($validation_errors)):
    ?>
    <div class="validation-errors">
        <ul>
            <?php foreach ($validation_errors as $error): ?>
                <li><?php echo esc_html($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
