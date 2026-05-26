<?php

declare(strict_types=1);

namespace LifePhp\PhpGen\Element;

use LifePhp\PhpGen\Element\Traits\HasConstants;
use LifePhp\PhpGen\Element\Traits\HasMethods;
use LifePhp\PhpGen\Element\Traits\HasProperties;
use LifePhp\PhpGen\Element\Traits\HasUsedTraits;
use Override;

class TraitDef extends AbstractClassLikeDef
{
    use HasConstants;
    use HasProperties;
    use HasMethods;
    use HasUsedTraits;

    #[Override]
    public function getUses(): array
    {
        return array_merge(
            $this->toClassType()->getUses(),
            $this->collectAttributeUses(),
            $this->collectUsedTraitUses(),
            $this->collectPropertyUses(),
            $this->collectMethodUses(),
        );
    }

    #[Override]
    public function render(): string
    {
        $declaration = 'trait ' . $this->name;

        $members = array_values(array_filter([
            $this->renderUsedTraitsLine(),
            ...$this->renderConstantMembers(),
            ...$this->renderPropertyMembers(),
            ...$this->renderMethodMembers(),
        ]));

        $body = empty($members)
            ? $declaration . "\n{\n}"
            : $declaration . "\n{\n" . implode("\n\n", $members) . "\n}";

        return $this->wrapWithDocAndAttributes($body);
    }
}
