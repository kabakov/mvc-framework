<?php
namespace App\Controller\Panel;

use App\Controller\Controller;
use App\Model as Model;
use App\Traits\Auth;
use App\Traits\Response;

class Articles extends Controller
{
    use Auth;
    use Response;

    private $articles, $comments;

    function __construct() {
        $this->articles = new Model\Articles();
        $this->comments = new Model\Comments();
    }

    public function All() {

        $this->articles->getAll();

        echo $this->view(
            "@panel/articles_list.html",
            [
                'title'    => "Список статей",
                'articles' => $this->articles->collection
            ]
        );
    }
    public function show($parameters)
    {
        $this->articles->getByPk($parameters['id']);
        echo $this->view("@panel/article.html", [
            'article' => $this->articles->collection
        ]);
    }

    public function add($parameters) {

        if($this->allow()) {
            $this->articles->getAll();
            $this->articles->add($parameters);
            echo $this->view("@panel/articles_list.html", [
                'title' => "Список статей",
                'articles' => $this->articles->collection,
                'auth' => $this->allow()
            ]);
        } else {
            $this->notAllowed();
        }
    }

    public function delete($parameters)
    {
        $this->comments->getDelAll((int)$parameters['id']);
        $this->articles->getDel($parameters['id']);
        $this->all();
    }

    public function update($parameters)
    {
        $this->articles->update($parameters);

        echo $this->view(
            "@panel/article.html",
            [
                'article'  => $this->articles->collection
            ]
        );
    }
}