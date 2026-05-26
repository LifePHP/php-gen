<?php

declare(strict_types=1);

namespace LifePhp\PhpGen\Element\Traits;

use LifePhp\PhpGen\Element\AbstractClassLikeDef;
use LifePhp\PhpGen\Element\TraitDef;
use LifePhp\PhpGen\Element\UseStatement;
use LogicException;

/**
 * @phpstan-require-extends AbstractClassLikeDef
 */
trait HasUsedTraits
{
    /**
     * @var array<string, TraitDef>
     */
    private array $usedTraits = [];

    public function addUseTrait(TraitDef $trait): self
    {
        $fqcn = $trait->getFullyQualifiedName();

        if (isset($this->usedTraits[$fqcn])) {
            throw new LogicException(sprintf('"%s" already uses trait "%s".', $this->name, $fqcn));
        }

        $this->usedTraits[$fqcn] = $trait;

        return $this;
    }

    /**
     * @return UseStatement[]
     */
    protected function collectUsedTraitUses(): array
    {
        $retVal = [];

        foreach ($this->usedTraits as $trait) {
            foreach ($trait->toClassType()->getUses() as $use) {
                $retVal[] = $use;
            }
        }

        return $retVal;
    }

    protected function renderUsedTraitsLine(): ?string
    {
        if (empty($this->usedTraits)) {
            return null;
        }

        $traitNames = array_map(
            fn (TraitDef $t) => $t->toClassType()->render(),
            array_values($this->usedTraits),
        );

        return '    use ' . implode(', ', $traitNames) . ';';
    }
}
