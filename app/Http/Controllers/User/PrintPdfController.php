<?php

namespace App\Http\Controllers\User;


use Illuminate\Http\Request;
use App\Models\BalanceTransfer;
use Barryvdh\DomPDF\Facade as PDF;
use App\Http\Controllers\Controller;


class PrintPdfController extends Controller
{
    public function TransferPdf($id)
    {
        $transfer = BalanceTransfer::find($id);

        if (!$transfer) {
            abort(404);
        }

        $data = [
            'logs' => [$transfer],
            'title' => 'Welcome to geniusbank',
            'date' => date('m/d/Y')
        ];

        $pdf = PDF::loadView('user.pdf.transactionPdf', $data);

        return $pdf->download('TransferDetails.pdf');
    }



}


