<?php

declare(strict_types=1);

namespace Atk4\ATK4DBSession\Tests;

class SessionHandlerTest extends BaseTestCase
{
    protected const TRACE_OPEN_VALIDATE_READ = [
        'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::open',
        'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::validateId',
        'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::read',
    ];

    protected const TRACE_WRITE = [
        'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::write',
    ];

    protected const TRACE_UPDATE_TIMESTAMP = [
        'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::updateTimestamp',
    ];

    protected const TRACE_CLOSE = [
        'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::close',
    ];

    protected const TRACE_DESTROY = [
        'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::destroy',
    ];

    protected const TRACE_END = [
        '',
    ];

    public function testServer(): void
    {
        $response = $this->getClient()->request('GET', '/ping');
        $status = $response->getStatusCode();
        $this->assertSame(200, $status);

        $response = $this->getClient()->request('GET', '/no-route-return-404');
        $status = $response->getStatusCode();
        $this->assertSame(404, $status);

        // Check if use_strict_mode=1 to test Session::validateId
        $response = $this->getClient()->request('GET', '/ini_get/session.use_strict_mode');
        $status = $response->getStatusCode();
        $this->assertSame(200, $status);
        $this->assertStringContainsString('session.use_strict_mode:1', (string) $response->getBody());
    }

    protected function getSid(): string
    {
        $response = $this->getClient()->request('GET', '/session/sid');
        $this->assertSame(200, $response->getStatusCode());

        $output_array = [];
        preg_match('/^\[SID](.*)$/m', (string) $response->getBody(), $output_array);

        $sid = $output_array[1] ?? '';

        $this->assertNotEmpty($sid);

        return $sid;
    }

    protected function requestSessionVarUnset(string $name): void
    {
        $response = $this->getClient()->request('GET', '/session/unset/' . $name);
        $this->assertSame(200, $response->getStatusCode());

        $response_body = (string) $response->getBody();

        $steps = implode(PHP_EOL, array_merge(
            self::TRACE_OPEN_VALIDATE_READ,
            self::TRACE_WRITE,
            self::TRACE_CLOSE,
            self::TRACE_END
        ));

        $this->assertSame($steps, $response_body);
    }

    protected function requestSessionVarSet(string $name, string $value): void
    {
        $response = $this->getClient()->request('GET', '/session/set/' . $name . '/' . $value);
        $this->assertSame(200, $response->getStatusCode());

        $response_body = (string) $response->getBody();

        $steps = implode(PHP_EOL, array_merge(
            self::TRACE_OPEN_VALIDATE_READ,
            self::TRACE_WRITE,
            self::TRACE_CLOSE,
            self::TRACE_END
        ));

        // if called twice trigger an UPDATE_TIMESTAMP in place of WRITE
        if (strpos($response_body, self::TRACE_UPDATE_TIMESTAMP[0]) !== false) {
            $steps = implode(PHP_EOL, array_merge(
                self::TRACE_OPEN_VALIDATE_READ,
                self::TRACE_UPDATE_TIMESTAMP,
                self::TRACE_CLOSE,
                self::TRACE_END
            ));
        }

        $this->assertSame($steps, $response_body);
    }

    protected function requestSessionVarGet(string $name): string
    {
        $actual_sid = $this->getSid();

        $response = $this->getClient()->request('GET', '/session/get/' . $name);
        $this->assertSame(200, $response->getStatusCode());

        $response_body = (string) $response->getBody();

        $output_array = [];
        preg_match('/^\[VAL](.*)$/m', $response_body, $output_array);
        $value = $output_array[1] ?? '';

        $steps = implode(PHP_EOL, array_merge(
            self::TRACE_OPEN_VALIDATE_READ,
            ['[VAL]' . $value],
            self::TRACE_WRITE,
            self::TRACE_CLOSE,
            self::TRACE_END
        ));

        // if called twice trigger an UPDATE_TIMESTAMP in place of WRITE
        if (strpos($response_body, self::TRACE_UPDATE_TIMESTAMP[0]) !== false) {
            $steps = implode(PHP_EOL, array_merge(
                self::TRACE_OPEN_VALIDATE_READ,
                ['[VAL]' . $value],
                self::TRACE_UPDATE_TIMESTAMP,
                self::TRACE_CLOSE,
                self::TRACE_END
            ));
        }

        $this->assertSame($steps, $response_body);

        return $value;
    }

