<?php
/**
 * Генератор тестовых объектов недвижимости
 * 
 * Использование:
 * 
 * 1. Через командную строку:
 *    php docs/kilocode/generator/PropertyGenerator.php --count=5
 * 
 * 2. Подключив в PHP:
 *    require_once get_template_directory() . '/docs/kilocode/generator/PropertyGenerator.php';
 *    $generator = new PropertyGenerator();
 *    $ids = $generator->generate(3);
 * 
 * 3. Через URL (добавить в functions.php):
 *    require_once get_template_directory() . '/docs/kilocode/generator/PropertyGenerator.php';
 *    add_action('init', function() {
 *        if (isset($_GET['generate_properties'])) {
 *            $generator = new PropertyGenerator();
 *            $count = isset($_GET['count']) ? intval($_GET['count']) : 2;
 *            $ids = $generator->generate($count);
 *            wp_die('Создано объектов: ' . count($ids));
 *        }
 *    });
 * 
 * Скоуп: generator.property
 */

// ABSPATH check can be disabled for CLI usage
// if (!defined('ABSPATH')) {
//     exit;
// }

class PropertyGenerator
{
    /**
     * Путь к сокету MySQL (для Local by Flywheel)
     * @var string
     */
    private $socket = '/Users/rendzhi/Library/Application Support/Local/run/uJDH2JMK_/mysql/mysqld.sock';
    
    /**
     * Префикс таблиц
     * @var string
     */
    private $table_prefix = 'wp_';
    
    /**
     * Подключение к БД
     * @var mysqli
     */
    private $conn = null;
    
    /**
     * Названия для объектов
     */
    private $prefixes = [
        'ЖК', 'Апартаменты', 'Пентхаус', 'Лофт', 'Студия', 'Комфорт', 
        'Премиум', 'Элитные', 'Дизайнерские', 'Апарт-отель', 'Бутик'
    ];
    
    private $names = [
        'Москва', 'Сити', 'Арбат', 'Тверская', 'Патриаршие', 'Таганка',
        'Замоскворечье', 'Пресня', 'Якиманка', 'Чистые Пруды', 'Нескучный сад',
        'Воробьёвы горы', 'Измайлово', 'Цветной бульвар', 'Никольская',
        'Кузнецкий мост', 'Петровка', 'Трубная площадь'
    ];
    
    private $postfixes = [
        'Премиум', 'Люкс', 'Центр', 'Парк', 'Сады', 'Река',
        'Вью', 'Хаус', 'Плейс', 'Резиденс', 'Вилла', 'Дом'
    ];
    
    /**
     * Улицы Москвы
     */
    private $streets = [
        'ул. Арбат', 'ул. Тверская', 'ул. Неглинная', 'ул. Покровка',
        'ул. Таганская', 'ул. Воронцово поле', 'ул. Маросейка',
        'ул. Пятницкая', 'ул. Ордынка', 'ул. Якиманка', 'ул. Пречистенка',
        'ул. Остоженка', 'ул. Волхонка', 'ул. Знаменка', 'ул. Моховая',
        'ул. Мясницкая', 'пр-т Ленинский', 'пр-т Кутузовский',
        'пр-т Мира', 'пр-т Вернадского', 'пр-т Профсоюзной'
    ];
    
    /**
     * Типы жилья
     */
    private $property_types = [
        'Дом', 'Квартира', 'Комната', 'Глэмпинг', 
        'Гостевой дом', 'Отель', 'Хостел'
    ];
    
    /**
     * Конструктор
     */
    public function __construct($socket = null)
    {
        if ($socket) {
            $this->socket = $socket;
        }
    }
    
    /**
     * Подключение к БД
     */
    private function connect()
    {
        if ($this->conn === null) {
            $this->conn = new mysqli();
            $this->conn->real_connect(
                'localhost', 
                'root', 
                'root', 
                'local', 
                0, 
                $this->socket
            );
            $this->conn->set_charset('utf8mb4');
        }
        return $this->conn;
    }
    
    /**
     * Генерация случайного названия объекта
     */
    public function generateName()
    {
        $prefix = $this->prefixes[array_rand($this->prefixes)];
        $name = $this->names[array_rand($this->names)];
        $postfix = $this->postfixes[array_rand($this->postfixes)];
        
        return sprintf('%s "%s %s"', $prefix, $name, $postfix);
    }
    
    /**
     * Генерация случайного адреса
     */
    public function generateAddress()
    {
        $street = $this->streets[array_rand($this->streets)];
        $num = rand(1, 50);
        $building = rand(1, 5);
        $apartment = rand(1, 200);
        
        return sprintf('%s, д. %d, стр. %d, кв. %d', $street, $num, $building, $apartment);
    }
    
