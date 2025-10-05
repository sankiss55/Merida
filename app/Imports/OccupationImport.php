<?php

namespace App\Imports;

use App\Models\Data;
use App\Traits\Translate;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Events\BeforeSheet;
use Maatwebsite\Excel\Concerns\WithEvents;


class OccupationImport implements ToCollection,WithStartRow, WithMultipleSheets, WithEvents
{
    use Translate;

    public object $load;

    public function __construct($load)
    {
        $this->load = (object)$load;
    }
 public function sheets(): array
    {
        return [
            0 => $this,
        ];
    }
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
    /**
     * @param Collection $collection
     */
    public $errors = [];

    public function collection(Collection $collection)
    {
         if( count($this->errors)>0){
            return $this->errors;
        }
        // Encabezados válidos en español e inglés
        $validHeaders = [
            'year' => ['year', 'año', 'anio'],
            'month' => ['month', 'mes'],
            'percent' => ['percent', 'porcentaje', 'porcent', '%']
        ];

        // Normalizar encabezados
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

        // Validar que estén los 3 encabezados requeridos
        $required = ['year', 'month', 'percent'];
        $missing = array_diff($required, $headers);
        if (count($missing) > 0) {
            $this->errors[] = 'Encabezado inválido o faltante: ' . implode(', ', $missing);
            return;
        }

        $rowsToInsert = [];
        foreach ($collection->skip(1) as $i => $row) {
            $rowArr = $row->toArray();
            $data = [];
            $rowError = [];

            // Validar year
            $year = trim($rowArr[array_search('year', $headers)] ?? '');
            $isTotalRow = preg_match('/^total$/i', $year);
            if (!preg_match('/^(\d{4}|total)$/i', $year)) {
                $rowError[] = "Fila " . ($i + 2) . ": 'year' inválido ($year)";
            }

            // Validar y normalizar month usando Translate
            $month = trim($rowArr[array_search('month', $headers)] ?? '');
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
            // Permitir año de 4 dígitos en month solo si year es 'Total'
            if ($isTotalRow) {
                if (!preg_match('/^\d{4}$/', $month)) {
                    $rowError[] = "Fila " . ($i + 2) . ": 'month' inválido ($month) para fila de Total";
                }
            } else {
                if (!in_array($monthNormLower, $validMonths) && !preg_match('/total/i', $monthNormLower)) {
                    $rowError[] = "Fila " . ($i + 2) . ": 'month' inválido ($month)";
                }
            }

            // Validar percent (permitir fórmulas si el resultado es numérico)
            $percent = trim($rowArr[array_search('percent', $headers)] ?? '');
            // Si viene una fórmula, intentar extraer el valor numérico si es posible
            if (is_string($percent) && preg_match('/^=/', $percent)) {
                // Si la celda es fórmula, intentar obtener el valor calculado (si el paquete lo soporta)
                // Si no, marcar como error
                $rowError[] = "Fila " . ($i + 2) . ": 'percent' contiene fórmula, debe ser valor numérico calculado";
            } elseif (!is_numeric($percent)) {
                $rowError[] = "Fila " . ($i + 2) . ": 'percent' inválido ($percent)";
            }

            if ($rowError) {
                $this->errors = array_merge($this->errors, $rowError);
                continue;
            }

            // Guardar datos para insertar después
            $data['year'] = $year;
            $data['month'] = $monthNormalized;
            $data['percent'] = $percent;
            $rowsToInsert[] = [
                'year' => $year,
                'month' => $monthNormalized,
                'percent' => $percent,
                'rowArr' => $rowArr
            ];
        }

        // Si hay errores, retornar errores y no insertar nada
        if (!empty($this->errors)) {
            return $this->errors;
        }

        // Insertar datos solo si no hay errores
        foreach ($rowsToInsert as $rowData) {
            $year = $rowData['year'];
            $monthNormalized = $rowData['month'];
            $percent = $rowData['percent'];
            $rowArr = $rowData['rowArr'];
            // Solo guardar si no es fila de total
            if (!preg_match('/total/i', $year) && !preg_match('/total/i', $monthNormalized)) {
                try {
                    $date = $monthNormalized . ' ' . $year;
                    $star = (new Carbon($this->bothMonths($date)))->format('Y-m-d H:i:s');
                    $end = (new Carbon($this->bothMonths($date)))->addMonth()->format('Y-m-d H:i:s');

                    Data::updateOrCreate(
                        [
                            'type_id' => $this->load->type_id,
                            'headline' => $year,
                            'key' => $monthNormalized,
                            'date_start' => $star,
                            'date_end' => $end
                        ],
                        [
                            'attr' => json_encode([
                                'year' => $year,
                                'month' => $monthNormalized,
                                'percent' => $percent
                            ]),
                            'load_id' => $this->load->id
                        ]
                    );
                } catch (\Exception $e) {
                   
                    Log::error($e->getMessage());
                    $this->errors[]="Revisa la documentacion para subir correctamente el documento";
                return $this->errors;
                }
            }
        }
        return empty($this->errors) ? true : $this->errors;
    }

    public function startRow(): int
    {
        return 1;
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
