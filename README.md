```php
require_once './vendor/bin';

$reflector =new \Fw2\Glimpse\Reflector::create();
$reflector->reflect();


```



```shell
./vendor/bin/pest
```

```shell
./vendor/bin/phpcbf
```

```shell
./vendor/bin/phpstan analyze ./src --level=6
```