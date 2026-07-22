<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\ActiveUserMiddleware;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class ActiveUserMiddlewareTest extends TestCase
{
    private function requestForUser(?User $user): Request
    {
        $request = Request::create('/protected', 'GET');
        $request->setUserResolver(fn () => $user);

        return $request;
    }

    public function test_allows_request_for_active_user(): void
    {
        $request = $this->requestForUser(new User(['is_active' => true]));

        $response = (new ActiveUserMiddleware)->handle(
            $request,
            fn () => new Response('passed')
        );

        $this->assertSame('passed', $response->getContent());
    }

    public function test_denies_request_for_inactive_user(): void
    {
        $request = $this->requestForUser(new User(['is_active' => false]));

        $response = (new ActiveUserMiddleware)->handle(
            $request,
            fn () => new Response('passed')
        );

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('Compte désactivé.', $response->getData()->message);
    }

    public function test_denies_request_when_there_is_no_authenticated_user(): void
    {
        $request = $this->requestForUser(null);

        $response = (new ActiveUserMiddleware)->handle(
            $request,
            fn () => new Response('passed')
        );

        $this->assertSame(403, $response->getStatusCode());
    }
}
