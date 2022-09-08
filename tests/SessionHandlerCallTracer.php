<?php

declare(strict_types=1);

namespace Atk4\ATK4DBSession\Tests;

use Atk4\ATK4DBSession\SessionHandler;

/**
 * Class SessionHandlerCallTracer.
 *
 * extended from SessionHandler to track calls to method of sessionHandler
 */
class SessionHandlerCallTracer extends SessionHandler
{
    public array $executed_actions = [];

    public function getCallsSequence(): array
    {
        return $this->executed_actions;
    }

    public function resetCallsSequence(): void
    {
        $this->executed_actions = [];
    }

    public function open($path, $name): bool
    {
        $this->addCall(__METHOD__);

        return parent::open($path, $name);
    }

    private function addCall(string $name): void
    {
        echo $name . PHP_EOL;
        error_log($name);
        $this->executed_actions[] = $name;
    }

    public function read($id): string
    {
        $this->addCall(__METHOD__);

        return parent::read($id);
    }

    public function close(): bool
    {
        $this->addCall(__METHOD__);

        return parent::close();
    }

    public function destroy($id): bool
    {
        $this->addCall(__METHOD__);

        return parent::destroy($id); // threshold
    }

    public function write($id, $data): bool
    {
        $this->addCall(__METHOD__);

        return parent::write($id, $data);
    }

    public function gc($max_lifetime)
    {
        $this->addCall(__METHOD__);

        return parent::gc($max_lifetime);
    }

    public function updateTimestamp($id, $data): bool
    {
        $this->addCall(__METHOD__);

        return parent::updateTimestamp($id, $data);
    }

    public function validateId($id): bool
    {
        $this->addCall(__METHOD__);

        return parent::validateId($id);
    }
}
