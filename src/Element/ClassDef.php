<?php

declare(strict_types=1);

namespace LifePhp\PhpGen\Element;

use LifePhp\PhpGen\Element\Traits\HasConstants;
use LifePhp\PhpGen\Element\Traits\HasImplements;
use LifePhp\PhpGen\Element\Traits\HasMethods;
use LifePhp\PhpGen\Element\Traits\HasProperties;
use LifePhp\PhpGen\Element\Traits\HasUsedTraits;
use LifePhp\PhpGen\Element\Type\ClassType;
use LogicException;
use Override;

class ClassDef extends AbstractClassLikeDef
{
    use HasConstants;
    use HasProperties;
    use HasMethods;
    use HasUsedTraits;
    use HasImplements;

    private bool $abstract = false;

    private bool $final = false;

    private bool $readonly = false;

    private ?ClassType $extends = null;

    private ?ClassDef $extendsDef = null;

    public function setAbstract(): self
    {
        if ($this->final) {
            throw new LogicException('Class cannot be both abstract and final.');
        }

        $this->abstract = true;

        return $this;
    }

    public function setFinal(): self
    {
        if ($this->abstract) {
            throw new LogicException('Class cannot be both abstract and final.');
        }

        $this->final = true;

        return $this;
    }

    public function setReadonly(): self
    {
        if ($this->extendsDef !== null && !$this->extendsDef->isReadonly()) {
            throw new LogicException('Readonly class can only extend another readonly class.');
        }

        $this->readonly = true;

        return $this;
    }

    public function setExtends(ClassDef|ClassType $extends): self
    {
        if ($extends instanceof ClassDef) {
            if ($extends->isFinal()) {
                throw new LogicException(sprintf(
                    'Cannot extend final class "%s".',
                    $extends->getFullyQualifiedName(),
                ));
            }

            if ($extends->isReadonly() && !$this->readonly) {
                throw new LogicException(sprintf(
                    'Non-readonly class cannot extend readonly class "%s".',
                    $extends->getFullyQualifiedName(),
                ));
            }

            if (!$extends->isReadonly() && $this->readonly) {
                throw new LogicException(sprintf(
                    'Readonly class cannot extend non-readonly class "%s".',
                    $extends->getFullyQualifiedName(),
                ));
            }

            if ($this->isReachableFrom($extends)) {
                throw new LogicException(sprintf(
                    'Circular extends detected: "%s" already extends "%s".',
                    $extends->getFullyQualifiedName(),
                    $this->getFullyQualifiedName(),
                ));
            }

            $this->extendsDef = $extends;
            $this->extends = $extends->toClassType();
        } else {
            $this->extends = $extends;
        }

        return $this;
    }

    public function isFinal(): bool
    {
        return $this->final;
    }

    public function isReadonly(): bool
    {
        return $this->readonly;
    }

    protected function validateAddConstructor(Method $constructor): void
    {
        if ($this->extendsDef === null) {
            return;
        }

        $parentConstructor = $this->extendsDef->getConstructor();

        if ($parentConstructor === null) {
            return;
        }

        if ($parentConstructor->isFinal()) {
            throw new LogicException(sprintf(
                'Cannot override final constructor of "%s".',
                $this->extendsDef->getFullyQualifiedName(),
            ));
        }

        if ($parentConstructor->getVisibility() === Visibility::Private) {
            throw new LogicException(sprintf(
                'Cannot override private constructor of "%s".',
                $this->extendsDef->getFullyQualifiedName(),
            ));
        }
    }

    #[Override]
    public function getUses(): array
    {
        return array_merge(
            $this->toClassType()->getUses(),
            $this->extends !== null ? $this->extends->getUses() : [],
            $this->collectImplementsUses(),
            $this->collectUsedTraitUses(),
            $this->collectPropertyUses(),
            $this->collectMethodUses(),
        );
    }

    #[Override]
    public function render(): string
    {
        foreach ($this->methods as $method) {
            if ($method->isAbstract() && !$this->abstract) {
                throw new LogicException(sprintf(
                    'Class "%s" must be abstract to contain abstract methods.',
                    $this->name,
                ));
            }
        }

        $parts = [];

        if ($this->abstract) {
            $parts[] = 'abstract';
        } elseif ($this->final) {
            $parts[] = 'final';
        }

        if ($this->readonly) {
            $parts[] = 'readonly';
        }

        $parts[] = 'class';
        $parts[] = $this->name;

        if ($this->extends !== null) {
            $parts[] = 'extends';
            $parts[] = $this->extends->render();
        }

        $implementsList = $this->renderImplementsList();

        if ($implementsList !== '') {
            $parts[] = 'implements';
            $parts[] = $implementsList;
        }

        $declaration = implode(' ', $parts);

        $members = array_values(array_filter([
            $this->renderUsedTraitsLine(),
            ...$this->renderConstantMembers(),
            ...$this->renderPropertyMembers(),
            ...$this->renderMethodMembers(),
        ]));

        $body = empty($members)
            ? $declaration . "\n{\n}"
            : $declaration . "\n{\n" . implode("\n\n", $members) . "\n}";

        $docComment = $this->getDocCommentPart();

        return $docComment !== '' ? $docComment . "\n" . $body : $body;
    }

    private function isReachableFrom(ClassDef $start): bool
    {
        $parent = $start->extendsDef;

        while ($parent !== null) {
            if ($parent->getFullyQualifiedName() === $this->getFullyQualifiedName()) {
                return true;
            }

            $parent = $parent->extendsDef;
        }

        return false;
    }
}
