<?php

namespace Arii\GalleryBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('AriiGalleryBundle:Default:index.html.twig' );
    }

    public function readmeAction()
    {
        return $this->render('AriiGalleryBundle:Default:readme.html.twig' );
    }

    public function toolbarAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render('AriiGalleryBundle:Default:toolbar.xml.twig', array(), $response );
    }

    public function treeAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
       return $this->render('AriiGalleryBundle:Default:tree.xml.twig', array(), $response  );
    }
    public function ribbonAction()
    {
        // Date du jour
        $date = localtime(time(), true);
        $date['Year'] = $date['tm_year']+1900;
        $date['Month'] = $date['tm_mon']+1;
        $date['Day'] = $date['tm_mday'];
        
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        
        return $this->render('AriiGalleryBundle:Default:ribbon.json.twig',array('Date' => $date), $response );
    }
}
