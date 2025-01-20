<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use TCPDF;
use setasign\Fpdi\Tcpdf\Fpdi;

class PdfController extends Controller
{
    public function mergePdf()
    {
        $pdf1Path = public_path('upload/pdf1.pdf');
        $pdf2Path = public_path('upload/pdf2.pdf');

        if (!file_exists($pdf1Path) || !file_exists($pdf2Path)) {
            return response()->json(['error' => 'Pliki PDF nie istnieją'], 404);
        }
        
        $pdf = new \setasign\Fpdi\Tcpdf\Fpdi();

        $pdf1 = $pdf->setSourceFile($pdf1Path);
        $pdf2 = $pdf->setSourceFile($pdf2Path);

        $maxPages = max($pdf1, $pdf2);

        for ($i = 1; $i <= $maxPages; $i++) {
            if ($i <= $pdf1) {
                $pdf->setSourceFile($pdf1Path);
                $tplId = $pdf->importPage($i);
                $pdf->AddPage();
                $pdf->useTemplate($tplId);
            }

            if ($i <= $pdf2) {
                $pdf->setSourceFile($pdf2Path);
                $tplId = $pdf->importPage($i);
                $pdf->AddPage();
                $pdf->useTemplate($tplId);
            }
        }

        $outputPath = public_path('upload/merged.pdf');
        $pdf->Output($outputPath, 'F'); // Zapisz plik na serwerze

        return response()->json([
            'message' => 'PDF został połączony i zapisany',
            'file_path' => url('upload/merged.pdf'), // URL do pobrania pliku
        ]);
    }
}