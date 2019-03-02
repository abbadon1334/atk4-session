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

*Create session table using atk4\schema*
``` php
(new \atk4\schema\Migration\MySQL(new \atk4\ATK4DBSession\SessionModel($p)))->migrate();
```

*OR*

*Create session table with SQL query*
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

Constructor of SessionController
================================

```php
/**
 * SessionController constructor.
 *
 * @param \atk4\data\Persistence    $p                      atk4 data persistence 
 * @param int                       $gc_maxlifetime         seconds until session expire
 * @param float                     $gc_probability         probability of gc for expired sessions 
 * @param array                     $php_session_options    options for session_start
 */
public function __construct($p, $gc_maxlifetime = null, $gc_probability = null, $php_session_options = [])
```

Why i need to replace the default PHP Session Handler with this?
================================================================

Because of file locking ( here a good article about the argument [link](https://ma.ttias.be/php-session-locking-prevent-sessions-blocking-in-requests/))

Every call that use sessions read a file and set a lock on it until release or output, to prevent race conditions.

It's clearly a shame to have file locking on things that are usually static, like nowadays sessions.

Using an alternative you'll have for sure race conditions, BUT what race condition can be if you, usually, have only an ID in $_SESSION and that is nearly immutable from login to logout.

SessionController will substitute SessionHandler class in PHP and will store session data in database using atk4\data instead of using files.

In atk4\ui where async calls are massively used, this problem is much more evident.

You can add it without breaking your project, it already works, but is still in development and need a strong review for security issue.  
