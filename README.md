# Version
### Take control over your Laravel app version

[![Latest Stable Version](https://img.shields.io/packagist/v/pragmarx/version.svg?style=flat-square)](https://packagist.org/packages/pragmarx/version)
[![License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md) 
[![Downloads](https://img.shields.io/packagist/dt/pragmarx/version.svg?style=flat-square)](https://packagist.org/packages/pragmarx/version) 
[![Code Quality](https://img.shields.io/scrutinizer/g/antonioribeiro/version.svg?style=flat-square)](https://scrutinizer-version.com/g/antonioribeiro/version/?branch=master) 
[![Build](https://img.shields.io/scrutinizer/build/g/antonioribeiro/version.svg?style=flat-square)](https://scrutinizer-version.com/g/antonioribeiro/version/?branch=master) 
[![Coverage](https://img.shields.io/scrutinizer/coverage/g/antonioribeiro/version.svg?style=flat-square)](https://scrutinizer-version.com/g/antonioribeiro/version/?branch=master)
[![StyleCI](https://styleversion.io/repos/112244465/shield)](https://styleversion.io/repos/112244465)

## Key features

### Easily control you app version using a YAML config file in dir/config/version.yml:

``` yaml
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

And you should have outputs like this

```
MyApp version 1.0.0 (build a9c03f)
```

Or just use a number:

``` yaml
build:
    mode: number
    number: 701036
```

To get

```
MyApp version 1.0.0 (build 701036)
```

### You can easily increment your build number, using an Artisan command

The command 

``` bash
php artisan version:build
```

Should give you 

``` bash
New build: 701037
MyApp version 1.0.0 (build 701037) 
```

### The output format is highly configurable

Those are the configuration keys.

``` yaml
format:
  version: "{$major}.{$minor}.{$patch} (build {$build})"
  full: "version {{'format.version'}}"
  compact: "v{$major}.{$minor}.{$patch}-{$build}"
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

### Load one file or a whole directory, recursively, so all those files would be loaded with a single command

``` php
.
└── myapp
    ├── multiple
    │   ├── alter.yml
    │   ├── app.yml
    │   └── second-level
    │       └── third-level
    │           ├── alter.yml
    │           └── app.yml
    ├── single
        └── single-app.yml
```

Then you would just have to use it like you usually do in Laravel

``` php
config('myapp.multiple.second-level.third-level.alter.person.name')
```

### Execute functions, like in the usual Laravel PHP array config.

``` php
repository: "{{ env('APP_NAME') }}"
path: "{{ storage_path('app') }}"
```

### Config values can reference config keys, you just have to quote it this way:

``` yaml
{{'format.version'}}
```

Here's an example showing `format.full` using `format.version` as value:

``` yaml
format:
  version: "{$major}.{$minor}.{$patch} (build {$build})"
  full: "version {{'format.version'}}"
```

## Install

Via Composer

``` bash
$ composer require pragmarx/yaml-conf
```

## Using

Publish your package as you would usually do:

``` php
$this->publishes([
    __DIR__.'/../config/version.yml' => $this->getConfigFile(),
]);
```

Load the configuration in your `boot()` method:

``` php
$this->app
     ->make('pragmarx.yaml-conf')
     ->loadToConfig($this->getConfigFile(), 'my-package');
```

Or use the Facade:

``` php
YamlConfig::loadToConfig(config_path('myconf.yml'), 'my-package');
```

And it's merged to your Laravel config:

``` php
config('my-package.name');
```

## But... why?!

Are your config files getting bigger and harder to maintain every day? Use Yaml format to load them!:

```
app-version:
  major: 1
  minor: 0
  patch: 0
  format: "{$major}.{$minor}.{$patch}"
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


 
