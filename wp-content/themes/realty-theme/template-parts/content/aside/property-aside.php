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

// Получаем имя хозяина из учетной записи WordPress
$owner_name = '';
if ( $pod && $pod->id() ) {
	$author_id = get_post_field( 'post_author', $pod->id() );
	if ( $author_id ) {
		$first_name = get_the_author_meta( 'first_name', (int) $author_id );
		$last_name = get_the_author_meta( 'last_name', (int) $author_id );
		
		if ( $first_name || $last_name ) {
			$owner_name = trim( $first_name . ' ' . $last_name );
		} else {
			$owner_name = get_the_author_meta( 'display_name', (int) $author_id );
		}
	}
}

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
    <!--Дата заезда и контакт (Vue 3)-->
    <?php
    // Подготовка данных для Vue
    $booking_app_data = array(
        'ajaxUrl'       => admin_url('admin-ajax.php'),
        'nonce'         => wp_create_nonce('property_filter_nonce'),
        'propertyId'    => $pod ? $pod->id() : 0,
        'ownerId'       => $author_id ?? 0,
        'config'        => array(
            'guests' => get_option('property_guest_types', array(
                array('name' => 'adults', 'label' => 'Взрослые', 'desc' => 'от 13 лет', 'min' => 1, 'max' => 10),
                array('name' => 'children', 'label' => 'Дети', 'desc' => 'от 2 до 12 лет', 'min' => 0, 'max' => 8)
            ))
        )
    );
    $booking_app_data_json = wp_json_encode($booking_app_data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
    ?>
    <div id="property-booking-sidebar" class="checkin-date" data-app="<?php echo esc_attr($booking_app_data_json); ?>">
        <property-booking-sidebar 
            :ajax-url="appData.ajaxUrl"
            :nonce="appData.nonce"
            :property-id="appData.propertyId"
            :owner-id="appData.ownerId"
            :config="appData.config"
        ></property-booking-sidebar>
    </div>

    <script type="module">
        const bookingAppData = JSON.parse(document.getElementById('property-booking-sidebar').dataset.app);
        const { createAppModule, PropertyBookingSidebar } = window.VueAppModule;
        
        const BookingSidebarApp = createAppModule({
            setup() {
                return {
                    appData: bookingAppData
                };
            }
        });
        
        BookingSidebarApp.component('property-booking-sidebar', PropertyBookingSidebar);
        BookingSidebarApp.mount('#property-booking-sidebar');
    </script>
    <!--./Дата заезда и контакт-->
    <div class="host-info">
        <div class="avatar-placeholder">
            <span class="material-symbols-outlined">person</span>
        </div>
        <div class="host-details">
            <h3>Хозяин: <?php echo $owner_name ? esc_html( $owner_name ) : 'Хост'; ?></h3>
            <div class="hint">Отвечает в течение часа</div>
        </div>
    </div>

    <div class="contact-card-wrap">
        <button class="button__com outline info-button">Узнать о длительном проживании</button>
    </div>

    <div class="card-footer">
        <span class="material-symbols-outlined security-icon">verified_user</span>
        <p>Личность подтверждена и безопасная переписка</p>
    </div>
</div>
