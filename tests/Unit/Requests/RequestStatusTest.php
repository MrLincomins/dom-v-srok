<?php

declare(strict_types=1);

use App\Domain\Requests\Enums\RequestStatus;

it('allows the documented transitions', function (RequestStatus $from, RequestStatus $to) {
    expect($from->canTransitionTo($to))->toBeTrue();
})->with([
    [RequestStatus::New, RequestStatus::Assigned],
    [RequestStatus::New, RequestStatus::InProgress],
    [RequestStatus::New, RequestStatus::Redirected],
    [RequestStatus::Assigned, RequestStatus::InProgress],
    [RequestStatus::InProgress, RequestStatus::Done],
    [RequestStatus::Done, RequestStatus::Confirmed],
    [RequestStatus::Done, RequestStatus::Returned],
    [RequestStatus::Returned, RequestStatus::InProgress],
]);

it('forbids transitions out of terminal statuses and backwards', function (RequestStatus $from, RequestStatus $to) {
    expect($from->canTransitionTo($to))->toBeFalse();
})->with([
    [RequestStatus::Confirmed, RequestStatus::InProgress],
    [RequestStatus::Redirected, RequestStatus::New],
    [RequestStatus::Done, RequestStatus::InProgress],
    [RequestStatus::InProgress, RequestStatus::New],
    [RequestStatus::New, RequestStatus::Confirmed],
]);

it('knows which statuses are open', function () {
    expect(RequestStatus::openValues())->toBe(['new', 'assigned', 'in_progress', 'returned'])
        ->and(RequestStatus::Confirmed->isTerminal())->toBeTrue()
        ->and(RequestStatus::Done->isOpen())->toBeFalse();
});
