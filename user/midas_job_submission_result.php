<?php
// This file is part of BOINC.
// http://boinc.berkeley.edu
// Copyright (C) 2016 University of California
//
// BOINC is free software; you can redistribute it and/or modify it
// under the terms of the GNU Lesser General Public License
// as published by the Free Software Foundation,
// either version 3 of the License, or (at your option) any later version.
//
// BOINC is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with BOINC.  If not, see <http://www.gnu.org/licenses/>.

//Added by Gerald Joshua

require_once("../inc/util.inc");
require_once("../inc/midas.inc");
require_once("../inc/red_keys.inc");

page_head(null, null, null, null, null, "Job Submission Result");

//Get the folder name
function get_folder_name(){
	return generateRandomString(); 
}

//Get the operating system researcher wants to use
function get_the_os(){
	$os = "";
	if(isset($_POST["operating_system"]))
		$os = $_POST["operating_system"];
	return $os;
}

//Get the list of programming languages will be used
function get_prog_lang_list(){
	$pLArr = array();
	foreach (langs_list() as $pL){
		if(isset($_POST[$pL]))
			array_push($pLArr, $_POST[$pL]);
	}
	
	return $pLArr;
}

//Get the list of libraries will be used
function get_library_list($pLArr=array()){
	$library_list = array();
	foreach (langLibs() as $pLAllowed => $pLName){
		if(in_array($pLAllowed, $pLArr) && isset($_POST[$pLName]))
			array_push($library_list, array($pLAllowed => explode(",", $_POST[$pLName])));
		else
			array_push($library_list, array($pLAllowed => array()));
	}
	
	$JSONLib = json_encode($library_list, JSON_UNESCAPED_SLASHES);
	$JSONLib = trim($JSONLib, '[]');
	$JSONLib = str_replace("},{", ",", $JSONLib);
	$JSONLib = json_decode($JSONLib);
	return $JSONLib;
}

//Create the folder that stores user input and setup files
function create_folder($the_folder_name){
	$success = true;

	// Generated by curl-to-PHP: http://incarnate.github.io/curl-to-php/
	$ch = curl_init();
	$url = "http://0.0.0.0:6055/boincserver/mkdir/tmp/".$the_folder_name;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$result = curl_exec($ch);
	if (curl_errno($ch) || strpos($result, 'exists') !== false) {
    	$success = false;
    }

	curl_close ($ch);

	return $success;
}

//Get the user's setup file name
function get_setup_file_name(){
	$setupFileName = "";
	if(isset($_FILES["setup_file"])){
		$setupFileName = $_FILES["setup_file"]["name"];
	}
	return $setupFileName;
}

//Get the command lines
function get_command_lines(){
	$commandList = "";
	if(isset($_POST["commandLine"])){
		$commandList = $_POST["commandLine"];
		$commandList = str_replace("\n", "", $commandList);
		$commandList = str_replace("\r", "", $commandList);
	}
	return $commandList;
}

//Get the list of user's output file
function get_output_file_list(){
	if(isset($_POST["outputFileList"]))
		return explode(",", $_POST["outputFileList"]);
	if(!isset($_POST["outputFileList"]))
		return array("ALL");
}

//Get the input file name
function get_input_file_name(){
	$midasInputFileName = "none";
	if(isset($_FILES['midas_input_file']))
		$midasInputFileName = $_FILES["midas_input_file"]["name"];
	return $midasInputFileName;
}

//Get the researcher unique token
function get_user_token(){
	$organizationKey = get_ok("TACC");
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "http://0.0.0.0:5078/boincserver/v2/api/user_tokens/".$_SESSION['user']."/".$organizationKey);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$result = curl_exec($ch);
	if (curl_errno($ch) || strpos($result, 'NOT') !== false){
		return "error";
	}
	else
		return explode(",", $result)[0];
}

//Move the midas input file to its unique directory
function move_midas_input_file($folderName, $midasInputFile){
	$target_dir = "/tmp/".$folderName."/";
	$target_file = $target_dir.basename($midasInputFile["name"]);
	return move_uploaded_file($midasInputFile["tmp_name"], $target_file);
}

