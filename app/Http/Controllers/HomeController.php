<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function index()
{
    // Kunin ang lahat ng announcements
    $announcements = Announcement::all();

    // Ipakita ang public homepage kahit naka-login
    return view('welcome', compact('announcements'));
}
}
