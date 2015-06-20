<?php 

require_once( 'Facebook/FacebookRequest.php' );
require_once( 'Facebook/FacebookSession.php' );
require_once( 'Facebook/FacebookRedirectLoginHelper.php' );
require_once( 'Facebook/FacebookResponse.php' );
require_once( 'Facebook/FacebookSDKException.php' );
require_once( 'Facebook/FacebookRequestException.php' );
require_once( 'Facebook/FacebookAuthorizationException.php' );
require_once( 'Facebook/FacebookServerException.php' );
require_once( 'Facebook/GraphObject.php' );
require_once( 'Facebook/GraphUser.php' );
require_once( 'Facebook/GraphLocation.php' );
require_once( 'Facebook/GraphSessionInfo.php' );
require_once( 'Facebook/Entities/AccessToken.php' );
require_once( 'Facebook/HttpClients/FacebookHttpable.php' );
require_once( 'Facebook/HttpClients/FacebookCurlHttpClient.php' );
require_once( 'Facebook/HttpClients/FacebookCurl.php' ); 

require_once( 'connToDB.php' );

use Facebook\FacebookSession;
use Facebook\FacebookRequest;
use Facebook\FacebookRequestException;
use Facebook\GraphUser;
use Facebook\GraphObject;
use Facebook\GraphLocation;
use Facebook\FacebookRedirectLoginHelper;
use Facebook\FacebookResponse;
use Facebook\FacebookSDKException;
use Facebook\FacebookServerException;
use Facebook\GraphSessionInfo;
use Facebook\FacebookAuthorizationException;
use Facebook\Entities\AccessToken;
use Facebook\HttpClients\FacebookHttpable;
use Facebook\HttpClients\FacebookCurlHttpClient;
use Facebook\HttpClients\FacebookCurl;

header('Content-type: text/html; charset=utf-8');
include_once( 'connToDB.php' );

date_default_timezone_set('Europe/Athens');


FacebookSession::setDefaultApplication('427999317379719', '74c48d7edd640209b90e455f93dda31d');
FacebookSession::enableAppSecretProof(false);

$accTok = 'CAAGFQ1tGyocBALyoT1U7J2RYmDODFVZBzfRJWlm2tDm2tnH2nqhcGnBXnrIMMb4mZA4H8S3ZCvQho1ooNJJZAOUFBTrQZCbGYAOtHYbcZA8to2ucuzs6TwyaUJTMvnwtLZCWSrEdFsAztO009cleZAxRZBj2oZB0R5mDZBsXxrTGfY8vveULCv8JKkMCvVZBq9sPNbZBOWLz7IUTMYuV48O7V1qwQ';

$session = new FacebookSession($accTok);

$add = mysqli_real_escape_string($conn, $_POST['addData']); 
//$add = "sdvcnpidvwpdivjwvdpij";

//count the rows before and after to check if the sql query was successful
$rowNum = mysqli_affected_rows($conn);


// Get cURL resource
  $curl = curl_init();
  // Set some options - we are passing in a useragent too here
  curl_setopt_array($curl, array(
      CURLOPT_RETURNTRANSFER => 1,
      CURLOPT_URL => 'https://graph.facebook.com/oauth/access_token?grant_type=fb_exchange_token&client_id=427999317379719&client_secret=74c48d7edd640209b90e455f93dda31d&fb_exchange_token=' . $accTok,
  ));
  // Send the request & save response to $resp
  $resp = curl_exec($curl);
  echo $resp;
  // Close request to clear up some resources
  curl_close($curl);

//$facebook = new Facebook();
//if($facebook->api($add)) {
	/* make the API call */
	$request = new FacebookRequest(
	  $session,
	  'GET',
	  '/'. $add .'/events'
	);

	$response = $request->execute();
	$graphObject = $response->getGraphObject()->asArray();

	/*  print_r($graphObject);
	  print_r($graphObject['data']['1']->id);
	  print_r($graphObject['data'][8]->name);
	*/
	$nextPageRequest = $response->getRequestForNextPage();  //get nextPageRequest


	foreach ($graphObject['data'] as $value) {
	    //echo "\n" . $value->id .  " -> " . $value->name . " -> " . $value->location . " -> " . $value->start_time . "\n";

	    $request1 = new FacebookRequest($session, 'GET', '/' . $value->id);
	    $response1 = $request1->execute();
	    $graphObject1 = $response1->getGraphObject()->asArray();

	    $owner = $graphObject1['owner']->name;
	    //echo $owner;
	    $descr = mysql_real_escape_string($graphObject1['description']);


	    $datetime = new DateTime($graphObject1['start_time']);
	    /*print_r($tempDatetime);
	    $date = $tempDatetime->format('d-m-Y');
	    $time = $tempDatetime->format('H:i');
	    echo $date . "->" . $time . "\n";
	    $datetime = DateTime::createFromFormat('$date', '$time');
	    print_r($datetime);
	*/
	    $dateFormatted = $datetime->format('Y-m-d H:i:s');

	    //echo $date_e . "\n" . $time_e . "\n";
	    //echo "\n" . $graphObject1['description'] . "\n";
	    //echo "Owner's name: " . $graphObject1['owner']->name . "\n";


	    $request2 = new FacebookRequest($session, 'GET', '/' . $value->id . "/picture?redirect=false");
	    $response2 = $request2->execute();
	    $graphObject2 = $response2->getGraphObject()->asArray();
	    $cover_photo = $graphObject2['url'];
	    //echo $graphObject2['url'] . "\n";
	    //utf8_decode($value->name);
	    $value->name = mysql_real_escape_string($value->name);

	    $sql = "INSERT INTO eventsData(id, name, dateNtime, cover_photo_url, owner_name, description, place) VALUES($value->id, '$value->name', '$dateFormatted', '$cover_photo', '$owner', '$descr', '$value->location')";
	    //$sql = "INSERT INTO eventsData(id) VALUES($value->id)";

	    if ($conn->query($sql) === TRUE) {
	        echo "\n New record created successfully \n";
	    } else {
	        echo "\nError: " . $sql . "<br>" . $conn->error . "\n";
	    }

	  }

	  if($rowNum != mysqli_affected_rows($conn)) {
		echo '{"deleteData": 0 }';
	//	die('Delete failed! ' . mysql_error());
		//echo "DELETE failed: $conn<br />" . 
	    //mysql_error() . "<br /><br />";
	} else {
		echo '{"deleteData": 1 }';
		//echo "DELETE succeed: $conn<br />";
	}
//} else {
//	print_r("error");
//}


  $conn->close();








?>