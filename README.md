[![Build Status](https://travis-ci.org/balihoo/POData.png?branch=master)](https://travis-ci.org/balihoo/POData) 
[![Coverage Status](https://coveralls.io/repos/balihoo/POData/badge.png)](https://coveralls.io/r/balihoo/POData)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/balihoo/POData/badges/quality-score.png?s=f64c3b87cfa28d109fa394e68dd34c3caa88bedc)](https://scrutinizer-ci.com/g/balihoo/POData/)
[![Code Coverage](https://scrutinizer-ci.com/g/balihoo/POData/badges/coverage.png?s=ce164b3b45a6a2f06fb42c344d61bed2e5eea63d)](https://scrutinizer-ci.com/g/balihoo/POData/)

POData - OData for the Poor PHP Developer
============

POData (pronounced like the [sandwich](http://en.wikipedia.org/wiki/Po'_boy)) a is an OData service framework for PHP Developers.  PHP Developers are dirt poor because they are not afforded a nice OData toolkit, but POData elimantes their poverty and brings the boutiful wealth of OData to the masses!.

POData vs odataphpprod
===================
POData started as a fork of [The OData Producer Library for PHP](https://github.com/MSOpenTech/odataphpprod) many thanks to that project for making this one possible.  The many goals of this fork are best tracked in the issues list but here are some highlights:

* OData v3 Support
* Full BreezeJS support (we love that library)
* Availability via Composer
* Simpler to plug in to common PHP frameworks (Zend, Symphony, Laravel)
* Offers your provider implementation more control on how to best execute the OData Query
* Optimized $expand support
* Support for an Annotation Based Provider Implementation

Long term goals include:

* OData v4 Support
* Create, Update, & Delete support
* Transaction support
* Port to Node
* Convince WordPress & MediaWiki to change their entire API to OData
