<?php

require_once 'AppController.php';

class HabitsController extends AppController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $this->requireLogin();
        return $this->render('habits');
    }
}
