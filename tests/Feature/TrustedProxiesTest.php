<?php

use Illuminate\Support\Facades\Route;

function forwardedUrl(): string
{
    Route::get('/_test/url', fn () => url('/plan'));

    return test()->get('/_test/url', [
        'X-Forwarded-Proto' => 'https',
        'X-Forwarded-Host' => 'rasoi.example.com',
        'X-Forwarded-Port' => '443',
    ])->getContent();
}

test('forwarded headers are ignored by default', function () {
    expect(forwardedUrl())->toBe(config('app.url').'/plan');
});

test('forwarded headers are trusted when trusted proxies is *', function () {
    config(['rasoi.trusted_proxies' => '*']);

    expect(forwardedUrl())->toBe('https://rasoi.example.com/plan');
});

test('forwarded headers are ignored from proxies not on the list', function () {
    config(['rasoi.trusted_proxies' => '10.0.0.1, 10.0.0.2']);

    expect(forwardedUrl())->toBe(config('app.url').'/plan');
});
