<?php

declare(strict_types=1);

namespace LifePhp\PhpGen\Element;

use LifePhp\PhpGen\Element\Literal\LiteralInterface;
use LifePhp\PhpGen\Element\Type\ClassType;
use LogicException;
use Override;

class Attribute implements ElementInterface
{
    /** @var array<int|string, LiteralInterface> */
    private array $args = [];

    private bool $hasNamedArg = false;

    public function __construct(private readonly ClassType $name)
    {
    }

    /**
     * We recommended to always use named arguments.
     */
    public function addArg(LiteralInterface $value, ?string $name = null): self
    {
        if ($name === null) {
            if ($this->hasNamedArg) {
                throw new LogicException('Positional arguments must precede named arguments.');
            }

            $this->args[] = $value;
        } else {
            $this->hasNamedArg = true;
            $this->args[$name] = $value;
        }

        return $this;
    }

    #[Override]
    public function getUses(): array
    {
        $retVal = $this->name->getUses();

        foreach ($this->args as $value) {
            foreach ($value->getUses() as $use) {
                $retVal[] = $use;
            }
        }

        return $retVal;
    }

    #[Override]
    public function getDocCommentPart(): string
    {
        return '';
    }

    #[Override]
    public function render(): string
    {
        if (empty($this->args)) {
            return '#[' . $this->name->render() . ']';
        }

        $rendered = [];

        foreach ($this->args as $key => $value) {
            $rendered[] = is_string($key)
                ? $key . ': ' . $value->render()
                : $value->render();
        }

        if (count($rendered) === 1) {
            return '#[' . $this->name->render() . '(' . $rendered[0] . ')]';
        }

        return '#[' . $this->name->render() . "(\n    " . implode(",\n    ", $rendered) . ",\n)]";
    }
}
