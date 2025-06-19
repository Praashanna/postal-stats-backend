<?php

test('basic math operations work correctly', function () {
    expect(2 + 2)->toBe(4);
    expect(10 - 5)->toBe(5);
    expect(3 * 4)->toBe(12);
});

test('string operations work correctly', function () {
    expect(strlen('hello'))->toBe(5);
    expect(strtoupper('world'))->toBe('WORLD');
    expect(substr('Laravel', 0, 3))->toBe('Lar');
});

test('array operations work correctly', function () {
    $array = [1, 2, 3, 4, 5];
    
    expect(count($array))->toBe(5);
    expect(array_sum($array))->toBe(15);
    expect(in_array(3, $array))->toBeTrue();
    expect(in_array(6, $array))->toBeFalse();
});
