<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\RoleMiddleware;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    private function requestForUser(?User $user): Request
    {
        $request = Request::create('/protected', 'GET');
        $request->setUserResolver(fn () => $user);

        return $request;
    }

    public function test_allows_request_when_user_role_matches(): void
    {
        $request = $this->requestForUser(new User(['role' => 'admin']));

        $response = (new RoleMiddleware)->handle(
            $request,
            fn () => new Response('passed'),
            'admin',
            'responsable'
        );

        $this->assertSame('passed', $response->getContent());
    }

    public function test_denies_request_when_user_role_does_not_match(): void
    {
        $request = $this->requestForUser(new User(['role' => 'user']));

        $response = (new RoleMiddleware)->handle(
            $request,
            fn () => new Response('passed'),
            'admin'
        );

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('Accès refusé.', $response->getData()->message);
    }

    public function test_denies_request_when_there_is_no_authenticated_user(): void
    {
        $request = $this->requestForUser(null);

        $response = (new RoleMiddleware)->handle(
            $request,
            fn () => new Response('passed'),
            'admin'
        );

        $this->assertSame(403, $response->getStatusCode());
    }
}
