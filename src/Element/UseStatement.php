<?php

declare(strict_types=1);

namespace LifePhp\PhpGen\Element;

use Override;

abstract class UseStatement implements ElementInterface
{
    protected const string USE_FORMAT = '';

    public ?string $alias = null;

    protected string $path;
    protected string $name;

    public function getPath(): string
    {
        return $this->path;
    }

    public function getName(): string
    {
        if ($this->alias !== null) {
            return $this->alias;
        }

        return $this->name;
    }

    #[Override]
    public function getUses(): array
    {
        return [];
    }

    #[Override]
    public function getDocCommentPart(): string
    {
        return $this->getName();
    }

    #[Override]
    public function render(): string
    {
        $alias = '';

        if ($this->alias !== null) {
            $alias = sprintf(
                ' as %s',
                $this->alias,
            );
        }

        return sprintf(
            'use %s%s%s;',
            static::USE_FORMAT,
            $this->path,
            $alias,
        );
    }
}
