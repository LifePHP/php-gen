<?php

declare(strict_types=1);

namespace LifePhp\PhpGen\Element;

use LifePhp\PhpGen\Element\Traits\HasAttributes;
use LifePhp\PhpGen\Element\Type\TypeInterface;
use LogicException;
use Override;

class Method implements ElementInterface
{
    use HasAttributes;

    /**
     * @var Parameter[]
     */
    private array $parameters = [];

    private bool $abstract = false;

    private bool $static = false;

    private bool $final = false;

    private ?string $deprecated = null;

    public function __construct(
        private readonly Visibility $visibility,
        private readonly string $name,
        private readonly TypeInterface $returnType,
    ) {
    }

    public function setAbstract(): self
    {
        if ($this->final) {
            throw new LogicException('Method cannot be both abstract and final.');
        }

        $this->abstract = true;

        return $this;
    }

    public function setStatic(): self
    {
        $this->static = true;

        return $this;
    }

    public function setFinal(): self
    {
        if ($this->abstract) {
            throw new LogicException('Method cannot be both abstract and final.');
        }

        $this->final = true;

        return $this;
    }

    public function isAbstract(): bool
    {
        return $this->abstract;
    }

    public function isFinal(): bool
    {
        return $this->final;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isConstructor(): bool
    {
        return $this->name === '__construct';
    }

    public function isDestructor(): bool
    {
        return $this->name === '__destruct';
    }

    public function getVisibility(): Visibility
    {
        return $this->visibility;
    }

    public function setDeprecated(string $message = ''): self
    {
        $this->deprecated = $message;

        return $this;
    }

    public function addParameter(Parameter $parameter): self
    {
        if ($parameter->isPromoted() && $this->name !== '__construct') {
            throw new LogicException('Promoted parameters are only allowed in constructors.');
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

        if ($this->deprecated !== null) {
            $lines[] = $this->deprecated !== ''
                ? ' * @deprecated ' . $this->deprecated
                : ' * @deprecated';
            $lines[] = ' *';
        }

        foreach ($this->parameters as $parameter) {
            $lines[] = sprintf(' * @param %s', $parameter->getDocCommentPart());
        }

        $lines[] = sprintf(' * @return %s', $this->returnType->getDocCommentPart());

        return sprintf("/**\n%s\n */", implode("\n", $lines));
    }

    public function renderAsInterfaceMethod(): string
    {
        $parameters = $this->buildParameters();

        $parts = [$this->visibility->value];

        if ($this->static) {
            $parts[] = 'static';
        }

        $parts[] = 'function';
        $parts[] = $this->name;

        $result = sprintf('%s(%s): %s;', implode(' ', $parts), $parameters, $this->returnType->render());

        $attrBlock = $this->renderAttributes();

        return $attrBlock !== '' ? $attrBlock . "\n" . $result : $result;
    }

    #[Override]
    public function render(): string
    {
        $renderedParams = $this->buildParameters();

        $parts = [];

        if ($this->abstract) {
            $parts[] = 'abstract';
        } elseif ($this->final) {
            $parts[] = 'final';
        }

        $parts[] = $this->visibility->value;

        if ($this->static) {
            $parts[] = 'static';
        }

        $parts[] = 'function';
        $parts[] = $this->name;

        $signature = implode(' ', $parts);

        $isMultiline = count($this->parameters) > 1;

        if ($this->abstract) {
            $result = sprintf('%s(%s): %s;', $signature, $renderedParams, $this->returnType->render());
        } elseif ($this->name === '__construct' && $isMultiline) {
            $result = sprintf("%s(%s): %s {}", $signature, $renderedParams, $this->returnType->render());
        } else {
            $result = sprintf("%s(%s): %s\n{\n}", $signature, $renderedParams, $this->returnType->render());
        }

        $attrBlock = $this->renderAttributes();

        return $attrBlock !== '' ? $attrBlock . "\n" . $result : $result;
    }

    private function buildParameters(): string
    {
        $seenDefault = false;
        $seenVariadic = false;

        $rendered = array_map(
            function (Parameter $p) use (&$seenDefault, &$seenVariadic): string {
                if ($p->isPromoted() && $this->name !== '__construct') {
                    throw new LogicException('Promoted parameters are only allowed in constructors.');
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

        return count($rendered) > 1
            ? "\n    " . implode(",\n    ", $rendered) . ",\n"
            : implode('', $rendered);
    }
}
