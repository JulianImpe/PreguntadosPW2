<?php

require_once __DIR__ . '/../helper/dompdf/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

class PdfGenerator
{
    private $dompdf;

    public function __construct()
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Courier');

        $this->dompdf = new Dompdf($options);
    }

    public function generarReporteAdmin($titulo, $tabla)
    {
        $html = $this->generarHtmlReporte($titulo, $tabla);

        $this->dompdf->loadHtml($html);
        $this->dompdf->setPaper('A4', 'portrait');
        $this->dompdf->render();

        $this->dompdf->stream('reporte_admin_' . date('Y-m-d_His') . '.pdf', [
            'Attachment' => false
        ]);
    }

    private function generarHtmlReporte($titulo, $tabla)
    {
        $html = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: "Courier New", Courier, monospace;
            padding: 30px;
            background-color: #ffffff;
            color: #000000;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 25px;
            background-color: #EF4444;
            border: 2px solid #000000;
        }

        h1 {
            color: #ffffff;
            font-size: 24px;
            margin-bottom: 8px;
            text-transform: uppercase;
            font-weight: bold;
        }

        .fecha {
            color: #ffffff;
            font-size: 11px;
            margin-top: 8px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            border: 2px solid #000000;
        }

        th {
            background-color: #000000;
            color: #ffffff;
            padding: 14px 12px;
            text-align: left;
            font-weight: bold;
            font-size: 13px;
            text-transform: uppercase;
            border: 1px solid #000000;
        }

        td {
            padding: 12px;
            border: 1px solid #000000;
            font-size: 12px;
            background-color: #ffffff;
            font-weight: bold;
        }

        tr:nth-child(even) td {
            background-color: #f5f5f5;
        }

        tr.seccion-header td {
            background-color: #EF4444;
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 12px;
            padding: 12px;
            border: 1px solid #000000;
        }

        tr.separador td {
            padding: 6px;
            background-color: #000000;
            border: none;
            height: 3px;
        }

        .valor {
            text-align: right;
            font-weight: bold;
            color: #EF4444;
        }

        .footer {
            margin-top: 40px;
            padding: 20px;
            text-align: center;
            font-size: 10px;
            color: #ffffff;
            background-color: #000000;
            border: 2px solid #000000;
            font-weight: bold;
        }

        .footer p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>' . htmlspecialchars($titulo) . '</h1>
        <p class="fecha">Generado: ' . date('d/m/Y H:i:s') . '</p>
    </div>
    
    <table>
        <thead>
            <tr>';

        foreach ($tabla['headers'] as $header) {
            $html .= '<th>' . htmlspecialchars($header) . '</th>';
        }

        $html .= '</tr>
        </thead>
        <tbody>';

        foreach ($tabla['rows'] as $row) {

            if (empty(trim($row[0])) && empty(trim($row[1]))) {
                $html .= '<tr class="separador"><td colspan="2"></td></tr>';
                continue;
            }

            if (empty(trim($row[1])) && strtoupper($row[0]) === $row[0]) {
                $html .= '<tr class="seccion-header"><td colspan="2">' . htmlspecialchars($row[0]) . '</td></tr>';
                continue;
            }

            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($row[0]) . '</td>';
            $html .= '<td class="valor">' . htmlspecialchars($row[1]) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>
    </table>
    
    <div class="footer">
        <p>Pokemon Trivia - Sistema de Administracion</p>
        <p>Reporte Confidencial - Uso Interno</p>
    </div>
</body>
</html>';

        return $html;
    }
}
