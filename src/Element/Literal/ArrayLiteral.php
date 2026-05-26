<?php

declare(strict_types=1);

namespace LifePhp\PhpGen\Element\Literal;

use LifePhp\PhpGen\Element\Type\ArrayKeyType;
use LifePhp\PhpGen\Element\Type\ArrayType;
use LifePhp\PhpGen\Element\Type\ScalarType;
use LifePhp\PhpGen\Element\Type\TypeInterface;
use LifePhp\PhpGen\Element\Type\UnionType;
use Override;

class ArrayLiteral extends Literal
{
    /**
     * @var array<array{key: ArrayKeyLiteral, value: LiteralInterface}>
     */
    private array $items = [];

    private ?ArrayType $cachedType = null;

    public function add(ArrayKeyLiteral $key, LiteralInterface $value): self
    {
        $this->items[] = ['key' => $key, 'value' => $value];
        $this->cachedType = null;

        return $this;
    }

    #[Override]
    public function getType(): ArrayType
    {
        if ($this->cachedType === null) {
            $this->cachedType = new ArrayType(
                valueType: $this->resolveValueType(),
                keyType: $this->resolveKeyType(),
            );
        }

        return $this->cachedType;
    }

    #[Override]
    public function getUses(): array
    {
        $retVal = [];

        foreach ($this->items as $item) {
            foreach ($item['value']->getUses() as $use) {
                $retVal[] = $use;
            }
        }

        return $retVal;
    }

    #[Override]
    public function render(): string
    {
        if (empty($this->items)) {
            return '[]';
        }

        $parts = [];

        foreach ($this->items as $item) {
            $parts[] = sprintf('%s => %s', $item['key']->render(), $item['value']->render());
        }

        return sprintf(
            "[\n    %s\n]",
            implode(",\n    ", $parts),
        );
    }

    private function resolveKeyType(): ArrayKeyType
    {
        $keyTypes = [];

        foreach ($this->items as $item) {
            $keyTypes[$item['key']->getType()->render()] = true;
        }

        if (count($keyTypes) > 1) {
            return ArrayKeyType::Both;
        }

        return array_key_first($keyTypes) === ScalarType::String->value
            ? ArrayKeyType::String
            : ArrayKeyType::Int;
    }

    private function resolveValueType(): TypeInterface
    {
        $types = [];

        foreach ($this->items as $item) {
            $types[$item['value']->getDocCommentPart()] = $item['value']->getType();
        }

        if (count($types) === 0) {
            return ScalarType::Never;
        }

        if (count($types) === 1) {
            return array_first($types);
        }

        $union = new UnionType();

        foreach ($types as $type) {
            $union->add($type);
        }

        return $union;
    }
}
