<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function create(): View
    {
        return view('chat.chat-index');
    }
}
