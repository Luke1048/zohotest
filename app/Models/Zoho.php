<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Models\User;
use App\Models\AccessToken;

class Zoho extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id','client_id','client_secret'
    ];

    private $domains = ['com', 'eu'];
    // private $domains = ['eu', 'com'];

    // Tokens
    public function generateRefreshToken($code, $client_id, $client_secret) {
        //get crm token credentials
        $zohoCredentials = $this->where('user_id',Auth::user()->id)->first();

        if(is_null($zohoCredentials)) {
            $this::create([
                'user_id' => Auth::user()->id,
                // 'client_id' => '1000.DCKGTZMMMJJVOBZE0K8DTZUVG5WBYE',
                'client_id' => $client_id,
                // 'client_secret' => '8f3baa9e9419d00a6047269e54f3e581735ce808b4',
                'client_secret' => $client_secret,
            ]);
        }


        $token = AccessToken::where('user_id', Auth::user()->id)->first();

        $post = [
            'code' => $code,
            'redirect_uri' => 'http://example.com/callbackurl',
            // 'client_id' => '1000.DCKGTZMMMJJVOBZE0K8DTZUVG5WBYE',
            'client_id' => $client_id,
            // 'client_secret' => '8f3baa9e9419d00a6047269e54f3e581735ce808b4',
            'client_secret' => $client_secret,
            'grant_type' => 'authorization_code',
        ];

        $ch=curl_init();

        for($i=0; $i<count($this->domains); $i++) {
            curl_setopt($ch, CURLOPT_URL, 'https://accounts.zoho.'. $this->domains[$i] .'/oauth/v2/token');
            // curl_setopt($ch, CURLOPT_URL, 'https://accounts.zoho.com/oauth/v2/token');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_HTTPHEADER,  array(
                'Content-Type: application/x-www-form-urlencoded'
            ));

            $response = curl_exec($ch);

            if(isset(json_decode($response, true)['access_token']) ){
                break;
            }
        }

        if(isset(json_decode($response, true)['access_token'])) {
            if(is_null($token)){
                AccessToken::create([
                    'user_id'=>Auth::user()->id,
                    'value'=>$response,
                ]);

                return response(['Zoho - Token successfully created'], 200);
            }else{
                $token->value=$response;
                $token->save();
            }
        } else {
            $error = \Illuminate\Validation\ValidationException::withMessages([
                'access_token' => [$response],
            ]);
            throw $error;
        }
    }

    public function generateAccessToken($token){
        //get crm token credentials
        $zohoCredentials = $this->where('user_id',Auth::user()->id)->first();
        $tokenval = json_decode($token->value);
        $refresh_token=$tokenval->refresh_token;

        $post = [
            'refresh_token' => $refresh_token,
            'redirect_uri' => 'http://example.com/callbackurl',
            'client_id' => $zohoCredentials->client_id,
            // 'client_id' => '1000.DCKGTZMMMJJVOBZE0K8DTZUVG5WBYE',
            'client_secret' => $zohoCredentials->client_secret,
            // 'client_secret' => '8f3baa9e9419d00a6047269e54f3e581735ce808b4',
            'grant_type' => 'refresh_token',
        ];

        $ch=curl_init();

        for($i=0; $i<count($this->domains); $i++) {
            curl_setopt($ch, CURLOPT_URL, 'https://accounts.zoho.'. $this->domains[$i] .'/oauth/v2/token');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_HTTPHEADER,  array('Content-Type: application/x-www-form-urlencoded'));

            $response = curl_exec($ch);

            if(isset(json_decode($response, true)['access_token']) ){
                break;
            }
        }


        if(curl_getinfo($ch, CURLINFO_RESPONSE_CODE) === 200) {
            $tokenval->access_token = json_decode($response)->access_token;
            $token->value=json_encode($tokenval);
            $token->save();
        }

        return json_decode($token->value)->access_token;
    }

    private function checkIsAccessTokenValid($token){
        $tokenexpires=strtotime($token->updated_at)+3500;
        if($tokenexpires<time()){
            return false;
        }
        return true;
    }

    // Methods
    public function getDeals() {
        $token = AccessToken::where('user_id',Auth::user()->id)->latest()->first();
        $access_token = json_decode($token->value)->access_token;

        if(!$this->checkIsAccessTokenValid($token)) {
            $access_token = $this->generateAccessToken($token);
            $token = AccessToken::where('user_id',Auth::user()->id)->latest()->first();
        }

        $domain = json_decode($token->value)->api_domain;

        $ch=curl_init();

        curl_setopt($ch, CURLOPT_URL, $domain .'/crm/v2/Deals');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER,  array(
            'Authorization: Zoho-oauthtoken '.$access_token,
            'Content-Type: application/x-www-form-urlencoded'
        ));

            $response = curl_exec($ch);



        if(isset(json_decode ($response, true)['data'])) {
            return json_decode ($response, true)['data'];
        }
    }

    public function getTasks() {
        $token = AccessToken::where('user_id',Auth::user()->id)->latest()->first();
        $access_token = json_decode($token->value)->access_token;

        if(!$this->checkIsAccessTokenValid($token)) {
            $access_token = $this->generateAccessToken($token);
            $token = AccessToken::where('user_id',Auth::user()->id)->latest()->first();
        }

        $domain = json_decode($token->value)->api_domain;

        $ch=curl_init();

        // curl_setopt($ch, CURLOPT_URL, 'https://www.zohoapis.'. $this->domains[$i] .'/crm/v2/Tasks');
        curl_setopt($ch, CURLOPT_URL, $domain.'/crm/v2/Tasks');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER,  array(
            'Authorization: Zoho-oauthtoken '.$access_token,
            'Content-Type: application/x-www-form-urlencoded'
        ));

        $response = curl_exec($ch);

        if(isset(json_decode ($response, true)['data'])) {
            return json_decode ($response, true)['data'];
        }
    }




    public function createDeal($dealData) {
        $token = AccessToken::where('user_id',Auth::user()->id)->latest()->first();
        $access_token = json_decode($token->value)->access_token;

        if(!$this->checkIsAccessTokenValid($token)) {
            $access_token = $this->generateAccessToken($token);
            $token = AccessToken::where('user_id',Auth::user()->id)->latest()->first();
        }

        $domain = json_decode($token->value)->api_domain;

        // $deal_name = 'Test Deal';
        $data = [
            // 'Deal_Name' => $deal_name,
            "Stage" => 'Picklist'
        ];

        $data = array_merge($data, $dealData);

        $post_data = [
            'data' => [
                $data
            ],
            'trigger' => [
                'approval',
                'workflow',
                'blueprint'
            ]
        ];

        $ch=curl_init();

        curl_setopt($ch, CURLOPT_URL, $domain .'/crm/v2/deals');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER,  array(
            'Authorization: Zoho-oauthtoken '.$access_token,
            'Content-Type: application/x-www-form-urlencoded'
        ));

        $response = curl_exec($ch);

        if(isset(json_decode($response, true)['data'][0]['details']['id'])) {
            return json_decode($response, true)['data'][0]['details']['id'];
        }
    }

    public function createTask($taskData) {
        $token = AccessToken::where('user_id',Auth::user()->id)->latest()->first();
        $access_token = json_decode($token->value)->access_token;

        if(!$this->checkIsAccessTokenValid($token)) {
            $access_token = $this->generateAccessToken($token);
            $token = AccessToken::where('user_id',Auth::user()->id)->latest()->first();
        }

        $domain = json_decode($token->value)->api_domain;

        $data = [
            // 'Subject'=>'Test  Task',
        ];
        $data = array_merge($data, $taskData);

        $post_data = [
            'data' => [
                $data
            ],
            'trigger' => [
                'approval',
                'workflow',
                'blueprint'
            ]
        ];

        $ch=curl_init();

        curl_setopt($ch, CURLOPT_URL, $domain .'/crm/v2/tasks');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER,  array(
            'Authorization: Zoho-oauthtoken '.$access_token,
            'Content-Type: application/x-www-form-urlencoded'
        ));

        $response = curl_exec($ch);

        if(isset(json_decode($response, true)['data'][0]['details']['id'])) {
            return json_decode($response, true)['data'][0]['details']['id'];
        }
    }

    public function createRecords($dealData, $taskData) {
        $token = AccessToken::where('user_id',Auth::user()->id)->latest()->first();
        $access_token = json_decode($token->value)->access_token;

        if(!$this->checkIsAccessTokenValid($token)) {
            $access_token = $this->generateAccessToken($token);
            $token = AccessToken::where('user_id',Auth::user()->id)->latest()->first();
        }

        $domain = json_decode($token->value)->api_domain;

        $dealId = $this->createDeal($dealData);
        $taskId = $this->createTask($taskData);

        $post_data = [
            'data' => [
                [
                    'id'=>$taskId,
                    '$se_module' => 'Deals',
                    "What_Id" => $dealId,
                ]
            ]
        ];


        $ch=curl_init();

        curl_setopt($ch, CURLOPT_URL, $domain .'/crm/v2/tasks');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');

        curl_setopt($ch, CURLOPT_HTTPHEADER,  array(
            'Authorization: Zoho-oauthtoken '.$access_token,
            'Content-Type: application/x-www-form-urlencoded',
        ));

        $response = curl_exec($ch);

        // return json_decode ($response, JSON_PRETTY_PRINT);
        return redirect()->route('records');
    }





}
