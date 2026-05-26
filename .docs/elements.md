# Elements

Elements are `ElementInterface` implementations that represent PHP constructs beyond types and imports — variables,
properties, methods, classes, functions, and so on. This is the layer where the library generates actual PHP code 
structure.

## Variable

`Variable` represents a standalone PHP variable — outside of class or function signature context. It has no type
annotation (type is the responsibility of subclasses: `Property`, `Parameter`, `PromotedParameter`).

```php
$var = new Variable('foo');
$var->render(); // "$foo"

$var->setValue(new BoolLiteral(true));
$var->render(); // "$foo = true"

$var->setValue(new NullLiteral());
$var->render(); // "$foo = null"

$var->setValue(new StringLiteral('hello'));
$var->render(); // "$foo = 'hello'"
```

`render()` never includes a semicolon — that is the responsibility of the parent element depending on context.
`getDocCommentPart()` returns `''`. `getUses()` aggregates from `$value` when set. The value can be any
`LiteralInterface` or another `Variable` (for `$foo = $bar` assignments).

See [Literals](literals.md) for the full list of available value types.

## Property

`Property` represents a class property with visibility, type, and optional default value.

Type is required — but can be either explicit or inferred from the value. At least one of `$type` or `$value` must be provided, enforced via named constructors:

```php
// Explicit type
$prop = Property::typed(Visibility::Private, ScalarType::String, 'name');
$prop->render(); // "private string $name"

// Value with inferred type
$prop = Property::inferred(Visibility::Public, new IntLiteral(0), 'count');
$prop->render(); // "public int $count = 0"

// Modifiers
Property::typed(Visibility::Public, ScalarType::String, 'prefix')
    ->setStatic()
    ->setValue(new StringLiteral('app'))
    ->render(); // "public static string $prefix = 'app'"

Property::typed(Visibility::Private, ScalarType::Int, 'id')
    ->setReadonly()
    ->render(); // "private readonly int $id"

Property::typed(Visibility::Public, ScalarType::String, 'label')
    ->setAbstract()
    ->render(); // "abstract public string $label"
```

Render order: `abstract` → visibility → `static` → `readonly` → type → `$name` → `= value`

`getDocCommentPart()` delegates to `getType()` — useful for generic types in `@var` annotations.

**Invalid combinations (throw `\LogicException`):**

| Combination                         | Reason                                        |
|-------------------------------------|-----------------------------------------------|
| `setReadonly()` after `setStatic()` | PHP does not support `static readonly`        |
| `setStatic()` after `setReadonly()` | PHP does not support `static readonly`        |
| `setAbstract()` when value is set   | Abstract property cannot have a default value |
| `setValue()` when abstract          | Abstract property cannot have a default value |
| `setReadonly()` when value is set   | Readonly property cannot have a default value |
| `setValue()` when readonly          | Readonly property cannot have a default value |

## Parameter

`Parameter` represents a function or method parameter, including promoted constructor parameters.

Type is required — enforced via named constructors same as `Property`:

```php
// Explicit type
Parameter::typed(ScalarType::String, 'name');
// render() → "string $name"

// Inferred type
Parameter::inferred(new IntLiteral(0), 'count');
// render() → "int $count = 0"

// Variadic
Parameter::typed(ScalarType::String, 'tags')->setVariadic();
// render() → "string ...$tags"

// By reference
Parameter::typed(ScalarType::Int, 'value')->setByReference();
// render() → "int &$value"

// Promoted constructor parameter
Parameter::typed(ScalarType::String, 'name')->setPromoted(Visibility::Private);
// render() → "private string $name"

// Promoted + readonly
Parameter::typed(ScalarType::String, 'name')
    ->setPromoted(Visibility::Private)
    ->setReadonly();
// render() → "private readonly string $name"
```

`isPromoted(): bool` — used by `Method` to validate that promoted parameters appear only in constructors.

`setReadonly()` requires `setPromoted()` to be called first — readonly is only meaningful on promoted parameters.

**Invalid combinations (throw `\LogicException`):**

