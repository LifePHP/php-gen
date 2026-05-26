# Architecture

## ElementInterface — The Core Contract

Everything in this library implements `ElementInterface`:

```php
interface ElementInterface
{
    /** @return UseStatement[] */
    public function getUses(): array;

    public function getDocCommentPart(): string;
    public function render(): string;
}
```

These three methods are the entire public contract between elements. Every class, type, variable, and statement is 
just an object that satisfies this interface.

## Composite Pattern — getUses() Propagation

Elements are composable. A complex type like `ArrayType` contains two child `TypeInterface` instances. When `getUses()`
is called on `ArrayType`, it delegates to both children and merges the results:

```php
public function getUses(): array
{
    return [
        ...$this->valueType->getUses(),
        ...$this->keyType->getUses(),
    ];
}
```

This means a consumer never needs to walk the element tree manually. Calling `getUses()` on any element returns the 
full flat list of imports required by that element and all its descendants.

```
ArrayType
├── keyType: ScalarType::String    → getUses() = []
└── valueType: ClassType(Uuid)     → getUses() = [UseStatementClass('Ramsey\Uuid\Uuid')]

ArrayType::getUses() = [UseStatementClass('Ramsey\Uuid\Uuid')]
```

## Dual Rendering

Each element has two rendering paths:

| Method                | Purpose               | Example               |
|-----------------------|-----------------------|-----------------------|
| `render()`            | Valid PHP syntax      | `array`               |
| `getDocCommentPart()` | PHPDoc representation | `array<string, Uuid>` |

These differ when PHP syntax cannot express what PHPDoc can. Generic types are the primary example — `array<int, Uuid>`
is valid PHPDoc but invalid PHP. `ClassType` with generics renders as just the class name in `render()`, but includes
the full generic signature in `getDocCommentPart()`.

`UseStatement` elements only implement `getDocCommentPart()` trivially (they render themselves the same way in both
contexts).

## Flat Namespace

All classes use the namespace `LifePhp\PhpGen` regardless of their location under `src/`. The subdirectory structure 
(`Element/`, `Element/Type/`) is organizational only — it does not create sub-namespaces.

## Key Design Decisions

**Enums for scalar types** — `ScalarType` is a backed string enum. This gives type safety at the call site
(`ScalarType::String` instead of the string `'string'`) while the value itself remains a plain string for rendering.

**`readonly` classes for immutable types** — `ArrayType` is declared `readonly`. Types that carry no mutable state
should be readonly. This is the direction the library is heading.

**`#[Override]` attribute** — All interface method implementations are marked with `#[Override]`. PHP will throw a 
compile error if the method does not actually override anything, preventing silent mistakes during refactoring.

**`UseStatement` knows its own name** — When a `ClassType` is constructed from an FQCN string, it internally creates 
a `UseStatementClass`. The `UseStatementClass` parses the short name from the FQCN. This means `ClassType::render()`
can return just the short name (`Uuid`) rather than the full path, because the import is tracked separately 
via `getUses()`.