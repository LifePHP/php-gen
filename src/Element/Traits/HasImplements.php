<?php

declare(strict_types=1);

namespace LifePhp\PhpGen\Element\Traits;

use LifePhp\PhpGen\Element\AbstractClassLikeDef;
use LifePhp\PhpGen\Element\InterfaceDef;
use LifePhp\PhpGen\Element\UseStatement;
use LogicException;

/**
 * @phpstan-require-extends AbstractClassLikeDef
 */
trait HasImplements
{
    /**
     * @var array<string, InterfaceDef>
     */
    private array $implements = [];

    public function addImplements(InterfaceDef $interface): self
    {
        $fqcn = $interface->getFullyQualifiedName();

        if (isset($this->implements[$fqcn])) {
            throw new LogicException(sprintf('"%s" already implements "%s".', $this->name, $fqcn));
        }

        $this->implements[$fqcn] = $interface;

        return $this;
    }

    /**
     * @return UseStatement[]
     */
    protected function collectImplementsUses(): array
    {
        $retVal = [];

        foreach ($this->implements as $interface) {
            foreach ($interface->toClassType()->getUses() as $use) {
                $retVal[] = $use;
            }
        }

        return $retVal;
    }

    protected function renderImplementsList(): string
    {
        if (empty($this->implements)) {
            return '';
        }

        return implode(', ', array_map(
            fn (InterfaceDef $i) => $i->toClassType()->render(),
            array_values($this->implements),
        ));
    }
}