    /**
     * Генерация случайной цены
     */
    public function generatePrice($min = 3000, $max = 50000)
    {
        return rand($min, $max);
    }
    
    /**
     * Генерация случайного количества гостей
     */
    public function generateGuestCount($min = 1, $max = 8)
    {
        return rand($min, $max);
    }
    
    /**
     * Генерация случайных координат Москвы
     */
    public function generateCoordinates()
    {
        return [
            'lat' => round(55.5 + (mt_rand() / mt_getrandmax()) * 0.5, 6),
            'lng' => round(37.3 + (mt_rand() / mt_getrandmax()) * 0.6, 6)
        ];
    }
    
    /**
     * Генерация случайных дат доступности
     */
    public function generateAvailabilityDates()
    {
        $start = date('Y-m-d', strtotime('+' . rand(1, 30) . ' days'));
        return [
            'start' => $start,
            'end' => date('Y-m-d', strtotime($start . ' +' . rand(3, 14) . ' days'))
        ];
    }
    
    /**
     * Генерация объекта недвижимости
     */
    public function generate($count = 1)
    {
        $conn = $this->connect();
        $created_ids = [];
        
        for ($i = 0; $i < $count; $i++) {
            $name = $this->generateName();
            $address = $this->generateAddress();
            $price = $this->generatePrice();
            $guests = $this->generateGuestCount();
            $coords = $this->generateCoordinates();
            $dates = $this->generateAvailabilityDates();
            
            // Генерация slug без WordPress функции
$post_name = strtolower(preg_replace('/[^a-zA-Z0-9а-яА-ЯёЁ]/u', '-', $name)) . '-' . ($i + 1);
$post_name = preg_replace('/-+/', '-', $post_name);
            
            // Вставка поста
            $content = 'Тестовое описание объекта недвижимости ' . $name . '. Отличное расположение в центре Москвы. Современный ремонт.';
            $guid = 'http://realproperty.local/?post_type=property&p=POST_ID';
            
            $sql = "INSERT INTO {$this->table_prefix}posts (
                post_author, post_date, post_date_gmt, post_content, post_title,
                post_excerpt, post_status, comment_status, ping_status, post_name,
                post_modified, post_modified_gmt, post_parent, guid, menu_order,
                post_type, to_ping, pinged, post_content_filtered
            ) VALUES (
                1, NOW(), NOW(), '" . $conn->real_escape_string($content) . "', '" . $conn->real_escape_string($name) . "', 
                '', 'publish', 'closed', 'closed', '" . $conn->real_escape_string($post_name) . "',
                NOW(), NOW(), 0, '" . $conn->real_escape_string($guid) . "', 0, 'property', '', '', ''
            )";
            
            $conn->query($sql);
            $post_id = $conn->insert_id;
            $created_ids[] = $post_id;
            
            // Метаполя
            $this->addMeta($post_id, 'price', $price);
            $this->addMeta($post_id, 'address', $address);
            $this->addMeta($post_id, 'guest_count', $guests);
            $this->addMeta($post_id, 'latitude', $coords['lat']);
            $this->addMeta($post_id, 'longitude', $coords['lng']);
            $this->addMeta($post_id, 'location', 'Москва');
            $this->addMeta($post_id, 'availability_dates', $dates['start']);
            $this->addMeta($post_id, 'availability_dates_end', $dates['end']);
            $this->addMeta($post_id, 'area', rand(25, 150));
            $this->addMeta($post_id, 'rooms', rand(1, 5));
            $this->addMeta($post_id, 'floor', rand(1, 25));
            
            // Установка таксономий (локация и тип жилья)
            $this->setTaxonomy($post_id, 'location', 'Москва');
            $this->setTaxonomy($post_id, 'property_type', $this->property_types[array_rand($this->property_types)]);
            
            echo "Создан объект #{$post_id}: {$name}\n";
        }
        
