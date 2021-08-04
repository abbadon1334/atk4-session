<?php

declare(strict_types=1);

namespace Atk4\ATK4DBSession\Tests;

use Atk4\ATK4DBSession\Tests\SessionTraits\traitNeededFiles;
use Atk4\ATK4DBSession\Tests\SessionTraits\traitPhpServerProcess;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class SessionHandlerTest extends TestCase
{
    use traitNeededFiles;
    use traitPhpServerProcess;

    public static string $db_file = __DIR__ . \DIRECTORY_SEPARATOR . 'dbsess.sqlite';
    public static string $jar_file = __DIR__ . \DIRECTORY_SEPARATOR . 'cookie.jar';

    public static FileCookieJar $jar;

    public static string $sid;

    public static function setUpBeforeClass(): void
    {
        self::createNeededFiles();

        self::$jar = new FileCookieJar(self::$jar_file);

        self::startBackgroundProcess();

        // any output will trigger sessions, best to remove output of any type
        //SessionHandlerTest::clearBackgroundProcessOutput();
        self::verifyBackgroundProcessStarted();

        parent::setUpBeforeClass();
    }

    public static function tearDownAfterClass(): void
    {
        self::stopBackgroundProcess();
        self::removeNeededFiles();

        parent::tearDownAfterClass();
    }

    protected static function getNeededFiles()
    {
        return [
            self::$db_file,
            self::$jar_file,
        ];
    }

    protected static function getPhpServerOptions()
    {
        return [
            'host' => 'localhost',
            'port' => 8888,
            'root_dir' => null, //__DIR__.DIRECTORY_SEPARATOR,
            'router' => __DIR__ . \DIRECTORY_SEPARATOR . 'webserver.php',
        ];
    }

    public function testServer(): void
    {
        $response = $this->getClient()->request('GET', '/ping');
        $status = $response->getStatusCode();
        $this->assertSame(200, $status);

        $response = $this->getClient()->request('GET', '/give404');
        $status = $response->getStatusCode();
        $this->assertSame(404, $status);

        $response = $this->getClient()->request('GET', '/ini_get/session.use_strict_mode');
        $status = $response->getStatusCode();
        $this->assertSame(200, $status);
        $this->assertStringContainsString('session.use_strict_mode:1', (string) $response->getBody()->getContents());

        $response = $this->getClient()->request('GET', '/ini_get/session.use_trans_sid');
        $status = $response->getStatusCode();
        $this->assertSame(200, $status);
        $this->assertStringContainsString('session.use_trans_sid:1', (string) $response->getBody()->getContents());

        // get SID
        self::$sid = $this->getSid();
    }

    protected function getClient()
    {
        $opts = [
            'base_uri' => 'http://' . self::getPhpServerOption(
                'host',
                'localhost'
            ) . ':' . self::getPhpServerOption('port', 80),
            'http_errors' => false,
            'cookies' => self::$jar,
        ];

        $client = new Client($opts);

        return $client;
    }

    public function getSid()
    {
        $response = $this->getClient()->request('GET', '/session/sid');
        $this->assertSame(200, $response->getStatusCode());

        $output_array = [];
        preg_match('/^\[SID](.*)$/m', (string) $response->getBody()->getContents(), $output_array);

        return $output_array[1] ?? '';
    }

    public function testSID(): void
    {
        $sid = $this->getSid();

        $this->assertNotEmpty($sid);
        $this->assertSame($sid, self::$sid);
    }

    public function testSessionSetVar(): void
    {
        $response = $this->getClient()->request('GET', '/session/clear/test');
        $this->assertSame(200, $response->getStatusCode());

        $assert_actions = [];
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::open';
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::validateId';
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::read';
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::write';
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::close';
        $assert_actions[] = '';

        $response = $this->getClient()->request('GET', '/session/set/test/1334');
        $this->assertSame(200, $response->getStatusCode());

        $this->assertSame(implode(PHP_EOL, $assert_actions), (string) $response->getBody()->getContents());
    }

    public function testSessionGetVar(): void
    {
        $response = $this->getClient()->request('GET', '/session/get/test');
        $this->assertSame(200, $response->getStatusCode());

        $response_body = (string) $response->getBody()->getContents();
        $output_array = [];
        preg_match('/^\[VAL](.*)$/m', $response_body, $output_array);
        $val = $output_array[1] ?? '';

        $assert_actions = [];
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::open';
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::validateId';
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::read';
        $assert_actions[] = '[VAL]' . $val;
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::updateTimestamp';
        $assert_actions[] = self::$sid;
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::close';
        $assert_actions[] = '';

        $this->assertSame(implode(PHP_EOL, $assert_actions), $response_body);
    }

    public function testSessionRegenerate(): void
    {
        $response = $this->getClient()->request('GET', '/session/regenerate');
        $this->assertSame(200, $response->getStatusCode());

        $assert_actions = [];
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::open';
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::validateId';
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::read';
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::write';
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::close';
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::open';
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::create_sid';
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::validateId';
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::create_sid';
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::read';
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::write';
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::close';
        $assert_actions[] = '';

        $this->assertSame(implode(PHP_EOL, $assert_actions), (string) $response->getBody()->getContents());
    }

    public function testSessionGetNewSidAfterRegenerate(): void
    {
        $new_sid = $this->getSid();

        $this->assertNotEmpty($new_sid);
        $this->assertNotEmpty(self::$sid);

        $this->assertNotSame($new_sid, self::$sid);

        self::$sid = $new_sid;
    }

    public function testSessionGetVarAfterRegenerate(): void
    {
        $response = $this->getClient()->request('GET', '/session/get/test');
        $this->assertSame(200, $response->getStatusCode());

        $response_body = (string) $response->getBody()->getContents();
        $output_array = [];
        preg_match('/^\[VAL](.*)$/m', $response_body, $output_array);
        $val = $output_array[1];

        $assert_actions = [];
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::open';
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::validateId';
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::read';
        $assert_actions[] = '[VAL]' . $val;
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::updateTimestamp';
        $assert_actions[] = self::$sid;
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::close';
        $assert_actions[] = '';

        $this->assertSame(implode(PHP_EOL, $assert_actions), $response_body);
    }

    public function testSessionRegenerateWithDelete(): void
    {
        $response = $this->getClient()->request('GET', '/session/regenerate/delete_old');
        $this->assertSame(200, $response->getStatusCode());

        $assert_actions = [];
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::open';
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::validateId';
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::read';
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::destroy';
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::close';
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::open';
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::create_sid';
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::validateId';
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::create_sid';
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::read';
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::write';
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::close';
        $assert_actions[] = '';

        $this->assertSame(implode(PHP_EOL, $assert_actions), (string) $response->getBody()->getContents());
    }

    // SETUP FUNCTIONS

    public function testSessionDestroy(): void
    {
        $response = $this->getClient()->request('GET', '/session/destroy');
        $this->assertSame(200, $response->getStatusCode());

        $assert_actions = [];
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::open';
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::validateId';
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::read';
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::destroy';
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::close';
        $assert_actions[] = '';

        $this->assertSame(implode(PHP_EOL, $assert_actions), (string) $response->getBody()->getContents());
    }

    public function testSessionGetVarAfterDestroy(): void
    {
        $response = $this->getClient()->request('GET', '/session/get/test');
        $this->assertSame(200, $response->getStatusCode());

        $assert_actions = [];
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::open';
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::validateId';
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::create_sid';
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::read';
        $assert_actions[] = '[VAL]'; // <-- val must be null
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::write';
        $assert_actions[] = 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::close';
        $assert_actions[] = '';

        $this->assertSame(implode(PHP_EOL, $assert_actions), (string) $response->getBody()->getContents());
    }

    // CLIENT

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
            $response = $this->getClient()->request('GET', '/session/sid?test_gc=1');
            $this->assertSame(200, $response->getStatusCode());
            $body = $response->getBody()->getContents();

            if (strpos($body, 'Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer::gc') !== false) {
                break;
            }

            ++$n_cycle;

            if ($n_cycle > 1000) {
                $this->fail('garbage collector not triggered after 1000 calls, with a probability 1 over 100 calls');
            }
        }

        $this->assertGreaterThanOrEqual(0, $n_cycle);

        echo 'collector trigger was done after ' . $n_cycle . ' calls to collect garbage';
    }
}
