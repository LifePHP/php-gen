<?php

declare(strict_types=1);

namespace LifePhp\PhpGen\Element\Traits;

use LifePhp\PhpGen\Element\AbstractClassLikeDef;
use LifePhp\PhpGen\Element\Property;
use LifePhp\PhpGen\Element\UseStatement;
use LogicException;

/**
 * @phpstan-require-extends AbstractClassLikeDef
 */
trait HasProperties
{
    /**
     * @var array<string, Property>
     */
    private array $properties = [];

    public function addProperty(Property $property): self
    {
        $name = $property->getName();

        if (isset($this->properties[$name])) {
            throw new LogicException(sprintf('"%s" already has a property named "%s".', $this->name, $name));
        }

        $this->properties[$name] = $property;

        return $this;
    }

    /**
     * @return UseStatement[]
     */
    protected function collectPropertyUses(): array
    {
        $retVal = [];

        foreach ($this->properties as $property) {
            foreach ($property->getUses() as $use) {
                $retVal[] = $use;
            }
        }

        return $retVal;
    }

    /**
     * @return string[]
     */
    protected function renderPropertyMembers(): array
    {
        return array_map(
            function (Property $property): string {
                $docComment = $property->getDocCommentPart();

                return $docComment !== ''
                    ? '    /** @var ' . $docComment . " */\n    " . $property->render() . ';'
                    : '    ' . $property->render() . ';';
            },
            $this->sortedByVisibility($this->properties),
        );
    }
}
