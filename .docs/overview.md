# Overview

`life-php/php-gen` is a PHP 8.5+ library for programmatically generating PHP source code that conforms to PSR and 
PER (PHP Evolving Recommendation) standards.

## What It Does

The library models PHP constructs (types, variables, use statements, classes, functions, ...) as objects. Each object
knows how to:

- **render itself** as a PHP code string
- **describe itself** in PHPDoc format
- **declare its dependencies** — which `use` imports it requires

A consumer builds a tree of these objects and calls `render()` on the root. Import collection works automatically — no 
manual `use` statement tracking needed.

## Core Idea

```php
// Build a typed array of Uuid objects
$type = new ArrayType(
    keyType: ScalarType::String,
    valueType: new ClassType(Uuid::class),
);

$type->render();           // "array"
$type->getDocCommentPart(); // "array<\n    string,\n    Uuid\n>"
$type->getUses();          // [UseStatementClass('Ramsey\Uuid\Uuid')]
```

The caller collects `getUses()` from the whole element tree to build the `use` block at the top of the generated file.

## Design Goals

- **PER-compliant output** — generated code follows current PHP community standards
- **Composable** — small objects combine into complex structures
- **Self-contained** — every element knows its own imports
- **PHPDoc-aware** — dual rendering distinguishes runtime types from documentation types (e.g., generics exist only in PHPDoc)

## Navigation

| Section                             | Description                                           |
|-------------------------------------|-------------------------------------------------------|
| [Architecture](architecture.md)     | Core patterns and design decisions                    |
| [Types](types.md)                   | The type system (`TypeInterface` and implementations) |
| [Use Statements](use-statements.md) | Import management (`UseStatement` hierarchy)          |
| [Elements](elements.md)             | Variables and other code elements                     |
| [Roadmap](roadmap.md)               | Project status and planned work                       |