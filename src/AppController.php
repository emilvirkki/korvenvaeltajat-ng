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
        $client = $this->get('contentful.delivery');
        $query = new \Contentful\Delivery\Query;
        $query->setContentType('article');
        $query->setLimit(3);
        $articles = $client->getEntries($query);

        return $this->renderTemplate('index', array(
          'articles' => $articles,
        ));
    }

    private function renderTemplate($name, $vars)
    {
        $vars = array_merge($vars, array('template' => $name));
        return $this->render("$name.html.twig", $vars);
    }
}
