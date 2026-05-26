<?php

declare(strict_types=1);

namespace LifePhp\PhpGen\Element;

use LifePhp\PhpGen\Element\Type\TypeInterface;
use Override;

class SetHook implements ElementInterface
{
    private function __construct(
        private readonly bool $abstract,
        private readonly ?string $body,
        private readonly ?TypeInterface $paramType,
        private readonly ?string $paramName,
    ) {
    }

    public static function abstract(): self
    {
        return new self(true, null, null, null);
    }

    public static function arrow(
        string $expression,
        ?TypeInterface $paramType = null,
        string $paramName = 'value',
    ): self {
        return new self(false, $expression, $paramType, $paramName);
    }

    #[Override]
    public function getUses(): array
    {
        return $this->paramType !== null ? $this->paramType->getUses() : [];
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
            return 'set;';
        }

        $param = '';

        if ($this->paramName !== null) {
            $typeStr = $this->paramType !== null ? $this->paramType->render() . ' ' : '';
            $param = '(' . $typeStr . '$' . $this->paramName . ')';
        }

        return 'set' . $param . ' => ' . $this->body . ';';
    }
}