//Move the midas setup file to its unique directory
function move_midas_setup_file($folderName, $setupFile){
	$target_dir = "/tmp/".$folderName."/";
	$target_file = $target_dir.basename($setupFile["name"]);
	return move_uploaded_file($setupFile["tmp_name"], $target_file);
}

function get_json(){
	$theFolderName = get_folder_name();
	$progLangArr = get_prog_lang_list();

	//For topic tags
	$theTopics = json_encode (json_decode ("{}"));

	//Check if a job topic is included 
	if(isset($_POST['midasTopic'])){
		if(strlen(trim($_POST['midasTopic'])) !== 0){
			$theTopics = trim($_POST['midasTopic']);
			//echo "trim: ".$theTopics.'<br/>';
			$theTopics = '{"'.$theTopics;
			//echo '{" '.$theTopics.'<br/>';
			if(strpos($theTopics, ',') !== false){
				$theTopics = str_replace(',', '","', $theTopics);	
			}
			//echo ', '.$theTopics.'<br/>';
			if(strpos($theTopics, '(') !== false){
				$theTopics = str_replace('(', '":["', $theTopics);	
			}
			//echo '( '.$theTopics.'<br/>';
			if(strpos($theTopics, ')') !== false){
				$theTopics = str_replace(')', '"]', $theTopics);	
			}
			//echo ') '.$theTopics.'<br/>';
			if(strpos($theTopics, ';') !== false){
				$theTopics = str_replace(';', ',"', $theTopics);	
			}
			//echo '; '.$theTopics.'<br/>';
			$theTopics .= '}';
			//echo ' '.$theTopics.'<br/>';

			/*//Convert string to json
			$theTopics = json_decode($theTopics, true);
			if($theTopics != NULL)
				$theJson = json_encode($theTopics, JSON_UNESCAPED_SLASHES);
				echo $theJson;*/
		}
	}
	
	//Create folder if necessary
	if(isset($_FILES["setup_file"]) || isset($_FILES["midas_input_file"])){
		while(!create_folder($theFolderName)){
			$theFolderName = get_folder_name();
			create_folder($theFolderName);
		}
		if(isset($_FILES["setup_file"])){
			move_midas_setup_file($theFolderName, $_FILES["setup_file"]);
		}
		if(isset($_FILES["midas_input_file"])){
			move_midas_input_file($theFolderName, $_FILES["midas_input_file"]);
		}
	}
	

	//Set up a json with all necessary information about the midas job
	$tempData = array(
					  'folder_name'=>$theFolderName,
					  'operating_system'=>get_the_os(), 
					  'programming_language'=>$progLangArr, 
					  'library_list'=>get_library_list($progLangArr),
					  'output_file'=>get_output_file_list(),
					  'input_file'=>get_input_file_name(),
					  'command_lines'=>get_command_lines(),
					  'token'=>get_user_token(),
					  'setup_filename'=>get_setup_file_name(),
					  'topics'=>$theTopics
				);
	$JSONdata = json_encode($tempData, JSON_UNESCAPED_SLASHES);

	return $JSONdata;
}

function submit_midas_job(){
	$success = true;

	$content = get_json();

	//echo $content;

	$url = "http://0.0.0.0:6075/boincserver/v2/api/process_midas_jobs";    
	
	
	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPHEADER,
	        array("Content-type: application/json"));
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $content);

	$result = curl_exec($curl);
	if (curl_errno($curl) || strpos($result, 'submitted') === false) {
	    $success = false;
	    echo '<center><h1>'.tra('Sorry, your job was not uploaded.').'</center></h1>';
	    echo '<center><h1>'.$result.'</center></h1>';
	}
	curl_close($curl);
	return $success;
}

if(submit_midas_job()){
	echo '<center><h1>'.tra('Congratulations, your file was uploaded.').'</center></h1>';
	echo '<center><a href="./job_submission.php" style="font-weight:bold" class="btn btn-success" role="button">Go Back to Job Submission Page</a></center>';
} else {
	echo '<center><h3>'.tra('Make sure to follow the requirements mentioned on Job Submission page for the input file').'</h3></center>';
	echo '<center><a href="./job_submission.php" style="font-weight:bold;" class="btn btn-success" role="button">Go Back to Job Submission Page</a></center>';
}
page_tail();
//End of the edit by Gerald Joshua
?>