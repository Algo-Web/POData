
Master Status: [![Build Status](https://travis-ci.org/Algo-Web/POData.svg?branch=master)](https://travis-ci.org/Algo-Web/POData)
[![Coverage Status](https://img.shields.io/coveralls/c-harris/POData.svg)](https://coveralls.io/r/c-harris/POData?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/c-harris/POData/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/c-harris/POData/?branch=master)

POData - OData for the Poor PHP Developer
============

POData (pronounced like the [sandwich](http://en.wikipedia.org/wiki/Po'_boy)) a is an OData service framework for PHP Developers.  PHP Developers are dirt poor because they are not afforded a nice OData toolkit, but POData elimantes their poverty and brings the bountiful wealth of OData to the masses!.

POData vs odataphpprod
===================
POData started as a fork of [The OData Producer Library for PHP](https://github.com/MSOpenTech/odataphpprod).  Many thanks to that project for making this one possible.  The many goals of this fork are best tracked in the issues list but here are some highlights:

* OData v3 Compliant
* Full [BreezeJS](http://www.breezejs.com/) & [JayData](http://jaydata.org/) support (we love those libraries)
* Availability via Composer
* Simpler to plug in to common PHP frameworks ([Zend](https://github.com/zendframework/zf1), [Symphony](https://github.com/symphonycms/symphony-2), [Laravel](https://github.com/laravel/laravel))
* Produce sample services that pass [OData Validation](http://services.odata.org/validation/)
* Offers your provider implementation more control on how to best execute the OData Query
* Optimized $expand support
* Support for an Annotation Based Provider Implementation

Long term goals include:

* OData v4 Support
* Create, Update, & Delete support
* Transaction support
* Port to Node
* Convince WordPress & MediaWiki to change their entire API to OData

MVC Integrations
=================
It's likley POData will execute in the context of an Web MVC Framework.  As such, the framework has probably already done a lot of the parsing for you and it makes no sense to have POData reinvent the wheel.  As such, some MVC adapters are provided to bridge the MVC framework to POData.
* ZendFramework 1 - [POData-ZF1](https://github.com/POData/POData-ZF1)


Getting Started
================
Check the Wiki for a [step by step getting started guide](https://github.com/POData/POData/wiki#getting-started-guide)

Contact
============
Need Support? Want to help contribute (but not yet ready to submit a pull request)?  Want to complain about something being too hard?  Doesn't matter why we're interested, contact us at:

* Our [POData google group](https://groups.google.com/d/forum/podata)
