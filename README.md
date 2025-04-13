
# Glimpse

`fw2/glimpse` is a static reflection library for PHP. It uses `nikic/php-parser` to analyze source code without executing it.

## Installation

Use Composer to install the package:

```bash
composer require fw2/glimpse
```

## Example Usage

```php
use Fw2\Glimpse\Reflector;

$reflector = Reflector::createInstance();
$reflection = $reflector->reflect('MyClass');
```

## Development

### Running phpstan

```shell
$ vendor/bin/phpstan analyze src --level=6
```

### Running code-style

*Show violations*

```shell
$ vendor/bin/phpcs
```

*Autofix if possible*

```shell
$ vendor/bin/phpcbf
```

### Running tests

```shell
$ vendor/bin/pest
```

*Coverage*

```shell
$ vendor/bin/pest --coverage
```

## License

This package is licensed under the MIT license.
