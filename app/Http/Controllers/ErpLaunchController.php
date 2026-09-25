<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ErpLaunchController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        abort_unless(config('services.erp.enabled'), 404);
        abort_unless($request->user()?->canAccessErp(), 403);

        $url = (string) config('services.erp.launch_url');
        abort_unless(filter_var($url, FILTER_VALIDATE_URL), 500);

        return redirect()->away($url);
    }
}
