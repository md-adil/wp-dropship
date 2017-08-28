<?php
namespace Bigly\Dropship\Controllers;

require_once(__DIR__ . '/Controller.php');

class CredentialController extends Controller
{
    public function index()
    {
        $this->view('api-credentials.php');
    }

    public function store()
    {
        // die('Storing');
        $this->redirectBack();
    }
}
