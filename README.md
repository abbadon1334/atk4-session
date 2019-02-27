# atk4-session
Session handler for atk4\data\Persistence (@see https://github.com/atk4/data)

initialize *session handler* 

``` php

// autoload
include '../vendor/autoload.php';

// create pesistence
$db = \atk4\data\Persistence::connect('mysql://root:root@localhost/test');

// init session handler
new \atk4\ATK4DBSession\SessionController($p);
```

Create session table using atk4\schema
``` php
(new \atk4\schema\Migration\MySQL(new \atk4\ATK4DBSession\SessionModel($p)))->migrate();
```

OR create without a simple query
``` sql
CREATE TABLE `session` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `data` text COLLATE utf8_unicode_ci,
  `stamp` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
```

Session handler will work as a normal PHP Session but in place of saving using file it'll use atk4\data\Persistence

It's clearly a shame to have file locking on things that are usually static, like nowadays sessions.

Every call that use sessions call a file an set a lock on it to prevent race conditions.

You'll have for sure race conditions, BUT what race condition can be if you have only an ID in $_SESSION and that is nearly immutable after login.

In atk4 where async calls are massively used, this problem is much more evident.

a good article speaking about the problem [link](https://ma.ttias.be/php-session-locking-prevent-sessions-blocking-in-requests/)


You can add it without breaking your project, it already works but is missing PHPUnit ( some problem to check sessions ) and a good Garbage Collector.  



