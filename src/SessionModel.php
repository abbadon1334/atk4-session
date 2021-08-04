<?php

declare(strict_types=1);

namespace Atk4\ATK4DBSession;

use Atk4\Data\Model;
use DateTime;

class SessionModel extends Model
{
    public $table = 'session';
    public $id_field = 'id';

    protected function init(): void
    {
        parent::init();

        $this->addFields([
            ['session_id', 'type' => 'string'],
            ['data', 'type' => 'text'], // < === must be text or other big data table
            ['created_on', 'type' => 'datetime', 'system' => true],
            ['updated_on', 'type' => 'datetime', 'system' => true],
        ]);

        $this->onHook(Model::HOOK_BEFORE_SAVE, function (self $m, bool $is_update) {
            $dt_formatted = (new DateTime())->format('Y-m-d H:i:s');

            if (!$is_update) {
                $m->set('created_on', $dt_formatted);
            }

            $m->set('updated_on', $dt_formatted);
        });
    }
}
