<?php

declare(strict_types=1);

namespace LifePhp\PhpGen\Element;

class UseStatementFunction extends UseStatement
{
    // Last space is mandatory
    protected const string USE_FORMAT = 'function ';

    public function __construct(
        string $functionName,
    ) {
        $this->path = $functionName;
        $slashPosition = strrpos($functionName, '\\');
        $this->name
            = $slashPosition === false
            ? $functionName
            : substr($functionName, $slashPosition + 1);
    }
}
