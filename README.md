```php
require_once './vendor/bin';

$reflector =new \Fw2\Glimpse\Reflector::create();
$reflector->reflect('Some\User\Class');
```

```shell
./vendor/bin/pest --coverage
```

```shell
./vendor/bin/phpcbf
```

```shell
./vendor/bin/phpstan analyze --level=6 --memory-limit 2G
```
