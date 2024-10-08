# Changelog
All notable changes to Neat Service components will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Unreleased
### Changed
- Parameters that are marked as optional and not explicitly registered (by instance, factory or alias) in the container
  won't be instantiated.

## [0.4.0] - 2022-02-08
### Added
- PhpStorm advanced metadata to support code completion after $container->get()
  or $container->getOrCreate() calls.
- Support for PHP >=8.0.
- Typehints.

### Removed
- Dropped support for PHP <7.2.

## [0.3.3] - 2020-06-19
### Fixed
- Call behaviour on classes with __invoke method.

## [0.3.2] - 2020-05-15
### Added
- Code coverage button in readme.
- Extend services using the $container->extend($service, $extension, $parameter) method. [#5](https://github.com/neat-php/service/issues/5)

### Fixed
- Full code coverage.
- Service sharing doesn't work with auto-wiring. [#6](https://github.com/neat-php/service/issues/6)

## [0.3.1] - 2019-12-31
### Fixed
- Support for invoking callable objects with an __invoke method.

## [0.3.0] - 2019-12-18
### Changed
- Use argument default values for types that aren't known by the container.

## [0.2.2] - 2019-10-30
### Added
- Factory method for retrieving a service factory from the container.

## [0.2.1] - 2019-10-30
### Added
- Aliases interface. Allows retrieving aliases from a service provider.
- Shares interface. Allows service providers to configure shared services as well.

## [0.2.0] - 2018-10-14
### Added
- ```getOrCreate``` method to the Container.
- Dependency injection documentation.

### Fixed
- Overwriting and removing services from the container.
- Merged Container and Injector.
- Removed namespace prefix feature.

## [0.1.0] - 2018-07-29
### Added
- Neat service container and injector.