    public function testGetSetGet(): void
    {
        $this->requestSessionVarUnset('test');

        $this->assertEmpty($this->requestSessionVarGet('test'));

        $this->requestSessionVarSet('test', '1334');

        $session_value = $this->requestSessionVarGet('test');

        $this->assertSame('1334', $session_value);

        $this->requestSessionVarUnset('test');

        $this->assertEmpty($this->requestSessionVarGet('test'));
    }

    protected function requestSessionRegenerate(): void
    {
        $response = $this->getClient()->request('GET', '/session/regenerate');
        $this->assertSame(200, $response->getStatusCode());

        $response_body = (string) $response->getBody();

        $assert_actions = array_merge(
            self::TRACE_OPEN_VALIDATE_READ,
            self::TRACE_WRITE,
            self::TRACE_CLOSE,
            self::TRACE_OPEN_VALIDATE_READ,
            self::TRACE_WRITE,
            self::TRACE_CLOSE,
            self::TRACE_END
        );

        $this->assertSame(implode(PHP_EOL, $assert_actions), $response_body);
    }

    public function testSessionRegenerate(): void
    {
        $this->requestSessionVarSet('test', 'before-regenerate');

        $sid_before_regenerate = $this->getSid();

        $this->requestSessionRegenerate();

        $sid_after_generate = $this->getSid();

        $this->assertNotSame($sid_before_regenerate, $sid_after_generate);

        $this->assertSame('before-regenerate', $this->requestSessionVarGet('test'));
    }

    public function testSessionRegenerateWithDelete(): void
    {
        $response = $this->getClient()->request('GET', '/session/regenerate/delete_old');
        $this->assertSame(200, $response->getStatusCode());

        $response_body = (string) $response->getBody();

        $assert_actions = array_merge(
            self::TRACE_OPEN_VALIDATE_READ,
            self::TRACE_DESTROY,
            self::TRACE_CLOSE,
            self::TRACE_OPEN_VALIDATE_READ,
            self::TRACE_WRITE,
            self::TRACE_CLOSE,
            self::TRACE_END
        );

        $this->assertSame(implode(PHP_EOL, $assert_actions), $response_body);
    }

    // SETUP FUNCTIONS

    public function testSessionDestroy(): void
    {
        $value = 'to-be-destroyed';
        $this->requestSessionVarSet('value-before-destroy', $value);

        $this->assertSame($value, $this->requestSessionVarGet('value-before-destroy'));

        $response = $this->getClient()->request('GET', '/session/destroy');
        $this->assertSame(200, $response->getStatusCode());

        $response_body = (string) $response->getBody();

        $assert_actions = array_merge(
            self::TRACE_OPEN_VALIDATE_READ,
            self::TRACE_DESTROY,
            self::TRACE_CLOSE,
            self::TRACE_END
        );

        $this->assertSame(implode(PHP_EOL, $assert_actions), $response_body);

        $this->assertEmpty($this->requestSessionVarGet('value-before-destroy'));
    }

    /**
     * Test triggering of Garbage collector.
     *
     * Due to the nature of random, can give false positive.
     *
     * WebServer for test purpose is set to trigger 1/100 request
     *
     * during tests i see values from 300 to 500 with peak of 700.
     *
     * i decided to put it at 1000, just to :
     * - check not triggering at all ( exit after 1000 n cycle )
     * - check for erroneous instant ( n cycle = 0 )
     */
    public function testTriggerGarbageCollector(): void
    {
        $n_cycle = 0;
        while (true) {
            $response = $this->getClient()->request('GET', '/session/gc-trigger?session-options-gc=1');
            $this->assertSame(200, $response->getStatusCode());
            $body = (string) $response->getBody();

            if (strpos($body, 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::gc') !== false) {
                break;
            }

            ++$n_cycle;

            if ($n_cycle > 1000) {
                $this->fail('garbage collector not triggered after 1000 calls, with a probability 1 over 100 calls');
            }
        }

        $this->assertGreaterThanOrEqual(0, $n_cycle);

        //echo 'collector trigger was done after ' . $n_cycle . ' calls to collect garbage';
    }
}
