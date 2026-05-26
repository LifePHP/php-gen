<?php

declare(strict_types=1);

namespace LifePhp\PhpGen\Element\Traits;

use LifePhp\PhpGen\Element\Attribute;
use LifePhp\PhpGen\Element\UseStatement;

trait HasAttributes
{
    /** @var Attribute[] */
    private array $attributes = [];

    public function addAttribute(Attribute $attribute): static
    {
        $this->attributes[] = $attribute;

        return $this;
    }

    /** @return UseStatement[] */
    protected function collectAttributeUses(): array
    {
        $retVal = [];

        foreach ($this->attributes as $attr) {
            foreach ($attr->getUses() as $use) {
                $retVal[] = $use;
            }
        }

        return $retVal;
    }

    protected function renderAttributes(): string
    {
        if (empty($this->attributes)) {
            return '';
        }

        return implode("\n", array_map(
            fn (Attribute $a) => $a->render(),
            $this->attributes,
        ));
    }
}
