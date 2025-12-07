<?php

namespace App\Http\Controllers;

class AppSettingsController extends Controller
{
    public function __invoke()
    {
        $used_exts = ['zip'];
        $available_exts = array_intersect(get_loaded_extensions(), $used_exts);
        return view('settings')
            ->with('php', PHP_VERSION)
            ->with('used_exts', $used_exts)
            ->with('available_exts', $available_exts);
    }
}