| Combination                             | Reason                                            |
|-----------------------------------------|---------------------------------------------------|
| `setVariadic()` when value is set       | Variadic parameters cannot have a default value   |
| `setValue()` when variadic              | Variadic parameters cannot have a default value   |
| `setVariadic()` when promoted           | Promoted parameters cannot be variadic            |
| `setByReference()` when promoted        | Promoted parameters cannot be passed by reference |
| `setPromoted()` when variadic           | Promoted parameters cannot be variadic            |
| `setPromoted()` when by reference       | Promoted parameters cannot be passed by reference |
| `setReadonly()` without `setPromoted()` | Readonly only applies to promoted parameters      |

## PhpFunction

`PhpFunction` represents a standalone PHP function (outside of a class).

```php
$fn = new PhpFunction('formatDate', ScalarType::String);
$fn->addParameter(Parameter::typed(ScalarType::String, 'date'));
$fn->addParameter(Parameter::inferred(new StringLiteral('Y-m-d'), 'format'));
$fn->render();
// function formatDate(string $date, string $format = 'Y-m-d'): string
// {
// }
```

Multiple parameters render multiline with a trailing comma. `getDocCommentPart()` returns a full PHPDoc
block with `@param` and `@return` lines.

**Parameter order validated at `render()` (throws `\LogicException`):**
- Required parameters must precede optional (with default value) parameters
- Variadic parameter must be last
- Promoted parameters are not allowed in standalone functions

## Method

`Method` represents a class method. Requires `Visibility` as the first constructor argument.

```php
// Regular method
$m = new Method(Visibility::Public, 'getName', ScalarType::String);
$m->render();
// public function getName(): string
// {
// }

// Abstract method — no body, ends with semicolon
$m = new Method(Visibility::Public, 'getName', ScalarType::String);
$m->setAbstract()->render();
// abstract public function getName(): string;

// Static final method
$m = new Method(Visibility::Public, 'create', ScalarType::String);
$m->setStatic()->setFinal()->render();
// final public static function create(): string
// {
// }

// Constructor with promoted parameters — body rendered inline
$m = new Method(Visibility::Public, '__construct', ScalarType::Void);
$m->addParameter(Parameter::typed(ScalarType::String, 'name')->setPromoted(Visibility::Private)->setReadonly());
$m->addParameter(Parameter::typed(ScalarType::Int, 'age')->setPromoted(Visibility::Private));
$m->render();
// public function __construct(
//     private readonly string $name,
//     private int $age,
// ): void {}
```

Render order of modifiers: `abstract`/`final` → visibility → `static` → `function` → name

Promoted parameters are only allowed in `__construct` — validated at both `addParameter()` and `render()`.

`setDeprecated(string $message = '')` — adds `@deprecated` to the PHPDoc block. Optional message:
```php
$m->setDeprecated();                     // @deprecated
$m->setDeprecated('Use bar() instead');  // @deprecated Use bar() instead
```

**Inspection methods:** `isAbstract()`, `isFinal()`, `isConstructor()`, `isDestructor()`, `getName()`, `getVisibility()`
— used by `ClassDef` for validation and method ordering.

**Invalid combinations (throw `\LogicException`):**

| Combination                              | Reason                                           |
|------------------------------------------|--------------------------------------------------|
| `setAbstract()` after `setFinal()`       | Method cannot be both abstract and final         |
| `setFinal()` after `setAbstract()`       | Method cannot be both abstract and final         |
| Promoted parameter outside `__construct` | Promoted parameters only allowed in constructors |
| Required parameter after optional        | Required parameters must precede optional ones   |
| Any parameter after variadic             | Variadic must be last                            |

## ClassConstant

`ClassConstant` represents a typed class constant (PHP 8.3+).

```php
$c = new ClassConstant(Visibility::Public, ConstantType::String, 'VERSION', new StringLiteral('1.0.0'));
$c->render(); // "public const string VERSION = '1.0.0'"
```

Type is enforced via `ConstantType` enum (not `TypeInterface`) — prevents invalid types such as class
instances or generics. Allowed values: `String`, `Int`, `Float`, `Bool`, `Null`, `Array`.

