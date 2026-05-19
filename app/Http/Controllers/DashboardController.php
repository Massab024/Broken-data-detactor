<?php

namespace App\Http\Controllers;

use App\Jobs\ProductSyncJob;



class DashboardController extends Controller
{
    public function index()
    {
        ProductSyncJob::dispatch(auth()->user()->id);
        return $this->render('Dashboard');
    }
}
