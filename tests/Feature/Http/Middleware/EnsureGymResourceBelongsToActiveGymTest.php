<?php

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\EnsureGymResourceBelongsToActiveGym;
use App\Models\Gym;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class EnsureGymResourceBelongsToActiveGymTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_allows_a_route_bound_resource_from_the_active_gym(): void
    {
        $gym = Gym::factory()->create();
        $administrator = User::factory()->for($gym)->admin()->create();
        $trainer = Trainer::factory()->for($gym)->create();
        $request = $this->requestWithRouteResource($administrator, $trainer);

        $response = app(EnsureGymResourceBelongsToActiveGym::class)->handle(
            $request,
            fn (): Response => response()->noContent(),
        );

        $this->assertSame(204, $response->getStatusCode());
    }

    public function test_it_hides_a_route_bound_resource_from_another_gym(): void
    {
        $activeGym = Gym::factory()->create();
        $administrator = User::factory()->for($activeGym)->admin()->create();
        $otherTrainer = Trainer::factory()->for(Gym::factory())->create();
        $request = $this->requestWithRouteResource($administrator, $otherTrainer);

        $this->expectException(NotFoundHttpException::class);

        app(EnsureGymResourceBelongsToActiveGym::class)->handle(
            $request,
            fn (): Response => response()->noContent(),
        );
    }

    public function test_it_hides_gym_resources_when_the_user_has_no_active_gym(): void
    {
        $administrator = User::factory()->admin()->create(['gym_id' => null]);
        $trainer = Trainer::factory()->create();
        $request = $this->requestWithRouteResource($administrator, $trainer);

        $this->expectException(NotFoundHttpException::class);

        app(EnsureGymResourceBelongsToActiveGym::class)->handle(
            $request,
            fn (): Response => response()->noContent(),
        );
    }

    private function requestWithRouteResource(User $user, Trainer $trainer): Request
    {
        $request = Request::create("/admin/trainers/{$trainer->id}", 'DELETE');
        $route = new Route('DELETE', '/admin/trainers/{trainer}', fn (): null => null);
        $route->bind($request);
        $route->setParameter('trainer', $trainer);

        $request->setUserResolver(fn (): User => $user);
        $request->setRouteResolver(fn (): Route => $route);

        return $request;
    }
}
