<?php
/**
 * Workrite nusoap authentication
 */

if ($_GET['authenticate'] != 5) {
    die('You do not have access permissions to execute this file');
}

include_once(dirname(__FILE__) . '/../../../config.php');

if (!isloggedin()) {
    die('You need to logged in to use this page');
}
/**
 * $cfg=array(
 *              'coSsoId'     => 'fa056d2b-9d40-4eb3-925a-9e5a86937d85',
 *              'coLogin'     => 'wr_wsuser@EastThamesGroup.co.uk',
 *              'coWsId'      => 'f13c6da7-a75e-4827-b34b-1421b9fc4cef',
 *              'coPassword'  => 'p56mx49q',
 *              'wsdlurl'     => 'https://www.workrite.co.uk/Services/Wr_Service.asmx?wsdl'
 *              'places'	  => 'Stella Heskey,member',
 *              'role'	      => 'student',
 *              'linktext'	  => 'Log in to E learning'
 *            );
 */
define("SUCCESS", 0);
define("FAILURE_LOGIN", 1);
define("FAILURE_USER_CREATION", 2);
define("FAILURE_NO_PLACE", 3);
define("FAILURE_NOTIFICATION", 4);
define("FAILURE_STATUS_CHANGE", 5);
define("FAILURE_LOCATION_MOVE", 6);
define("FAILURE_LOCATION_REMOVE", 7);
define("FAILURE_INVALID_PLACE", 8);
define("FAILURE_INVALID_MEMBER", 9);
define("FAILURE_INVALID_ROLE", 10);
define("FAILURE_INVALID_EMAIL", 11);
define("FAILURE_INVALID_PLACE_FORMAT", 12);
define("FAILURE_DUPLICATE_USER", 13);

$error_messages = array();

$error_messages[SUCCESS] = "operation was successful";
$error_messages[FAILURE_LOGIN] = "credentials check failed";
$error_messages[FAILURE_USER_CREATION] = "user creation failed";
$error_messages[FAILURE_NO_PLACE] = "failed to add user place";
$error_messages[FAILURE_NOTIFICATION] = "email notification failed";
$error_messages[FAILURE_STATUS_CHANGE] = "user status change failed";
$error_messages[FAILURE_LOCATION_MOVE] = "user location move(s) failed";
$error_messages[FAILURE_LOCATION_REMOVE] = "userplace deletion failed - at least one location must remain";
$error_messages[FAILURE_INVALID_PLACE] = "invalid place parameter";
$error_messages[FAILURE_INVALID_MEMBER] = "invalid member or movement type";
$error_messages[FAILURE_INVALID_ROLE] = "student cannot be added as manager of place";
$error_messages[FAILURE_INVALID_EMAIL] = "invalid email address supplied";
$error_messages[FAILURE_INVALID_PLACE_FORMAT] = "invalid format of place string, user not created";
$error_messages[FAILURE_DUPLICATE_USER] = "user not created - duplicate username";

$wsdl = get_config('soap_login', 'wsdlurl');
$link = get_config('soap_login', 'linktext');
?>
<html>
    <head><title>SSO SOAP Login</title>
        <style type="text/css">
            a {
                font-family: verdana;
                font-size: 9pt;
            }
        </style>
    </head>
    <body>
<?php
include_once('lib/nusoap.php');

$client = new soapclient($wsdl);
$client->debug_flag = false;

//stick in the stuff here to create the user
$cfg = array(
    'coSsoId' => get_config('soap_login','coSsoId'),
    'coWsId' => get_config('soap_login','coWsId'),
    'coLogin' => get_config('soap_login','coLogin'),
    'coPassword' => get_config('soap_login','coPassword'),
    'emailAddress' => $USER->email,
    'role' => get_config('soap_login','role'),
    'facilitator' => false,
    'firstName' => $USER->firstname,
    'surname' => $USER->lastname,
    'places' => get_config('soap_login','places'),
);
$result = $client->CreateUser($cfg);
$responsecode = $result->CreateUserResult;

//if successful, or if the user already exists, then we're good to go ...
if ($responsecode === SUCCESS || $responsecode === FAILURE_DUPLICATE_USER) {
    //get log in
    $cfg = array(
        'coSsoId' => get_config('soap_login','coSsoId'),
        'coLogin' => get_config('soap_login','coLogin'),
        'coPassword' => get_config('soap_login','coPassword'),
        'emailAddress' => $USER->email
    );

    $result = $client->GetLoginUrl($cfg);
    $wresult = $result->GetLoginUrlResult;
    ?>
            <a class="txt" href="<?php echo $wresult; ?>" target="_blank"><?php echo $link; ?></a>
            <?php
        } else {
            $error_message = $error_messages[$responsecode] ? $error_messages[$responsecode] : "No error code available";
            echo('Error encountered. ' . $error_message);
        }
        ?>

    </body>
</html>

