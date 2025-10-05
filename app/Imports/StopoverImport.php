<?php

namespace App\Imports;

use App\Models\Data;
use App\Traits\Translate;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeSheet;

class StopoverImport implements ToCollection, WithStartRow, WithMultipleSheets, WithEvents
{
    use Translate;

    public object $load;
    public array $errors = [];

    public function __construct($load)
    {
        $this->load = (object)$load;
    }

    public function collection(Collection $collection)
    {
        if (count($this->errors) > 0) {
            return null;
        }
        $this->errors = [];
        // Encabezados válidos
        $validHeaders = [
            'año' => ['año', 'year'],
            'mes' => ['mes', 'month'],
            'nacionales' => ['nacionales', 'nationals'],
            'extranjeros' => ['extranjeros', 'foreigners'],
            'total' => ['total']
        ];

        $rawHeaders = $collection->first()->toArray();
        $headers = [];
        foreach ($rawHeaders as $h) {
            $hNorm = mb_strtolower(trim($h));
            foreach ($validHeaders as $key => $opts) {
                if (in_array($hNorm, $opts)) {
                    $headers[] = $key;
                    continue 2;
                }
            }
            $headers[] = $hNorm; // Si no es válido, lo deja igual para marcar error
        }

        $required = ['año', 'mes', 'nacionales', 'extranjeros', 'total'];
        $missing = array_diff($required, $headers);
        if (count($missing) > 0) {
            $this->errors[] = 'Encabezado inválido o faltante: ' . implode(', ', $missing);
            return null;
        }

        $rowsToInsert = [];
        foreach ($collection->skip(1) as $i => $row) {
            $rowArr = $row->toArray();
            $rowError = [];

            // Validar año
            $year = trim($rowArr[array_search('año', $headers)] ?? '');
            if (!preg_match('/^\d{4}$/', $year)) {
                $rowError[] = "Fila " . ($i + 2) . ": 'Año' inválido ($year)";
            }

            // Validar y normalizar mes
            $month = trim($rowArr[array_search('mes', $headers)] ?? '');
            $monthNormalized = trim($this->bothMonths($month));
            $monthNormLower = mb_strtolower($monthNormalized);
            $validMonths = [
                'january',
                'february',
                'march',
                'april',
                'may',
                'june',
                'july',
                'august',
                'september',
                'october',
                'november',
                'december',
                'ene',
                'feb',
                'mar',
                'abr',
                'may',
                'jun',
                'jul',
                'ago',
                'sep',
                'oct',
                'nov',
                'dic',
                'enero',
                'febrero',
                'marzo',
                'abril',
                'mayo',
                'junio',
                'julio',
                'agosto',
                'septiembre',
                'octubre',
                'noviembre',
                'diciembre'
            ];
            if (!in_array($monthNormLower, $validMonths)) {
                $rowError[] = "Fila " . ($i + 2) . ": 'Mes' inválido ($month)";
            }

            // Validar nacionales
            $nacionales = trim($rowArr[array_search('nacionales', $headers)] ?? '');
            $nacionalesClean = str_replace([',', ' '], '', $nacionales);
            if (!is_numeric($nacionalesClean)) {
                $rowError[] = "Fila " . ($i + 2) . ": 'Nacionales' inválido ($nacionales)";
            }

            // Validar extranjeros
            $extranjeros = trim($rowArr[array_search('extranjeros', $headers)] ?? '');
            $extranjerosClean = str_replace([',', ' '], '', $extranjeros);
            if (!is_numeric($extranjerosClean)) {
                $rowError[] = "Fila " . ($i + 2) . ": 'Extranjeros' inválido ($extranjeros)";
            }

            // Validar total
            $total = trim($rowArr[array_search('total', $headers)] ?? '');
            $totalClean = str_replace([',', ' '], '', $total);
            if (!is_numeric($totalClean)) {
                $rowError[] = "Fila " . ($i + 2) . ": 'Total' inválido ($total)";
            }

            if ($rowError) {
                $this->errors = array_merge($this->errors, $rowError);
                continue;
            }

            $rowsToInsert[] = [
                'año' => $year,
                'mes' => $monthNormalized,
                'nacionales' => $nacionales,
                'extranjeros' => $extranjeros,
                'total' => $total,
                'rowArr' => $rowArr
            ];
        }

        if (!empty($this->errors)) {
            return null;
        }

        // Insertar datos solo si no hay errores
        foreach ($rowsToInsert as $rowData) {
            try {
                // Convertir mes a abreviatura de 3 letras y obtener número de mes
                $mesCorto = substr(ucfirst(mb_strtolower($rowData['mes'])), 0, 3);

                // Usar un mapeo directo para asegurar la correcta conversión del mes
                $mesesNumeros = [
                    'enero' => '01',
                    'ene' => '01',
                    'january' => '01',
                    'jan' => '01',
                    'febrero' => '02',
                    'feb' => '02',
                    'february' => '02',
                    'marzo' => '03',
                    'mar' => '03',
                    'march' => '03',
                    'abril' => '04',
                    'abr' => '04',
                    'april' => '04',
                    'apr' => '04',
                    'mayo' => '05',
                    'may' => '05',
                    'junio' => '06',
                    'jun' => '06',
                    'june' => '06',
                    'julio' => '07',
                    'jul' => '07',
                    'july' => '07',
                    'agosto' => '08',
                    'ago' => '08',
                    'august' => '08',
                    'aug' => '08',
                    'septiembre' => '09',
                    'sep' => '09',
                    'september' => '09',
                    'octubre' => '10',
                    'oct' => '10',
                    'october' => '10',
                    'noviembre' => '11',
                    'nov' => '11',
                    'november' => '11',
                    'diciembre' => '12',
                    'dic' => '12',
                    'december' => '12',
                    'dec' => '12'
                ];

                // Obtener el número de mes de forma confiable
                $mesKey = mb_strtolower(trim($rowData['mes']));
                $mesNum = $mesesNumeros[$mesKey] ?? '01'; // Valor por defecto si no se encuentra

                // Construir fecha manualmente con el año y mes correcto
                $year = $rowData['año'];
                $month = $mesNum;

                // Crear fechas de inicio y fin
                $startDate = "{$year}-{$month}-01 00:00:00";
                $endMonth = $month == '12' ? '01' : sprintf('%02d', intval($month) + 1);
                $endYear = $month == '12' ? (intval($year) + 1) : $year;
                $endDate = "{$endYear}-{$endMonth}-01 00:00:00";

                $star = $startDate;
                $end = $endDate;



                // Limpiar números (quitar comas)
                $nacionales = str_replace([',', ' '], '', $rowData['nacionales']);
                $extranjeros = str_replace([',', ' '], '', $rowData['extranjeros']);
                $total = str_replace([',', ' '], '', $rowData['total']);

                Data::updateOrCreate([
                    'type_id' => $this->load->type_id,
                    'headline' => $rowData['año'],
                    'key' => $rowData['mes'],
                    'sub' => $mesCorto . ' ' . $rowData['año'],
                    'date_start' => $star,
                    'date_end' => $end
                ], [
                    'attr' => json_encode([
                        'Año' => $rowData['año'],
                        'Mes' => $mesCorto,
                        'Nacionales' => $nacionales,
                        'Extranjeros' => $extranjeros,
                        'Total' => $total
                    ]),
                    'load_id' => $this->load->id
                ]);
            } catch (\Exception $e) {
                $this->errors[]="Revisa la documentacion para subir correctamente el documento";

                Log::error($e->getMessage());
                return $this->errors;
            }
        }

        return null;
    }

    public function startRow(): int
    {
        return 1;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    /**
     * Define las hojas que se procesarán
     */
    public function sheets(): array
    {
        return [
            0 => $this,
        ];
    }

    /**
     * Valida que el Excel tenga solo una hoja
     */
    public function registerEvents(): array
    {
        return [
            BeforeSheet::class => function (BeforeSheet $event) {
                $workbook = $event->getDelegate()->getParent();
                $totalSheets = count($workbook->getAllSheets());
                if ($totalSheets > 1) {
                    $this->errors[] = 'El archivo contiene más de una hoja de trabajo.';
                    return;
                }
            },
        ];
    }
}
