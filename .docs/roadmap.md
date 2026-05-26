# Roadmap

## Current Status

The project is in early development. The core architecture (`ElementInterface`, `TypeInterface`, `UseStatement`) is 
established and stable. All planned elements are complete; the only remaining gap is tests.

## File Status

| File                   | Status     | Notes                                                                                                                             |
|------------------------|------------|-----------------------------------------------------------------------------------------------------------------------------------|
| `ElementInterface`     | ✅ Complete | Core contract, stable                                                                                                             |
| `TypeInterface`        | ✅ Complete | Marker interface, stable                                                                                                          |
| `ScalarType`           | ✅ Complete | All scalar types covered                                                                                                          |
| `ClassType`            | ✅ Complete | Generics supported                                                                                                                |
| `ArrayKeyType`         | ✅ Complete | Enum constraining array key types to int\|string                                                                                  |
| `ArrayType`            | ✅ Complete | Typed arrays, readonly, key type enforced via ArrayKeyType                                                                        |
| `ListType`             | ✅ Complete | Sequential integer-keyed arrays, readonly                                                                                         |
| `UseStatement`         | ✅ Complete | Abstract base, alias support                                                                                                      |
| `UseStatementClass`    | ✅ Complete |                                                                                                                                   |
| `UseStatementFunction` | ✅ Complete |                                                                                                                                   |
| `UseStatementConstant` | ✅ Complete |                                                                                                                                   |
| `ShapeType`            | ✅ Complete | Array shape `array{key: Type}` syntax                                                                                             |
| `UnionType`            | ✅ Complete | Union types `A\|B\|C`, chainable `add()`                                                                                          |
| `IntersectionType`     | ✅ Complete | Intersection types `A&B`, only `ClassType` allowed, enforced statically                                                           |
| `Variable`             | ✅ Complete | Standalone variable, optional value, no type annotation                                                                           |
| `LiteralInterface`     | ✅ Complete | Contract for concrete PHP values, exposes `getType()`                                                                             |
| `ArrayKeyLiteral`      | ✅ Complete | Marker interface for int/string array key literals, adds `getValue()`                                                             |
| `NullLiteral`          | ✅ Complete |                                                                                                                                   |
| `BoolLiteral`          | ✅ Complete | Returns `ScalarType::True` / `ScalarType::False` for narrow typing                                                                |
| `IntLiteral`           | ✅ Complete | Implements `ArrayKeyLiteral`                                                                                                      |
| `FloatLiteral`         | ✅ Complete |                                                                                                                                   |
| `StringLiteral`        | ✅ Complete | Implements `ArrayKeyLiteral`                                                                                                      |
| `ListLiteral`          | ✅ Complete | Lazy type inference, cached, `list<never>` for empty                                                                              |
| `ArrayLiteral`         | ✅ Complete | Lazy type inference, cached, `ArrayKeyType::Both` for mixed keys                                                                  |
| `ShapeLiteral`         | ✅ Complete | Lazy type inference, cached                                                                                                       |
| `Visibility`           | ✅ Complete | Backed string enum: `Public`, `Protected`, `Private`; `rank()` for ordering                                                       |
| `Property`             | ✅ Complete | Named constructors, abstract/readonly/static, asymmetric visibility (`setSetVisibility()`), PHP 8.4 hooks (`setGetHook`/`setSetHook`), runtime combination guard |
| `Parameter`            | ✅ Complete | Named constructors, variadic/byRef/promoted, asymmetric visibility for promoted (`setSetVisibility()`), `isPromoted()` for Method |
| `PhpFunction`          | ✅ Complete | Signature only, parameter order validation, full PHPDoc                                                                           |
| `Method`               | ✅ Complete | Signature only, abstract/static/final, constructor inline body, deprecated                                                        |
| `ConstantType`         | ✅ Complete | Backed enum for typed constants: String/Int/Float/Bool/Null/Array                                                                 |
| `ClassConstant`        | ✅ Complete | Typed class constant (PHP 8.3+), no PHPDoc needed                                                                                 |
| `ClassDef`             | ✅ Complete | extends (circular detection) + implements, members ordered/unique                                                                 |
| `InterfaceDef`         | ✅ Complete | Multiple extends, circular detection, public-only methods, dedup                                                                  |
| `TraitDef`             | ✅ Complete | methods, properties, constants, used traits                                                                                       |
| `EnumCase`             | ✅ Complete | pure and backed cases, factory methods `pure()` / `backed()`                                                                      |
| `EnumBackingType`      | ✅ Complete | Backed enum `string` / `int`                                                                                                      |
| `EnumDef`              | ✅ Complete | pure and backed enums, cases, implements, used traits, constants, methods                                                         |
| `AbstractClassLikeDef` | ✅ Complete | Abstract base for all class-like elements; `setDocComment`, `toClassType`                                                         |
| `Traits/HasConstants`  | ✅ Complete | Mixin for ClassDef, TraitDef, InterfaceDef, EnumDef                                                                               |
| `Traits/HasProperties` | ✅ Complete | Mixin for ClassDef, TraitDef                                                                                                      |
| `Traits/HasMethods`    | ✅ Complete | Mixin for ClassDef, TraitDef, EnumDef                                                                                             |
| `Traits/HasUsedTraits` | ✅ Complete | Mixin for ClassDef, TraitDef, EnumDef                                                                                             |
| `Traits/HasImplements` | ✅ Complete | Mixin for ClassDef, EnumDef                                                                                                       |
| `Traits/HasAttributes` | ✅ Complete | Mixin for all elements; `addAttribute()`, `renderAttributes()`, `renderAttributesInline()`                                        |
| `Attribute`            | ✅ Complete | `#[Name(args)]`; positional and named args; single-arg inline, multi-arg multiline with trailing comma                            |
| `GetHook`              | ✅ Complete | PHP 8.4 `get` hook; `abstract()` → `get;`, `arrow(expr)` → `get => expr;`                                                        |
| `SetHook`              | ✅ Complete | PHP 8.4 `set` hook; `abstract()` → `set;`, `arrow(expr, ?type, name)` → `set($type $name) => expr;`                              |
| `PhpFile`              | ✅ Complete | `<?php`, `declare`, namespace, deduplicated use statements, alias conflict resolution                                             |

