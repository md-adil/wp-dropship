<?php
namespace Bigly\Dropship\Controllers;

/**
*
*/
class HomeController extends Controller
{
    public function index()
    {
        return $this->view('home.php');
    }
}
