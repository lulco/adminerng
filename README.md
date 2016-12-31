Adminer Next Generation
========================
Universal system for managing different services (MySql, Redis, Memcache, Rabbit).

[![Build Status](https://travis-ci.org/lulco/adminerng.svg?branch=master)](https://travis-ci.org/lulco/adminerng)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/lulco/adminerng/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/lulco/adminerng/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/lulco/adminerng/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/lulco/adminerng/?branch=master)

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
