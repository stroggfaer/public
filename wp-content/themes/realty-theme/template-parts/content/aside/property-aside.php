<?php
/**
 * @package WordPress
 * @param object $pod Pods объект недвижимости
 */
if (!defined('ABSPATH')) {
    exit;
}

$pod = $args['pod'] ?? null;
$price_raw = $pod ? $pod->field('price') : 0;
$price_formatted = $price_raw ? number_format((float) $price_raw, 0, '.', ' ') . ' ₽' : '—';

$period_label = '';

if ($pod && $value = get_post_meta($pod->id(), 'hours_limit', true)) {
    $chars = realty_get_property_characteristics($pod->id(), null, [
            'type' => 'one',
            'system_temp' => 'hours_limit',
            'limit' => -1,
    ])['characteristics'] ?? [];

    $match = current(array_filter($chars, fn($c) => $c['title'] === $value));

    $period_label = $match['label'] ?? $match['title'] ?? $value;
}

?>
<div class="property-contact-card">
    <div class="card-header">
        <div class="price">
            <span class="amount"><?php echo esc_html($price_formatted); ?></span>
            <?php if ( $period_label ) : ?>
                <span class="period">/ <?php echo esc_html( $period_label ); ?></span>
            <?php endif; ?>
        </div>
        <div class="rating">
            <span class="material-symbols-outlined star-icon">star</span>
            <span class="rating-value">4.98</span>
            <span class="reviews-count">(124)</span>
        </div>
    </div>

    <div class="host-info">
        <div class="avatar-placeholder">
            <span class="material-symbols-outlined">person</span>
        </div>
        <div class="host-details">
            <h3>Хозяин: Сара</h3>
            <div class="hint">Отвечает в течение часа</div>
        </div>
    </div>

    <div class="contact-card-wrap">
        <button class="button__com contact-button">Написать хозяину</button>
        <button class="button__com outline info-button">Узнать о длительном проживании</button>
    </div>

    <div class="card-footer">
        <span class="material-symbols-outlined security-icon">verified_user</span>
        <p>Личность подтверждена и безопасная переписка</p>
    </div>
</div>
