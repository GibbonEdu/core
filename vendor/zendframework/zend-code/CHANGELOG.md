# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 3.2.0 - TBD

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 3.1.1 - TBD

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 3.1.0 - 2016-10-24

### Added

- [#87](https://github.com/zendframework/zend-code/pull/95) support for
  PHP 7.1's `void` return type declaration.
- [#87](https://github.com/zendframework/zend-code/pull/95) support for
  PHP 7.1's nullable type declarations.
- [#87](https://github.com/zendframework/zend-code/pull/95) support for
  PHP 7.1's `iterable` type declaration.
- [#62](https://github.com/zendframework/zend-code/pull/62) added
  `Zend\Code\Generator\MethodGenerator#getReturnType()` accessor.
- [#68](https://github.com/zendframework/zend-code/pull/68)
  [#26](https://github.com/zendframework/zend-code/pull/26) added mutators
  to allow removing/checking for existence of methods, properties, constants,
  parameters and type declarations across all the code generator API.
- [#65](https://github.com/zendframework/zend-code/pull/65) continuous
  integration testing now checks locked, newest and oldest dependency
  sets.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 3.0.5 - 2016-10-24

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#92](https://github.com/zendframework/zend-code/pull/92) corrected
  `Zend\Code\Scanner\ClassScanner` to detect multiple interface inheritance.
- [#95](https://github.com/zendframework/zend-code/pull/95) corrected
  `Zend\Code\Generator\ParameterGenerator` to allow copying parameter signatures
  for non-optional parameters that are still nullable via a default `= null`
  value.
- [#94](https://github.com/zendframework/zend-code/pull/94) corrected
  `Zend\Code\Generator\ValueGenerator` so that class constants can now
  be generated with arrays as default value (supported since PHP 5.6).

## 3.0.4 - 2016-06-30

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#59](https://github.com/zendframework/zend-code/pull/59) fixes an issue with
  detection of multiple trait `use` statements.
- [#75](https://github.com/zendframework/zend-code/pull/75) provides a patch to
  ensure that `extends` statements qualify the parent class based on the current
  namespace and/or import statements.

## 3.0.3 - 2016-06-27

### Added

- [#66](https://github.com/zendframework/zend-code/pull/66) publishes the
  documentation to https://docs.zendframework.com/zend-code/.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#61](https://github.com/zendframework/zend-code/pull/61) fixes an issue with
  how parameter typehints were generated; previously, fully-qualified class
  names were not being generated with the leading backslash, causing them to
  attempt to resolve as if they were relative to the current namespace.
- [#69](https://github.com/zendframework/zend-code/pull/69) fixes an issue with
  how class names under the same namespace are generated when generating
  typehints, extends, and implements values; they now strip the
  common namespace from the class name.
- [#72](https://github.com/zendframework/zend-code/pull/72) fixes an issue
  within the `TokenArrayScanner` when scanning closures.

## 3.0.2 - 2016-04-20

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#52](https://github.com/zendframework/zend-code/pull/52) updates several
  dependency constraints:
  - zend-stdlib now allows either the 2.7 or 3.0 series, as the APIs consumed by
    zend-code are compatible across versions.
  - PHP now excludes the 7.0.5 release, as it has known issues in its tokenizer
    implementation that make the zend-code token scanner unusable.
- [#46](https://github.com/zendframework/zend-code/pull/46) updates all
  generators to use `\n` for line endings in generated code, vs `PHP_EOL`,
  ensuring cross-platform consistency.

## 3.0.1 - 2016-01-26

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#34](https://github.com/zendframework/zend-code/pull/34) method name cannot be optional when adding a method
  to a class generator.
- [#38](https://github.com/zendframework/zend-code/pull/38) PHP_CodeSniffer was moved to dev dependencies

## 3.0.0 - 2016-01-13

### Changed

This section refers to breaking changes: please refer to
[doc/book/migration.md](doc/book/migration.md) for migration instructions.

- Types `string`, `int`, `float`, `bool` passed to `Zend\Code\Generator\ParameterGenerator#setType()`
  are no longer ignored in generated code [#30](https://github.com/zendframework/zend-code/pull/30)
- Types declared in DocBlocks are now ignored when creating a `Zend\Code\Generator\ParameterGenerator` via
  `Zend\Code\Generator\ParameterGenerator::fromReflection()`. [#30](https://github.com/zendframework/zend-code/pull/30)
- Type strings are now validated: passing an invalid type to any method in the generator API
  may lead to a `Zend\Code\Generator\InvalidArgumentException` being thrown.
  [#30](https://github.com/zendframework/zend-code/pull/30)
- `Zend\Code\Generator\ParameterGenerator::$simple` was removed. [#30](https://github.com/zendframework/zend-code/pull/30)
- `Zend\Code\Generator\ParameterGenerator#$type` is now a `null|Zend\Code\Generator\TypeGenerator`: was a
  `string` before. [#30](https://github.com/zendframework/zend-code/pull/30)
- `Zend\Code\Generator` type-hints are now always prefixed with the namespace separator `\`.
  [#30](https://github.com/zendframework/zend-code/pull/30)
- `Zend\Code\Reflection\ParameterReflection#getType()` was renamed 
  to `Zend\Code\Reflection\ParameterReflection#detectType()` in order to not override the inherited
  `ReflectionParameter#getType()`, introduced in PHP 7. [#30](https://github.com/zendframework/zend-code/pull/30)

### Added

- PHP 7 return type hints generation support via `Zend\Code\Generator\MethodGenerator#setReturnType()`.
  [#30](https://github.com/zendframework/zend-code/pull/30)
- PHP 7 scalar type hints generation support via `Zend\Code\Generator\ParameterGenerator#setType()` and 
  `Zend\Code\Generator\ParameterGenerator#getType()`. [#30](https://github.com/zendframework/zend-code/pull/30)
- PHP 5.6 variadic arguments support via `Zend\Code\Generator\ParameterGenerator#setVariadic()` and
  `Zend\Code\Generator\ParameterGenerator#getVariadic()`. [#30](https://github.com/zendframework/zend-code/pull/30)
- Generation of methods returning by reference is supported via `Zend\Code\Generator\ParameterGenerator#setReturnsReference()`.
  [#30](https://github.com/zendframework/zend-code/pull/30)

### Deprecated

- Nothing.

### Removed

- `Zend\Code\ParameterGenerator::$simple` was removed. [#30](https://github.com/zendframework/zend-code/pull/30)

### Fixed

- Nothing.

## 2.6.2 - 2015-01-05

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#31](https://github.com/zendframework/zend-code/pull/31) updated license year.

## 2.6.2 - 2015-01-05

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#31](https://github.com/zendframework/zend-code/pull/31) updated license year.

## 2.6.1 - 2015-11-24

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#25](https://github.com/zendframework/zend-code/pull/25) changes the
  `doctrine/common` suggestion/dev-dependency to the more specific
  `doctrine/annotations` package (which is what is actually consumed).

## 2.6.0 - 2015-11-18

### Added

- [#12](https://github.com/zendframework/zend-code/pull/12) adds the ability to
  generate arrays using either long/standard syntax (`array(...)`) or short
  syntax (`[...]`). This can be accomplished by setting the value type to
  `ValueGenerator::TYPE_ARRAY_SHORT` instead of using `TYPE_ARRAY`.
  Additionally, you can use `TYPE_ARRAY_LONG` instead of `TYPE_ARRAY`; the two
  constants are synonyms.
- [#11](https://github.com/zendframework/zend-code/pull/11) adds the ability to
  generate interfaces via the new class `Zend\Code\Generator\InterfaceGenerator`.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#20](https://github.com/zendframework/zend-code/pull/20) updates
  the zend-eventmanager dependency to `^2.6|^3.0`, and changes its
  internal usage to use the `triggerEventUntil()` signature.

## 2.5.3 - 2015-11-18

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- [#10](https://github.com/zendframework/zend-code/pull/10) removes a
  development dependency on zendframework/zend-version.
- [#23](https://github.com/zendframework/zend-code/pull/23) removes a
  requirement on zendframework/zend-stdlib. This results in a slight change in
  `Zend\Code\Generator\ValueGenerator`: `setConstants()` and `getConstants()`
  can now receive/emit *either* an SPL `ArrayObject` or
  `Zend\Stdlib\ArrayObject`. Since these are functionally equivalent, however,
  you will experience no change in behavior.

### Fixed

- Nothing.
