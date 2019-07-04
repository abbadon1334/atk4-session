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

namespace atk4\ATK4DBSession\tests\SessionTraits;

trait traitPhpServerProcess
{
    use traitBackgroundProcess;

    protected static function getCommand()
    {
        return self::getPhpServerCommand();
    }

    protected static function getPhpServerCommand()
    {
        $rootDir = self::getPhpServerOption('root_dir');
        $router = self::getPhpServerOption('router');
        $host = self::getPhpServerOption('host', 'localhost');
        $port = self::getPhpServerOption('port', 8000);

        return sprintf(
            'php -S %s:%d%s%s',
            $host,
            $port,
            $rootDir ? ' -t '.$rootDir : '',
            $router ? ' '.$router : ''
        );
    }

    protected static function getPhpServerOptions()
    {
        return [
            'host'     => 'localhost',
            'port'     => 8000,
            'root_dir' => null,
            'router'   => null,
        ];
    }

    private static function getPhpServerOption($option, $default = null)
    {
        $options = self::getPhpServerOptions();

        return array_key_exists($option, $options) ? $options[$option] : $default;
    }
}
