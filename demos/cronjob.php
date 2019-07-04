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
include __DIR__.'/../vendor/autoload.php';

$p = new \atk4\data\Persistence_SQL('mysql:dbname=atk4;host=localhost', 'atk4', '');

// create the table in database using schema\Migration
// use it one time to creqate the table, don't migrate on every call, please leave mysql alone ;)
(new \atk4\schema\Migration\MySQL(new \atk4\ATK4DBSession\SessionModel($p)))->migrate();

// init SessionHandler
(new \atk4\ATK4DBSession\SessionHandler($p))->executeGC();
