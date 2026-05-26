<?php

declare(strict_types=1);

namespace LifePhp\PhpGen;

use LifePhp\PhpGen\Element\AbstractClassLikeDef;
use LifePhp\PhpGen\Element\UseStatement;
use LifePhp\PhpGen\Element\UseStatementConstant;
use LifePhp\PhpGen\Element\UseStatementFunction;

class PhpFile
{
    /** @var AbstractClassLikeDef[] */
    private array $elements = [];

    public function __construct(private readonly string $namespace)
    {
    }

    public function addClassLikeElement(AbstractClassLikeDef $element): self
    {
        $this->elements[] = $element;

        return $this;
    }

    public function render(): string
    {
        [$classUses, $functionUses, $constUses] = $this->collectAndResolveUses();

        $parts = [
            '<?php',
            '',
            'declare(strict_types=1);',
            '',
            'namespace ' . $this->namespace . ';',
        ];

        $useGroups = array_filter([
            $this->renderUseGroup($classUses),
            $this->renderUseGroup($functionUses),
            $this->renderUseGroup($constUses),
        ]);

        if (!empty($useGroups)) {
            $parts[] = '';
            $parts[] = implode("\n\n", $useGroups);
        }

        if (!empty($this->elements)) {
            $parts[] = '';
            $parts[] = implode("\n\n", array_map(
                fn (AbstractClassLikeDef $e) => $e->render(),
                $this->elements,
            ));
        }

        return implode("\n", $parts) . "\n";
    }

    /**
     * @return array{
     *     array<string, UseStatement>,
     *     array<string, UseStatement>,
     *     array<string, UseStatement>
     * }
     */
    private function collectAndResolveUses(): array
    {
        /** @var array<string, UseStatement> $classReps */
        $classReps = [];
        /** @var array<string, list<UseStatement>> $classAll */
        $classAll = [];
        /** @var array<string, UseStatement> $functionReps */
        $functionReps = [];
        /** @var array<string, list<UseStatement>> $functionAll */
        $functionAll = [];
        /** @var array<string, UseStatement> $constReps */
        $constReps = [];
        /** @var array<string, list<UseStatement>> $constAll */
        $constAll = [];

        foreach ($this->elements as $element) {
            foreach ($element->getUses() as $use) {
                $path = $use->getPath();

                if ($this->isSameNamespace($path)) {
                    continue;
                }

                if ($use instanceof UseStatementFunction) {
                    if (!isset($functionReps[$path])) {
                        $functionReps[$path] = $use;
                        $functionAll[$path]  = [$use];
                    } else {
                        $functionAll[$path][] = $use;
                    }
                } elseif ($use instanceof UseStatementConstant) {
                    if (!isset($constReps[$path])) {
                        $constReps[$path] = $use;
                        $constAll[$path]  = [$use];
                    } else {
                        $constAll[$path][] = $use;
                    }
                } elseif (!isset($classReps[$path])) {
                    $classReps[$path] = $use;
                    $classAll[$path]  = [$use];
                } else {
                    $classAll[$path][] = $use;
                }
            }
        }

        $definedNames = array_map(
            fn (AbstractClassLikeDef $e) => $e->getName(),
            $this->elements,
        );

        $this->resolveUseConflicts($classReps, $classAll, $definedNames);
        $this->resolveUseConflicts($functionReps, $functionAll);
        $this->resolveUseConflicts($constReps, $constAll);

        ksort($classReps);
        ksort($functionReps);
        ksort($constReps);

        return [$classReps, $functionReps, $constReps];
    }

    /**
     * Detects short-name conflicts within a group of use statements and between use statements
     * and the defined class-like elements ($reservedNames). Resolves conflicts by numbering
     * (Database1, Database2, …). The alias is written to every object instance sharing the same
     * path so it propagates automatically via PHP's object reference semantics.
     *
     * @param array<string, UseStatement>       $reps          one representative per path
     * @param array<string, list<UseStatement>> $all           all instances per path
     * @param string[]                          $reservedNames short names that must not be used
     */
    private function resolveUseConflicts(array $reps, array $all, array $reservedNames = []): void
    {
        /** @var array<string, list<string>> $pathsByShortName */
        $pathsByShortName = [];

        foreach ($reps as $path => $use) {
            $pathsByShortName[$use->getName()][] = $path;
        }

        foreach ($pathsByShortName as $shortName => $paths) {
            if (count($paths) <= 1 && !in_array($shortName, $reservedNames, true)) {
                continue;
            }

            $counter = 1;

            foreach ($paths as $path) {
                $alias = $shortName . $counter;

                foreach ($all[$path] ?? [] as $use) {
                    $use->alias = $alias;
                }

                $counter++;
            }
        }
    }

    /**
     * @param array<string, UseStatement> $uses
     */
    private function renderUseGroup(array $uses): string
    {
        if (empty($uses)) {
            return '';
        }

        return implode("\n", array_map(
            fn (UseStatement $u) => $u->render(),
            array_values($uses),
        ));
    }

    private function isSameNamespace(string $path): bool
    {
        $lastBackslash = strrpos($path, '\\');

        if ($lastBackslash === false) {
            return false;
        }

        return substr($path, 0, $lastBackslash) === $this->namespace;
    }
}
