# Type System

All types implement `TypeInterface`, which extends `ElementInterface`. Types represent PHP type annotations — they
appear in property declarations, function parameters, return types, and PHPDoc comments.

```php
interface TypeInterface extends ElementInterface {}
```

`TypeInterface` adds no methods of its own. It is a semantic marker that distinguishes type objects from other elements.

## ScalarType

A backed string enum covering all PHP built-in scalar and special types:

| Case                 | Renders as |
|----------------------|------------|
| `ScalarType::String` | `string`   |
| `ScalarType::Int`    | `int`      |
| `ScalarType::Float`  | `float`    |
| `ScalarType::Bool`   | `bool`     |
| `ScalarType::Null`   | `null`     |
| `ScalarType::True`   | `true`     |
| `ScalarType::False`  | `false`    |
| `ScalarType::Void`   | `void`     |
| `ScalarType::Mixed`  | `mixed`    |

`getUses()` returns `[]` — scalars require no imports. `getDocCommentPart()` delegates to `render()`.

## ClassType

Represents a class or interface as a type. Accepts either an FQCN string or a `UseStatementClass` instance.

```php
$type = new ClassType(Uuid::class);
$type->render();            // "Uuid"
$type->getUses();           // [UseStatementClass('Ramsey\Uuid\Uuid')]
```

**Generics** — PHPDoc generics are supported via `addGeneric()`. Generics appear only in `getDocCommentPart()`, 
not in `render()` (PHP syntax does not support them at runtime).

```php
$type = new ClassType(Collection::class);
$type->addGeneric(ScalarType::String);
$type->addGeneric(new ClassType(User::class));

$type->render();            // "Collection"
$type->getDocCommentPart(); // "Collection<\n    string,\n    User\n>"
$type->getUses();           // [UseStatementClass('Collection'), UseStatementClass('User')]
```

`addGeneric()` returns `static` — calls are chainable.

## ListType

Represents a PHP list — an array with sequential integer keys starting from 0. Immutable (`readonly` class).

```php
$type = new ListType(new ClassType(User::class));

$type->render();            // "array"
$type->getDocCommentPart(); // "list<User>"
$type->getUses();           // [UseStatementClass('User')]
```

Use `ListType` over `ArrayType` when the keys are always `0, 1, 2, ...` and order matters. PHPStan and Psalm both 
understand `list<T>` and apply stricter checks than `array<int, T>`.

## ArrayKeyType

A backed string enum that constrains valid PHP array key types. Only `int` and `string` are valid array keys in PHP.

| Case                   | Renders as |
|------------------------|------------|
| `ArrayKeyType::Int`    | `int`      |
| `ArrayKeyType::String` | `string`   |

Used exclusively as the `$keyType` parameter of `ArrayType`. Implements `TypeInterface`.

## ArrayType

Represents a typed array with an explicit key type and value type. Immutable (`readonly` class). Key type is 
constrained to `ArrayKeyType` — invalid key types are caught by static analysis.

```php
$type = new ArrayType(
    valueType: new ClassType(User::class),
    keyType: ArrayKeyType::Int,
);

$type->render();            // "array"
$type->getDocCommentPart(); // "array<\n    int,\n    User\n>"
$type->getUses();           // [UseStatementClass('User')]
```

`getUses()` aggregates uses from both `$keyType` and `$valueType` using the spread operator.

## ShapeType

Represents a structured array shape — specific named or positional keys with specific types. Equivalent to
PHPStan/Psalm `array{key: Type}` syntax.

```php
$type = new ShapeType();
$type->add('age', ScalarType::Int);
$type->add('name', ScalarType::String);
$type->add('address', new ClassType(Address::class));

$type->render();            // "array"
$type->getDocCommentPart();
// array{
//     age: int,
//     name: string,
//     address: Address
// }
```

Integer keys render positionally (without the `key:` prefix), string keys render as `key: Type`. `add()`
returns `self` — calls are chainable.

## IntersectionType

Represents a PHP 8.1+ intersection type (`A&B&C`). Only class and interface types are valid members — PHP does not allow scalar types in intersections. Enforced statically: `add()` accepts only `ClassType`.

```php
$type = new IntersectionType();
$type->add(new ClassType(Countable::class));
$type->add(new ClassType(Iterator::class));

$type->render();            // "Countable&Iterator"
$type->getDocCommentPart(); // "Countable&Iterator"
$type->getUses();           // [UseStatementClass('Countable'), UseStatementClass('Iterator')]
```

`add()` returns `self` — calls are chainable.

## UnionType

Represents a PHP union type (`A|B|C`).

> **Status:** Stub — `getUses()` and `render()` are not yet implemented. See [Roadmap](roadmap.md).

## Combining Types

Types compose naturally. `getUses()` always returns the full flat list of imports needed by the entire subtree:

```php
$type = new UnionType(); // once implemented
// ScalarType::Null | new ClassType(User::class)
// getUses() → [UseStatementClass('User')]
```