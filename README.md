# ATK4 Session Handler

[![License](https://img.shields.io/github/license/abbadon1334/atk4-session.svg)](https://github.com/abbadon1334/atk4-session)
[![Maintainability](https://img.shields.io/codeclimate/maintainability/abbadon1334/atk4-session.svg)](https://codeclimate.com/github/abbadon1334/atk4-session)
[![Maintainability](https://img.shields.io/codeclimate/maintainability-percentage/abbadon1334/atk4-session.svg)](https://codeclimate.com/github/abbadon1334/atk4-session)
[![Technical Debt](https://img.shields.io/codeclimate/tech-debt/abbadon1334/atk4-session.svg)](https://codeclimate.com/github/abbadon1334/atk4-session)
[![Test Coverage](https://img.shields.io/codecov/c/github/abbadon1334/atk4-session/master.svg)](https://codecov.io/github/abbadon1334/atk4-session?branch=master)
[![PHP version](https://img.shields.io/packagist/php-v/abbadon1334/atk4-session.svg)](https://packagist.org/packages/abbadon1334/atk4-session)

Session handler for Atk4\Data (@see https://github.com/atk4/data)

### Install

`composer require abbadon1334/atk4-session`

### Initialize **without atk4\ui**

``` php

// autoload
include '../vendor/autoload.php';

// create pesistence
$db = \Atk4\data\Persistence::connect('mysql://root:password@localhost/atk4');

// init session handler
new \Atk4\ATK4DBSession\SessionHandler($p, [/* session options */]);
```

*Create session table using atk4\schema*
``` php
(new \Atk4\Data\Schema\Migrator(new \atk4\ATK4DBSession\SessionModel($p)))->create();
```

*OR*

*Create session table with SQL query*
``` sql
CREATE TABLE `session` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `data` text COLLATE utf8_unicode_ci,
  `created_on` timestamp NULL DEFAULT NULL,
  `updated_on` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
```

## Session GC 

if you use InnoDB deletes are slow, so the best option for huge request application is to set a cronjob which run every 
2 minutes, you can find an example in : `demos/example/cronjob.example.php`.
When you instantiate the SessionHandler, if you use the crojob, set the gc_probability option to 0 to disable automatic triggering of gc.   

### Why i need to replace the default PHP Session Handler with this?

Because of file locking ( here a good article about the argument [link](https://ma.ttias.be/php-session-locking-prevent-sessions-blocking-in-requests/))

Every call that use sessions read a file and set a lock on it until release or output, to prevent race conditions.

It's clearly a shame to have file locking on things that are usually static, like nowadays sessions.

Using an alternative you'll have for sure race conditions, BUT what race condition can be if you, usually, have only an ID in $_SESSION and that is nearly immutable from login to logout.

SessionHandler will substitute SessionHandler class in PHP and will store session data in database using atk4\data instead of using files.

In atk4\ui where async calls are massively used, this problem is much more evident.

You can add it without breaking your project, it already works, but is still in development and need a strong review for security issue.  
