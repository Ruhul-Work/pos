<?php
namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::if('perm', fn($keyOrRoute, $ability = 'view') =>
            auth()->check()
            && method_exists(auth()->user(), 'canDo')
            && auth()->user()->canDo($keyOrRoute, $ability)
        );

        // Multiple prefix supported: @permgroup(['usermanage.users.','usermanage.rbac.'])
        Blade::if('permgroup', function ($prefixes, array $abilities = null) {
            $u = auth()->user();
            if (! $u || ! method_exists($u, 'canAnyPrefix')) {
                return false;
            }

            $abilities = $abilities ?? ['view', 'add', 'edit', 'delete', 'export'];
            $prefixes  = is_array($prefixes) ? $prefixes : [$prefixes];

            foreach ($prefixes as $p) {
                if ($u->canAnyPrefix(rtrim($p, '.'), $abilities)) { // ⬅️ এখানে rtrim
                    return true;
                }
            }
            return false;
        });
    }
}
