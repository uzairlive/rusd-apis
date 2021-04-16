<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Http\Request;
use Validator;
use App\User;
use Auth;
use Mail;

class AuthController extends Controller
{
    //
    public function login(Request $request)
    {
	    try {
	    	$validator = Validator::make($request->all(), [
	            'email' => 'required|email',
	            'password' => 'required',
	        ]);

	        if($validator->fails()){
	            return response()->json([
                    'status'  => false,
                    'message' => $validator->errors(),
                    'code'    => 404
                ], 404); 


	        }

	        $credentials = request(['email', 'password']);

	        if (!Auth::attempt($credentials)) {
			      return response()->json([
			      	'data' 		=> NULL,
			        'message'	=> 'Invalid credentials!',
			        'code' 		=> 500
			      ]);
			}

			$user = User::where('email', $request->email)->first();

		    if ( ! Hash::check($request->password, $user->password, [])) {
		       return response()->json([
		       		'data'	  => NULL,
	                'success' => false,
	                'message' => 'Invalid credentials!'
	            ], 400);
		    }

		    $tokenResult  = $user->createToken('authToken')->plainTextToken;

		    return response()->json([
	            'success' => true,
	            'token'   => $tokenResult,
	      		'token_type' => 'Bearer',
	            'data' 		 => $user,
	            'code'		 => 200
	        ],200);

	    } catch (Exception $error) {
			    return response()->json([
			      'status_code' => 500,
			      'message' 	=> 'Error in Login',
			      'error' 		=> $error,
			    ]);
		}


    }


    public function signUp(Request $request)
    {
    	$rules = [
	        'email'    => 'unique:users|required|min:3',
	        'password' => 'required|min:5',

	    ];

    	$validator = Validator::make($request->all(), $rules);
	    if ($validator->fails()) {

	    	return response()->json([
                    'status'  => false,
                    'message' => $validator->errors(),
                    'code'    => 404
                ], 404); 
	    	
	        // return response()->json($validator->errors());
	    }

	    $user = User::create([

            'email' 	=> $request->email,
            'password'  => Hash::make($request->password),
            'form_step' => 0,
            'role_id'	=> 1 // role id 1 for app user

        ]);

        $user->sendApiEmailVerificationNotification();

 
       return response()->json([
       	"data" 		=> $user,
       	"message" 	=> "Verification email has been sent to your account",
       	 "code" 	=> 200
       ]);

    }

    public function forgot_password(Request $request)
    {
    	$input = $request->all();
	    $rules = array(
	        'email' => "required|email",
	    );

	    $validator = Validator::make($input, $rules);
	    if ($validator->fails()) {
	        $arr = array("status" => 400, "message" => $validator->errors()->first(), "data" => array());
	    } else {
	        try {
	            $response = Password::sendResetLink($request->only('email'), function (Message $message) {
	                $message->subject('RUSD - Reset Password');
	            });
	            switch ($response) {
	                case Password::RESET_LINK_SENT:
	                    return \Response::json(array("status" => 200, "message" => trans($response), "data" => array()));
	                case Password::INVALID_USER:
	                    return \Response::json(array("status" => 400, "message" => trans($response), "data" => array()));
	            }
	        } catch (\Swift_TransportException $ex) {
	            $arr = array("status" => 400, "message" => $ex->getMessage(), "data" => []);
	        } catch (Exception $ex) {
	            $arr = array("status" => 400, "message" => $ex->getMessage(), "data" => []);
	        }
	    }
	    return \Response::json($arr);
    }

 //    public function change_password(Request $request)
	// {
	//     $input = $request->all();
	//     $userid =  Auth::user()->id;
	//     $rules = array(
	//         'old_password' => 'required',
	//         'new_password' => 'required|min:6',
	//         'confirm_password' => 'required|same:new_password',
	//     );
	//     $validator = Validator::make($input, $rules);
	//     if ($validator->fails()) {
	//         $arr = array("status" => 400, "message" => $validator->errors()->first(), "data" => array());
	//     } else {
	//         try {
	//             if ((Hash::check(request('old_password'), Auth::user()->password)) == false) {
	//                 $arr = array("status" => 400, "message" => "Check your old password.", "data" => array());
	//             } else if ((Hash::check(request('new_password'), Auth::user()->password)) == true) {
	//                 $arr = array("status" => 400, "message" => "Please enter a password which is not similar then current password.", "data" => array());
	//             } else {
	//                 User::where('id', $userid)->update(['password' => Hash::make($input['new_password'])]);
	//                 $arr = array("status" => 200, "message" => "Password updated successfully.", "data" => array());
	//             }
	//         } catch (\Exception $ex) {
	//             if (isset($ex->errorInfo[2])) {
	//                 $msg = $ex->errorInfo[2];
	//             } else {
	//                 $msg = $ex->getMessage();
	//             }
	//             $arr = array("status" => 400, "message" => $msg, "data" => array());
	//         }
	//     }
	//     return \Response::json($arr);
	// }

