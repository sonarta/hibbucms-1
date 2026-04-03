<?php

use App\Models\Category;

test('it can create a root category', function () {
    $category = Category::create([
        'name' => 'Root Category',
        'slug' => 'root-category-'.uniqid(),
    ]);

    expect($category)->toBeInstanceOf(Category::class)
        ->and($category->isRoot())->toBeTrue();
});

test('it can create a child category', function () {
    $root = Category::create([
        'name' => 'Parent Category',
        'slug' => 'parent-category-'.uniqid(),
    ]);

    $child = new Category([
        'name' => 'Child Category',
        'slug' => 'child-category-'.uniqid(),
    ]);
    $child->appendToNode($root)->save();

    expect($child->parent_id)->toBe($root->id)
        ->and($child->isDescendantOf($root))->toBeTrue();
});

test('it can move a category', function () {
    $root1 = Category::create(['name' => 'Root 1', 'slug' => 'root-1-'.uniqid()]);
    $root2 = Category::create(['name' => 'Root 2', 'slug' => 'root-2-'.uniqid()]);
    $child = new Category(['name' => 'Child', 'slug' => 'child-'.uniqid()]);
    $child->appendToNode($root1)->save();

    expect($child->parent_id)->toBe($root1->id);

    $child->appendToNode($root2)->save();
    $child->refresh();

    expect($child->parent_id)->toBe($root2->id)
        ->and($child->isDescendantOf($root2))->toBeTrue()
        ->and($child->isDescendantOf($root1))->toBeFalse();
});

test('it maintains tree integrity', function () {
    $root = Category::create(['name' => 'Root', 'slug' => 'root-'.uniqid()]);
    $child1 = new Category(['name' => 'Child 1', 'slug' => 'child-1-'.uniqid()]);
    $child1->appendToNode($root)->save();
    $child2 = new Category(['name' => 'Child 2', 'slug' => 'child-2-'.uniqid()]);
    $child2->appendToNode($root)->save();

    expect($root->children()->count())->toBe(2);

    $errors = (array) Category::countErrors();
    expect($errors['oddness'])->toBe(0)
        ->and($errors['duplicates'])->toBe(0)
        ->and($errors['wrong_parent'])->toBe(0)
        ->and($errors['missing_parent'])->toBe(0);
});
