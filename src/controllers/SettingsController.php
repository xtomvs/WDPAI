<?php

require_once 'AppController.php';

class SettingsController extends AppController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $this->requireLogin();
        return $this->render('settings');
    }
}
