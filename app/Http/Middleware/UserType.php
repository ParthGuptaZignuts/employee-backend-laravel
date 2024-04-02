<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserType
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ... $types): Response
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized: No user authenticated'], 401);
        }

        if (!is_array($types)) {
            $types = [$types];
        }

        if (in_array($user->type, $types)) {
            return $next($request); 
        }
        
        $errorMessage = 'Unauthorized: You are not a ';
        $errorMessage .= implode(' or ', $types) . ' user';

        return response()->json(['error' => $errorMessage], 403);
    }
}
