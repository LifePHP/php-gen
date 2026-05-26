<?php

declare(strict_types=1);

namespace LifePhp\PhpGen\Element;

use LifePhp\PhpGen\Element\Literal\IntLiteral;
use LifePhp\PhpGen\Element\Literal\StringLiteral;
use LifePhp\PhpGen\Element\Traits\HasConstants;
use LifePhp\PhpGen\Element\Traits\HasImplements;
use LifePhp\PhpGen\Element\Traits\HasMethods;
use LifePhp\PhpGen\Element\Traits\HasUsedTraits;
use LogicException;
use Override;

class EnumDef extends AbstractClassLikeDef
{
    use HasConstants;
    use HasMethods {
        addConstructor as private;
        addDestructor as private;
        getConstructor as private;
        getDestructor as private;
    }
    use HasUsedTraits;
    use HasImplements;

    /**
     * @var array<string, EnumCase>
     */
    private array $cases = [];

    private function __construct(
        string $namespace,
        string $name,
        private readonly ?EnumBackingType $backingType,
    ) {
        parent::__construct($namespace, $name);
    }

    public static function pure(string $namespace, string $name): self
    {
        return new self($namespace, $name, null);
    }

    public static function backed(string $namespace, string $name, EnumBackingType $backingType): self
    {
        return new self($namespace, $name, $backingType);
    }

    public function addCase(EnumCase $case): self
    {
        $name = $case->getName();

        if (isset($this->cases[$name])) {
            throw new LogicException(sprintf('Enum "%s" already has a case named "%s".', $this->name, $name));
        }

        $value = $case->getValue();

        if ($this->backingType === null && $value !== null) {
            throw new LogicException(sprintf(
                'Pure enum "%s" cannot have backed case "%s".',
                $this->name,
                $name,
            ));
        }

        if ($this->backingType !== null && $value === null) {
            throw new LogicException(sprintf(
                'Backed enum "%s" requires a value for case "%s".',
                $this->name,
                $name,
            ));
        }

        if ($this->backingType === EnumBackingType::String && !($value instanceof StringLiteral)) {
            throw new LogicException(sprintf(
                'String-backed enum "%s" requires a string value for case "%s".',
                $this->name,
                $name,
            ));
        }

        if ($this->backingType === EnumBackingType::Int && !($value instanceof IntLiteral)) {
            throw new LogicException(sprintf(
                'Int-backed enum "%s" requires an int value for case "%s".',
                $this->name,
                $name,
            ));
        }

        $this->cases[$name] = $case;

        return $this;
    }

    protected function validateAddMethod(Method $method): void
    {
        if ($method->isAbstract()) {
            throw new LogicException('Enums cannot have abstract methods.');
        }
    }

    #[Override]
    public function getUses(): array
    {
        return array_merge(
            $this->toClassType()->getUses(),
            $this->collectAttributeUses(),
            $this->collectImplementsUses(),
            $this->collectUsedTraitUses(),
            $this->collectMethodUses(),
        );
    }

    #[Override]
    public function render(): string
    {
        $parts = ['enum', $this->name];

        if ($this->backingType !== null) {
            $parts[] = ':';
            $parts[] = $this->backingType->value;
        }

        $implementsList = $this->renderImplementsList();

        if ($implementsList !== '') {
            $parts[] = 'implements';
            $parts[] = $implementsList;
        }

        $declaration = implode(' ', $parts);

        $caseMembers = array_map(
            fn (EnumCase $case) => '    ' . $case->render(),
            array_values($this->cases),
        );

        $members = array_values(array_filter([
            $this->renderUsedTraitsLine(),
            ...$this->renderConstantMembers(),
            ...$caseMembers,
            ...$this->renderMethodMembers(),
        ]));

        $body = empty($members)
            ? $declaration . "\n{\n}"
            : $declaration . "\n{\n" . implode("\n\n", $members) . "\n}";

        return $this->wrapWithDocAndAttributes($body);
    }
}
