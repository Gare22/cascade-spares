<?php

/*****Constant Contact 2 Website .php**************
Author:Garrett Tallent, https://github.com/Gare22
Date Created: 9/24/21
Description: Update Cascade Spares's Marketing page when constant contact is sent out.
    This program checks a gmail account
    for any emails with the "Marketing" label. Gmail automatically labels
    any email from Cascade Spare's constant contact as "Marketing"
    If any emails are present, a new mgitem (marketing gallery item) is
    added to the site's HTML.
Preconditions: 
    1)The second image in the constant contact must be a part image. If it's not, it will break. (Always put the logo above)
    2)IMAP module must be enabled in php.ini
    3)No invisible text (will ruin the line order of marketing)
**************************************************/
echo("||==============================================||\r\n");
echo("|| Cascade Spares Constant Contact Email Reader ||\r\n");
echo("|| Author: Garrett Tallent                      ||\r\n");
echo("||==============================================||\r\n");
//******************//
//  LOGIN TO EMAIL  //
//******************//
//Mailserver and credentials.
$mailserver = '{imap.gmail.com:993/ssl/novalidate-cert}';
$address = 'EmailAddress';
$password = 'EmailPassword';

//Attempt to open imap stream using the imap_open function.
$imapResource = imap_open($mailserver, $address, $password);

//If the imap_open function returns FALSE,
//then we failed to connect.
if($imapResource === false){
    //If it failed, throw an exception that contains
    //the last imap error.
    throw new Exception(imap_last_error());
}

//Open the mailbox that holds all mail with the "Marketing" label
imap_reopen($imapResource, $mailserver.'Marketing');

//Get all the emails
$marketing = imap_search($imapResource,'ALL');


//if the marketing mailbox is not empty
if(!empty($marketing)){
    //*********************************************************************||
    //Parse out the PN, Description, and Trace information from the message||
    //*********************************************************************||
        
    
    //First email
    $email = 1;

    foreach($marketing as $email){

        //Get the first email as plaintext
        $message = imap_fetchbody($imapResource, $email, 2);

        //mark email to be deleted
        //imap_delete($imapResource, $email);

        //fix message encoding
        $message = quoted_printable_decode($message);

        //Strip all html tags except for img tags and style tags
        $message = strip_tags($message, "<style><img>");

        //remove everything in style tag
        $style = substr($message, strpos($message, "<style"), strpos($message, "</style>")); //get style sub string
        $message = str_replace($style, "", $message); //replace style substring with empty string

        //Do it twice for CONSTANT CONTACT
        $style = substr($message, strpos($message, "<style"), strpos($message, "</style>")); //get style sub string
        $message = str_replace($style, "", $message); //replace style substring with empty string


        //Break string into individual lines array
        $individuallines = preg_split("/\r\n|</", $message);
        

        //Dev Print Statement
        //print_r($individuallines);

        //Make a new array for lines that are not empty (constant contact adds in a bunch of table elements, so when we strip it it makes a lot of blank lines)
        $newlines = array();
        //Make an array for constant contact imgs
        $ccimgs = array();

        //For each line, check if it has an image. If so, put it in the constant contact images array, if it doesn't put it in the newlines array if it isn't empty
        foreach($individuallines as $line){
            if(str_contains($line, "img")){
                array_push($ccimgs, trim($line));
            }
            else if(strlen($line) > 0 && strlen(trim($line)) != 0){
                array_push($newlines, trim($line)); //trim the line to make there not be spaces on any side
            } 
        }

        $plane = $newlines[1];
        $partnumber = $newlines[2];
        $description = $newlines[3];
        $trace = $newlines[4];
        $info = "";
        for ($i = 5; $i<count($newlines); $i++){
            if(str_contains($newlines[$i], "-Stock")){
                break;
            }
            $info=$info . $newlines[$i] . "\r\n";
        }

        echo("Plane:" . $plane . "\r\nPN:" . $partnumber . "\r\nDescription:" . $description . "\r\n" . $trace . "\r\n" . $info . "\r\n");
        

        //********************||
        //Parse the part image||
        //********************||
        $ccimgsrcs = array();
        foreach($ccimgs as $ccimg){
            preg_match_all('/src="(.*?)"/', $ccimg, $src);
            if(!str_contains($src[1][0], "https://imgssl") && !str_contains($src[1][0], "https://r20")){
                array_push($ccimgsrcs, $src[1][0]);
            }
        }
        
        //print_r($ccimgsrcs);
        $partimage = $ccimgsrcs[1];


        //copy the img to a unique location (consider using the date and time as the file name?)
        $datetime = date("dis");
        $newimg = "CC" . $partnumber . "_" . $datetime . ".jpg";
        echo("Copying from ". $partimage . " to " . $newimg . "\r\n");
        copy($partimage,"./images/".$newimg);
        

        //*****************************************||
        //      Add new info as new mgitem         ||
        //*****************************************||

        //Constuct new DOMDocument for html to be loaded
        $html = new DOMDocument();
        //Formatting to make sure output html is spaced out (instead of one single line)
        $html->formatOutput = true;
        $html->preserveWhiteSpace = false;
        

        //Load index.html (main website) into the DOMDocument
        $html->loadHTMLFile("index.html");

        //Get the marketing gallery div
        $marketgallery = $html->getElementById('marketinggallery');
        
        //Create the mgitem div element of marketinggallery
        $mgitem = $html->createElement('div');
        $mgitem->setAttribute('class','mgitem');

        //Create the mgimg div element of mgitem
        $mgimg = $html->createElement('div');
        $mgimg->setAttribute('class','mgimg');

        //Create h3 elements for plane, pn, and desc
        $planeh3 = $html->createElement('h3',$plane);
        $partnumberh3 = $html->createElement('h3',$partnumber);
        $descriptionh3 = $html->createElement('h3',$description);

        //Create the a element of mgimg div
        $anchor = $html->createElement('a');
        $anchor->setAttribute('href',"images/".$newimg);
        $anchor->setAttribute('target','_blank');

        //Create the img element of a
        $img = $html->createElement('img');
        $img->setAttribute('src',"images/".$newimg);

        //Create p element for trace
        $tracep = $html->createElement('p',$trace);
        //Create p element for info
        $infop = $html->createElement('p',$info);

        /*<div id="marketinggallery">
            <div class="mgitem">
                <div class="mgimg">
                    <h3>$Plane</h3>
                    <h3>$PN</h3>
                    <h3>$Desc</h3>
                    <a href="images/specials.png" target="_blank">
                        <img src="images/specials.png">
                    </a>
                    <p>$Trace</p>
                </div>
            </div>
        </div>*/
        
        //Append children to their respective parents as shown above
        $anchor->appendChild($img);
        $mgimg->appendChild($planeh3);
        $mgimg->appendChild($partnumberh3);
        $mgimg->appendChild($descriptionh3);
        $mgimg->appendChild($anchor);
        $mgimg->appendChild($tracep);
        $mgimg->appendChild($infop);
        $mgitem->appendChild($mgimg);

        if ($marketgallery->hasChildNodes()) {
            $marketgallery->insertBefore($mgitem,$marketgallery->firstChild);
        } else {
            $marketgallery->appendChild($mgitem);
        }

        //Save the html to update site
        $html->saveHTMLFile("index.html");
    }
    
    //delete all marked emails (imap_expunge(resource $imap): bool)
    imap_expunge($imapResource);
    //close the imapStream
    imap_close($imapResource);
}
?>