`getDocCommentPart()` returns `''` — the value is always visible in the declaration, PHPStan infers the
type directly.

## ClassDef

`ClassDef` represents a PHP class definition (class / abstract class / final class / readonly class).

```php
$class = new ClassDef('App\\Service', 'UserService');

// Modifiers
$class->setAbstract();   // abstract class
$class->setFinal();      // final class
$class->setReadonly();   // readonly class (PHP 8.2+)

// Inheritance
$base = new ClassDef('App\\Base', 'BaseService');
$class->setExtends($base);         // accepts ClassDef or ClassType, circular detection when ClassDef
$class->setExtends(new ClassType('App\\Base\\BaseService'));

// Interfaces
$loggable = new InterfaceDef('App\\Contract', 'Loggable');
$class->addImplements($loggable);  // no duplicate FQCN allowed

// Members
$class->addConstant($constant);    // ClassConstant — unique by name, ordered public→protected→private
$class->addProperty($property);    // Property — unique by name, ordered public→protected→private
$class->addConstructor($method);   // Method with name "__construct"
$class->addDestructor($method);    // Method with name "__destruct"
$class->addMethod($method);        // any other Method — unique by name

// Getters
$class->getConstructor();          // ?Method
$class->getDestructor();           // ?Method
$class->getFullyQualifiedName();   // "App\\Service\\UserService"
$class->toClassType();             // ClassType for use in extends/implements
```

**Render order of members:**
- Constants: public → protected → private
- Properties: public → protected → private
- Methods: `__construct` → public → protected → private → `__destruct`

**Render output example:**
```php
class UserService extends BaseService implements Loggable
{
    public const string VERSION = '1.0.0';

    /** @var array<int, string> */
    public array $tags;

    private string $name;

    public function __construct(private readonly string $name): void {}

    public function getName(): string
    {
    }
}
```

**Invalid combinations (throw `\LogicException`):**

| Combination                                   | Reason                                             |
|-----------------------------------------------|----------------------------------------------------|
| `setAbstract()` + `setFinal()`                | Cannot be both abstract and final                  |
| `setReadonly()` when parent is non-readonly   | Readonly class can only extend readonly class      |
| `setExtends(final class)`                     | Cannot extend a final class                        |
| Readonly/non-readonly mismatch on extends     | Both parent and child must agree on readonly       |
| `setExtends()` creating a cycle               | Circular extends detected (iterative chain walk)   |
| `addImplements()` with duplicate FQCN         | Interface already implemented                      |
| `addConstructor()` when constructor exists    | Only one constructor allowed                       |
| `addDestructor()` when destructor exists      | Only one destructor allowed                        |
| `addMethod()` with duplicate name             | Method names must be unique                        |
| `addMethod()` with `__construct`/`__destruct` | Use dedicated `addConstructor()`/`addDestructor()` |
| Abstract method in non-abstract class         | Validated at `render()`                            |
| Override of final parent constructor          | Cannot override final constructor                  |
| Override of private parent constructor        | Cannot override private constructor                |

**Not validated (rely on PHPStan):**

| Situation | Why not validated here |
|---|---|
| Non-abstract class doesn't implement all interface methods | Parent class or trait may provide the implementation — full inheritance chain would need traversal |
| Non-abstract class doesn't implement all abstract parent methods | Same reason — incomplete without full tree |

## InterfaceDef

`InterfaceDef` represents a PHP interface definition.

```php
$countable = new InterfaceDef('App\\Contract', 'Countable');
$serializable = new InterfaceDef('App\\Contract', 'Serializable');

$repo = new InterfaceDef('App\\Contract', 'RepositoryInterface');
$repo->addExtends($countable);
$repo->addExtends($serializable);

$repo->addConstant(new ClassConstant(Visibility::Public, ConstantType::String, 'VERSION', new StringLiteral('1.0')));

$method = new Method(Visibility::Public, 'findById', ScalarType::Mixed);
$method->addParameter(Parameter::typed(ScalarType::Int, 'id'));
$repo->addMethod($method);

$repo->render();
// interface RepositoryInterface extends Countable, Serializable
// {
//     public const string VERSION = '1.0';
//
//     public function findById(int $id): mixed;
// }
```

