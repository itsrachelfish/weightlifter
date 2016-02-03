<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Event;

class PageController extends Controller
{
    // Display different home page views if you're logged in or out
    public function home()
    {
        if($this->auth->check())
        {
            $applications = $this->auth->user()->applications;
            return view('pages/dashboard', compact('applications'));
        }
        else
        {
            return view('pages/home');
        }
    }
    
    // General purpose function for displaying views
    public function view(Request $request)
    {
        return view('pages/' . $request->path());
    }
}
