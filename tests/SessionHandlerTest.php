<?php

declare(strict_types=1);

namespace atk4\ATK4DBSession\tests;

use atk4\ATK4DBSession\tests\SessionTraits\traitNeededFiles;
use atk4\ATK4DBSession\tests\SessionTraits\traitPhpServerProcess;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class SessionHandlerTest extends TestCase
{
    use traitPhpServerProcess;
    use traitNeededFiles;

    public static $db_file  = __DIR__.DIRECTORY_SEPARATOR.'dbsess.sqlite';
    public static $jar_file = __DIR__.DIRECTORY_SEPARATOR.'cookie.jar';

    public static $jar;

    public static $sid;

    public static function setUpBeforeClass(): void
    {
        static::createNeededFiles();

        static::$jar = new \GuzzleHttp\Cookie\FileCookieJar(static::$jar_file);

        static::startBackgroundProcess();

        // any output will trigger sessions, best to remove output of any type
        //static::clearBackgroundProcessOutput();
        static::verifyBackgroundProcessStarted();

        parent::setUpBeforeClass();
    }

    public static function tearDownAfterClass(): void
    {
        static::stopBackgroundProcess();
        static::removeNeededFiles();

        parent::tearDownAfterClass();
    }

    public function getSid()
    {
        $response = $this->getClient()->request('GET', '/session/sid');

        $output_array = [];
        preg_match('/^\[SID\](.*)$/m', (string) $response->getBody()->getContents(), $output_array);

        return $output_array[1] ?? '';
    }

    public function testServer(): void
    {
        $response = $this->getClient()->request('GET', '/ping');

        $status = $response->getStatusCode();

        $this->assertEquals(200, $status);

        $response = $this->getClient()->request('GET', '/give404');

        $status = $response->getStatusCode();

        $this->assertEquals(404, $status);

        // get SID

        static::$sid = $this->getSid();
    }

    public function testSID(): void
    {
        $sid = $this->getSid();

        $this->assertEquals($sid, static::$sid);
    }

    public function testSessionSetVar(): void
    {
        $response = $this->getClient()->request('GET', '/session/clear/test');
        $this->assertEquals(200, $response->getStatusCode());

        $assert_actions   = [];
        $assert_actions[] = 'atk4\ATK4DBSession\tests\SessionHandlerCallTracer::open';
        $assert_actions[] = 'atk4\ATK4DBSession\tests\SessionHandlerCallTracer::read';
        $assert_actions[] = 'atk4\ATK4DBSession\tests\SessionHandlerCallTracer::write';
        $assert_actions[] = '';

        $response = $this->getClient()->request('GET', '/session/set/test/1334');

        $this->assertEquals(implode(PHP_EOL, $assert_actions), (string) $response->getBody()->getContents());
    }

    public function testSessionGetVar(): void
    {
        $response = $this->getClient()->request('GET', '/session/get/test');

        $response_body = (string) $response->getBody()->getContents();
        $output_array = [];
        preg_match('/^\[VAL\](.*)$/m', $response_body, $output_array);
        $val = $output_array[1] ?? '';

        $assert_actions   = [];
        $assert_actions[] = 'atk4\ATK4DBSession\tests\SessionHandlerCallTracer::open';
        $assert_actions[] = 'atk4\ATK4DBSession\tests\SessionHandlerCallTracer::read';
        $assert_actions[] = '[VAL]'.$val;
        $assert_actions[] = 'atk4\ATK4DBSession\tests\SessionHandlerCallTracer::updateTimestamp';
        $assert_actions[] = static::$sid;
        $assert_actions[] = 'atk4\ATK4DBSession\tests\SessionHandlerCallTracer::write';
        $assert_actions[] = '';

        $this->assertEquals(implode(PHP_EOL, $assert_actions), $response_body);
    }

    public function testSessionRegenerate(): void
    {
        $response = $this->getClient()->request('GET', '/session/regenerate');

        $assert_actions   = [];
        $assert_actions[] = 'atk4\ATK4DBSession\tests\SessionHandlerCallTracer::open';
        $assert_actions[] = 'atk4\ATK4DBSession\tests\SessionHandlerCallTracer::read';
        //$assert_actions[] = 'atk4\ATK4DBSession\tests\SessionHandlerCallTracer::updateTimestamp';
        //$assert_actions[] = static::$sid;
        $assert_actions[] = 'atk4\ATK4DBSession\tests\SessionHandlerCallTracer::destroy';
        $assert_actions[] = 'atk4\ATK4DBSession\tests\SessionHandlerCallTracer::open';
        $assert_actions[] = 'atk4\ATK4DBSession\tests\SessionHandlerCallTracer::create_sid';
        $assert_actions[] = 'atk4\ATK4DBSession\tests\SessionHandlerCallTracer::read';
        $assert_actions[] = 'atk4\ATK4DBSession\tests\SessionHandlerCallTracer::write';
        $assert_actions[] = '';

        $this->assertEquals(implode(PHP_EOL, $assert_actions), (string) $response->getBody()->getContents());
    }

    public function testSessionGetNewSidAfterRegenerate(): void
    {
        $new_sid = $this->getSid();

        $this->assertNotEquals($new_sid, static::$sid);

        static::$sid = $new_sid;
    }

    public function testSessionGetVarAfterRegenerate(): void
    {
        $response = $this->getClient()->request('GET', '/session/get/test');

        $response_body = (string) $response->getBody()->getContents();
        $output_array = [];
        preg_match('/^\[VAL\](.*)$/m', $response_body, $output_array);
        $val = $output_array[1];

        $assert_actions   = [];
        $assert_actions[] = 'atk4\ATK4DBSession\tests\SessionHandlerCallTracer::open';
        $assert_actions[] = 'atk4\ATK4DBSession\tests\SessionHandlerCallTracer::read';
        $assert_actions[] = '[VAL]'.$val;
        $assert_actions[] = 'atk4\ATK4DBSession\tests\SessionHandlerCallTracer::updateTimestamp';
        $assert_actions[] = static::$sid;
        $assert_actions[] = 'atk4\ATK4DBSession\tests\SessionHandlerCallTracer::write';
        $assert_actions[] = '';

        $this->assertEquals(implode(PHP_EOL, $assert_actions), $response_body);
    }

    public function testSessionDestroy(): void
    {
        $response = $this->getClient()->request('GET', '/session/destroy');

        $assert_actions   = [];
        $assert_actions[] = 'atk4\ATK4DBSession\tests\SessionHandlerCallTracer::open';
        $assert_actions[] = 'atk4\ATK4DBSession\tests\SessionHandlerCallTracer::read';
        $assert_actions[] = 'atk4\ATK4DBSession\tests\SessionHandlerCallTracer::destroy';
        $assert_actions[] = '';

        $this->assertEquals(implode(PHP_EOL, $assert_actions), (string) $response->getBody()->getContents());
    }

    public function testSessionGetVarAfterDestroy(): void
    {
        $response = $this->getClient()->request('GET', '/session/get/test');

        $assert_actions   = [];
        $assert_actions[] = 'atk4\ATK4DBSession\tests\SessionHandlerCallTracer::open';
        $assert_actions[] = 'atk4\ATK4DBSession\tests\SessionHandlerCallTracer::read';
        $assert_actions[] = '[VAL]'; // <-- val must be null
        $assert_actions[] = 'atk4\ATK4DBSession\tests\SessionHandlerCallTracer::write';
        $assert_actions[] = '';

        $this->assertEquals(implode(PHP_EOL, $assert_actions), (string) $response->getBody()->getContents());
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
            $response = $this->getClient()->request('GET', '/session/sid');
            $body     = $response->getBody()->getContents();

            if (false !== strpos($body, 'atk4\ATK4DBSession\tests\SessionHandlerCallTracer::gc')) {
                break;
            }

            ++$n_cycle;

            if ($n_cycle > 1000) {
                $this->fail('garbage collector not triggered after 1000 calls');
            }
        }

        $this->assertGreaterThanOrEqual(0, $n_cycle);

        echo 'collector trigger was done after '.$n_cycle.' calls to collect garbage';
    }

    /* SETUP FUNCTIONS */
    protected static function getNeededFiles()
    {
        return [
            static::$db_file,
            static::$jar_file,
        ];
    }

    protected static function getPhpServerOptions()
    {
        return [
            'host'     => 'localhost',
            'port'     => 8080,
            'root_dir' => null, //__DIR__.DIRECTORY_SEPARATOR,
            'router'   => __DIR__.DIRECTORY_SEPARATOR.'webserver.php',
        ];
    }

    /* CLIENT */

    protected function getClient()
    {
        $opts = [
            'base_uri'    => 'http://'.static::getPhpServerOption('host', 'localhost').':'.static::getPhpServerOption('port', 80),
            'http_errors' => false,
            'cookies'     => static::$jar,
        ];

        $client = new \GuzzleHttp\Client($opts);

        return $client;
    }
}
