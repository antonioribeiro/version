# Version
### Take control over your Laravel app version

[![Latest Stable Version](https://img.shields.io/packagist/v/pragmarx/version.svg?style=flat-square)](https://packagist.org/packages/pragmarx/version)
[![License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md) 
[![Code Quality](https://img.shields.io/scrutinizer/g/antonioribeiro/version.svg?style=flat-square)](https://scrutinizer-version.com/g/antonioribeiro/version/?branch=master) 
[![Build](https://img.shields.io/scrutinizer/build/g/antonioribeiro/version.svg?style=flat-square)](https://scrutinizer-version.com/g/antonioribeiro/version/?branch=master) 
[![Coverage](https://img.shields.io/scrutinizer/coverage/g/antonioribeiro/version.svg?style=flat-square)](https://scrutinizer-version.com/g/antonioribeiro/version/?branch=master)
[![StyleCI](https://styleci.io/repos/112244465/shield)](https://styleci.io/repos/112244465)

## Key features

### Easily control you app version using a YAML config file in dir/config/version.yml:

``` yaml
version: 
    current:
        major: 1
        minor: 0
        patch: 0
        format: '{$major}.{$minor}.{$patch}'
    build:
        mode: number
        number: 701036
```

### Use your git commit as your app build number

Configure it

``` yaml
build:
    mode: git-local
```

And you may have an output like this

```
MyApp version 1.0.0 (build a9c03f)
```

Or just use an incremental build number:

``` yaml
build:
    mode: number
    number: 701036
```

To get

```
MyApp version 1.0.0 (build 701036)
```

### You can easily increment your build number, using this Artisan command

``` bash
php artisan version:build
```

Which should give you 

``` bash
New build: 701037
MyApp version 1.0.0 (build 701037) 
```

### The output format is highly configurable

You can configure the :

``` yaml
format:
  version: "{$major}.{$minor}.{$patch}"
  full: "version {{'format.version'}} (build {$build})"
  compact: "v{{'format.version'}}-{$build}"
```

Those are the results for `full` and `compact` formats

```
MyApp version 1.0.0 (build 701037)
MyApp v1.0.0-701037
```

### A Blade directive is also ready to be used in your views

``` bash
@version('full')
@version('compact')
```

## Minimum requirements

- Laravel 5.5
- PHP 7.0

## Author

[Antonio Carlos Ribeiro](http://twitter.com/iantonioribeiro)

## License

This package is licensed under the MIT License - see the `LICENSE` file for details

## Contributing

Pull requests and issues are welcome.


<!-- [![Downloads](https://img.shields.io/packagist/dt/pragmarx/version.svg?style=flat-square)](https://packagist.org/packages/pragmarx/version) --> 
