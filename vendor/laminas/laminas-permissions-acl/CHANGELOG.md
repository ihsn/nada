# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 2.7.2 - 2020-09-22


-----

### Release Notes for [2.7.2](https://github.com/laminas/laminas-permissions-acl/milestone/1)



### 2.7.2

- Total issues resolved: **2**
- Total pull requests resolved: **2**
- Total contributors: **1**

#### Bug

 - [8: Use provided assertion implementation to determine rule type](https://github.com/laminas/laminas-permissions-acl/pull/8) thanks to @weierophinney

#### Documentation

 - [7: Add LIFO example to documentation](https://github.com/laminas/laminas-permissions-acl/pull/7) thanks to @weierophinney

## 2.7.1 - 2019-06-25

### Added

- [zendframework/zend-permissions-acl#38](https://github.com/zendframework/zend-permissions-acl/pull/38) adds support for PHP 7.3.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 2.7.0 - 2018-05-01

### Added

- [zendframework/zend-permissions-acl#23](https://github.com/zendframework/zend-permissions-acl/pull/23) adds a new assertion, `ExpressionAssertion`, to allow programatically or
  automatically (from configuration) building standard comparison assertions
  using a variety of operators, including `=` (`==`), `!=`, `<`, `<=`, `>`,
  `>=`, `===`, `!==`, `in` (`in_array`), `!in` (`! in_array`), `regex`
  (`preg_match`), and `!regex` (`! preg_match`). See https://docs.laminas.dev/laminas-permissions-acl/expression/
  for details on usage.

- [zendframework/zend-permissions-acl#3](https://github.com/zendframework/zend-permissions-acl/pull/3) adds two new interfaces designed to allow creation of ownership-based assertions
  easier:

  - `Laminas\Permissions\Acl\ProprietaryInterface` is applicable to both roles and
    resources, and provides the method `getOwnerId()` for retrieving the owner
    role of an object.

  - `Laminas\Permissions\Acl\Assertion\OwnershipAssertion` ensures that the owner
    of a proprietary resource matches that of the role.

  See https://docs.laminas.dev/laminas-permissions-acl/ownership/ for details
  on usage.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 2.6.1 - 2018-05-01

### Added

- [zendframework/zend-permissions-acl#35](https://github.com/zendframework/zend-permissions-acl/pull/35) adds support for PHP 7.2.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-permissions-acl#29](https://github.com/zendframework/zend-permissions-acl/pull/29) provides a change to `Acl::removeResourceAll()` that increases performance by a factor of 100.

## 2.6.0 - 2016-02-03

### Added

- [zendframework/zend-permissions-acl#15](https://github.com/zendframework/zend-permissions-acl/pull/15) adds
  completed documentation, and publishes it to
  https://docs.laminas.dev/laminas-permissions-acl/

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-permissions-acl#7](https://github.com/zendframework/zend-permissions-acl/pull/7) and
  [zendframework/zend-permissions-acl#14](https://github.com/zendframework/zend-permissions-acl/pull/14) update the
  component to be forwards-compatible with laminas-servicemanager v3.
