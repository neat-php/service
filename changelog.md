# Changelog
All notable changes to Neat Service components will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Unreleased

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