## Missing Infrastructure

| Item    | Notes                                                               |
|---------|---------------------------------------------------------------------|
| Tests   | `tests/` directory exists and is autoloaded, but contains no tests |

## Planned Work

1. **Write tests** — PHPUnit + Mockery are installed; no tests exist yet

## Expression / Statement Layer (Future)

Method and Function bodies require a dedicated expression/statement architecture. Deferred until after
`ClassElement` is complete.

### Planned interfaces

- **`ExpressionInterface extends ElementInterface`** — something that produces a value; can appear on
  the right side of an assignment: `new Foo()`, `fn() => x`, `$foo->bar()`, anonymous functions
- **`StatementInterface extends ElementInterface`** — a complete line of code (rendered with `;`):
  `return`, `if`, `foreach`, assignment, expression statement

### Planned constructs

| Construct                | Type       | Notes                                      |
|--------------------------|------------|--------------------------------------------|
| `ReturnStatement`        | Statement  | `return $expr;`                            |
| `AssignStatement`        | Statement  | `$var = $expr;`                            |
| `IfStatement`            | Statement  | with `ElseIf` / `Else` branches            |
| `MatchExpression`        | Expression | `match($x) { ... }`                        |
| `SwitchStatement`        | Statement  | `switch($x) { ... }`                       |
| `ForeachStatement`       | Statement  | `foreach($x as $k => $v) { ... }`          |
| `ForStatement`           | Statement  | `for(...) { ... }`                         |
| `WhileStatement`         | Statement  | `while(...) { ... }`                       |
| `FunctionCallExpression` | Expression | `foo($a, $b)`                              |
| `MethodCallExpression`   | Expression | `$obj->method($a)`                         |
| `AnonymousFunction`      | Expression | `function(int $y): mixed use ($x) { ... }` |
| `ArrowFunction`          | Expression | `fn(int $x): mixed => $x * 2`              |

### Note on anonymous/arrow functions

Both are **expressions** (can be assigned to variables or passed as arguments), not statements.
`AnonymousFunction` can have a body (`StatementInterface[]`). `ArrowFunction` has a single
`ExpressionInterface` as its body.

## Legend

| Symbol | Meaning                    |
|--------|----------------------------|
| ✅      | Complete and working       |
| ⚠️     | Has a known bug            |
| 🔲     | Stub / not yet implemented |