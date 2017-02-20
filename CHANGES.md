This file is now obsolete - look in CHANGELOG.md for details post-fork

## 1.1

* Removed stdClass restrictions (see [#113](https://github.com/POData/POData/issues/113))

## 1.0

* Added composer support (see [#11](https://github.com/balihoo/POData/issues/11))
* Reorganized tests and got all unit tests passing (see [#31](https://github.com/balihoo/POData/issues/31), [#36](https://github.com/balihoo/POData/issues/36), [#55](https://github.com/balihoo/POData/issues/55)  )
* Added in Travis CI & Coveralls ( see [#19](https://github.com/balihoo/POData/issues/19) & [#26](https://github.com/balihoo/POData/issues/26) )
* Fixed a bug when handling query string ($filter & $expand) decoding (see [#10](https://github.com/balihoo/POData/issues/10) )
* Reduced to only one type of QueryProvider (the one that passes expressions down to the persistence layer) (see [#27](https://github.com/balihoo/POData/issues/56) )
* Refactored the way format serializers are choosen (see [#60](https://github.com/balihoo/POData/issues/60) & [#68](https://github.com/balihoo/POData/issues/68) )
** Added v3 JSON light format (see [#6](https://github.com/balihoo/POData/issues/6) )
** Change writers to have a fluent interface (see [#58](https://github.com/balihoo/POData/issues/58) )
* Allowed for optimiation of $count implementations (see [#3](https://github.com/balihoo/POData/issues/3) )
* Added $inlinecount support (see [#4](https://github.com/balihoo/POData/issues/4) )
* Added any function query in $filter clause support (see [#1](https://github.com/balihoo/POData/issues/1) ) 
* Switched names & namespace to POData (see [#27](https://github.com/balihoo/POData/issues/27) )
* Fixed a ton of PHP Doc problems (see [#45](https://github.com/balihoo/POData/issues/45), [#44](https://github.com/balihoo/POData/issues/44), [#39](https://github.com/balihoo/POData/issues/39), [#46](https://github.com/balihoo/POData/issues/46) )
* Shortened the name of many classes removing redundent prefixes (see [#57](https://github.com/balihoo/POData/issues/57) )
* Remove troublesome ?> from end of php files ( see [#42](https://github.com/balihoo/POData/issues/42) )
* Huge copyright blocks move out of code base ( see [#20](https://github.com/balihoo/POData/issues/20) )
* Various smaller bug fixes ( see [#75](https://github.com/balihoo/POData/issues/75) )
* Fixed a ton of grammar mistakes & typos ( https://github.com/MSOpenTech/odataphpprod/issues/2 )

## 0.0

Initial clone from https://github.com/MSOpenTech/odataphpprod/
