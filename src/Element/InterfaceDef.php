<?php

declare(strict_types=1);

namespace LifePhp\PhpGen\Element;

use LifePhp\PhpGen\Element\Traits\HasConstants;
use LogicException;
use Override;

class InterfaceDef extends AbstractClassLikeDef
{
    use HasConstants;

    /**
     * @var array<string, InterfaceDef>
     */
    private array $extends = [];

    /**
     * @var array<string, Method>
     */
    private array $methods = [];

    public function addExtends(InterfaceDef $interface): self
    {
        $fqcn = $interface->getFullyQualifiedName();

        if (isset($this->extends[$fqcn])) {
            throw new LogicException(sprintf(
                'Interface "%s" already extends "%s".',
                $this->name,
                $fqcn,
            ));
        }

        if ($this->isReachableFrom($interface)) {
            throw new LogicException(sprintf(
                'Circular extends detected: "%s" already extends "%s".',
                $fqcn,
                $this->getFullyQualifiedName(),
            ));
        }

        $this->extends[$fqcn] = $interface;

        return $this;
    }

    public function addMethod(Method $method): self
    {
        if ($method->getVisibility() !== Visibility::Public) {
            throw new LogicException('Interface methods must be public.');
        }

        if ($method->isFinal()) {
            throw new LogicException('Interface methods cannot be final.');
        }

        $name = $method->getName();

        if (isset($this->methods[$name])) {
            throw new LogicException(sprintf(
                'Interface "%s" already has a method named "%s".',
                $this->name,
                $name,
            ));
        }

        $this->methods[$name] = $method;

        return $this;
    }

    #[Override]
    public function getUses(): array
    {
        $retVal = array_merge(
            $this->toClassType()->getUses(),
            $this->collectAttributeUses(),
        );

        foreach ($this->extends as $interface) {
            foreach ($interface->toClassType()->getUses() as $use) {
                $retVal[] = $use;
            }
        }

        foreach ($this->methods as $method) {
            foreach ($method->getUses() as $use) {
                $retVal[] = $use;
            }
        }

        return $retVal;
    }

    #[Override]
    public function render(): string
    {
        $parts = ['interface', $this->name];

        if (!empty($this->extends)) {
            $parts[] = 'extends';
            $parts[] = implode(', ', array_map(
                fn (InterfaceDef $i) => $i->toClassType()->render(),
                array_values($this->extends),
            ));
        }

        $declaration = implode(' ', $parts);

        $members = $this->renderConstantMembers();

        foreach ($this->methods as $method) {
            $docComment = $method->getDocCommentPart();
            $rendered = '    ' . $method->renderAsInterfaceMethod();
            $member = $docComment !== ''
                ? $this->indent($docComment) . "\n" . $rendered
                : $rendered;
            $members[] = $member;
        }

        $body = empty($members)
            ? $declaration . "\n{\n}"
            : $declaration . "\n{\n" . implode("\n\n", $members) . "\n}";

        return $this->wrapWithDocAndAttributes($body);
    }

    private function isReachableFrom(InterfaceDef $start): bool
    {
        foreach ($start->extends as $parent) {
            if ($parent->getFullyQualifiedName() === $this->getFullyQualifiedName()) {
                return true;
            }

            if ($this->isReachableFrom($parent)) {
                return true;
            }
        }

        return false;
    }
}
