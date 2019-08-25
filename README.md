# atk4-session

[![Codacy Badge](https://api.codacy.com/project/badge/Grade/1942c3eb6be54d45a3c3fb84989b598f)](https://app.codacy.com/app/abbadon1334/atk4-session?utm_source=github.com&utm_medium=referral&utm_content=abbadon1334/atk4-session&utm_campaign=Badge_Grade_Dashboard)

Session handler for atk4\data\Persistence (@see https://github.com/atk4/data)

initialize **without atk4\ui**

``` php

// autoload
include '../vendor/autoload.php';

// create pesistence
$db = \atk4\data\Persistence::connect('mysql://root:password@localhost/atk4');

// init session handler
new \atk4\ATK4DBSession\SessionHandler($p);
```

initialize **with atk4\ui in App::init method**

``` php
$this->add(new AppSessionHandler());
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
  `created_on` timestamp NULL DEFAULT NULL,
  `updated_on` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
```

## Constructor of SessionHandler

```php
/**
 * SessionHandler constructor.
 *
 * @param \atk4\data\Persistence    $p                      atk4 data persistence 
 * @param int                       $gc_maxlifetime         seconds until session expire
 * @param float                     $gc_probability         probability of gc for expired sessions 
 * @param array                     $php_session_options    options for session_start
 */
public function __construct($p, $gc_maxlifetime = null, $gc_probability = null, $php_session_options = [])
```

## $gc_maxlifetime
max session lifetime before eligible to gc, default value is set to 60 * 60 secods = 1 hour

## $gc_probability
percentage of probability of gc expired sessions, default is set to 1/1000 request.
You have to consider few things for tweaking this value, because it must be sized to your project

if you use InnoDB deletes are slow and if set it low too many calls will have a little delay, if you set too high few calls will have a huge delay.  

Considering disable it setting this value to *false* and use an alternative method like cronJob with frequency */2 * * * * that calls code like example : demos/cronjob.php


 

### Why i need to replace the default PHP Session Handler with this?

Because of file locking ( here a good article about the argument [link](https://ma.ttias.be/php-session-locking-prevent-sessions-blocking-in-requests/))

Every call that use sessions read a file and set a lock on it until release or output, to prevent race conditions.

It's clearly a shame to have file locking on things that are usually static, like nowadays sessions.

Using an alternative you'll have for sure race conditions, BUT what race condition can be if you, usually, have only an ID in $_SESSION and that is nearly immutable from login to logout.

SessionHandler will substitute SessionHandler class in PHP and will store session data in database using atk4\data instead of using files.

In atk4\ui where async calls are massively used, this problem is much more evident.

You can add it without breaking your project, it already works, but is still in development and need a strong review for security issue.  
