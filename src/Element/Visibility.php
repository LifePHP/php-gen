<?php

declare(strict_types=1);

namespace LifePhp\PhpGen\Element;

enum Visibility: string
{
    case Public = 'public';
    case Protected = 'protected';
    case Private = 'private';

    public function rank(): int
    {
        return match ($this) {
            self::Public    => 0,
            self::Protected => 1,
            self::Private   => 2,
        };
    }
}
