<?php

declare(strict_types=1);

namespace LifePhp\PhpGen\Element;

use LifePhp\PhpGen\Element\Traits\HasAttributes;
use LifePhp\PhpGen\Element\Type\TypeInterface;
use LogicException;
use Override;

class PhpFunction implements ElementInterface
{
    use HasAttributes;

    /**
     * @var Parameter[]
     */
    private array $parameters = [];

    public function __construct(
        private readonly string $name,
        private readonly TypeInterface $returnType,
    ) {
    }

    public function addParameter(Parameter $parameter): self
    {
        if ($parameter->isPromoted()) {
            throw new LogicException('Standalone functions cannot have promoted parameters.');
        }

        $this->parameters[] = $parameter;

        return $this;
    }

    #[Override]
    public function getUses(): array
    {
        $retVal = $this->collectAttributeUses();

        foreach ($this->returnType->getUses() as $use) {
            $retVal[] = $use;
        }

        foreach ($this->parameters as $parameter) {
            foreach ($parameter->getUses() as $use) {
                $retVal[] = $use;
            }
        }

        return $retVal;
    }

    #[Override]
    public function getDocCommentPart(): string
    {
        $lines = [];

        foreach ($this->parameters as $parameter) {
            $lines[] = sprintf(' * @param %s', $parameter->getDocCommentPart());
        }

        $lines[] = sprintf(' * @return %s', $this->returnType->getDocCommentPart());

        return sprintf("/**\n%s\n */", implode("\n", $lines));
    }

    #[Override]
    public function render(): string
    {
        $seenDefault = false;
        $seenVariadic = false;

        $renderedParams = array_map(
            function (Parameter $p) use (&$seenDefault, &$seenVariadic): string {
                if ($p->isPromoted()) {
                    throw new LogicException('Standalone functions cannot have promoted parameters.');
                }

                if ($seenVariadic) {
                    throw new LogicException('Variadic parameter must be the last parameter.');
                }

                if ($p->isVariadic()) {
                    $seenVariadic = true;
                } elseif ($p->hasDefaultValue()) {
                    $seenDefault = true;
                } elseif ($seenDefault) {
                    throw new LogicException('Required parameters cannot follow parameters with default values.');
                }

                return $p->render();
            },
            $this->parameters,
        );

        $parameters = count($renderedParams) > 1
            ? "\n    " . implode(",\n    ", $renderedParams) . ",\n"
            : implode('', $renderedParams);

        $result = sprintf(
            "function %s(%s): %s\n{\n}",
            $this->name,
            $parameters,
            $this->returnType->render(),
        );

        $attrBlock = $this->renderAttributes();

        return $attrBlock !== '' ? $attrBlock . "\n" . $result : $result;
    }
}
