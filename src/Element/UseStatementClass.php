<?php

declare(strict_types=1);

namespace LifePhp\PhpGen\Element;

class UseStatementClass extends UseStatement
{
    /**
     * @param class-string $className
     */
    public function __construct(
        string $className,
    ) {
        $this->path = $className;
        $slashPosition = strrpos($className, '\\');
        $this->name
            = $slashPosition === false
            ? $className
            : substr($className, $slashPosition + 1);
    }
}
