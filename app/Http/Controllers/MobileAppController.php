<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\View\View;

class MobileAppController extends Controller
{
    public function index(): View
    {
        $products = Product::orderBy('name')->get();

        $enquirySources = [
            'WhatsApp',
            'Instagram',
            'Store Walk-in',
            'Facebook',
            'Phone Call',
            'Referral',
            'Website',
            'Other',
        ];

        return view('app.index', compact('products', 'enquirySources'));
    }
}
