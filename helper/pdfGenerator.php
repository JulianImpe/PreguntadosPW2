<?php
require_once __DIR__ . '/../FPDF/fpdf.php';

class PdfGenerator
{
    private function convertir($texto)
    {
        return mb_convert_encoding($texto, 'ISO-8859-1', 'UTF-8');
    }

    public function generarReporteAdmin($titulo, $tabla)
    {
        $pdf = new FPDF();
        $pdf->AddPage();

        // Título
        $pdf->SetFont('Arial', 'B', 18);
        $pdf->Cell(0, 12, $this->convertir($titulo), 0, 1, 'C');
        $pdf->Ln(5);

        // Encabezados de tabla
        $pdf->SetFont('Arial', 'B', 12);
        foreach ($tabla['headers'] as $header) {
            $pdf->Cell(45, 10, $this->convertir($header), 1, 0, 'C');
        }
        $pdf->Ln();

        // Filas
        $pdf->SetFont('Arial', '', 12);
        foreach ($tabla['rows'] as $row) {
            foreach ($row as $col) {
                $pdf->Cell(45, 10, $this->convertir($col), 1, 0, 'C');
            }
            $pdf->Ln();
        }

        // Enviar PDF al navegador
        $pdf->Output('I', 'reporte.pdf');
    }
}
