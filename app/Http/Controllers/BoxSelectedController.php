<?php

namespace App\Http\Controllers;

use App\Events\CardClick;
use Illuminate\Http\Request;

class BoxSelectedController extends Controller
{
    public function cardClicked(Request $request)
    {
        $card_id = $request->input('card_id');
        broadcast(new CardClick($card_id));
        return response()->json(['status' => 'Card clicked!', 'card_id' => $card_id]);
    }
}
