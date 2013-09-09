<?php



// Prepare SoapHeader parameters 
$sh_params = array( 
            	'Username'    =>    'rob', 
            	'Password'    =>    'gws'
			);


header('Content-Type: text/plain-text;');

error_reporting(E_ALL);

$soapClient = new SoapClient("https://member.goodpractice.net/gwsv2.asmx?wsdl",array('trace' => 1,'exceptions'=>1)); 

$headers = array();

//Create the authroisation header			
$strHeaderComponent_Session = "<AuthorisationHeader xmlns=\"http://member.goodpractice.net/gwsv2/\"><UserName>{$sh_params['Username']}</UserName><Password>{$sh_params['Password']}</Password></AuthorisationHeader>";
$objVar_Session_Inside = new SoapVar($strHeaderComponent_Session, XSD_ANYXML, null, null, null);
$headers[] = new SoapHeader('https://member.goodpractice.net/gwsv2/', 'AuthorisationHeader', $objVar_Session_Inside);

//Add the header to the SOAP client 
if(!$soapClient->__setSoapHeaders($headers)){
	die('Failed to set SOAP Client Headers');
}

//Check for authentication
echo "Authenticated = " . (($soapClient->Authenticated()->AuthenticatedResult)?'True':'Failed' ). "\n";

echo "\n{$soapClient->__getLastRequestHeaders()}\n";



//Prepare search
$ap_param = array('searchTerms'=>'test','tags'=>'test2','contentCategories'=>'test3');
//$ap_param = array('test','test2','test3');
$ap_param = array('tags'=>'Exercises');

$error = 0; 
try {
	//Attempt search 
    $search = $soapClient->__call("GetFilterableSearchResults", array($ap_param));
	
	echo "\n{$soapClient->__getLastRequestHeaders()}\n";
	echo "\nSearch Results\n";
	
	print_r($search);
	 
} catch (SoapFault $fault) { 
    $error = 1; 
    echo "Sorry, client returned the following \nERROR: ".$fault->faultcode."-".$fault->faultstring."\n"; 
}


exit;
/****
 * Only way to have got it work in first instance 
 */
 require_once('lib/nusoap/nusoap.php');

$soapClient = new nusoap_client("https://member.goodpractice.net/gwsv2.asmx?wsdl",true);

$stHeader = '<AuthorisationHeader xmlns="http://member.goodpractice.net/gwsv2/">
      <UserName>rob</UserName>
      <Password>gws</Password>
    </AuthorisationHeader>';

$soapClient->setHeaders($stHeader);

print_r($soapClient->call('Authenticated'));

echo "\n/***\n*\tNew way of doing it\n***/\n";


 
