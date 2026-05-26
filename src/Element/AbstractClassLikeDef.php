<?php

declare(strict_types=1);

namespace LifePhp\PhpGen\Element;

use LifePhp\PhpGen\Element\Type\ClassType;
use Override;

abstract class AbstractClassLikeDef implements ElementInterface
{
    private ?string $docComment = null;

    public function __construct(
        protected readonly string $namespace,
        protected readonly string $name,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setDocComment(string $text): self
    {
        $this->docComment = $text;

        return $this;
    }

    /** @return class-string */
    public function getFullyQualifiedName(): string
    {
        /** @var class-string $fqcn */
        $fqcn = $this->namespace . '\\' . $this->name;

        return $fqcn;
    }

    public function toClassType(): ClassType
    {
        return new ClassType($this->getFullyQualifiedName());
    }

    #[Override]
    public function getDocCommentPart(): string
    {
        if ($this->docComment === null) {
            return '';
        }

        return $this->formatDocComment($this->docComment);
    }

    /**
     * @template T of ClassConstant|Property
     * @param array<string, T> $items
     * @return T[]
     */
    protected function sortedByVisibility(array $items): array
    {
        $order = [
            Visibility::Public->value    => 0,
            Visibility::Protected->value => 1,
            Visibility::Private->value   => 2,
        ];

        $sorted = array_values($items);

        usort(
            $sorted,
            fn (ClassConstant|Property $a, ClassConstant|Property $b)
                => $order[$a->getVisibility()->value] <=> $order[$b->getVisibility()->value],
        );

        return $sorted;
    }

    protected function indent(string $code): string
    {
        return implode("\n", array_map(
            fn (string $line) => '    ' . $line,
            explode("\n", $code),
        ));
    }

    private function formatDocComment(string $text): string
    {
        $wrapped = wordwrap($text, 117, "\n", false);
        $lines = explode("\n", $wrapped);
        $body = implode("\n", array_map(fn (string $line) => ' * ' . $line, $lines));

        return "/**\n" . $body . "\n */";
    }
}
