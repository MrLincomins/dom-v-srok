<?php

declare(strict_types=1);

use App\Domain\Requests\QueueSearch;

it('splits the query into words and drops address abbreviations', function (string $query, array $terms) {
    expect(QueueSearch::parse($query))->toBe($terms);
})->with([
    'number with sign' => ['№5', ['5']],
    'number with sign and space' => ['№ 17', ['17']],
    'flat' => ['кв 45', ['45']],
    'flat glued to abbreviation' => ['кв.45', ['45']],
    'full address' => ['ул. Мира, д. 12, кв. 3', ['мира', '12', '3']],
    'case' => ['ДОМОФОН', ['домофон']],
    'only an abbreviation' => ['кв', ['кв']],
    'blank' => ['   ', []],
    'too many words' => ['а б в г д е ж', ['а', 'б', 'в', 'г', 'е']],
]);

it('takes a lone number as a request number', function () {
    expect((new QueueSearch('№ 12'))->requestNumber())->toBe(12)
        ->and((new QueueSearch('Мира 12'))->requestNumber())->toBeNull()
        ->and((new QueueSearch('1234567890'))->requestNumber())->toBeNull();
});
