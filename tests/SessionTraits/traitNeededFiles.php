<?php

declare(strict_types=1);

namespace atk4\ATK4DBSession\tests\SessionTraits;

trait traitNeededFiles
{
    protected static function getNeededFiles()
    {
        return [];
    }

    protected static function createNeededFiles(): void
    {
        foreach (static::getNeededFiles() as $file) {
            if (!file_exists($file)) {
                @touch($file);
                @chmod($file, 0777);
            }
        }
    }

    protected static function removeNeededFiles(): void
    {
        foreach (static::getNeededFiles() as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }
    }
}
