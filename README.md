UniMan
======
Universal system for managing different services (MySql, Redis, Memcache, Rabbit).

[![Build Status](https://travis-ci.org/lulco/uniman.svg?branch=master)](https://travis-ci.org/lulco/uniman)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/lulco/uniman/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/lulco/uniman/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/lulco/uniman/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/lulco/uniman/?branch=master)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/f3fb2f65-7b76-443e-9f0c-0823aae1b772/mini.png)](https://insight.sensiolabs.com/projects/f3fb2f65-7b76-443e-9f0c-0823aae1b772)
[![PHP 7 ready](http://php7ready.timesplinter.ch/lulco/uniman/master/badge.svg)](https://travis-ci.org/lulco/uniman)

Installation
-----------

Checkout or download this repository and run init script:
```
sh scripts/init.sh
```

It simply runs `composer install` and creates two directories: `temp` and `log` in application root.

And that's all. Now you can use the application.

Usage
-----
In general, usage is simple: select one of available driver (service), connect to the server and view, edit or delete available data.
