<?php

declare(strict_types=1);

namespace LifePhp\PhpGen\Element;

interface ElementInterface
{
    /**
     * @return UseStatement[]
     */
    public function getUses(): array;

    public function getDocCommentPart(): string;
    public function render(): string;
}
