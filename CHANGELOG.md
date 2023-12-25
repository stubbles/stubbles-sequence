# Changelog

## 10.1.0 (2023-12-25)

### BC breaks

* raised minimum required PHP version to 8.2

## 10.0.0 (2022-12-26)

### BC breaks

* raised minimum required PHP version to 8.0

## 9.0.0 (2019-10-29)

* raised minimum required PHP version to 7.3

## 8.1.0 (2016-07-11)

* implemented #2: create sequence with variadic arguments

## 8.0.0 (2016-07-11)

### BC breaks

* raised minimum required PHP version to 7.0.0
* introduced scalar type hints and strict type checking

### Other changes

* `stubbles\sequence\Sequence` can now be casted to string, which provides information about how the sequence is build
* added `stubbles\sequence\assert\Provides:values()` which creates a predicate that allows to assert that a sequence contains the expected values
* added `stubbles\sequence\assert\Provides:data()` which creates a predicate that allows to assert that a sequence contains the expected data
* added `stubbles\sequence\castToIterator()`

## 7.0.0 (2016-01-11)

* split off from [stubbles/core](https://github.com/stubbles/stubbles-core)
* fixed `stubbles\sequence\iterator\MappingIterator` calling value- and key-mapper when end of iteration reached
