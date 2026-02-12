<?php
/**
 * Шаблон rules
 *
 * @param object $pod Pods объект недвижимости
 */
if (!defined('ABSPATH')) {
    exit;
}

$pod = $args['pod'] ?? null;
if (!$pod) {
    return;
}
?>

<div class="section-property property-rules">
    <h3 class="title">Правила размещения</h3>
    <div class="characteristics-grid__com">
        <div class="characteristic-item">
            <div class="label-text">Заселение:</div>
            <div class="desc">после 15:00 > Выезд: до 12:00</div>
        </div>
        <div class="characteristic-item">
            <div class="label-text">Политика отмены:</div>
            <div class="desc">Умеренная</div>
        </div>
        <div class="characteristic-item">
            <div class="label-text">Доступ к объекту:</div>
            <div class="desc">Передача ключей через ключной сейф</div>
        </div>
    </div>
    <div class="characteristics-grid__com">
        <div class="characteristic-item">
            <span class="material-symbols-outlined diagonal-strike">celebration</span>
            <span class="label">Нельзя проводить вечеринки</span>
        </div>
        <div class="characteristic-item">
            <span class="material-symbols-outlined diagonal-strike">pets</span>
            <span class="label">С животными нельзя</span>
        </div>
        <div class="characteristic-item">
            <span class="material-symbols-outlined diagonal-strike">child_care</span>
            <span class="label">Подходит для проживания с детьми младше 3 лет</span>
        </div>
        <div class="characteristic-item">
            <span class="material-symbols-outlined diagonal-strike">bolt</span>
            <span class="label">Мгновенное бронирование</span>
        </div>
    </div>
</div>
