<?php

declare(strict_types=1);

namespace LifePhp\PhpGen\Element\Traits;

use LifePhp\PhpGen\Element\AbstractClassLikeDef;
use LifePhp\PhpGen\Element\Method;
use LifePhp\PhpGen\Element\UseStatement;
use LifePhp\PhpGen\Element\Visibility;
use LogicException;

/**
 * @phpstan-require-extends AbstractClassLikeDef
 */
trait HasMethods
{
    /**
     * @var array<string, Method>
     */
    private array $methods = [];

    public function addMethod(Method $method): self
    {
        if ($method->isConstructor()) {
            throw new LogicException('Use addConstructor() to add a constructor.');
        }

        if ($method->isDestructor()) {
            throw new LogicException('Use addDestructor() to add a destructor.');
        }

        $this->validateAddMethod($method);

        $name = $method->getName();

        if (isset($this->methods[$name])) {
            throw new LogicException(sprintf('"%s" already has a method named "%s".', $this->name, $name));
        }

        $this->methods[$name] = $method;

        return $this;
    }

    protected function validateAddMethod(Method $method): void
    {
    }

    public function addConstructor(Method $constructor): self
    {
        if (!$constructor->isConstructor()) {
            throw new LogicException('Method passed to addConstructor() must be named "__construct".');
        }

        if (isset($this->methods['__construct'])) {
            throw new LogicException(sprintf('"%s" already has a constructor.', $this->name));
        }

        $this->validateAddConstructor($constructor);

        $this->methods['__construct'] = $constructor;

        return $this;
    }

    protected function validateAddConstructor(Method $constructor): void
    {
    }

    public function addDestructor(Method $destructor): self
    {
        if (!$destructor->isDestructor()) {
            throw new LogicException('Method passed to addDestructor() must be named "__destruct".');
        }

        if (isset($this->methods['__destruct'])) {
            throw new LogicException(sprintf('"%s" already has a destructor.', $this->name));
        }

        $this->methods['__destruct'] = $destructor;

        return $this;
    }

    public function getConstructor(): ?Method
    {
        return $this->methods['__construct'] ?? null;
    }

    public function getDestructor(): ?Method
    {
        return $this->methods['__destruct'] ?? null;
    }

    /**
     * @return UseStatement[]
     */
    protected function collectMethodUses(): array
    {
        $retVal = [];

        foreach ($this->methods as $method) {
            foreach ($method->getUses() as $use) {
                $retVal[] = $use;
            }
        }

        return $retVal;
    }

    /**
     * @return string[]
     */
    protected function orderedMethodKeys(): array
    {
        $public = array_keys(array_filter(
            $this->methods,
            fn (Method $m) => !$m->isConstructor() && !$m->isDestructor()
                && $m->getVisibility() === Visibility::Public,
        ));
        $protected = array_keys(array_filter(
            $this->methods,
            fn (Method $m) => !$m->isConstructor() && !$m->isDestructor()
                && $m->getVisibility() === Visibility::Protected,
        ));
        $private = array_keys(array_filter(
            $this->methods,
            fn (Method $m) => !$m->isConstructor() && !$m->isDestructor()
                && $m->getVisibility() === Visibility::Private,
        ));

        return array_merge(
            isset($this->methods['__construct']) ? ['__construct'] : [],
            $public,
            $protected,
            $private,
            isset($this->methods['__destruct']) ? ['__destruct'] : [],
        );
    }

    /**
     * @return string[]
     */
    protected function renderMethodMembers(): array
    {
        $members = [];

        foreach ($this->orderedMethodKeys() as $key) {
            $method = $this->methods[$key];
            $docComment = $method->getDocCommentPart();
            $indented = $this->indent($method->render());
            $members[] = $docComment !== ''
                ? $this->indent($docComment) . "\n" . $indented
                : $indented;
        }

        return $members;
    }
}
