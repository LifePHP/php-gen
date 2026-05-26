<?php

declare(strict_types=1);

namespace LifePhp\PhpGen\Element;

use LifePhp\PhpGen\Element\Literal\LiteralInterface;
use LifePhp\PhpGen\Element\Type\TypeInterface;
use LogicException;
use Override;

class Parameter implements ElementInterface
{
    private bool $variadic = false;

    private bool $byReference = false;

    private bool $readonly = false;

    private ?Visibility $promoted = null;

    private ?Visibility $setVisibility = null;

    private function __construct(
        private readonly string $name,
        private ?TypeInterface $type,
        private ?LiteralInterface $value,
    ) {
    }

    public static function typed(TypeInterface $type, string $name): self
    {
        return new self($name, $type, null);
    }

    public static function inferred(LiteralInterface $value, string $name): self
    {
        return new self($name, null, $value);
    }

    public function setVariadic(): self
    {
        if ($this->value !== null) {
            throw new LogicException('Variadic parameter cannot have a default value.');
        }

        if ($this->promoted !== null) {
            throw new LogicException('Promoted parameter cannot be variadic.');
        }

        $this->variadic = true;

        return $this;
    }

    public function setByReference(): self
    {
        if ($this->promoted !== null) {
            throw new LogicException('Promoted parameter cannot be passed by reference.');
        }

        $this->byReference = true;

        return $this;
    }

    public function setPromoted(Visibility $visibility): self
    {
        if ($this->variadic) {
            throw new LogicException('Promoted parameter cannot be variadic.');
        }

        if ($this->byReference) {
            throw new LogicException('Promoted parameter cannot be passed by reference.');
        }

        $this->promoted = $visibility;

        return $this;
    }

    public function setReadonly(): self
    {
        if ($this->promoted === null) {
            throw new LogicException('Only promoted parameters can be readonly.');
        }

        $this->readonly = true;

        return $this;
    }

    public function setSetVisibility(Visibility $setVisibility): self
    {
        if ($this->promoted === null) {
            throw new LogicException('Set visibility requires a promoted parameter.');
        }

        if ($setVisibility->rank() <= $this->promoted->rank()) {
            throw new LogicException('Set visibility must be more restrictive than get visibility.');
        }

        $this->setVisibility = $setVisibility;

        return $this;
    }

    public function setValue(LiteralInterface $value): self
    {
        if ($this->variadic) {
            throw new LogicException('Variadic parameter cannot have a default value.');
        }

        $this->value = $value;

        return $this;
    }

    public function isPromoted(): bool
    {
        return $this->promoted !== null;
    }

    public function isVariadic(): bool
    {
        return $this->variadic;
    }

    public function hasDefaultValue(): bool
    {
        return $this->value !== null;
    }

    public function getType(): TypeInterface
    {
        if ($this->type !== null) {
            return $this->type;
        }

        assert($this->value !== null);

        return $this->value->getType();
    }

    #[Override]
    public function getUses(): array
    {
        $retVal = [];

        if ($this->type !== null) {
            foreach ($this->type->getUses() as $use) {
                $retVal[] = $use;
            }
        }

        if ($this->value !== null) {
            foreach ($this->value->getUses() as $use) {
                $retVal[] = $use;
            }
        }

        return $retVal;
    }

    #[Override]
    public function getDocCommentPart(): string
    {
        return $this->getType()->getDocCommentPart() . ' $' . $this->name;
    }

    #[Override]
    public function render(): string
    {
        $parts = [];

        if ($this->promoted !== null) {
            $parts[] = $this->promoted->value;

            if ($this->setVisibility !== null) {
                $parts[] = $this->setVisibility->value . '(set)';
            }

            if ($this->readonly) {
                $parts[] = 'readonly';
            }
        }

        $name = ($this->byReference ? '&' : '')
            . ($this->variadic ? '...' : '')
            . '$' . $this->name;

        $parts[] = $this->getType()->render();
        $parts[] = $name;

        $rendered = implode(' ', $parts);

        if ($this->value !== null) {
            $rendered .= ' = ' . $this->value->render();
        }

        return $rendered;
    }
}
