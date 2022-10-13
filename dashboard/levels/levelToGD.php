<?php
session_start();
include "../../incl/lib/connection.php";
include "../../incl/lib/Captcha.php";
require "../../incl/lib/XORCipher.php";
require_once "../../incl/lib/generatePass.php";
require_once "../../incl/lib/exploitPatch.php";
require_once "../../incl/lib/generateHash.php";
include "../../incl/lib/mainLib.php";
$gs = new mainLib();
include "../incl/dashboardLib.php";
$dl = new dashboardLib();
global $lrEnabled;
$dl->title($dl->getLocalizedString("levelToGD"));
$dl->printFooter('../');
if($lrEnabled) {
if(!isset($_SESSION["accountID"]) OR $_SESSION["accountID"] == 0) {
		$dl->printSong('<div class="form">
    	<h1>'.$dl->getLocalizedString("errorGeneric").'</h1>
		<form class="form__inner" method="post" action="../dashboard/login/login.php">
			<p>'.$dl->getLocalizedString("noLogin?").'</p>
	        <button type="submit" class="btn-song">'.$dl->getLocalizedString("LoginBtn").'</button>
  	  	</form>
		</div>', 'reupload');
  		die();
}
if(!empty($_POST["usertarg"]) AND !empty($_POST["passtarg"]) AND !empty($_POST["levelID"])AND !empty($_POST["server"])) {
  	if(!Captcha::validateCaptcha()) {
		$dl->printSong('<div class="form">
			<h1>'.$dl->getLocalizedString("errorGeneric").'</h1>
			<form class="form__inner" method="post" action="">
			<p>'.$dl->getLocalizedString("invalidCaptcha").'</p>
			<button type="submit" class="btn-song">'.$dl->getLocalizedString("tryAgainBTN").'</button>
			</form>
		</div>', 'reupload');
		die();
	}
  	if(empty($_POST["debug"])) $debug = 0;
	$usertarg = ExploitPatch::remove($_POST["usertarg"]);
	$passtarg = ExploitPatch::remove($_POST["passtarg"]);
	$levelID = ExploitPatch::remove($_POST["levelID"]);
	$server = trim($_POST["server"]);
	$query = $db->prepare("SELECT * FROM levels WHERE levelID = :level");
	$query->execute([':level' => $levelID]);
	$levelInfo = $query->fetch();
  	if(empty($levelInfo)) {
			$dl->printSong('<div class="form">
				<h1>'.$dl->getLocalizedString("errorGeneric").'</h1>
				<form class="form__inner" method="post" action="">
				<p>'.$dl->getLocalizedString("levelNotFound").'</p>
				<button type="submit" class="btn-song">'.$dl->getLocalizedString("tryAgainBTN").'</button>
				</form>
			</div>', 'reupload');
      		die();
	}
	$userID = $levelInfo["userID"];
	$accountID = $_SESSION["accountID"];
	$yourUserID = $gs->getUserID($accountID);
	if($yourUserID != $userID) { 
			$dl->printSong('<div class="form">
				<h1>'.$dl->getLocalizedString("errorGeneric").'</h1>
				<form class="form__inner" method="post" action="">
				<p>'.$dl->getLocalizedString("notYourLevel").'</p>
				<button type="submit" class="btn-song">'.$dl->getLocalizedString("tryAgainBTN").'</button>
				</form>
			</div>', 'reupload');
      		die();
	}
	$udid = "S" . mt_rand(111111111,999999999) . mt_rand(111111111,999999999) . mt_rand(111111111,999999999) . mt_rand(111111111,999999999) . mt_rand(1,9); //getting accountid
	$sid = mt_rand(111111111,999999999) . mt_rand(11111111,99999999);
	//echo $udid;
	$post = ['userName' => $usertarg, 'udid' => $udid, 'password' => $passtarg, 'sID' => $sid, 'secret' => 'Wmfv3899gc9'];
	$ch = curl_init($server . "/accounts/loginGJAccount.php");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
	$result = curl_exec($ch);
	curl_close($ch);
	if($result == "" OR $result == "-1" OR $result == "No no no"){
		if($result==""){
			$dl->printSong('<div class="form">
				<h1>'.$dl->getLocalizedString("errorGeneric").'</h1>
				<form class="form__inner" method="post" action="">
				<p>'.$dl->getLocalizedString("errorConnection").'</p>
				<button type="submit" class="btn-song">'.$dl->getLocalizedString("tryAgainBTN").'</button>
				</form>
			</div>', 'reupload');
          	die();
		}elseif($result=="-1"){
			$dl->printSong('<div class="form">
				<h1>'.$dl->getLocalizedString("errorGeneric").'</h1>
				<form class="form__inner" method="post" action="">
				<p>'.$dl->getLocalizedString("wrongNickOrPass").'</p>
				<button type="submit" class="btn-song">'.$dl->getLocalizedString("tryAgainBTN").'</button>
				</form>
			</div>', 'reupload');
          	die();
		}else{
			$dl->printSong('<div class="form">
				<h1>'.$dl->getLocalizedString("errorGeneric").'</h1>
				<form class="form__inner" method="post" action="">
				<p>'.$dl->getLocalizedString("robtopLol").'</p>
				<button type="submit" class="btn-song">'.$dl->getLocalizedString("tryAgainBTN").'</button>
				</form>
			</div>', 'reupload');
          	die();
		}
    }
	if(!is_numeric($levelID)){ //checking if the level id is numeric due to possible exploits
			$dl->printSong('<div class="form">
				<h1>'.$dl->getLocalizedString("errorGeneric").'</h1>
				<form class="form__inner" method="post" action="">
				<p>'.$dl->getLocalizedString("invalidPost").'</p>
				<button type="submit" class="btn-song">'.$dl->getLocalizedString("tryAgainBTN").'</button>
				</form>
			</div>', 'reupload');
      		die();
	}
	//TODO: move all file_get_contents calls like this to a separate function
	$levelString = file_get_contents("../../data/levels/$levelID");
	$seed2 = base64_encode(XORCipher::cipher(GenerateHash::genSeed2noXor($levelString),41274));
	$accountID = explode(",",$result)[0];
	$gjp = base64_encode(XORCipher::cipher($passtarg,37526));
	$post = ['gameVersion' => $levelInfo["gameVersion"], 
	'binaryVersion' => $levelInfo["binaryVersion"], 
	'gdw' => "0", 
	'accountID' => $accountID, 
	'gjp' => $gjp,
	'userName' => $usertarg,
	'levelID' => "0",
	'levelName' => $levelInfo["levelName"],
	'levelDesc' => $levelInfo["levelDesc"],
	'levelVersion' => $levelInfo["levelVersion"],
	'levelLength' => $levelInfo["levelLength"],
	'audioTrack' => $levelInfo["audioTrack"],
	'auto' => $levelInfo["auto"],
	'password' => $levelInfo["password"],
	'original' => "0",
	'twoPlayer' => $levelInfo["twoPlayer"],
	'songID' => $levelInfo["songID"],
	'objects' => $levelInfo["objects"],
	'coins' => $levelInfo["coins"],
	'requestedStars' => $levelInfo["requestedStars"],
	'unlisted' => "0",
	'wt' => "0",
	'wt2' => "3",
	'extraString' => $levelInfo["extraString"],
	'seed' => "v2R5VPi53f",
	'seed2' => $seed2,
	'levelString' => $levelString,
	'levelInfo' => $levelInfo["levelInfo"],
	'secret' => "Wmfd2893gb7"];
	if($debug == 1){
		var_dump($post);
	}
	$ch = curl_init($server . "/uploadGJLevel21.php");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
	$result = curl_exec($ch);
	curl_close($ch);
	if($result == "" OR $result == "-1" OR $result == "No no no"){
		if($result==""){
			$dl->printSong('<div class="form">
				<h1>'.$dl->getLocalizedString("errorGeneric").'</h1>
				<form class="form__inner" method="post" action="">
				<p>'.$dl->getLocalizedString("errorConnection").'</p>
				<button type="submit" class="btn-song">'.$dl->getLocalizedString("tryAgainBTN").'</button>
				</form>
			</div>', 'reupload');
		}else if($result=="-1"){
			$dl->printSong('<div class="form">
				<h1>'.$dl->getLocalizedString("errorGeneric").'</h1>
				<form class="form__inner" method="post" action="">
				<p>'.$dl->getLocalizedString("reuploadFailed").'</p>
				<button type="submit" class="btn-song">'.$dl->getLocalizedString("tryAgainBTN").'</button>
				</form>
			</div>', 'reupload');
		}else{
			$dl->printSong('<div class="form">
				<h1>'.$dl->getLocalizedString("errorGeneric").'</h1>
				<form class="form__inner" method="post" action="">
				<p>'.$dl->getLocalizedString("robtopLol").'</p>
				<button type="submit" class="btn-song">'.$dl->getLocalizedString("tryAgainBTN").'</button>
				</form>
			</div>', 'reupload');
		}
    } 
  	$dl->printSong('<div class="form">
			<h1>'.$dl->getLocalizedString("levelToGD").'</h1>
			<form class="form__inner" method="post" action="">
				<p>'.$dl->getLocalizedString("levelReuploaded").' '.$result.'</p>
				<button type="submit" class="btn-song">'.$dl->getLocalizedString("tryAgainBTN").'</button>
			</form>
	</div>', 'reupload');
} else {
	$dl->printSong('<div class="form">
    <h1>'.$dl->getLocalizedString("levelToGD").'</h1>
    <form class="form__inner" method="post" action="">
		<p>'.$dl->getLocalizedString("levelToGDDesc").'</p>
        <div class="field"><input type="text" name="levelID" placeholder="'.$dl->getLocalizedString("levelID").'"></div>
        <div class="field"><input type="text" name="usertarg" placeholder="'.$dl->getLocalizedString("usernameTarget").'"></div>
        <div class="field"><input type="password" name="passtarg" placeholder="'.$dl->getLocalizedString("passwordTarget").'"></div>
		<details class="details">
			<summary style="width: 100%;">'.$dl->getLocalizedString("advanced").'</summary>
			<div class="field" style="display: inline-flex;width:100%;justify-content: space-between;">
            	<input type="text" name="server" value="http://www.boomlings.com/database/" placeholder="'.$dl->getLocalizedString("server").'" style="width: 86%;">
			<input class="checkbox" type="checkbox" name="debug" value="1" placeholder="Debug" style="width: 8%;">
			</div>
		</details>', 'reupload');
		Captcha::displayCaptcha();
        echo '
        <button type="submit" class="btn-song">'.$dl->getLocalizedString("reuploadBTN").'</button>
    </form>
</div>';
}
} else {
		$dl->printSong('<div class="form">
			<h1>'.$dl->getLocalizedString("errorGeneric").'</h1>
			<form class="form__inner" method="post" action="../dashboard">
			<p>'.$dl->getLocalizedString("pageDisabled").'</p>
			<button type="submit" class="btn-song">'.$dl->getLocalizedString("dashboard").'</button>
			</form>
		</div>', 'reupload');
}
?>