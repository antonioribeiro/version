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
</p>
<p align="center">
    <a href="https://scrutinizer-version.com/g/antonioribeiro/version/?branch=master"><img alt="Coverage" src="https://img.shields.io/scrutinizer/coverage/g/antonioribeiro/version.svg?style=flat-square"></a>
    <a href="https://styleci.io/repos/112244465"><img alt="StyleCI" src="https://styleci.io/repos/112244465/shield"></a>
    <a href="https://insight.sensiolabs.com/projects/0fd56820-866c-4f21-a6dd-cdc5db95c651"><img alt="SensioLabsInsight" src="https://img.shields.io/sensiolabs/i/0fd56820-866c-4f21-a6dd-cdc5db95c651.svg?style=flat-square"></a>
</p>

## Description

This package is a Laravel (5.5+) utility which helps you keep and manage your application version, increment version numbers (major, minor, patch, build), and can also use your last commit hash as build number.

#### The end results of this package are:

- Print a version on a page.
- Print it in the console, via an Artisan command.

#### Some use cases for those results could be: 
 
- Make sure a rollback was successful.
- Know if an update reached all servers.
- Check if a user is looking at the last version of your app.
- Verify if is Travis CI testing the version it is supposed to be testing.
- You simple love to version your stuff, and you like to see them in all your pages? That's cool too. :)
- What's your use case? [Tell us!](https://github.com/antonioribeiro/version/issues/new) 

## Features

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

### Easily increment your version numbers, using Artisan commands

``` bash
php artisan version:build
```

Which should print the new version number 

``` bash
New build: 701037
MyApp version 1.0.0 (build 701037) 
```

Available for all of them:

``` bash
php artisan version:major   
php artisan version:minor   
php artisan version:patch   
php artisan version:build   
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

It gives you access to dynamic methods:

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

### Instantiating it

If you prefer not to use the Façade:

``` php
dd(
    Version::format()
);
```

The best ways to instantiate it are:

A simple PHP object instantiation:

``` php
$version = new \PragmaRX\Version\Package\Version();

dd(
    $version->format()
);
```

Or to get an already instantiated Version object from the container:

``` php
dd(
    app(\PragmaRX\Version\Package\Version::class)->format()
);
```

But you have to make sure you [published the config file](/install)

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

You can configure the directive name:

``` yaml
blade_directive: printversion
```

Then 

``` php
@printversion('compact')
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

### Matching other version (git tags) formats

You probably only need to change the git version matcher 

``` yaml
git:
  ...
  version:
    matcher: "/[V|v]*[ersion]*\\s*\\.*(\\d+)\\.(\\d+)\\.(\\d+)\\.*(\\w*)/"
```

So let's say you tag your releases as 

``` text
2017120299
YYYYMMDD##
```

You can change your matcher to

``` yaml
git:
  version:
    matcher: "/(\d{4})(\d{2})(\d{2})(?:\d{2})/"
```

And remove dots from your formats:

``` yaml
format:
  compact: "v{$major}{$minor}{$patch}-{$build}"
```

### Artisan commands

Those are the commands you have at your disposal:

#### version:show

Show the current app version:

``` text
> php artisan version:show
> PragmaRX version 1.0.0 (build 701031)

> php artisan version:show --format=compact
> PragmaRX v1.0.0-701031

> php artisan version:show --format=compact --suppress-app-name
> v1.0.0-701031
```

#### version:(major|minor|patch|build)

Increment the version item:

``` text
$ php artisan version:minor
New minor version: 5
MyApp version 1.5.0 (build 701045)
```

#### version:refresh

Clear cache and refresh versions

``` text
> a version:refresh
> Version was refreshed.
> PragmaRX version 1.0.0 (build 4f76c)
```

#### version:absorb

Version can absorb git version and build to the config file, so you can delete the .git folder and still keep your version and build cached for fast access. You have to configure `git_absorb` in your config file:

``` yaml
build:
  #...  
  git_absorb: git-local # "false", "git-local" or "git-remote"
```

And run it 

``` bash
php artisan version:absorb
```

The usual configuration setup to implement absorb is:

``` yaml
version_source: config             ## must be set as config
current:
    major: 1                       ## |
    minor: 0                       ## | --> will be changed by absorb
    patch: 0                       ## |
    git_absorb: git-local          ## configure to get from local or remote
build:
    mode: number                   ## must be set as number
    number: f477c8                 ## will be changed by absorb
    git_absorb: git-local          ## configure to get from local or remote 
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

As git versions are cached, you can tell composer to refresh your version numbers every time an update or install occur, by adding the refresh command to `post-autoload-dump`:  

``` json
"post-autoload-dump": [
    ...
    "@php artisan version:refresh"
]
```

[Optional] You may also can automated this process by set inside your `.git/hooks/post-commit`. It will automatic run the command once you have make a commit.

``` bash
#!/bin/sh

php artisan version:refresh
```

If you are using Git commits on your build numbers, you may have to add the git repository to your .env file

``` text
VERSION_GIT_REMOTE_REPOSITORY=https://github.com/antonioribeiro/version.git
```

**If you are using `git-local` make sure the current folder is a git repository**

## Minimum requirements

- Laravel 5.5
- PHP 7.0

## Testing

``` bash
$ composer test
```

## Troubleshooting

- If you are having trouble to install because of symfony/router (3.3/3.4) or symfony/yaml (3.3/3.4), you can try to:

```
rm -rf vendor
rm composer.lock
composer install
```

## Author

[Antonio Carlos Ribeiro](http://twitter.com/iantonioribeiro)

## License

This package is licensed under the MIT License - see the `LICENSE` file for details

## Contributing

Pull requests and issues are welcome.


<!-- [![Downloads](https://img.shields.io/packagist/dt/pragmarx/version.svg?style=flat-square)](https://packagist.org/packages/pragmarx/version) --> 
