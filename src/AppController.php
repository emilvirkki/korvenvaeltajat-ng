<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class AppController extends AbstractController
{
    /**
     * @Route("/")
     */
    public function index()
    {
        return $this->renderTemplate('index', array(
          'name' => 'world'
        ));
    }

    private function renderTemplate($name, $vars) {
        $vars = array_merge($vars, array('template' => $name));
        return $this->render("$name.html.twig", $vars);
    }
}
