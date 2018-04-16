<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use AppBundle\Utils\Protocol;
use AppBundle\Utils\Tools;
use AppBundle\DependencyInjection\Webserver;

class DefaultController extends Controller
{

    /**
     * @Route("/webserver.php", name="homepage")
     * @Route("/webserver/wsdl.html", name="homepage2")
     */
    public function indexAction()
    {

      header("Content-type: text/xml; charset=utf-8");
      /**
       * Generate WSDL file
       * eg. http://localhost/webserver/wsdl.html
       */
      if ($_SERVER['REQUEST_METHOD'] != 'POST') {

          $content = file_get_contents(__DIR__."/../wsdl/webserver.wsdl");
          header('Content-Length: ' . (function_exists('mb_strlen') ? mb_strlen($content, '8bit') : strlen($content)));

          echo $content;
      }
      /** Create SOAP Server */
      else {
          $soapServer = new \SoapServer(__DIR__."/../wsdl/webserver.wsdl");
          $soapServer->setObject($this->get('webserver'));

          $response = new Response();
          $response->headers->set('Content-Type', 'text/xml; charset=ISO-8859-1');

          ob_start();
          $soapServer->handle();
          $response->setContent(ob_get_clean());

          return $response;
      }
      die();
      return true;
    }


    /**
     * @Route("/test", name="homepage3")
     */
    public function index2Action(Request $request)
    {
        $path = pathinfo($_SERVER['PHP_SELF']);
        $url = $_SERVER['HTTP_HOST'] . $path['dirname'] . '/webserver/wsdl.html?ws=1';
        $url = $_SERVER['SERVER_PORT'] == 443 ? 'https://' . $url : 'http://' . $url;

        $wsdl = $url;
$wsdl = $this->generateUrl('homepage2', array(), true);

        /** Disable the cache of WSDL */
        ini_set('soap.wsdl_cache_enabled', "0");

        $soapClient = new \SoapClient($url);
        try {
            //$result = $soapClient->actionRegister(base64_encode('12345678900987654321                 1.0                2.01                   1'));
            $result = $soapClient->actionTransport('a823ca8d9e2181d12727a03172ed182e', 'EAs7qKpmwrgG418qrz/nzKqdbb5sObuhbMupd6BMJEonKne58YU/u4J5kSsbe86Fdz2dCjYdfmJfU9qKRxIdw0ufWq74qtfkl/uWnDU3kUdNcIewKcqcyEufWq74qtfk');
            var_dump(base64_decode($result));
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        die();
        return true;
    }
}
