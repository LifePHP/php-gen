<?php

declare(strict_types=1);

namespace LifePhp\PhpGen\Element\Traits;

use LifePhp\PhpGen\Element\AbstractClassLikeDef;
use LifePhp\PhpGen\Element\ClassConstant;
use LogicException;

/**
 * @phpstan-require-extends AbstractClassLikeDef
 */
trait HasConstants
{
    /**
     * @var array<string, ClassConstant>
     */
    private array $constants = [];

    public function addConstant(ClassConstant $constant): self
    {
        $name = $constant->getName();

        if (isset($this->constants[$name])) {
            throw new LogicException(sprintf('"%s" already has a constant named "%s".', $this->name, $name));
        }

        $this->constants[$name] = $constant;

        return $this;
    }

    /**
     * @return string[]
     */
    protected function renderConstantMembers(): array
    {
        return array_map(
            fn (ClassConstant $c) => '    ' . $c->render() . ';',
            $this->sortedByVisibility($this->constants),
        );
    }
}
