<?php

namespace App\Http\Controllers;

use App\menu;
use App\menu_items;
use App\Pledge;
use App\ussd_response;
use App\ussdLog;
use App\Ussduser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UssdController extends Controller
{
    public function index()
    {

        error_reporting(0);
        header('Content-type: text/plain');
        set_time_limit(100000);

        //get inputs
        $sessionId = $_REQUEST["sessionId"];
        $serviceCode = $_REQUEST["serviceCode"];
        $phoneNumber = $_REQUEST["phoneNumber"];
        $text = $_REQUEST["text"];   //

        $data = ['phone' => $phoneNumber, 'text' => $text, 'service_code' => $serviceCode, 'session_id' => $sessionId];

        //log USSD request
        ussdLog::create($data);

        //verify that the user exists
        $no = substr($phoneNumber, -9);

        $user = Ussduser::where('phone', "0" . $no)->orWhere('phone', "254" . $no)->orWhere('email', "254" . $no)->first();

        if (self::user_is_starting($text)) {

            //lets get the home menu
            //reset user
            if($user){
                self::resetUser($user);
            }else{
                //create a new user
                $user = new Ussduser();
                $user->phone = "254" . $no;
                $user->name = "254" . $no;
                $user->email = "254" . $no;
                $user->password = bcrypt("254" . $no);
                $user->save();
            }
            //home Page
            $menu = menu::find(1);
            $response = self::nextMenuSwitch($user,$menu);
//            $response = self::getMenuAndItems(1, $user);

            self::sendResponse($response,1,$user);
//            self::sendResponse("Get your name today.".PHP_EOL.$response, 1, $user);
        } else {
            //message is the latest stuff
            $result = explode("*", $text);
            if (empty($result)) {
                $message = $text;
            } else {
                end($result);
                // move the internal pointer to the end of the array
                $message = current($result);
            }
            //switch based on user session

            switch ($user->session) {

                case 0 :
                    //neutral user
                    break;
                case 1 :
                    //user authentication
                    $response = self::continueUssdProgress($user, $message);
                    break;
                case 2 :
                    //confirm USSD Process
                    $response = self::confirmUssdProcess($user, $message);
                    break;
                case 3 :
                    //Go back menu
                    $response = self::confirmUssdProcess($user, $message);
                    break;
                default:
                    break;
            }

            self::sendResponse($response, 1, $user);
        }


    }
    public function user_is_starting($text)
    {
        if (strlen($text) > 0) {
            return FALSE;
        } else {
            return TRUE;
        }
    }

    public function getMenuAndItems($id,$user){
        $user->menu_id = $id;
        $user->session = 1;
        $user->progress = 1;
        $user->save();
        //get home menu
        $menu = menu::find($id);
        $menu_items = self::getMenuItems($menu->id);
        $i = 1;
        $response = $menu->title . PHP_EOL;
        foreach ($menu_items as $key => $value) {
            $response = $response . $i . ": " . $value->description . PHP_EOL;
            $i++;
        }
        self::sendResponse($response, 1, $user);
    }

    public static function getMenuItems($menu_id)
    {
        $menu_items = menu_items::whereMenuId($menu_id)->get();
        return $menu_items;

    }

    public function resetUser($user)
    {
        $user->session = 0;
        $user->progress = 0;
        $user->menu_id = 0;
        $user->difficulty_level = 0;
        $user->confirm_from = 0;
        $user->menu_item_id = 0;
        return $user->save();
    }

    public function sendResponse($response, $type, $user = null)
    {
        $sessionId = $_REQUEST["sessionId"];
        $serviceCode = $_REQUEST["serviceCode"];
        $phoneNumber = $_REQUEST["phoneNumber"];

        $data = ['phone' => $phoneNumber, 'text' => $response, 'service_code' => $serviceCode, 'session_id' => $sessionId];

        //log USSD request
        ussdLog::create($data);

        if ($type == 1) {
            $output = "CON ";
        } elseif ($type == 2) {
            $output = "CON ";
            $response = $response . PHP_EOL . "1. Back to main menu" . PHP_EOL . "2. Log out";
            $user->session = 4;
            $user->progress = 0;
            $user->save();
        } else {
            $output = "END ";
        }
        $output .= $response;
        header('Content-type: text/plain');
        echo $output;
        exit;
    }

    public function continueSingleProcess($user, $message, $menu)
    {
        $response = '';
        //validate input to be numeric
        self::storeUssdResponse($user, $message);
        //validate response

        if($user->menu_item_id == 1){
            //validate name
            if (1 === preg_match('~[0-9]~', $message)) {
                $response = "Name should not contain numbers." . PHP_EOL;
                $user->progress = $user->progress - 1;
                $user->save();
            } else {

                $exploded_name = explode(" ", $message);
                if (count($exploded_name) < 2) {
                    $response = "Enter at least two names.". PHP_EOL;
                    $user->progress = $user->progress - 1;
                    $user->save();
                } else {
                    if ((strlen($exploded_name[0]) < 2) || (strlen($exploded_name[1]) < 2)) {
                        $response = "A name must contain at least two characters.". PHP_EOL;
                        $user->progress = $user->progress - 1;
                        $user->save();
                    }else{
                        $user->name = $message;
                        $user->save();
                    }
                }
            }
        }
        if($user->menu_item_id == 2){
            //validate email
            $rules = [
                'email' => 'required|email',
            ];
            $data = [
                'email' => $message
            ];
            $validator = Validator::make($data, $rules);
            if ($validator->fails()) {
                $response = "Invalid Email".PHP_EOL;
                $user->progress = $user->progress - 1;
                $user->save();
            }else{
                $user->email = $message;
                $user->save();
            }
        }

        if($user->menu_item_id == 3){
            //validate Chasis
            if(strlen($message)==14 || strlen($message)==17){
                $user->chasis = $message;
                $user->save();
            }else{ 
                $response = "Invalid Chasis".PHP_EOL;
                $user->progress = $user->progress - 1;
                $user->save();
            }
        }
        $step = $user->progress+1;

        $menuItem = menu_items::whereMenuIdAndStep($menu->id, $step)->first();
        if ($menuItem) {
            $user->menu_item_id = $menuItem->id;
            $user->menu_id = $menu->id;
            $user->progress = $step;
            $user->save();
            return $response.$menuItem->description;
        } else {
            $msg = self::confirmBatch($user, $menu);
            return $response.$msg;

        }
    }

    public function validateResponse($message,$user){


        $is_valid = TRUE;

        switch ($user->menu_item_id) {

            case 0 :
                //neutral user
                break;
            case 1 :
                //validate name
                if (1 === preg_match('~[0-9]~', $message)) {
                    $response = "Name should not contain numbers." . PHP_EOL;
                    $user->progress = $user->progress - 1;
                    $user->save();
                } else {

                    $exploded_name = explode(" ", $message);
                    if (count($exploded_name) < 2) {
                        $response = "Enter at least two names.";
                        $user->progress = $user->progress - 1;
                        $user->save();
                    } else {
                        if ((strlen($exploded_name[0]) < 2) || (strlen($exploded_name[1]) < 2)) {
                            $response = "A name must contain at least two characters.";
                            $user->progress = $user->progress - 1;
                            $user->save();
                        }else{
                            return TRUE;
                        }
                    }
                }
                break;
            case 2 :
                //echo "Main Menu";
                $response = self::authenticateUser($user, $message);
                break;
            case 3 :
                //validate the TLD
                $is_valid = self::validateDomain($user,$message);
                break;
            case 4 :
                //Go back menu
                $response = self::confirmGoBack($user, $message);
                break;
            case 5 :
                //Go back menu
                $response = self::resetPIN($user, $message);
                break;
            case 6 :
                //accept terms and conditions
                $response = self::acceptTerms($user, $message);
                break;
            default:
                break;
        }

        return $is_valid;
    }
    public function postUssdConfirmationProcess($user)
    {

        switch ($user->confirm_from) {
            case 1:
                //create user profile
                $name = ussd_response::whereUserIdAndMenuIdAndMenuItemId($user->id, 1, 1)->orderBy('id', 'DESC')->first()->response;
                $email = ussd_response::whereUserIdAndMenuIdAndMenuItemId($user->id, 1, 2)->orderBy('id', 'DESC')->first()->response;
                $chasis = ussd_response::whereUserIdAndMenuIdAndMenuItemId($user->id, 1, 3)->orderBy('id', 'DESC')->first()->response;

                $user->name = $name;
                $user->email = $email;
                $user->chasis = $chasis;
                $user->save();
                $msg = "Thanks for your verification.".PHP_EOL."Regards, MBCK";
                self::sendResponse($msg,3,$user);
                break;
            default :
                return true;
                break;
        }

    }

    public function confirmUssdProcess($user, $message)
    {
        $menu = menu::find($user->menu_id);
        if (self::validationVariations($message, 1, "yes")) {
            //if confirmed

            if (self::postUssdConfirmationProcess($user)) {
                $response = $menu->confirmation_message;
            } else {
                $response = "We had a problem processing your request. Please contact Customer Care on 0704 000 999";
            }

            self::resetUser($user);

            $notify = new NotifyController();
            $notify->sendSms($user->phone_no, $response);

            self::sendResponse($response, 2, $user);

        } elseif (self::validationVariations($message, 2, "no")) {
            if ($user->menu_id == 3) {
                self::resetUser($user);
                $menu = menu::find(1);
                $user->menu_id = 2;
                $user->session = 2;
                $user->progress = 1;
                $user->save();
                //get home menu
                $menu = menu::find(1);
                $response = self::nextMenuSwitch($user,$menu);
                self::sendResponse($response, 1, $user);
            }


            $response = self::nextMenuSwitch($user, $menu);
            return $response;

        } else {
            //not confirmed
            $response = "Please enter 1 or 2";
            //restart the process
            $output = self::confirmBatch($user, $menu);

            $response = $response . PHP_EOL . $output;
            return $response;
        }


    }

    public function confirmBatch($user, $menu)
    {
        //confirm this stuff
        $menu_items = self::getMenuItems($user->menu_id);

        $confirmation = "Confirm: " . $menu->title;
        $amount = 0;
        foreach ($menu_items as $key => $value) {

            $response = ussd_response::whereUserIdAndMenuIdAndMenuItemId($user->id, $user->menu_id, $value->id)->orderBy('id', 'DESC')->first();
            if( $value->confirmation_phrase !="IGNORE"){
                $confirmation = $confirmation . PHP_EOL . $value->confirmation_phrase . ": " . $response->response;
            }
            $amount = $response->response;
        }
        $response = $confirmation .PHP_EOL."1. Confirm" . PHP_EOL . "2. Change Details";
        $user->session = 3;
        $user->confirm_from = $user->menu_id;
        $user->save();
        return $response;
    }

    public function continueUssdMenu($user, $message, $menu)
    {
        self::storeUssdResponse($user,$message);
        //verify response
        $menu_items = self::getMenuItems($user->menu_id);

        $i = 1;
        $choice = "";
        $next_menu_id = 0;
        foreach ($menu_items as $key => $value) {
            if (self::validationVariations(trim($message), $i, $value->description)) {
                $choice = $value->id;
                $next_menu_id = $value->next_menu_id;

                break;
            }
            $i++;
        }
        if (empty($choice)) {
            //get error, we could not understand your response
            $response = "We could not understand your response" . PHP_EOL;
            $i = 1;
            $response = $menu->title . PHP_EOL;
            foreach ($menu_items as $key => $value) {
                $response = $response . $i . ": " . $value->description . PHP_EOL;
                $i++;
            }

            return $response;
            //save the response
        } else {
            //there is a selected choice
            $menu = menu::find($next_menu_id);
            //next menu switch
            $response = self::nextMenuSwitch($user, $menu);
            return $response;
        }

    }

    public function nextMenuSwitch($user, $menu)
    {

        switch ($menu->type) {
            case 0:
                //authentication mini app

                break;
            case 1:
                //continue to another menu
                $menu_items = self::getMenuItems($menu->id);
                $i = 1;
                $response = $menu->title . PHP_EOL;
                foreach ($menu_items as $key => $value) {
                    $response = $response . $i . ": " . $value->description . PHP_EOL;
                    $i++;
                }
                $user->menu_id = $menu->id;
                $user->menu_item_id = 0;
                $user->progress = 0;
                $user->save();
//                self::continueUssdMenu($user,$message,$menu);
                break;
            case 2:
                //start a process
//                self::storeUssdResponse($user, $menu->id);

                $response = self::singleProcess($menu, $user, 1);
                return $response;

                break;
            case 3:
                self::infoMiniApp($user, $menu);
                break;
            default :
                self::resetUser($user);
                $response = "An authentication error occurred";
                break;
        }

        return $response;

    }

    public function singleProcess($menu, $user, $step)
    {

        $menuItem = menu_items::whereMenuIdAndStep($menu->id, $step)->first();
        if ($menuItem) {
            //update user data and next request and send back
            $user->menu_item_id = $menuItem->id;
            $user->menu_id = $menu->id;
            $user->progress = $step;
            $user->session = 1;
            $user->save();
            return $menu->title.PHP_EOL.$menuItem->description;
        }
    }

    public function storeUssdResponse($user, $message)
    {
        $data = ['user_id' => $user->id, 'menu_id' => $user->menu_id, 'menu_item_id' => $user->menu_item_id, 'response' => $message];
        return ussd_response::create($data);
    }
    //validation variations
    public function validationVariations($message, $option, $value)
    {
        if ((trim(strtolower($message)) == trim(strtolower($value))) || ($message == $option) || ($message == "." . $option) || ($message == $option . ".") || ($message == "," . $option) || ($message == $option . ",")) {
            return TRUE;
        } else {
            return FALSE;
        }

    }


    public function continueUssdProgress($user, $message)
    {
        $menu = menu::find($user->menu_id);
        //check the user menu

        switch ($menu->type) {
            case 0:
                //authentication mini app

                break;
            case 1:
                //continue to another menu
                $response = self::continueUssdMenu($user, $message, $menu);
                break;
            case 2:
                //continue to a processs
                $response = self::continueSingleProcess($user, $message, $menu);
                break;
            case 3:
                //infomation mini app
                //
                self::infoMiniApp($user, $menu);
                break;
            default :
                self::resetUser($user);
                $response = "An authentication error occurred";
                break;
        }

        return $response;

    }

}
