<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Content\SlugRedirect;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Old addresses (a slug that changed, a lesson that moved) get a 301. */
class FallbackController extends Controller
{
    public function __invoke(Request $request): Response
    {
        if ($request->isMethod('GET') && ($new = SlugRedirect::resolve($request->getPathInfo()))) {
            return redirect()->to(app_url($new), 301);
        }

        abort(404);
    }
}
