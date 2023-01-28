<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseController as BaseController;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Newsletter;
use App\Models\Role;
use App\Models\Info;
use App\Models\Social;
use App\Models\Roleuser;
use Illuminate\Support\Facades\Auth;
use Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\PasswordReset;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use telesign\sdk\messaging\MessagingClient;

class UserController extends BaseController
{
    
    
    public function roles()
    {
        $roles = Role::where('id', '!=', 1)->get();
        return response()->json([
                    'data' => json_decode($roles)
                ]);
    }
    
    
    public function sms(Request $request)
    {
          $customer_id = "E6F75B68-0E00-4BEE-AD9F-B54FCF52B54C";
          $api_key = "9OPjc8RO0ea25oD66TI6VDlvsUAhT4pWArqZzM6ZVJQjK957ZGUW+ZNeTpk60lrwBviqj8ZRlwpWLjw50tsvRw==";
          $phone_number = $request->phone_number;
           //$phone_number = "2237674717852";
          $message = $request->message;
          $message_type = "ARN";
          $messaging = new MessagingClient($customer_id, $api_key);
          $response = $messaging->message($phone_number, $message, $message_type);
          if($response){
              $tatus = true;
          }
          else{
              $tatus = false;
          }
          return response()->json([
                   'status' =>$tatus
                ]);
    }
    
    
    public function newsletter(Request $request){
        
        $newsletters = Newsletter::query()->where('email', $request->email)->count();
        if($newsletters > 0){
                    return response()->json([
                                'data' => json_decode($newsletters),
                                'status' => 'exist',
                    ]); 
        }
        else{
                    $newsletter = new Newsletter;
                    $newsletter->email = $request->email;
                    $newsletter->save();
                    return response()->json([
                                'data' => json_decode($newsletter),
                                'status' => 'success',
                    ]); 
        }

    }
    
    
    public function emailexist(Request $request){
        
        $user = User::query()->where('email', $request->email)->get();
        return response()->json([
                    'data' => json_decode($user),
                    'status' => 'success',
                ]);
    }
    
    public function register(Request $request){
        
        $validator = Validator::make($request->all(), [
            'role' => 'required',
            'email' => 'required|email',
            'password' => 'required',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
   
        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);
        $success['token'] =  $user->createToken('MyApp')->plainTextToken;
        $user->sendEmailVerificationNotification();
        
        //Creae role user
        $role = new Roleuser;
        $role->user = $user->id;
        $role->role = $request->role;
        $role->save();
        
        //Creae infos user
         $infos = new Info;
         $infos->user = $user->id;
         $infos->object = 'null';
         $infos->save();
         

        
        return $this->sendResponse($success, 'User register successfully.');
    }
    
    public function login(Request $request)
    {
        $credentials = request(['email', 'password']);
        if (!Auth::attempt($credentials)) {
            return response()->json([
                    'message' => "Unauthorized"
                ]);  
        }
        $user = $request->user();
        if($user->email_verified_at == null){
        return response()->json([
                    'data' => $user,
                    'message' => "Unverified"
                ]);  
        }
        //$token = $user->createToken('auth_token')->plainTextToken;
        $token = $user->createToken('MyApp')->plainTextToken;
        
        $infos = Info::query()->where("user", $user->id)->get();
        $role = Roleuser::query()->where("user", $user->id)->get();
        $cocial = Social::query()->where("user", $user->id)->get();
        //Social
        
        return response()->json([
                    'data' => $user,
                    'infos' => $infos,
                    'role' => $role,
                    'social' => $cocial,
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                    'message' => "Success"
                ]);

    }
    
    public function reset(Request $request){
        $input = $request->only('email');
        
        $user = User::query()->where("email", $input)->get();
        if($user->isEmpty()){
            $message = "Email could not be sent to this email address";
            return response()->json([
                    'status' => 'No existed',
                    'message' => $message
                ]);
        }
        else{
           $response =  Password::sendResetLink($input);
            if($response == Password::RESET_LINK_SENT){
              $message = "Mail send successfully";
              return response()->json([
                    'status' => 'Success',
                    'message' => $message
                ]);
                
              return $this->sendResponse($message, 'Success');
            }else{
             $message = "Email could not be sent to this email address";
             return response()->json([
                    'status' => 'Error',
                    'message' => $message
                ]);
            } 

        }
    }
    
    
        
    public function resent(Request $request){
        $user = User::where('email',$request->email)->first();
        $user->sendEmailVerificationNotification();
            return response()->json([
                    'status' => 'success',
                    'message' => 'resent email'
                ]);
    }
    
    
    
    public function editpass(Request $request){
        $user = User::where('id',$request->id)->first();
        if (!Hash::check($request->current,$user->password)) {
        return response()->json([
                    'message' => 'Error'
                ]);
            //return response()->json(['error' => 'Mot de passe différent'], 401);
        } else {
            $user = User::query()->find($request->id);
            $user->password = Hash::make($request->password);
            $user->update();
           // return $this->sendResponse('Success', 'School');
            return response()->json([
                    'data' => $user
                ]);
        }
    }
    
    public function photo(Request $request){
            if ($request->image) {
                //The base64 encoded image data
                $image_path =  "images/users/"; //path location
                $image_64 = $request->image;
                // exploed the image to get the extension
                $extension = explode(';base64',$image_64);
                //from the first element
                $extension = explode('/',$extension[0]);
                // from the 2nd element
                $extension = $extension[1];
        
                $replace = substr($image_64, 0, strpos($image_64, ',')+1);
        
                // finding the substring from 
                // replace here for example in our case: data:image/png;base64,
                $image = str_replace($replace, '', $image_64);
                // replace
                $image = str_replace(' ', '+', $image);
                // set the image name using the time and a random string plus
                // an extension
                $imageName = time().'_'.Str::random(20).'.'.$extension;
                // save the image in the image path we passed from the 
                // function parameter.
                Storage::disk('public')->put($image_path.'/' .$imageName, base64_decode($image));
                // return the image path and feed to the function that requests it
                $user = User::query()->find($request->user);
                $user->photo = "https://resokom.hellomoney.cm/storage/".$image_path.'/'.$imageName;
                $user->update();
                return response()->json([
                            'data' => $user,
                            'message' => 'New user'
                        ]);
            }
    }
    
    
    public function editprofile(Request $request){
        $info = Info::query()->where("user", $request->user)->first();
        //$info = Info::query()->find($infos->id);
        $info-> object = $request->object;
        $info->update();
        return response()->json([
                    'data' => $info
                ]);
    }
    
    
    public function users(Request $request){
        $users = Roleuser::query()->where("role", $request->role)->with("infos", "social")->get();
        return response()->json([
                    'data' => $users
                ]);

    }
    
}