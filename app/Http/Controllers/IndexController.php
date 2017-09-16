<?php

namespace Polyglot\Http\Controllers;

use Illuminate\Http\Request;

class IndexController extends Controller
{
    function index() {
        return view('index.index');
    }
}
