<?php
/**
 * Copyright (c) 2019.
 *
 * Francesco "Abbadon1334" Danti <fdanti@gmail.com>
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following
 * conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 */

namespace atk4\ATK4DBSession;

class SessionModel extends \atk4\data\Model
{
    public $table = 'session';
    public $id_field = 'id';

    public function init()
    {
        parent::init();

        $this->addFields([
            ['session_id', 'type' => 'string'],
            ['data', 'type' => 'text'], // < === must be text or other big data table
            ['created_on', 'type' => 'datetime', 'system' => 1, 'default' => date('Y-m-d H:i:s')],
            ['updated_on', 'type' => 'datetime', 'system' => 1, 'default' => null],
        ]);

        // thx @skondakov
        $this->addHook('beforeSave', function ($m) {
            if ($m['id']) {
                $m['updated_on'] = date('Y-m-d H:i:s');
            }
        });
    }
}