<?php

declare(strict_types=1);

namespace LifePhp\PhpGen\Element;

use LifePhp\PhpGen\Element\Literal\LiteralInterface;
use LifePhp\PhpGen\Element\Traits\HasAttributes;
use LifePhp\PhpGen\Element\Type\TypeInterface;
use LogicException;
use Override;

class Property implements ElementInterface
{
    use HasAttributes;

    private ?TypeInterface $type;

    private ?LiteralInterface $value;

    private bool $readonly = false;

    private bool $static = false;

    private bool $abstract = false;

    private ?Visibility $setVisibility = null;

    private function __construct(
        private readonly Visibility $visibility,
        private readonly string $name,
        ?TypeInterface $type,
        ?LiteralInterface $value,
    ) {
        $this->type = $type;
        $this->value = $value;
    }

    public static function typed(Visibility $visibility, TypeInterface $type, string $name): self
    {
        return new self($visibility, $name, $type, null);
    }

    public static function inferred(Visibility $visibility, LiteralInterface $value, string $name): self
    {
        return new self($visibility, $name, null, $value);
    }

    public function setReadonly(): self
    {
        if ($this->static) {
            throw new LogicException('Property cannot be both readonly and static.');
        }

        if ($this->value !== null) {
            throw new LogicException('Readonly property cannot have a default value.');
        }

        $this->readonly = true;

        return $this;
    }

    public function setStatic(): self
    {
        if ($this->readonly) {
            throw new LogicException('Property cannot be both static and readonly.');
        }

        if ($this->setVisibility !== null) {
            throw new LogicException('Static property cannot have asymmetric visibility.');
        }

        $this->static = true;

        return $this;
    }

    public function setSetVisibility(Visibility $setVisibility): self
    {
        if ($this->static) {
            throw new LogicException('Static property cannot have asymmetric visibility.');
        }

        if ($setVisibility->rank() <= $this->visibility->rank()) {
            throw new LogicException('Set visibility must be more restrictive than get visibility.');
        }

        $this->setVisibility = $setVisibility;

        return $this;
    }

    public function setAbstract(): self
    {
        if ($this->value !== null) {
            throw new LogicException('Abstract property cannot have a default value.');
        }

        $this->abstract = true;

        return $this;
    }

    public function setValue(LiteralInterface $value): self
    {
        if ($this->abstract) {
            throw new LogicException('Abstract property cannot have a default value.');
        }

        if ($this->readonly) {
            throw new LogicException('Readonly property cannot have a default value.');
        }

        $this->value = $value;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVisibility(): Visibility
    {
        return $this->visibility;
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
        $retVal = $this->collectAttributeUses();

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
        return $this->getType()->getDocCommentPart();
    }

    #[Override]
    public function render(): string
    {
        $parts = [];

        if ($this->abstract) {
            $parts[] = 'abstract';
        }

        $parts[] = $this->visibility->value;

        if ($this->setVisibility !== null) {
            $parts[] = $this->setVisibility->value . '(set)';
        }

        if ($this->static) {
            $parts[] = 'static';
        }

        if ($this->readonly) {
            $parts[] = 'readonly';
        }

        $parts[] = $this->getType()->render();
        $parts[] = '$' . $this->name;

        $rendered = implode(' ', $parts);

        if ($this->value !== null) {
            $rendered .= ' = ' . $this->value->render();
        }

        $attrBlock = $this->renderAttributes();

        return $attrBlock !== '' ? $attrBlock . "\n" . $rendered : $rendered;
    }
}