        return $created_ids;
    }
    
    /**
     * Добавление метаполя
     */
    private function addMeta($post_id, $key, $value)
    {
        $sql = "INSERT INTO {$this->table_prefix}postmeta (post_id, meta_key, meta_value) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('iss', $post_id, $key, $value);
        $stmt->execute();
    }
    
    /**
     * Установка термина таксономии
     */
    private function setTaxonomy($post_id, $taxonomy, $term_name)
    {
        // Проверяем, существует ли термин
        $result = $this->conn->query("SELECT term_id FROM {$this->table_prefix}terms WHERE name = '" . $this->conn->real_escape_string($term_name) . "'");
        $row = $result->fetch_assoc();
        
        if ($row) {
            $term_id = $row['term_id'];
        } else {
            // Создаём термин
            $this->conn->query("INSERT INTO {$this->table_prefix}terms (name, slug, term_group) VALUES ('" . $this->conn->real_escape_string($term_name) . "', '" . $this->conn->real_escape_string(strtolower(preg_replace('/[^a-zA-Z0-9]/u', '-', $term_name))) . "', 0)");
            $term_id = $this->conn->insert_id;
            
            // Создаём term_taxonomy
            $this->conn->query("INSERT INTO {$this->table_prefix}term_taxonomy (term_id, taxonomy, description, parent, count) VALUES ($term_id, '" . $this->conn->real_escape_string($taxonomy) . "', '', 0, 1)");
        }
        
        // Получаем term_taxonomy_id
        $result = $this->conn->query("SELECT term_taxonomy_id FROM {$this->table_prefix}term_taxonomy WHERE term_id = $term_id AND taxonomy = '" . $this->conn->real_escape_string($taxonomy) . "'");
        $row = $result->fetch_assoc();
        $term_taxonomy_id = $row['term_taxonomy_id'];
        
        // Связываем пост с термином
        $this->conn->query("INSERT INTO {$this->table_prefix}term_relationships (object_id, term_taxonomy_id, term_order) VALUES ($post_id, $term_taxonomy_id, 0)");
        
        // Обновляем счётчик
        $this->conn->query("UPDATE {$this->table_prefix}term_taxonomy SET count = count + 1 WHERE term_taxonomy_id = $term_taxonomy_id");
    }
    
    /**
     * Очистка всех объектов недвижимости
     */
    public function clearAll()
    {
        $conn = $this->connect();
        
        // Получаем все ID объектов
        $result = $conn->query("SELECT ID FROM {$this->table_prefix}posts WHERE post_type = 'property'");
        $ids = [];
        while ($row = $result->fetch_assoc()) {
            $ids[] = $row['ID'];
        }
        
        if (empty($ids)) {
            return 0;
        }
        
        // Удаляем метаполя
        $conn->query("DELETE FROM {$this->table_prefix}postmeta WHERE post_id IN (" . implode(',', $ids) . ")");
        
        // Удаляем посты
        $conn->query("DELETE FROM {$this->table_prefix}posts WHERE post_type = 'property'");
        
        return count($ids);
    }
    
    /**
     * Получение списка всех объектов
     */
    public function getAll()
    {
        $conn = $this->connect();
        $result = $conn->query("
            SELECT p.ID, p.post_title, p.post_status,
                   pm_price.meta_value as price,
                   pm_address.meta_value as address,
                   pm_guests.meta_value as guests
            FROM {$this->table_prefix}posts p
            LEFT JOIN {$this->table_prefix}postmeta pm_price ON p.ID = pm_price.post_id AND pm_price.meta_key = 'price'
            LEFT JOIN {$this->table_prefix}postmeta pm_address ON p.ID = pm_address.post_id AND pm_address.meta_key = 'address'
            LEFT JOIN {$this->table_prefix}postmeta pm_guests ON p.ID = pm_guests.post_id AND pm_guests.meta_key = 'guest_count'
            WHERE p.post_type = 'property' AND p.post_status != 'trash'
            ORDER BY p.ID DESC
        ");
        
        $properties = [];
        while ($row = $result->fetch_assoc()) {
            $properties[] = $row;
        }
        
        return $properties;
    }
    
    /**
     * Закрытие соединения
     */
    public function __destruct()
    {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}

// Запуск из командной строки
if (php_sapi_name() === 'cli' && isset($argv[0]) && strpos($argv[0], 'PropertyGenerator.php') !== false) {
    $count = 2;
    
    // Парсинг аргументов
    foreach ($argv as $arg) {
        if (strpos($arg, '--count=') === 0) {
            $count = (int) str_replace('--count=', '', $arg);
        }
        if ($arg === '--clear') {
            $generator = new PropertyGenerator();
            $deleted = $generator->clearAll();
            echo "Удалено объектов: {$deleted}\n";
            exit;
        }
        if ($arg === '--list') {
            $generator = new PropertyGenerator();
            $properties = $generator->getAll();
            echo "Всего объектов: " . count($properties) . "\n\n";
            foreach ($properties as $p) {
                echo "ID: {$p['ID']} | {$p['post_title']} | {$p['price']} руб | {$p['address']}\n";
            }
            exit;
        }
    }
    
    echo "=== Генератор объектов недвижимости ===\n";
    echo "Создаем {$count} объектов...\n\n";
    
    $generator = new PropertyGenerator();
    $ids = $generator->generate($count);
    
    echo "\nГотово! Создано объектов: " . count($ids) . "\n";
    echo "ID: " . implode(', ', $ids) . "\n";
}
