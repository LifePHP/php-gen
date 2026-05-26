<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';


$variable = new \LifePhp\PhpGen\Element\Variable('ahoj')
    ->setValue(new \LifePhp\PhpGen\Element\Variable('nazdar'));

$method = new \LifePhp\PhpGen\Element\Method(
    \LifePhp\PhpGen\Element\Visibility::Public,
    'hello_world',
    new \LifePhp\PhpGen\Element\Type\ClassType(
        \LifePhp\PhpGen\Element\Type\ClassType::class,
    ),
)->addParameter(
    \LifePhp\PhpGen\Element\Parameter::typed(
        \LifePhp\PhpGen\Element\Type\ScalarType::String,
        'input',
    ),
)->addParameter(
    \LifePhp\PhpGen\Element\Parameter::inferred(
        (new \LifePhp\PhpGen\Element\Type\UnionType())
            ->add(new \LifePhp\PhpGen\Element\Literal\IntLiteral(123))
            ->add(new \LifePhp\PhpGen\Element\Literal\NullLiteral()),
        'input',
    ),
);

echo $method->render();
