<?php

declare(strict_types=1);

namespace LifePhp\PhpGen\Element;

class UseStatementConstant extends UseStatement
{
    // Last space is mandatory
    protected const string USE_FORMAT = 'const ';

    public function __construct(
        string $constantName,
    ) {
        $this->path = $constantName;
        $slashPosition = strrpos($constantName, '\\');
        $this->name
            = $slashPosition === false
            ? $constantName
            : substr($constantName, $slashPosition + 1);
    }
}
