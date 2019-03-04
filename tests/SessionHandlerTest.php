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

use atk4\ATK4DBSession\tests\SessionTraits\traitNeededFiles;
use atk4\ATK4DBSession\tests\SessionTraits\traitPhpServerProcess;
use atk4\core\PHPUnit_AgileTestCase;

class SessionHandlerTest extends PHPUnit_AgileTestCase
{
    use traitPhpServerProcess;
    use traitNeededFiles;
    
    public static $db_file  = __DIR__ . DIRECTORY_SEPARATOR . 'dbsess.sqlite';
    public static $jar_file = __DIR__ . DIRECTORY_SEPARATOR . 'cookie.jar';
    
    public static $jar;
    
    public static $sid;
    
    /* SETUP FUNCTIONS */
    protected static function getNeededFiles()
    {
        return [
            static::$db_file,
            static::$jar_file
        ];
    }
    
    protected static function getPhpServerOptions()
    {
        return [
            'host' => 'localhost',
            'port' => 8080,
            'root_dir' => __DIR__ . DIRECTORY_SEPARATOR,
            'router' => __DIR__ . DIRECTORY_SEPARATOR . 'webserver.php'
        ];
    }
    
    public static function setUpBeforeClass()
    {
        static::createNeededFiles();
    
        static::$jar = new \GuzzleHttp\Cookie\FileCookieJar(static::$jar_file);
        
        static::startBackgroundProcess();
        
        // any output will trigger sessions, best to remove output of any type
        static::clearBackgroundProcessOutput();
        
        static::verifyBackgroundProcessStarted();
    }
    
    public static function tearDownAfterClass()
    {
        static::stopBackgroundProcess();
        static::removeNeededFiles();
    }
    
    /* CLIENT */
    
    protected function getClient()
    {
        $opts = [
            'http_errors' => false,
            'cookies' => static::$jar
        ];
        
        $client = new \GuzzleHttp\Client($opts);
        
        return $client;
    }
    
    public function getSid()
    {
        $response = $this->getClient()->request('GET',"http://localhost:8080/session/sid");
    
        $output_array = [];
        preg_match('/^\[SID\](.*)$/m', (string) $response->getBody(), $output_array);
        
        return $output_array[1];
    }
    
    public function testServer()
    {
        $response = $this->getClient()->request('GET',"http://localhost:8080/ping");
        
        $status = $response->getStatusCode();
        
        $this->assertEquals(200,$status);
        
        $response = $this->getClient()->request('GET',"http://localhost:8080/give404");
    
        $status = $response->getStatusCode();
    
        $this->assertEquals(404,$status);
        
        // get SID
    
        static::$sid = $this->getSid();
    }
    
    public function testSID()
    {
        $sid = $this->getSid();
        
        $this->assertEquals($sid,static::$sid);
    }
    
    public function testSessionSetVar()
    {
        $response = $this->getClient()->request('GET',"http://localhost:8080/session/clear/test");
        $this->assertEquals(200,$response->getStatusCode());
    
        $assert_actions = [];
        $assert_actions[] = 'atk4\ATK4DBSession\tests\SessionHandlerCallTracer::open';
        $assert_actions[] = 'atk4\ATK4DBSession\tests\SessionHandlerCallTracer::read';
        $assert_actions[] = 'atk4\ATK4DBSession\tests\SessionHandlerCallTracer::write';
        $assert_actions[] = '';
    
        $response = $this->getClient()->request('GET',"http://localhost:8080/session/set/test/1334");
        
        $this->assertEquals(implode(PHP_EOL,$assert_actions), (string) $response->getBody());
        
    }
    
    public function testSessionGetVar()
    {
        $response = $this->getClient()->request('GET',"http://localhost:8080/session/get/test");
    
        $output_array = [];
        preg_match('/^\[VAL\](.*)$/m', (string) $response->getBody(), $output_array);
        $val = $output_array[1];
        
        $assert_actions = [];
        $assert_actions[] = 'atk4\ATK4DBSession\tests\SessionHandlerCallTracer::open';
        $assert_actions[] = 'atk4\ATK4DBSession\tests\SessionHandlerCallTracer::read';
        $assert_actions[] = '[VAL]' . $val;
        $assert_actions[] = 'atk4\ATK4DBSession\tests\SessionHandlerCallTracer::updateTimestamp';
        $assert_actions[] = static::$sid;
        $assert_actions[] = 'atk4\ATK4DBSession\tests\SessionHandlerCallTracer::write';
        $assert_actions[] = '';
        
        $this->assertEquals(implode(PHP_EOL,$assert_actions), (string) $response->getBody());
    }
    
