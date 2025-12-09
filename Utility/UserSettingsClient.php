<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'PageClient.php';
require_once 'IClient.php';

/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 4/25/2017
 * Time: 4:44 PM
 */
class UserSettingsClient extends PageClient implements IClient {

    public function __construct(array $data = null) {
        parent::__construct($data);

        $this->addStylesheet('css/settings.css');
        $this->addScript('js/settings.js');
    }

    public function printPage() {

        $email = $this->User->email;
        $password = "";
        $password2 = "";
        $emailErrorHtml = "";
        $passwordErrorHtml = "";
        $errMsg = "";
        $passwordDisabled = "disabled='disabled'";
        $passwordStyle = "";
        $displayResetPasswordLink = "";
        $resetPasswordInput = 0;
        if (array_key_exists("errMsg", $_SESSION)) {

            $errMsg = $_SESSION['errMsg'];
            $_SESSION['errMsg'] = "";
            unset($_SESSION['errMsg']);

            $inputFields = $_SESSION['inputFields'];
            $_SESSION['inputFields'] = "";
            unset($_SESSION['inputFields']);

            if (array_key_exists("resetPasswordInput", $inputFields)) {
                $resetPasswordInput = $inputFields['resetPasswordInput'];
            }


            $email = $inputFields['email'];
            $password = $inputFields['password'];
            $password2 = $inputFields['password2'];

            if (array_key_exists("email", $errMsg)) {
                $emailErrorHtml = "<span class=\"error\">" . $errMsg['email'] . "</span>";
            }
            if (array_key_exists("password", $errMsg)) {
                $passwordErrorHtml = "<span class=\"error\">" . $errMsg['password'] . "</span>";
                $passwordDisabled = "";
                $passwordStyle = "style='background: #FAFFBD;'";
                $displayResetPasswordLink = "style='display: none;'";
            }
        }

        $msg = "";
        if (array_key_exists('msg', $_SESSION)) {
            $msg = "<h4 id='msg'>" . $_SESSION['msg'] . "</h4>";
            $_SESSION['msg'] = "";
            unset($_SESSION['msg']);
        }

        $html = "
            <div class='container'>
                <div class='row pad-top gap-top'>
                    <div class='one mobile third'><h5>User Settings</h5></div>
                    <div class='one mobile third'>$msg</div>
                </div>

                <form name='frmSettings' id='frmSettings' action='settingsb.php' method='post'>
                <input type='hidden' name='resetPasswordInput' id='resetPasswordInput' value='$resetPasswordInput' />
                <input type='hidden' name='action' id='action' value='1' />
                
                <div class='row'>
                    <div class='one mobile whole gap-top'>
                        <label for='email'>Email</label>
                        $emailErrorHtml
                        <input type='text' name='email' id='email' value='$email' />
                    </div>
                </div>

                <div class='row pad-top'>
                    <div class='one mobile half pad-right double-gap-top'>
                        <label for='password'>Password</label>
                        <a href=\"javascript:void(0)\" id=\"resetPassword\" $displayResetPasswordLink>Click here to reset password</a>
                        $passwordErrorHtml
                        <input type='password' name='password' id='password' value='$password' $passwordStyle autocomplete='off' $passwordDisabled />
                    </div>
                    <div class='one mobile half double-gap-top'>
                        <label for='password2'>Verify Password</label>
                        <input type='password' name='password2' id='password2' value='$password2' $passwordStyle autocomplete='off' $passwordDisabled />
                    </div>
                </div>

                <div class='row pad-top'>
                    <div class='one mobile whole'>
                        <button class='green submit' id='btnAddSubmit'>Submit</button>
                    </div>
                </div>
                </form>
            </div>
        ";

        echo $html;
    }
}