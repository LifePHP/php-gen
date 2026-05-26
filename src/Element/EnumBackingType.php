<?php

declare(strict_types=1);

namespace LifePhp\PhpGen\Element;

enum EnumBackingType: string
{
    case String = 'string';
    case Int = 'int';
}
