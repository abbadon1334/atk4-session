<?php

declare(strict_types=1);

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

    public function resetCallsSequence(): void
    {
        $this->executed_actions = [];
    }

    public function open($save_path, $session_name): bool
    {
        $this->addCall(__METHOD__);

        return parent::open($save_path, $session_name);
    }

    public function read($session_id): string
    {
        $this->addCall(__METHOD__);

        return parent::read($session_id);
    }

    public function destroy($session_id): bool
    {
        $this->addCall(__METHOD__);

        return parent::destroy($session_id);
    }

    public function write($session_id, $session_data): bool
    {
        $this->addCall(__METHOD__);

        return parent::write($session_id, $session_data);
    }

    public function gc($maxlifetime): bool
    {
        $this->addCall(__METHOD__);

        return parent::gc($maxlifetime);
    }

    public function create_sid(): string
    {
        $this->addCall(__METHOD__);

        return parent::create_sid();
    }

    public function updateTimestamp($sessionId, $sessionData): bool
    {
        $this->addCall(__METHOD__);
        echo $sessionId.PHP_EOL;

        return parent::updateTimestamp($sessionId, $sessionData);
    }

    public function validateId($sessionId): bool
    {
        $this->addCall(__METHOD__);

        return parent::validateId($sessionId);
    }

    private function addCall($name): void
    {
        echo $name.PHP_EOL;
        $this->executed_actions[] = $name;
    }
}
