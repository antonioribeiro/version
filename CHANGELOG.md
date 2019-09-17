# Changelog

## [1.0.1] - 2019-09-17
### Added
- Fire events for some changes and updates. Please check the file `src/package/Support/Constants.php`

## [1.0.0] - 2019-09-16
#### This is a complete refactor, things will break if you don't review the `version.yml` file.
### Changed
- Complete refactor to fully comply with SemVer (https://semver.org/) specification
- "Build" renamed to "Commit"
- Please check the new version.yml config format
### Added
- "prerelease" and "buildmetadata" information to the version stack
- Commit timestamp information to the version stack 

## [0.3.0] - 2019-09-11
### Added
- Laravel 6 support

## [0.2.7] - 2018-03-16
### Added
- Option "--ignore-errors" to version:absorb Artisan command

## [0.2.5] - 2018-02-10
### Added
- Support for Laravel 5.6 and Symfony 4

## [0.2.4] - 2018-01-09
### Fixed
- Package booting Blade too soon
### Added
- Blade directive is now configurable
- Default value for build.length

## [0.2.3] - 2017-12-18
### Fixed
- A problem with absorb where git tags were not found

## [0.2.2] - 2017-12-14
### Added
- Allow users to suppress app name in version:show Artisan command
- Ability to absorb current git version and commit to the config file

## [0.2.1] - 2017-12-03
### Fixed
- Some minor bug fixed

## [0.2.0] - 2017-12-02
### Changed
- This is a breaking change, in configuration, that's we are moving to 0.2.0
- Allow getting the version from a git remote: 
    version_source: git-remote

## [0.1.7] - 2017-11-30
## ...
## [0.1.1] - 2017-11-30
### Changed
- Improvements

## [0.1.0] - 2017-11-27
### Added
- First version
