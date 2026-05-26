<?php

declare(strict_types=1);

namespace LifePhp\PhpGen\Element\Type;

use LifePhp\PhpGen\Element\UseStatementClass;
use Override;

class ClassType implements TypeInterface
{
    private readonly UseStatementClass $type;

    /**
     * @var TypeInterface[]
     */
    private array $generics = [];

    /**
     * @param class-string|UseStatementClass $className
     */
    public function __construct(
        string|UseStatementClass $className,
    ) {
        if (is_string($className)) {
            $className = new UseStatementClass($className);
        }

        $this->type = $className;
    }

    public function addGeneric(TypeInterface $generic): static
    {
        $this->generics[] = $generic;

        return $this;
    }

    #[Override]
    public function getUses(): array
    {
        $retVal = [$this->type];

        foreach ($this->generics as $generic) {
            foreach ($generic->getUses() as $use) {
                $retVal[] = $use;
            }
        }

        return $retVal;
    }

    #[Override]
    public function getDocCommentPart(): string
    {
        $generics = '';

        if (!empty($this->generics)) {
            $docCommentGenerics = array_map(
                fn (TypeInterface $generic) => $generic->getDocCommentPart(),
                $this->generics,
            );

            $docCommentGeneric = implode(",\n    ", $docCommentGenerics);

            $generics = sprintf(
                "<\n    %s\n>",
                $docCommentGeneric,
            );
        }

        return sprintf(
            '%s%s',
            $this->type->getDocCommentPart(),
            $generics,
        );
    }

    #[Override]
    public function render(): string
    {
        return $this->type->getName();
    }
}
