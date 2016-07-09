8.0.0 (2016-??-??)
------------------

### BC breaks

  * raised minimum required PHP version to 7.0.0
  * introduced scalar type hints and strict type checking


### Other changes

  * `stubbles\sequence\Sequence` can now be casted to string, which provides information about how the sequence is build


7.0.0 (2016-01-11)
------------------

  * split off from [stubbles/core](https://github.com/stubbles/stubbles-core)
  * fixed `stubbles\sequence\iterator\MappingIterator` calling value- and key-mapper when end of iteration reached
