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

namespace atk4\ATK4DBSession\tests;

use atk4\ATK4DBSession\SessionHandler;

/**
 * Class SessionHandlerCallTracer.
 *
 * extended from SessionHandler to track calls to method of sessionHandler
 */
class SessionHandlerCallTracer extends SessionHandler
{
    public $executed_actions = [];

    public function getCallsSequence()
    {
        return $this->executed_actions;
    }

    public function resetCallsSequence()
    {
        $this->executed_actions = [];
    }

    private function addCall($name)
    {
        echo($name).PHP_EOL;
        $this->executed_actions[] = $name;
    }

    public function open($save_path, $session_name)
    {
        $this->addCall(__METHOD__);

        return parent::open($save_path, $session_name);
    }

    public function read($session_id)
    {
        $this->addCall(__METHOD__);

        return parent::read($session_id);
    }

    public function destroy($session_id)
    {
        $this->addCall(__METHOD__);

        return parent::destroy($session_id);
    }

    public function write($session_id, $session_data)
    {
        $this->addCall(__METHOD__);

        return parent::write($session_id, $session_data);
    }

    public function gc($maxlifetime)
    {
        $this->addCall(__METHOD__);

        return parent::gc($maxlifetime);
    }

    public function create_sid()
    {
        $this->addCall(__METHOD__);

        return parent::create_sid();
    }

    public function updateTimestamp($sessionId, $sessionData)
    {
        $this->addCall(__METHOD__);
        echo($sessionId).PHP_EOL;
        //print($sessionData) . PHP_EOL;
        return parent::updateTimestamp($sessionId, $sessionData);
    }

    public function validateId($sessionId)
    {
        $this->addCall(__METHOD__);

        return parent::validateId($sessionId);
    }
}