	public function logout(Request $request) {
	  Auth::logout();
	  return redirect('/login');
	}


	public function sendForgetPasswordCode(Request $request)
    {

    	try {

    		$validator = Validator::make($request->all(), [
	            'email' => 'required|email',
	        ]);

	        if($validator->fails()){
	            return response()->json([
                    'status'  => false,
                    'data'	  => NULL,
                    'message' => 'validation error',
                    'code'    => 422
                ], 422); 
	        }

	        $email = User::where('email',$request->email)->get()->count();

	        if ( $email == 0 ) {
	        	return response()->json([
                    'status'  => false,
                    'data'	  => NULL,
                    'message' => 'Email not found',
                    'code'    => 422
                ], 422); 
	        }


	        $code = mt_rand(10000, 99999);
		    $user = User::where('email',$request->email)->update([
	            'verify_code'		=> $code,
	        ]);

	         Mail::send('mails.verfication',['data'=>['code'=>$code]], function ($message)use($request) {

			    $message->to($request->email);
			});


	        // Mail::to($request->email)->send(new VerificationCode(['code'=>$code]));


	        return response()->json([
	       	"status"	=> true,
	     //   	'token' 	 => $tokenResult,
		    // 'token_type' => 'Bearer',
	       	"data" 		=> NULL,
	       	"message" 	=> "Verification email has been sent to your account",
	       	"code" 		=> 200
	       ]);

    		
    	} catch (Exception $e) {
    		
    	}

    }

    public function checkResetCode(Request $request)
	{
		$validator = Validator::make($request->all(), [
	            'code' 			=> 'required',
	        ]);


	        if($validator->fails()){
	            return response()->json([
                    'status'  => false,
                    'data' 	  => NULL,
                    'message' => $validator->errors(),
                    'code'    => 422
                ], 422);


                $statusCode = 422; 
	        }

		try {

			$checkUserCode = User::where('verify_code',$request->code)->first();

			if (empty($checkUserCode)) {
				$res = [
	        			"status" 	=> false,
	        		 	"data" 		=> NULL,
	        			"message"	=> "Please enter valid Verification Code",
	        		 	"code" 		=> 422
	        		];

				$statusCode = 422;

			}else{

				$res = [
	        			"status" 	=> true,
	        		 	"data" 		=> NULL,
	        			"message"	=> "Verification Code matched",
	        		 	"code" 		=> 200
	        		];

				$statusCode = 200;


			}
			
		} catch (Exception $e) {

			return response()->json([
			      'status' 		=> false,
	        	  'data' 		=> NULL,
			      'message' 	=> 'Error in Code',
			      'code' 		=> 500,
			    ],500);

		}

		return \Response::json($res,$statusCode);
	}

    public function resetPassword(Request $request)
    {
    	$statusCode = 200;

    	$input = $request->all();
	    $rules = array(
	    	'code' 				=> 'required',
	        'new_password'	 	=> 'required',
	        // 'new_password' 		=> 'required|string|min:10|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/',
	        // 'confirm_password' 	=> 'required|same:new_password',
	    );
	    $validator = Validator::make($input, $rules);
	    if ($validator->fails()) {

	        $arr = [
	        	"status" 	=> false, 
	        	"message" 	=> 'validation error', 
	        	"data" 		=> NULL,
	        	"code" 		=> 422
	        ];

	        $statusCode = 422;

	    } else {
	 
	 
	        try {
	        
	        	$checkUserCode = User::where('verify_code',$request->code)->first();

	        	if (empty($checkUserCode)) {

	        		$arr = [
	        			"status" 	=> false,
	        		 	"data" 		=> NULL,
	        			"message"	=> "Please enter valid Verification Code",
	        		 	"code" 		=> 422
	        		];

	        		$statusCode = 422;


	        	}else{
	        	
		            User::where('verify_code', $request->code)->update([
		            	'password' 		=> Hash::make($input['new_password']),
		            	'verify_code'	=> 'verfied'
		            ]);

		            $arr = array("status" => 200, "message" => "Password updated successfully.", "data" => NULL);
	        		$statusCode = 200;


	            }

	        } catch (\Exception $ex) {

	            if (isset($ex->errorInfo[2])) {
	                $msg = $ex->errorInfo[2];
	            } else {
	                $msg = $ex->getMessage();
	            }

	            $arr = [
	            	"status"	=> false, 
	            	"data" 		=> NULL , 
	            	"message" 	=> $msg, 
	            	"code" 		=> 422
	            ];
	            $statusCode = 422;
	        }
	    }

	    return \Response::json($arr,$statusCode);

    }
    



}
