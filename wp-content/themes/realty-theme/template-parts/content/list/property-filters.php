<div class="property-archive-filters">
    <?php  $filters = get_current_filters_data(); ?>
    <div class="active-filters-notification" data-has-filters="true">
        <strong>Результаты фильтрации:</strong>
        <span class="active-filters-list">
            <?php foreach ($filters as $i => $f): ?>
                <span class="filter-tag"
                      data-key="<?= esc_attr($f['key']) ?>"
                      data-type="<?= esc_attr($f['type']) ?>">
                    <?= esc_html($f['label']) ?>: <?= esc_html($f['value']) ?>
                </span>
                <?php if ($i < count($filters) - 1): ?>,<?php endif; ?>
            <?php endforeach; ?>
        </span>
    </div>
    <div class="property-filter-wrapper">
        <?php echo property_filter_render_form('archive'); ?>
    </div>
</div>
<?php

?>
