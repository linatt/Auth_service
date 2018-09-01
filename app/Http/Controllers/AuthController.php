<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RecoverPasswordRequest;
use App\Http\Requests\RegisterRequest;
use App\Mail\PasswordReset;
use App\Mail\Welcome;
use App\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Firebase\JWT\JWT;
use Firebase\JWT\ExpiredException;
use Illuminate\Support\Facades\Hash;
use Validator;
use Illuminate\Http\RedirectResponse;


/**
* Class AuthController
* @package App\Http\Controllers
*/
class AuthController extends Controller
{

  /**
  * The request instance.
  *
  * @var \Illuminate\Http\Request
  */
  private $request;
  /**
  * Create a new controller instance.
  *
  * @param  \Illuminate\Http\Request  $request
  * @return void
  */
  public function __construct(Request $request) {
    $this->request = $request;
  }

  /**
  * @return JsonResponse
  */
  public function getUser()
  {
    return response()->json(Auth::user());
  }

  /**
  * @param LoginRequest $request
  *
  * @return JsonResponse
  */
  public function login(LoginRequest $request)
  {
    $credentials = $request->only('email', 'password');

    $token = Auth::attempt($credentials);

    if (!$token) {
      return response()->json([
        'message' => 'Unauthorized'
      ], 401);
    }

    return response()->json(['user' => Auth::user(), 'token' => $token]);
  }

  /**
  * @param RegisterRequest $request
  *
  * @return JsonResponse
  */
  public function register(RegisterRequest $request)
  {
    $email = $request->input('email');
    $password = $request->input('password');
    $name = $request->input('name');

    $user = User::createFromValues($name, $email, $password);

    Mail::to($user)->send(new Welcome($user));

    return response()->json(['message' => 'Account created.']);
  }

  /**
  * @param String $token
  *
  * @return Response
  * @throws Exception
  */
  public function verify($token)
  {
    $user = User::verifyByToken($token);

    if (!$user) {
      return response()->json(['message' => 'Invalid verification token'], 400);
    }

    return response()->json(['message' => 'Account has been verified']);
  }

  /**
  * @param Request $request
  * @throws ValidationException
  */
  public function forgotPassword(Request $request)
  {
    $this->validate($request, [
      'email' => 'required|exists:users,email'
    ]);

    $user = User::byEmail($request->input('email'));

    Mail::to($user)->send(new PasswordReset($user));
  }

  /**
  * @param Request $request
  * @param $token
  * @throws ValidationException
  */
  public function recoverPassword(Request $request, $token)
  {
    $this->validate($request, [
      'password' => 'required|min:8',
    ]);

    User::newPasswordByResetToken($token, $request->input('password'));
  }

  /**
  * Create a new token.
  *
  * @param  \App\User   $user
  * @return string
  */
  protected function jwt(User $user) {
    $payload = [
      'iss' => "lumen-jwt", // Issuer of the token
      'sub' => $user->id, // Subject of the token
      'iat' => time(), // Time when JWT was issued.
      'exp' => time() + 60*60 // Expiration time
    ];

    // As you can see we are passing `JWT_SECRET` as the second parameter that will
    // be used to decode the token in the future.
    return JWT::encode($payload, env('JWT_SECRET'));
  }
  /**
  * Authenticate a user and return the token if the provided credentials are correct.
  *
  * @param  \App\User   $user
  * @return mixed
  */
  public function jwtauthenticate(User $user) {
    $this->validate($this->request, [
      'email'     => 'required|email',
      'password'  => 'required'
    ]);
    // Find the user by email
    $user = User::where('email', $this->request->input('email'))->first();
    $url = 'http://gateway.homestead/home?token=';
    $token = $this->jwt($user);
    if (!$user) {
      // You wil probably have some sort of helpers or whatever
      // to make sure that you have the same response format for
      // differents kind of responses. But let's return the
      // below respose for now.
      return response()->json([
        'error' => 'Email does not exist.'
      ], 400);
    }
    // Verify the password and generate the token
    if (Hash::check($this->request->input('password'), $user->password)) {
    //  return view('sender', ['token' => $this->jwt($user)]);
    return redirect($url. $token);


    /*  response()->json([
        'token' => $this->jwt($user)
      ], 200) ; */
    }
    // Bad Request response
    return response()->json([
      'error' => 'Email or password is wrong.'
    ], 400);
  }
}
