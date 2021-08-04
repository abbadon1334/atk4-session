<?php

declare(strict_types=1);

namespace Atk4\ATK4DBSession\Tests\SessionTraits;

use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

trait traitBackgroundProcess
{
    /**
     * @var Process
     */
    public static $process;

    public static function startBackgroundProcess(): void
    {
        $cmd = static::getCommand();

        static::$process = $cmd instanceof Process ? $cmd : Process::fromShellCommandline(static::getCommand());

        static::$process->enableOutput();
        static::$process->start();
    }

    /**
     * Returns a command to run in background.
     *
     * @return string
     */
    abstract protected static function getCommand();

    public static function setTimeoutBackgroundProcess($seconds): void
    {
        static::$process->setTimeout($seconds);
    }

    public static function verifyBackgroundProcessStarted(): void
    {
        time_nanosleep(1, 250000);
        if (!static::$process->isRunning()) {
            throw new RuntimeException(sprintf(
                'Failed to start "%s" in background: %s',
                static::$process->getCommandLine(),
                static::$process->getErrorOutput()
            ));
        }
    }

    /**
     * @afterClass
     */
    public static function stopBackgroundProcess(): void
    {
        try {
            static::$process->signal(9);
            time_nanosleep(0, 250000);
            static::$process->stop(0);
            time_nanosleep(0, 250000);
            static::$process->stop(1, 9);
        } catch (Throwable $t) {
        }
    }

    /**
     * @after
     */
    public static function clearBackgroundProcessOutput(): void
    {
        static::$process->clearOutput();
        static::$process->clearErrorOutput();
    }
}
