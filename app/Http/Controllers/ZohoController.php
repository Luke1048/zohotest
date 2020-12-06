<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\Models\Zoho;
use App\Models\AccessToken;
use Illuminate\Support\Facades\Validator;

class ZohoController extends Controller {
    public function __construct() {
        $this->middleware('auth');
    }

    public function connection(Zoho $zoho, Request $request) {
        $request->validate([
            'field_code' => ['required', 'string', 'min:1'],
            'field_client_id' => ['required', 'string', 'min:1'],
            'field_client_secret' => ['required', 'string', 'min:1'],
        ]);

        $response = $zoho->generateRefreshToken($request->field_code, $request->field_client_id, $request->field_client_secret);

        if($response->status() === 200) {
            return redirect()->route('records');
        } else {
            return redirect()->route('dashboard');
        }
    }

    public function form() {
        // check if token exists for user
        $token = AccessToken::where('user_id',Auth::user()->id)->latest()->first();

        if($token) {
            return redirect()->route('records');
        } else {
            return view('zohoform');
        }
    }

    /*public function getDeals(Zoho $zoho) {
        $response = $zoho->getDeals();
        return $response;
    }

    public function getTasks(Zoho $zoho) {
        $response = $zoho->getTasks();
        return $response;
    }

    public function createDeal(Zoho $zoho) {
        $response = $zoho->createDeal();
        return $response;
    }

    public function createTask(Zoho $zoho) {
        $response = $zoho->createTask();
        return $response;
    }*/

    public function createRecords(Zoho $zoho,  Request $request) {
        $request->validate([
            'deal_name' => ['required', 'string', 'min:1'],
            'task_name' => ['required', 'string', 'min:1'],
        ]);

        $response = $zoho->createRecords(['Deal_Name' => $request->deal_name], ['Subject' => $request->task_name]);
        return $response;
    }

}
