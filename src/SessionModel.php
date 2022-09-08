<?php

declare(strict_types=1);

namespace Atk4\ATK4DBSession;

use Atk4\Data\Model;
use DateTime;

class SessionModel extends Model
{
    public $table = 'session';

    protected function init(): void
    {
        parent::init();

        $this->addField('session_id', ['type' => 'string']);

        // N.B. must be text to store whole serialized session
        $this->addField('data', ['type' => 'text', 'default' => '']);

        $this->addField('updated_on', ['type' => 'datetime', 'system' => true]);

        $this->onHook(Model::HOOK_BEFORE_INSERT, function (self $m, array &$data) {
            $data['updated_on'] = new DateTime();
        });
    }
}
