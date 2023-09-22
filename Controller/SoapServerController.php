<?php

namespace Corp\EiisBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Router;
use Zend\Soap\AutoDiscover;
use Zend\Soap\Server;

class SoapServerController extends AbstractController {

    public function eiis(Request $request, EiisServiceController $eiisServiceController){
        if($request->query->has('wsdl')){
            return new Response($this->getEiisServiceWsdl(), 200, ['Content-Type'=>'text/xml; charset=UTF-8']);
        }
        $options = array('uri' => 'http://example.com');
        $server = new Server(null, $options);
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml; charset=UTF-8');
        $server->setClass($eiisServiceController);
        $server->setDebugMode(true);
        $server->setReturnResponse(true);
        $server->handle();
        return $response->setContent($server->getResponse());
    }

    private function getEiisServiceWsdl(){
        $autoDiscover = (new AutoDiscover())
            ->setClass(EiisServiceController::class)
            ->setUri($this->generateUrl('soapserver_eiis',[], Router::ABSOLUTE_URL))
            ->setServiceName('reporter_service_for_eiis');
        return $autoDiscover->generate()->toXML();
    }
}
