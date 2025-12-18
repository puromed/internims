<?php

use Livewire\Volt\Volt;

it('can render', function () {
    $component = Volt::test('admin.dates.index');

    $component->assertSee('');
});
