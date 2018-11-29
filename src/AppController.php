<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Contentful\Delivery\Query;

class AppController extends AbstractController
{
    /**
     * @Route("/", name="home")
     */
    public function home()
    {
        $articles = $this->getEntries(
            $this->query('article')->setLimit(3)
        );

        return $this->renderTemplate('index', array(
          'articles' => $articles,
        ));
    }

    /**
     * @Route("/ajankohtaista/{slug}", name="article")
     */
    public function showArticle($slug)
    {
        $article = $this->getEntry(
            $this->query('article')->where('fields.slug', $slug)
        );

        return $this->renderTemplate('article', array(
          'article' => $article,
        ));
    }

    private function query($content_type)
    {
        $query = new Query();
        return $query->setContentType($content_type);
    }

    private function getEntries($query)
    {
        $client = $this->get('contentful.delivery');
        return $client->getEntries($query);
    }

    private function getEntry($query)
    {
        $query->setLimit(1);
        $entries = $this->getEntries($query);

        if (count($entries) === 0) {
            throw $this->createNotFoundException('Artikkelia ei löydy');
        }

        return $entries[0];
    }

    private function renderTemplate($name, $vars)
    {
        $vars = array_merge($vars, array('template' => $name));
        return $this->render("$name.html.twig", $vars);
    }
}