    public function testSessionRegenerate()
    {
        $response = $this->getClient()->request('GET',"http://localhost:8080/session/regenerate");
    
        $assert_actions = [];
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
    
        $this->assertEquals(implode(PHP_EOL,$assert_actions), (string) $response->getBody());
    }
    
    public function testSessionGetNewSidAfterRegenerate()
    {
    
        $new_sid = $this->getSid();
    
        $this->assertNotEquals($new_sid,static::$sid);
    
        static::$sid = $new_sid;
    }
    
    public function testSessionGetVarAfterRegenerate()
    {
        $response = $this->getClient()->request('GET',"http://localhost:8080/session/get/test");
        
        $output_array = [];
        preg_match('/^\[VAL\](.*)$/m', (string) $response->getBody(), $output_array);
        $val = $output_array[1];
        
        $assert_actions = [];
        $assert_actions[] = 'atk4\ATK4DBSession\tests\SessionHandlerCallTracer::open';
        $assert_actions[] = 'atk4\ATK4DBSession\tests\SessionHandlerCallTracer::read';
        $assert_actions[] = '[VAL]' . $val;
        $assert_actions[] = 'atk4\ATK4DBSession\tests\SessionHandlerCallTracer::updateTimestamp';
        $assert_actions[] = static::$sid;
        $assert_actions[] = 'atk4\ATK4DBSession\tests\SessionHandlerCallTracer::write';
        $assert_actions[] = '';
        
        $this->assertEquals(implode(PHP_EOL,$assert_actions), (string) $response->getBody());
    }
    
    public function testSessionDestroy()
    {
        $response = $this->getClient()->request('GET',"http://localhost:8080/session/destroy");
        
        $assert_actions = [];
        $assert_actions[] = 'atk4\ATK4DBSession\tests\SessionHandlerCallTracer::open';
        $assert_actions[] = 'atk4\ATK4DBSession\tests\SessionHandlerCallTracer::read';
        $assert_actions[] = 'atk4\ATK4DBSession\tests\SessionHandlerCallTracer::destroy';
        $assert_actions[] = '';
        
        $this->assertEquals(implode(PHP_EOL,$assert_actions), (string) $response->getBody());
    }
    
    
    public function testSessionGetVarAfterDestroy()
    {
        $response = $this->getClient()->request('GET',"http://localhost:8080/session/get/test");
        
        $assert_actions = [];
        $assert_actions[] = 'atk4\ATK4DBSession\tests\SessionHandlerCallTracer::open';
        $assert_actions[] = 'atk4\ATK4DBSession\tests\SessionHandlerCallTracer::read';
        $assert_actions[] = '[VAL]'; // <-- val must be null
        $assert_actions[] = 'atk4\ATK4DBSession\tests\SessionHandlerCallTracer::write';
        $assert_actions[] = '';
        
        $this->assertEquals(implode(PHP_EOL,$assert_actions), (string) $response->getBody());
    }
    
    
    /**
     * Test triggering of Garbage collector
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
    public function testTriggerGarbageCollector()
    {
        $n_cycle = 0;
        while(true)
        {
            $response = $this->getClient()->request('GET', "http://localhost:8080/session/sid");
            $body = $response->getBody();
            
            if(strpos($body,'atk4\ATK4DBSession\tests\SessionHandlerCallTracer::gc') !== false)
            {
                break;
            }
            
            $n_cycle++;
            
            if($n_cycle > 1000)
            {
                $this->fail('garbage collector not triggered after 1000 calls');
            }
        }
    
        $this->assertGreaterThanOrEqual(0,$n_cycle);
        
        echo "collector trigger was done at : " . $n_cycle;
    }
}