All methods must be `public` and non-`final` — validated in `addMethod()`. Methods render without the
`abstract` keyword (via `renderAsInterfaceMethod()`).

`toClassType(): ClassType` — for use as a type hint or in `ClassDef::addImplements()` (future).

**Invalid combinations (throw `\LogicException`):**

| Combination                              | Reason                                                |
|------------------------------------------|-------------------------------------------------------|
| `addExtends()` with duplicate FQCN       | Interface already extends that interface              |
| `addExtends()` creating a cycle          | Circular extends detected (DFS through extends graph) |
| `addConstant()` with duplicate name      | Constant name must be unique                          |
| `addMethod()` with non-public visibility | Interface methods must be public                      |
| `addMethod()` with `final` method        | Interface methods cannot be final                     |
| `addMethod()` with duplicate name        | Method names must be unique                           |

## TraitDef

`TraitDef` represents a PHP trait definition.

```php
$trait = new TraitDef('App\\Concern', 'HasTimestamps');

$trait->addProperty(Property::typed(Visibility::Private, ScalarType::String, 'createdAt'));
$trait->addConstant(new ClassConstant(Visibility::Public, ConstantType::String, 'FORMAT', new StringLiteral('Y-m-d')));

$method = new Method(Visibility::Public, 'getCreatedAt', ScalarType::String);
$trait->addMethod($method);

$trait->render();
// trait HasTimestamps
// {
//     public const string FORMAT = 'Y-m-d';
//
//     private string $createdAt;
//
//     public function getCreatedAt(): string
//     {
//     }
// }
```

Traits support `addConstant()`, `addProperty()`, `addMethod()`, `addConstructor()`, `addDestructor()`,
`addUseTrait()`. `getUses()` aggregates from used traits, properties, and methods.

## EnumDef

`EnumDef` represents a PHP enum definition — either pure or backed.

```php
// Pure enum
$suit = EnumDef::pure('App\\Enum', 'Suit');
$suit->addCase(EnumCase::pure('Hearts'));
$suit->addCase(EnumCase::pure('Diamonds'));
$suit->render();
// enum Suit
// {
//     case Hearts;
//     case Diamonds;
// }

// Backed enum
$status = EnumDef::backed('App\\Enum', 'Status', EnumBackingType::String);
$status->addCase(EnumCase::backed('Active', new StringLiteral('active')));
$status->addCase(EnumCase::backed('Inactive', new StringLiteral('inactive')));
$status->render();
// enum Status: string
// {
//     case Active = 'active';
//     case Inactive = 'inactive';
// }
```

Enums support `addCase()`, `addConstant()`, `addMethod()`, `addImplements()`, `addUseTrait()`.
Constructors, destructors, and abstract methods are not allowed — validated in `addMethod()`.

**Invalid combinations (throw `\LogicException`):**

| Combination                                | Reason                                                   |
|--------------------------------------------|----------------------------------------------------------|
| `addCase()` with duplicate name            | Case names must be unique                                |
| Pure enum with backed case value           | Pure enum cases cannot have a value                      |
| Backed enum case without a value           | Backed enum cases require a value                        |
| String-backed enum with non-string value   | Case value must be a `StringLiteral`                     |
| Int-backed enum with non-int value         | Case value must be an `IntLiteral`                       |
| `addMethod()` with abstract method         | Enums cannot have abstract methods                       |
| `addMethod()` with `__construct`           | Enums cannot have a constructor                          |
| `addMethod()` with `__destruct`            | Enums cannot have a destructor                           |

## Adding New Elements

When implementing a new element:

1. Implement `ElementInterface` (or `TypeInterface` if it represents a type)
2. Add `declare(strict_types=1)` and namespace `LifePhp\PhpGen`
3. Mark all interface method implementations with `#[Override]`
4. Aggregate `getUses()` from all child elements using the spread operator pattern
5. Consider `readonly` if the element holds no mutable state
6. Update [Roadmap](roadmap.md) to reflect the new element's status
