<?php

require("getid3/getid3.php");
require('ImageHandler/WideImage.php');
$getID3 = new getID3;

$method = $_SERVER['REQUEST_METHOD'];
$body = file_get_contents('php://input');

if ($method === 'GET') {

    chdir(__DIR__."/songs");
    $files = glob("{*.mp3}", GLOB_BRACE);
    $List = array("error"=>"false","data"=>array());

    if(isset($_GET['filename']) && $_GET['filename'] != NULL && $_GET['filename'] != ""){

        $this_file = array(
            "art"=>null
        );

        $ThisFileInfo = $getID3->analyze($_GET['filename']);

        if(array_key_exists('comments',$ThisFileInfo)){
            if(array_key_exists('picture',$ThisFileInfo['comments'])){
                if(array_key_exists('data',$ThisFileInfo['comments']['picture'][0])){

                    $image = $ThisFileInfo['comments']['picture'][0]['data'];
                    $image = WideImage::load($image);

                    if(!isset($_GET['fullsize']) || $_GET['fullsize']!=true){
                        $image = $image->resize(50, 50);
                    }

                    $this_file = array(
                        "art"=>base64_encode($image)
                    );
                }
            }
        }
        
        array_push($List["data"], $this_file);
        echo json_encode($List);
        return;
    }

    if(isset($_GET['artist']) && $_GET['artist'] != NULL && $_GET['artist'] != ""){
        foreach($files as $file) {

            $ThisFileInfo = $getID3->analyze($file);
    
            $title = "Unknown";
            $artist = $ThisFileInfo['filename'];
            $year = "Unknown";
    
            $isFindArtist = false;

            if(array_key_exists('tags', $ThisFileInfo)){
                if(array_key_exists('id3v1',$ThisFileInfo['tags'])){
                    if(array_key_exists('title',$ThisFileInfo['tags']['id3v1'])){
                        $title = $ThisFileInfo['tags']['id3v1']['title'][0];
                    }
                    if(array_key_exists('artist',$ThisFileInfo['tags']['id3v1'])){
                        $artist = $ThisFileInfo['tags']['id3v1']['artist'][0];
                    }
                    if(array_key_exists('year',$ThisFileInfo['tags']['id3v1'])){
                        $year = $ThisFileInfo['tags']['id3v1']['year'][0];
                    }
                }else 
                
                if(array_key_exists('id3v2',$ThisFileInfo['tags'])){
                    if(array_key_exists('title',$ThisFileInfo['tags']['id3v2'])){
                        $title = $ThisFileInfo['tags']['id3v2']['title'][0];
                    }
                    if(array_key_exists('artist',$ThisFileInfo['tags']['id3v2'])){
                        $artist = $ThisFileInfo['tags']['id3v2']['artist'][0];
                    }
                    if(array_key_exists('year',$ThisFileInfo['tags']['id3v2'])){
                        $year = $ThisFileInfo['tags']['id3v2']['year'][0];
                    }
                }
            }
            
            if($artist == $_GET['artist']){
                $this_file = array(
                    "filename"=>$ThisFileInfo['filename'],
                    "title"=>$title,
                    "artist"=>$artist,
                    "year"=>$year
                );
        
                array_push($List["data"], $this_file);

            //echo json_encode($ThisFileInfo['tags']['id3v1']);die;
            }
        }
    
        echo json_encode($List);
        return;
    }

    foreach($files as $file) {

        $ThisFileInfo = $getID3->analyze($file);

        $artist = null;

        try{
            if(array_key_exists('tags', $ThisFileInfo)){
                if(array_key_exists('id3v1',$ThisFileInfo['tags'])){
                    if(array_key_exists('artist',$ThisFileInfo['tags']['id3v1'])){
                        $artist = $ThisFileInfo['tags']['id3v1']['artist'][0];
                    }
                } else
                
                if(array_key_exists('id3v2',$ThisFileInfo['tags'])){
                    if(array_key_exists('artist',$ThisFileInfo['tags']['id3v2'])){
                        $artist = $ThisFileInfo['tags']['id3v2']['artist'][0];
                    }
                }
            }
            
        }catch (Exception $e){

        }
        
        $this_file = array(
            "artist"=>$artist
        );

        array_push($List["data"], $this_file);
    }

    $new_list = array("error"=>"false","data"=>array());
    for ($i = 0; $i <= count($List['data']); $i++) {

        if(isset($List['data'][$i]['artist'])){
            $artist = $List['data'][$i]['artist'];
        }else{
            $artist = "Unknown";
        }
        $isFind = false;

        $count = (int)count($new_list['data']);

        for($j = 0; $j < $count; $j++){
            $this_artist = $new_list['data'][$j]['artist'];
            if($artist == $this_artist){
                $quantity = $new_list['data'][$j]['quantity'];
                $quantity++;
                $new_list['data'][$j]['quantity'] = $quantity;
                $isFind=true;
                $j = count($new_list['data']);
            }
        }

        if(!$isFind){
            $this_file = array(
                "artist"=>$artist,
                "quantity"=>1
            );
            
            array_push($new_list['data'], $this_file);
        }
    }

    echo json_encode($new_list);
}