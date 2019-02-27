# atk4-session
Session handler for atk4\data\Persistence (@see https://github.com/atk4/data)

initialize *session handler* 

``` php

// autoload
include '../vendor/autoload.php';

// create pesistence
$db = \atk4\data\Persistence::connect('mysql://root:password@localhost/atk4');

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

Session handler will function like a normal PHP session but will use atk4\data\Persistence instead of files.

It's clearly a shame to have file locking on things that are usually static, like nowadays sessions.

Every call that use sessions read a file and set a lock on it to prevent race conditions until release or output.

A good article speaking about the problem [link](https://ma.ttias.be/php-session-locking-prevent-sessions-blocking-in-requests/)

Using an alternative you'll have for sure race conditions, BUT what race condition can be if you, usually, have only an ID in $_SESSION and that is nearly immutable from login to logout.

In atk4\ui where async calls are massively used, this problem is much more evident.

You can add it without breaking your project, it already works but is missing PHPUnit ( some problem to check sessions ) and a good Garbage Collector.  



