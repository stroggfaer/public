#Создать новые объекты:
```
php -r "require_once 'wp-content/themes/realty-theme/docs/kilocode/generator/PropertyGenerator.php'; $g = new PropertyGenerator(); $ids = \$g->generate(5);"
php -r "require_once 'PropertyGenerator.php'; $g = new PropertyGenerator(); $ids = \$g->generate(5);

```
#Показать список всех объектов:
```
php -r "require_once 'wp-content/themes/realty-theme/docs/kilocode/generator/PropertyGenerator.php'; print_r((new PropertyGenerator())->getAll());"

```
#Очистить все объекты:
```
php -r "require_once 'wp-content/themes/realty-theme/docs/kilocode/generator/PropertyGenerator.php'; echo (new PropertyGenerator())->clearAll() . ' objects deleted';"

```