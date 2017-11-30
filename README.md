# Version
### Take control over your Laravel app version

<p align="center">
    <img src="docs/screenshot.png">
</p>

<p align="center">
    <a href="https://packagist.org/packages/pragmarx/version"><img alt="Latest Stable Version" src="https://img.shields.io/packagist/v/pragmarx/version.svg?style=flat-square"></a>
    <a href="/antonioribeiro/version/blob/master/LICENSE.md"><img alt="License" src="https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square"></a>
    <a href="https://scrutinizer-version.com/g/antonioribeiro/version/?branch=master"><img alt="Code Quality" src="https://img.shields.io/scrutinizer/g/antonioribeiro/version.svg?style=flat-square"></a>
    <a href="https://scrutinizer-version.com/g/antonioribeiro/version/?branch=master"><img alt="Build" src="https://img.shields.io/scrutinizer/build/g/antonioribeiro/version.svg?style=flat-square"></a>
    <a href="https://scrutinizer-version.com/g/antonioribeiro/version/?branch=master"><img alt="Coverage" src="https://img.shields.io/scrutinizer/coverage/g/antonioribeiro/version.svg?style=flat-square"></a>
    <a href="https://styleci.io/repos/112244465"><img alt="StyleCI" src="https://styleci.io/repos/112244465/shield"></a>
</p>

## Description

This package is a Laravel (5.5+) utility wich helps you keep and manage your application version, increment version numbers (major, minor, patch, build), and can also use your last commit hash as build number.

## Key features

### Easily control you app version using a YAML config file

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

It gives you acces to dynamic methods:

``` php
Version::compact()
```

And should you create a new one:

``` yaml
format:
  awesome: "awesome version {$major}.{$minor}.{$patch}"
```

It will also become callable:

``` php
Version::awesome()
```
 
### A Facade is available

``` php
Version::version() // 1.2.25

Version::build() // 703110

Version::major() // 1

Version::minor() // 2

Version::patch() // 25

Version::format('full') // version 1.0.0 (build 703110)

Version::full() // version 1.0.0 (build 703110) -- dynamic method

Version::format('compact') // v.1.0.0-703110

Version::compact() // v.1.0.0-703110 -- dynamic method
```

### A Blade directive is also ready to be used in your views

You can use this directive to render a full version format:

``` php
@version
```

Or choose the format:

``` php
@version('full')
@version('compact')
```

### Git tags

You can use your git tags as application versions, all you need is to set the version source to "git":

``` yaml
version_source: git
```

And if you add a build number to your tags:

``` bash
git tag -a -f v0.1.1.3128
```

Version will use it as your app build number

### Artisan commands

Those are the commands you have at your disposal:

``` text
  version:show     Show current app version and build

  version:major    Increment app major version

  version:minor    Increment app minor version

  version:patch    Increment app patch version

  version:build    Increment app build number
  
  version:refresh  Clear cache and refresh versions
```

Here's an example of `version:minor`:

``` text
$ php artisan version:minor
New minor version: 5
MyApp version 1.5.0 (build 701045)
```

## Install

Via Composer

``` bash
composer require pragmarx/version
```

Then publish the configuration file you'll have to:

``` bash
php artisan vendor:publish --provider="PragmaRX\Version\Package\ServiceProvider"
```

And you should be good to use it in your views:

``` php
@version
```

If you are using Git commits on your build numbers, you may have to add the git repository to your .env file

``` text
VERSION_GIT_REMOTE_REPOSITORY=https://github.com/antonioribeiro/version.git
```

**If you are using `git-local` make sure the current folder is a git repository**

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
