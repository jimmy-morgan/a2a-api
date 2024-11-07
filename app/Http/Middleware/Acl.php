<?php

namespace App\Http\Middleware;

use App\Repositories\OutputRepository;
use App\Repositories\SessionRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Auth;
use Closure;

class Acl
{
    public function handle($request, Closure $next, $acl)
    {
        $authGuard = Auth::guard();
        $outputRepository = new OutputRepository();
        $sessionRepository = new SessionRepository();
        $userRepository = new UserRepository($authGuard->user(), $sessionRepository);

        if ($authGuard->guest()) {
            return $outputRepository->setMessages(406)->render();
        }

        $has_admin_role = $userRepository->hasRole(['admin']);

        if (!$has_admin_role) {
            $acl = is_array($acl)
                ? $acl
                : explode('|', $acl);

            $acl_roles = $acl_permissions = [];
            $has_permission = $has_role = false;

            if (!empty($acl)) {
                foreach ($acl as $a) {
                    list($type, $permission) = explode(':', $a);
                    if ($type == 'role') {
                        $acl_roles[] = $permission;
                    } elseif ($type == 'permission') {
                        $acl_permissions[] = $permission;
                    }
                }
            }

            if (!empty($acl_permissions)) {
                $has_permission = $userRepository->hasPermission($acl_permissions);
            }
            if (!empty($acl_roles)) {
                $has_role = $userRepository->hasRole($acl_roles);
            }


            if (!$has_role && !$has_permission) {
                return $outputRepository->setMessages(406)->render();
            }
        }

        return $next($request);
    }
}
