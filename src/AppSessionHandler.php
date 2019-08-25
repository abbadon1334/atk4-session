<?php

declare(strict_types=1);

namespace atk4\ATK4DBSession;

class AppSessionHandler
{
    use \atk4\core\AppScopeTrait;
    use \atk4\core\InitializerTrait {
        init as _init;
    }

    public $persistence;

    public $gc_maxlifetime;

    public $gc_probability;

    public $php_session_options;

    public $session_handler;

    public function init(): void
    {
        $this->_init();

        $this->session_handler = new SessionHandler(
            $this->persistence ?: $this->app->db,
            $this->gc_maxlifetime,
            $this->gc_probability,
            $this->php_session_options
        );
    }
}
