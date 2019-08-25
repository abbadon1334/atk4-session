<?php

declare(strict_types=1);

namespace atk4\ATK4DBSession\tests\SessionTraits;

trait traitPhpServerProcess
{
    use traitBackgroundProcess;

    protected static function getCommand()
    {
        return static::getPhpServerCommand();
    }

    protected static function getPhpServerCommand()
    {
        $rootDir = static::getPhpServerOption('root_dir');
        $router  = static::getPhpServerOption('router');
        $host    = static::getPhpServerOption('host', 'localhost');
        $port    = static::getPhpServerOption('port', 8000);

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
        $options = static::getPhpServerOptions();

        return array_key_exists($option, $options) ? $options[$option] : $default;
    }
}
