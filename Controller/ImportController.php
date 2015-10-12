<?php

namespace Arii\GalleryBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class ImportController extends Controller
{	
	protected $data;
	protected $source;

    public function importAction($source)
    {
        $db = $this->container->get('arii_core.db');
        $this->data = $db->Connector('data');

        $path =  '/home/eric/Images/Import';
	$this->source = $source;
        $this->ProcessImages($path);
	exit();
    }
    
    public function ProcessImages($dir) {
         if (is_dir($dir)) {
                if ($dh = opendir($dir)) {
                while (($file = readdir($dh)) !== false) {
                    if ($file != '.' and $file != '..') {
                        if (is_dir($dir.'/' . $file)) 
                                $this->ProcessImages($dir.'/' . $file);
                        else 
                                $this->ImportImage($dir.'/' . $file);
                    }
                }
                closedir($dh);
            }
        }
    }

    public function ImportImage($file) {
         echo "fichier : $file <br/>";
	if (strtolower(substr($file,-3))!='jpg') {
		print "(((extension ".substr($file,-3).")))";
		return;
	}
         $Infos = $this->getExif($file);

	 // print_r($Infos);
/*
         $Date =  localtime( $Infos['FileDateTime'], true );
	$dir = sprintf("%04d/%02d/%02d", $Date['tm_year']+1900, $Date['tm_mon']+1, $Date['tm_mday'] );
*/
	$dir = str_replace(':','/',substr($Infos['DateTimeOriginal'],0,10));
	
	$this->CheckDir("/home/eric/Images/Photos/$dir");
	
	$Photo = $dir.'/'.$Infos['FileName'];

	// est ce qu'il existe ?
	$data= $this->data; 
	$qry = 'select * from Photo where Photo="'.$Photo.'"';

        $res = $data->sql->query($qry);  
	$rep = $data->sql->get_next($res);

	//  exceptions
	if (!isset($Infos['ExposureBiasValue']))
		$Infos['ExposureBiasValue'] = '';

	if (!empty($rep)) {
		print_r( $rep );
		$id = $rep['id'];
        $qry = 'Update Photo set `Photo`="'.$Photo.'" , 
	`Source`="'.$this->source.'",
	`Timestamp`="'.$Infos['FileDateTime'].'",
	`FileSize`="'.$Infos['FileSize'].'",
	`Make`="'.$Infos['Make'].'", 
	`Model`="'.$Infos['Model'].'", 
	`Orientation`="'.$Infos['Orientation'].'", 
	`ExposureTime`="'.$Infos['ExposureTime'].'", 
	`FNumber`="'.$Infos['FNumber'].'", 
	`ExposureProgram`="'.$Infos['ExposureProgram'].'", 
	`ISOSpeedRatings`="'.$Infos['ISOSpeedRatings'].'", 
	`ShutterSpeedValue`="'.$Infos['ShutterSpeedValue'].'", 
	`ApertureValue`="'.$Infos['ApertureValue'].'", 
	`ExposureBiasValue`="'.$Infos['ExposureBiasValue'].'", 
	`MeteringMode`="'.$Infos['MeteringMode'].'", 
	`Flash`="'.$Infos['Flash'].'", 
	`FocalLength`="'.$Infos['FocalLength'].'" where id='.$id;
print $qry;
        $res = $data->sql->query($qry);  
	// $rep = $data->sql->get_next($res); 
	}
	else {
	// on sauvegarde
        $qry = 'Insert into Photo ( 
		`Photo`, `Source`, `Timestamp`, `FileSize`, `Make`, `Model`, `Orientation`, `ExposureTime`, `FNumber`, `ExposureProgram`, `ISOSpeedRatings`, `ShutterSpeedValue`, `ApertureValue`, `ExposureBiasValue`, `MeteringMode`, `Flash`, `FocalLength` ) 
	values ( "'.$Photo.'" , "'.$this->source.'" , 
	"'.$Infos['FileDateTime'].'",
	"'.$Infos['FileSize'].'",
	"'.$Infos['Make'].'", 
	"'.$Infos['Model'].'", 
	"'.$Infos['Orientation'].'", 
	"'.$Infos['ExposureTime'].'", 
	"'.$Infos['FNumber'].'", 
	"'.$Infos['ExposureProgram'].'", 
	"'.$Infos['ISOSpeedRatings'].'", 
	"'.$Infos['ShutterSpeedValue'].'", 
	"'.$Infos['ApertureValue'].'", 
	"'.$Infos['ExposureBiasValue'].'", 
	"'.$Infos['MeteringMode'].'", 
	"'.$Infos['Flash'].'", 
	"'.$Infos['FocalLength'].'" )';
        $res = $data->sql->query($qry);  
	// $rep = $data->sql->get_next($res); 
	}
print "<br/>$file -> /home/eric/Images/Photos/$Photo"; 

	if (!file_exists("/home/eric/Images/Photos/$Photo")) {
		rename($file,"/home/eric/Images/Photos/$Photo"  );
	}
    }
    
    function getExif($file) {
        $Head = array( 'FileName', 'FileDateTime', 'FileSize', 'FileType', 'MimeType', 'Make', 'Model', 'Orientation', 'XResolution', 'YResolution', 'ResolutionUnit', 'DateTime', 'YCbCrPositioning',
      'ExposureTime', 
      'FNumber', 
      'ExposureProgram', 
      'ISOSpeedRatings', 
      'ExifVersion', 
      'DateTimeOriginal', 
      'DateTimeDigitized', 
      'ShutterSpeedValue', 
      'ApertureValue', 
      'ExposureBiasValue', 
      'MeteringMode', 
      'Flash', 
      'FocalLength' );
          $Inf = exif_read_data( $file );

          // Exif ?
          if (!empty($Inf)) {
            // on essaye de completer les donnees manquantes
            if (!isset($Inf['DateTimeOriginal'])) {
              $DT=localtime($Inf['FileDateTime'],true);
              $Inf['DateTimeOriginal'] = sprintf('%02d:%02d:%02d %02d:%02d:%02d',$DT['tm_year']+1900,$DT['tm_mon']+1,$DT['tm_mday'],$DT['tm_hour'],$DT['tm_min'],$DT['tm_sec']);
            }
            
            $Info = array();
            foreach ($Head as $h) {
                if (isset($Inf[$h])) 
			$Info[$h] = $Inf[$h];
		else
			$Info[$h] = '';
            }
            return $Info;
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

}
