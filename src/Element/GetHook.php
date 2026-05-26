<?php

declare(strict_types=1);

namespace LifePhp\PhpGen\Element;

use Override;

class GetHook implements ElementInterface
{
    private function __construct(
        private readonly bool $abstract,
        private readonly ?string $body,
    ) {
    }

    public static function abstract(): self
    {
        return new self(true, null);
    }

    public static function arrow(string $expression): self
    {
        return new self(false, $expression);
    }

    #[Override]
    public function getUses(): array
    {
        return [];
    }

    #[Override]
    public function getDocCommentPart(): string
    {
        return '';
    }

    #[Override]
    public function render(): string
    {
        if ($this->abstract) {
            return 'get;';
        }

        return 'get => ' . $this->body . ';';
    }
}
