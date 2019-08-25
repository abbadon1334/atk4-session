<?php

declare(strict_types=1);

namespace atk4\ATK4DBSession\tests\SessionTraits;

use Symfony\Component\Process\Process;

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

    public static function setTimeoutBackgroundProcess($seconds): void
    {
        static::$process->setTimeout($seconds);
    }

    public static function verifyBackgroundProcessStarted(): void
    {
        sleep(1);
        if (!static::$process->isRunning()) {
            throw new \RuntimeException(sprintf(
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
            sleep(1);
            static::$process->stop(0);
            sleep(1);
            static::$process->stop(1, 9);
        } catch (\Throwable $t) {
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

    /**
     * Returns a command to run in background.
     *
     * @return string
     */
    abstract protected static function getCommand();
}
