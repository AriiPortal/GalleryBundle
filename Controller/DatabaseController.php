<?php

namespace Arii\GalleryBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DatabaseController extends Controller
{
    protected $Galery     = '/home/eric/Images/Photos';
    protected $Thumbnails = '/var/symfony/web/thumbnails'; 

    public function dataviewAction()
    {
         set_time_limit ( 300 );
       
       $request = Request::createFromGlobals();
       $annee = $request->query->get('year');
       $mois = $request->query->get('month');
       $jour = $request->query->get('day');
       $limit = $request->query->get('limit');

       $now = localtime (time(),true);
       if ($annee=="") $annee = $now['tm_year']+1900;
       if ($mois=="") $mois = sprintf("%02d",$now['tm_mon']+1);
       if ($jour=="") $jour = $now['tm_mday'];
       if ($limit=="") $limit = 10;

       if ($annee=="ALL") $annee = "%";
       if ($mois=="ALL") $mois = "%";
       if ($jour=="ALL") $jour = "%";

        $db = $this->container->get('arii_core.db');
        $data = $db->Connector('dataview');
        $qry = "select id,Photo,Orientation,Timestamp from Photo where Photo like \"$annee/$mois/$jour/%\" limit 0,$limit";

        $data->event->attach("beforeRender", array($this, 'beforeRender') );
        $data->render_sql($qry,"id","Photo");   
    }
    
    public function timelineAction()
    {
       set_time_limit ( 3000 );
 
       $request = Request::createFromGlobals();
       $annee = $request->query->get('year');
       $mois = $request->query->get('month');
       $jour = $request->query->get('day');
       $limit = $request->query->get('limit');

       $now = localtime (time(),true);
       if ($annee=="") $annee = $now['tm_year']+1900;
       if ($mois=="") $mois = sprintf("%02d",$now['tm_mon']+1);
       if ($jour=="") $jour = $now['tm_mday'];
       if ($limit=="") $limit = 10;

       if ($annee=="ALL") $annee = "%";
       if ($mois=="ALL") $mois = "%";
       if ($jour=="ALL") $jour = "%";

        $db = $this->container->get('arii_core.db');
        $data = $db->Connector('scheduler');
        $qry = "select id,Photo,Orientation,Timestamp from Photo where Photo like \"$annee/$mois/$jour/%\" limit 0,$limit";

        $data->event->attach("beforeRender", array($this, 'beforeTimeline') );
        $data->render_sql($qry,"id","start_date,end_date,event_name,details");
    }

    public function beforeTimeline($row) {
	$photo = $row->get_value('Photo');
	$tm = localtime($row->get_value('Timestamp'),true);	
	$date = sprintf("%04d/%02d/%02d %02d:%02d:%02d",$tm['tm_year']+1900,$tm['tm_mon']+1,$tm['tm_mday'],$tm['tm_hour'],$tm['tm_min'],$tm['tm_sec']);
	$row->set_value('start_date',$date);
	$row->set_value('end_date',$date );
        $this->Thumbnail($this->Galery.'/'.$photo, $this->Thumbnails.'/'.$photo,$row->get_value('Orientation'),45,45 );
	$row->set_value('event_name', '<img src="/icons_/'.$photo.'"/>' );
	$row->set_value('details', '<img src="/tumbnails/'.$photo.'"/>' );
	}

    public function beforeRender($row) {
        $photo = utf8_encode( $row->get_value('Photo') );
        $this->Thumbnail($this->Galery.'/'.$photo, $this->Thumbnails.'/'.$photo,$row->get_value('Orientation'),45,45 );
        $row->set_value('Photo', $photo);
    }
    
    function Thumbnail($filename,$thumbnail,$orientation,$new_width,$new_height,$display=0) {

        if (file_exists($thumbnail)) {      
            return;
        }
        $Info=@getimagesize($filename);
        if (!$Info) {
            // print "($filename)";
            return;
        }
        
           list($width, $height) = $Info;
                   
            if ($width > $height) {
              $ratio = $new_width/$width;
              $new_height = round( $height * $ratio);
            }
            else {
              $ratio = $new_height/$height;
              $new_width = round( $width * $ratio);
            }
            // Redimensionnement
            $image_p = imagecreatetruecolor($new_width, $new_height );
            $image = imagecreatefromjpeg($filename);
            imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
	    if ($orientation == 1) 	
            	$image = imagerotate($image_p, 0, 0);
	    elseif ($orientation == 3 )
		$image = imagerotate($image, 180, 0);
	    elseif ($orientation == 6)
            	$image = imagerotate($image_p, -90, 0);
	    elseif ($orientation == 8)
            	$image = imagerotate($image_p, 90, 0);
            $this->CheckDir(dirname($thumbnail));
            $save = imagejpeg  ( $image ,  $thumbnail  );
            if ($display==0) {
              return $save;
            }
            else {
              return $image;
            }

    }
    
    function CheckDir($dir) {
        if ($dir=='.')
                return 0;

        // On verifie la presence du repertoire
  if (is_dir($dir))
                return 0;

        // on cr�� l'arborescence
        $Dir = explode("/",$dir);
        $dir = '';

        foreach ($Dir as $d) {
                if (!is_dir($dir.$d))
                        @mkdir($dir.$d);
                $dir .= $d.'/';
        }

return 1;

        }

    public function photoAction()
    {
        $request = Request::createFromGlobals();
        $id = $request->query->get('id');
        $screen_width = $request->query->get('width');
       
        $db = $this->container->get('arii_core.db');
        $data = $db->Connector('data');

        $qry = "select Photo,Orientation from Photo where id=$id";
        $res = $data->sql->query($qry);
        $line = $data->sql->get_next($res);
        if ($line['Photo']=='') {
            print "(($id))".$this->Galery.'/'.$line['Photo'];
            exit();
        }

        $photo = $line['Photo'];
        
        // mini
        $image = imagecreatefromjpeg($this->Galery.'/'.$photo);
	$orientation = $line['Orientation'];
	if (($orientation == 6) or ($orientation == 8)) {
		$width = imagesy ( $image );
		$height = imagesx ( $image );
		$new_width = $screen_width;
		$ratio = $height/$width;
		$new_height= $screen_width*$ratio;
		if ($orientation == 8 )
			$image = imagerotate($image, 90, 0);
		else 
			$image = imagerotate($image, -90, 0);
		$image2 = imagecreatetruecolor($new_width, $new_height);
		imagecopyresampled($image2, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
	} 
	else {
		$width = imagesx ( $image );
		$height = imagesy ( $image );
		$new_width = $screen_width;
		$ratio = $height/$width;
		$new_height= $screen_width*$ratio;
		if ($orientation == 3 )
			$image = imagerotate($image, 180, 0);
		$image2 = imagecreatetruecolor($new_width, $new_height);
		imagecopyresampled($image2, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
	}
        header('Content-Type: image/jpeg');
        imagejpeg  ( $image2 );
        imagedestroy($image2);
        imagedestroy($image);
        exit();
    }

    public function formAction()
    {
       $request = Request::createFromGlobals();
       $id = $request->query->get('id');
       
        $db = $this->container->get('arii_core.db');
        $data = $db->Connector('form');
        $qry = "select id,Photo,Orientation,Timestamp from Photo where Photo like \"$annee/$mois/$jour/%\" limit 0,$limit";
       	$data->render_table("Photo","id","Photo,Source,FileSize,Make,Model,Orientation,ExposureTime,FNumber,ExposureProgram,ISOSpeedRatings,ShutterSpeedValue,ApertureValue,ExposureBiasValue,MeteringMode,Flash,FocalLength");   
    }

}
