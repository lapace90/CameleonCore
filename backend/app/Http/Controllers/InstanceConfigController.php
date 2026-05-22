<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class InstanceConfigController extends Controller
{
    public function public(): JsonResponse
    {
        return response()->json([
            'name' => config('instance.name'),
            'type' => config('instance.type'),
            'logo' => config('instance.logo'),
            'country' => config('instance.country'),
            'modules' => config('instance.modules'),
            'productables' => config('instance.productables'),
            'features' => config('instance.features'),
            'contact' => [
                'phone' => config('instance.contact.phone'),
                'email' => config('instance.contact.email'),
                
            ],
        ]);
    }
}