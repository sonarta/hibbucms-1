<?php

use App\Models\Category;

test('it can create a root category', function () {
    $category = Category::create([
        'name' => 'Root Category',
        'slug' => 'root-category',
    ]);

    expect($category)->toBeInstanceOf(Category::class)
        ->and($category->isRoot())->toBeTrue();
});

test('it can create a child category', function () {
    $root = Category::create([
        'name' => 'Parent Category',
    ]);

    $child = Category::create([
        'name' => 'Child Category',
        'parent_id' => $root->id,
    ]);

    expect($child->parent_id)->toBe($root->id)
        ->and($child->isDescendantOf($root))->toBeTrue();
});

test('it can move a category', function () {
    $root1 = Category::create(['name' => 'Root 1']);
    $root2 = Category::create(['name' => 'Root 2']);
    $child = Category::create(['name' => 'Child', 'parent_id' => $root1->id]);

    expect($child->parent_id)->toBe($root1->id);

    $child->parent_id = $root2->id;
    $child->save();

    $child->refresh();

    expect($child->parent_id)->toBe($root2->id)
        ->and($child->isDescendantOf($root2))->toBeTrue()
        ->and($child->isDescendantOf($root1))->toBeFalse();
});

test('it maintains tree integrity', function () {
    $root = Category::create(['name' => 'Root']);
    $child1 = Category::create(['name' => 'Child 1', 'parent_id' => $root->id]);
    $child2 = Category::create(['name' => 'Child 2', 'parent_id' => $root->id]);

    expect($root->children()->count())->toBe(2);
    expect(Category::countErrors())->toBe(0);
});
