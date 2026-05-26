# Use Statements

`UseStatement` is an abstract class that models a PHP `use` declaration. It implements `ElementInterface` and is also 
returned by `getUses()` calls throughout the element tree — making it both an element that renders itself and the 
unit of dependency tracking.

## Abstract Base: UseStatement

```php
abstract class UseStatement implements ElementInterface
{
    protected const string USE_FORMAT = '';
    public ?string $alias;
    protected string $path;
    protected string $name;
}
```

`render()` produces the full `use` statement:

```
use [USE_FORMAT][name][ as alias];
```

`getName()` returns the alias if set, otherwise the short name. This is what consumers use when referencing the 
imported symbol in generated code.

`getUses()` returns `[]` — use statements do not themselves require further imports.

## Concrete Subclasses

Each subclass sets `USE_FORMAT` and implements a constructor that parses the input string to extract the short `$name`.

### UseStatementClass

```php
new UseStatementClass(\Ramsey\Uuid\Uuid::class);
// renders: "use Ramsey\Uuid\Uuid;"
// getName(): "Uuid"
```

Parses the last `\` to extract the class short name. `USE_FORMAT = ''` (no prefix for class imports).

### UseStatementFunction

```php
new UseStatementFunction('App\\Helpers\\formatDate');
// renders: "use function App\Helpers\formatDate;"
// getName(): "formatDate"
```

`USE_FORMAT = 'function '` (trailing space is required). Parses the last `:` to extract the function name.

### UseStatementConstant

```php
new UseStatementConstant('App\\Constants\\MAX_SIZE');
// renders: "use const App\Constants\MAX_SIZE;"
// getName(): "MAX_SIZE"
```

`USE_FORMAT = 'const '` (trailing space is required). Parses the last `:` to extract the constant name.

## Aliases

All three subclasses support aliasing via the public `$alias` property:

```php
$use = new UseStatementClass('App\\Models\\User');
$use->alias = 'AppUser';

$use->render();    // "use App\Models\User as AppUser;"
$use->getName();   // "AppUser"
```

## Role in the Element Tree

`UseStatement` objects serve dual roles:

1. **As elements** — they implement `ElementInterface` and can be rendered directly into the `use` block of a generated file
2. **As dependency tokens** — `getUses()` throughout the element tree returns `UseStatement[]`, allowing consumers to collect all needed imports in one pass

`ClassType` internally holds a `UseStatementClass` instance and returns it from `getUses()`. The caller accumulates 
these across the entire element tree, deduplicates them, and renders them as the file's import block.