<?php
namespace App\Controller;

use App\Model as Model;

class Comment extends Controller
{
    private $comments;

    function __construct()
    {
        $this->comments = new Model\Comments();
    }

    public function add($parameters)
    {
        $this->comments->add($parameters);
        header("Location: /mvc-framework/articles/show/".$parameters['post_id']);
    }
}