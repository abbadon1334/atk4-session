<?php

declare(strict_types=1);

namespace atk4\ATK4DBSession;

class SessionModel extends \atk4\data\Model
{
    public $table    = 'session';
    public $id_field = 'id';

    public function init(): void
    {
        parent::init();

        $this->addFields([
            ['session_id', 'type' => 'string'],
            ['data', 'type' => 'text'], // < === must be text or other big data table
            ['created_on', 'type' => 'datetime', 'system' => 1, 'default' => date('Y-m-d H:i:s')],
            ['updated_on', 'type' => 'datetime', 'system' => 1, 'default' => null],
        ]);

        // thx @skondakov
        $this->addHook('beforeSave', function ($m): void {
            if ($m['id']) {
                $m['updated_on'] = date('Y-m-d H:i:s');
            }
        });
    }
}
