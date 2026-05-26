# Literals

Literals represent concrete PHP values — the right-hand side of an assignment. They live in
`src/Element/Literal/` under the namespace `LifePhp\PhpGen\Element\Literal`.

## Interface Hierarchy

```
ElementInterface
└── LiteralInterface            getType(): TypeInterface
    ├── ArrayKeyLiteral         getValue(): int|string
    └── Literal (abstract)      getDocCommentPart() via getType()
        ├── NullLiteral
        ├── BoolLiteral
        ├── IntLiteral          + ArrayKeyLiteral
        ├── FloatLiteral
        ├── StringLiteral       + ArrayKeyLiteral
        ├── ListLiteral
        ├── ArrayLiteral
        └── ShapeLiteral
```

### `LiteralInterface`

Extends `ElementInterface`. Adds:

```php
public function getType(): TypeInterface;
```

`getType()` is the primary design feature of this layer — it lets collection literals (`ListLiteral`,
`ArrayLiteral`, `ShapeLiteral`) build their own type by inspecting contained items, without any external
type annotation.

### `Literal` (abstract)

Implements `getDocCommentPart()` by delegating to `getType()->getDocCommentPart()`. Simple literals
inherit this and do not override it. `getUses()` defaults to `[]` — collection literals override it.

### `ArrayKeyLiteral`

Marker interface for literals that can be used as PHP array keys. Adds `getValue(): int|string` — the
raw value without PHP syntax quoting — used by `ShapeLiteral` when building a `ShapeType`.

```php
interface ArrayKeyLiteral extends LiteralInterface
{
    public function getValue(): int|string;
}
```

Implemented by `IntLiteral` and `StringLiteral`.

## Scalar Literals

| Class           | PHP value       | `render()`       | `getType()`                              |
|-----------------|-----------------|------------------|------------------------------------------|
| `NullLiteral`   | —               | `null`           | `ScalarType::Null`                       |
| `BoolLiteral`   | `bool $value`   | `true` / `false` | `ScalarType::True` / `ScalarType::False` |
| `IntLiteral`    | `int $value`    | `42`             | `ScalarType::Int`                        |
| `FloatLiteral`  | `float $value`  | `3.14`           | `ScalarType::Float`                      |
| `StringLiteral` | `string $value` | `'hello'`        | `ScalarType::String`                     |

**`BoolLiteral`** returns `ScalarType::True` or `ScalarType::False` (not `ScalarType::Bool`) — this
preserves the narrowest possible type in PHPDoc.

**`FloatLiteral::render()`** ensures a decimal point is always present — `(string) 1.0` produces `'1'`
in PHP, so the method appends `.0` when neither `.` nor `E` is present in the string representation.

**`StringLiteral::render()`** wraps the value in single quotes and calls `addslashes()`.

## Collection Literals

Collection literals hold other literals and derive their type lazily on first call to `getType()`,
caching the result. The cache is invalidated on every `add()` call.

### `ListLiteral`

Sequential list — integer-indexed, homogeneous values. Maps to `ListType`.

```php
$list = new ListLiteral();
$list->add(new IntLiteral(1))->add(new IntLiteral(2));
$list->render();          // "[\n    1,\n    2\n]"
$list->getType();         // ListType(ScalarType::Int)
$list->getDocCommentPart(); // "list<int>"
```

`resolveValueType()` deduplicates types by `getDocCommentPart()`. Multiple distinct types produce a
`UnionType`. An empty list produces `ScalarType::Never` → `list<never>`.

### `ArrayLiteral`

Key–value array with explicit `ArrayKeyLiteral` keys. Maps to `ArrayType`.

```php
$arr = new ArrayLiteral();
$arr->add(new StringLiteral('name'), new StringLiteral('Alice'));
$arr->getDocCommentPart(); // "array<\n    string,\n    string\n>"
```

`resolveKeyType()` inspects all keys:
- All `int` keys → `ArrayKeyType::Int`
- All `string` keys → `ArrayKeyType::String`
- Mixed → `ArrayKeyType::Both` → renders as `int|string` in PHPDoc

An empty array produces `ArrayType(keyType: ArrayKeyType::Int, valueType: ScalarType::Never)`.

### `ShapeLiteral`

Structured array with named keys. Maps to `ShapeType`.

```php
$shape = new ShapeLiteral();
$shape->add(new StringLiteral('age'), new IntLiteral(30));
$shape->getDocCommentPart(); // "array{age: int}"
$shape->render();            // "[\n    'age' => 30\n]"
```

Key rendering differs between the two outputs:
- `render()` uses `$key->render()` → PHP syntax with quotes: `'age' => 30`
- `getType()` uses `$key->getValue()` → raw value without quotes, passed to `ShapeType::add()`

## Key Design Decisions

**Lazy type inference with caching** — Collection literals compute their type on demand and cache it.
`add()` invalidates the cache. This avoids redundant traversal during construction.

**`ScalarType::Never` for empty collections** — An empty `ListLiteral` / `ArrayLiteral` correctly types
as `list<never>` / `array<int, never>`. PHPStan recognises this as a valid empty collection type.

**`ArrayKeyType::Both` for mixed keys** — Rather than a runtime exception or a `UnionType`, mixed-key
arrays use the dedicated `ArrayKeyType::Both` enum case, which renders as `int|string` in PHPDoc.

**`getUses()` propagation** — Scalar literals inherit `getUses(): []` from `Literal`. Collection
literals override it and aggregate from all contained values (keys are always scalar, so they never
carry uses).