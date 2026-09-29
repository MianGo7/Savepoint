<?php

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;

/**
 * The pages that a visitor without a session may open. Everything else has to
 * send a guest to the login page (NFR5).
 */
const PUBLIC_ROUTES = ['home', 'login', 'register', 'password.request', 'password.reset'];

/**
 * @return list<Route>
 */
function applicationPages(): array
{
    return collect(RouteFacade::getRoutes()->getRoutes())
        ->filter(fn (Route $route): bool => in_array('GET', $route->methods(), true))
        ->filter(fn (Route $route): bool => ! str_starts_with((string) $route->getName(), 'livewire.')
            && ! str_starts_with((string) $route->getName(), 'flux.')
            && ! str_starts_with((string) $route->getName(), 'storage.')
            && ! str_starts_with($route->uri(), '_')
            && ! str_starts_with($route->uri(), 'flux/')
            && ! str_starts_with($route->uri(), 'livewire')
            && $route->uri() !== 'up')
        ->values()
        ->all();
}

test('every page except the public ones sends a guest to the login page', function () {
    $checked = 0;

    foreach (applicationPages() as $route) {
        if (in_array($route->getName(), PUBLIC_ROUTES, true)) {
            continue;
        }

        $uri = preg_replace('/\{[^}]+\}/', '1', $route->uri());

        $this->get('/'.ltrim($uri, '/'))->assertRedirect(route('login'));
        $checked++;
    }

    expect($checked)->toBeGreaterThanOrEqual(10);
});

test('the public pages are reachable without a session', function () {
    foreach (['login', 'register', 'password.request'] as $name) {
        $this->get(route($name))->assertOk();
    }

    $this->get(route('home'))->assertOk();
});
