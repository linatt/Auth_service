<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RecoverPasswordRequest;
use App\Http\Requests\RegisterRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;


class RequestController extends BaseController
{

  public function sendRequestToRegistry(Request $request)
{

  $token = $request->get('token');
  $requestedService = 'A-Service';

  $client = new Client(array( 'curl' => array( CURLOPT_SSL_VERIFYPEER => false))); //GuzzleHttp\Client

  $serviceLocation = $client->get('http://registrydb.homestead/api/services/' . $requestedService)->getBody()->read(256);
  $result = $client->post('https://' . $serviceLocation . '/api/toB', ['json' => ['token' => $token]])->getBody()->read(128);
  return array($result);
}

}
