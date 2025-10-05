<?php

namespace App\Imports;

use App\Models\Data;
use App\Models\Error;
use App\Models\Load;
use App\Traits\Translate;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeSheet;
use Illuminate\Support\Str;

class OperationalImport implements ToCollection, WithMultipleSheets, WithEvents
{
    use Translate;

    public object $load;
    public $errors = [];

    public function __construct($load)
    {
        $this->load = (object)$load;
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
                    $this->errors[] = 'El archivo debe contener solo una hoja de trabajo.';
                    return $this->errors;
                }
            },
        ];
    }

    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {if (count($this->errors) > 0) {
    return;
}
        if ($collection->isEmpty()) {
            $this->errors[] = 'El archivo está vacío';
            return $this->errors;
        }

        // Obtener a�o y mes de las celdas A3 y B3
        $rawYear = $collection[2][0] ?? null;  // �ndice 2 corresponde a la fila 3
        $month = $collection[2][1] ?? null;    // �ndice 2 corresponde a la fila 3

        // Extraer el año numérico usando una expresión regular
        if (preg_match('/\d{4}/', $rawYear, $matches)) {
            $year = $matches[0];
        } else {
            $this->errors[] = 'Formato de año inválido en celda A3: ' . $rawYear;
            return $this->errors;
        }

        if (empty($year) || empty($month)) {
            $this->errors[] = 'No se encontró año o mes en las celdas A3 y B3';
            return $this->errors;
        }

        // Validar año (debe ser de 4 dígitos)
        if (!preg_match('/^\d{4}$/', $year)) {
            $this->errors[] = "Año inválido: $year, debe ser un número de 4 dígitos";
            return $this->errors;
        }

        // Validar mes
        $monthNumber = $this->parseMonth($month);
        if ($monthNumber === null) {
            $this->errors[] = "Mes inválido: $month, debe ser un mes válido en español o inglés";
            return $this->errors;
        }

        try {
            // Crear el intervalo de fechas
            $date = Carbon::createFromDate($year, $monthNumber, 1);

            // Verificar que la fecha sea válida
            if (!$date->isValid()) {
                $this->errors[] = "Fecha inválida: año=$year, mes=$monthNumber";
                return $this->errors;
            }

            $rowsToInsert = [];

            // Obtener el nombre corto del mes
            $monthShort = $this->getMonthShort($monthNumber);

            // Procesar datos desde la fila 5 (�ndice 4)
            $mainHeadline = '';
            foreach ($collection->slice(4) as $r => $row) {
                Log::info("Fila {$r} - columnas: " . count($row) . " - valores: " . json_encode($row));
                if (!isset($row[0]) || trim($row[0]) === '' || !isset($row[1]) || trim($row[1]) === '') {
                    continue;
                }

                try {
                    $headlineValue = trim($row[0]);
                    $value = trim($row[1]);

                    // Validar que el valor sea un porcentaje válido
                    if (!preg_match('/^\d+(\.\d+)?%?$/', $value)) {
                        $this->errors[] = "Fila " . ($r + 5) . ": valor inválido: $value - debe ser un porcentaje";
                        continue;
                    }

                    $region = $headlineValue;
                    $period = $monthShort . ' ' . $year;

                    // Si la fila es "Llegada", "Salida" o "Total"
                    if (in_array(mb_strtolower($headlineValue), ['llegada', 'salida', 'total'])) {
                        $mainHeadline = $headlineValue;
                        $headline = $headlineValue;
                        $key = 'Total';
                        $sub = $period;
                    } else {
                        // El headline es el �ltimo principal, el key es la regi�n, el sub es el periodo
                        $headline = $mainHeadline;
                        $key = $region;
                        $sub = $period;
                    }

                    // Calcular fechas
                    $start = $date->startOfMonth()->format('Y-m-d H:i:s');
                    $end = $date->copy()->addMonth()->startOfMonth()->format('Y-m-d H:i:s');

                    $rowsToInsert[] = [
                        'headline' => $headline,
                        'key' => $key,
                        'sub' => $sub,
                        'value' => $value,
                        'start' => $start,
                        'end' => $end,
                    ];
                } catch (\Exception $e) {
                    $this->errors[] = "Error en fila " . ($r + 5) . ": " . $e->getMessage();
                }
            }

            // Si hay errores, retornarlos sin insertar datos
            if (!empty($this->errors)) {
                return $this->errors;
            }

            // Insertar datos si no hay errores
            foreach ($rowsToInsert as $row) {
                Data::updateOrCreate([
                    'type_id' => $this->load->type_id,
                    'headline' => $row['headline'],
                    'key' => $row['key'],
                    'sub' => $row['sub'],
                    'date_start' => $row['start'],
                    'date_end' => $row['end'],
                ], [
                    'attr' => $this->cleanNumber($row['value']),
                    'load_id' => $this->load->id
                ]);
            }

            return true;
        } catch (\Exception $e) {
            $this->errors[]="Revisa la documentacion para subir correctamente el documento";

                Log::error($e->getMessage());
                return $this->errors;
        }
    }

    private function parseMonth($month)
    {
        $months = [
            'enero' => 1,
            'febrero' => 2,
            'marzo' => 3,
            'mar' => 3,
            'abril' => 4,
            'abr' => 4,
            'mayo' => 5,
            'may' => 5,
            'junio' => 6,
            'jun' => 6,
            'julio' => 7,
            'jul' => 7,
            'agosto' => 8,
            'ago' => 8,
            'septiembre' => 9,
            'sep' => 9,
            'octubre' => 10,
            'oct' => 10,
            'noviembre' => 11,
            'nov' => 11,
            'diciembre' => 12,
            'dic' => 12
        ];

        $monthLower = mb_strtolower(trim($month));
        return $months[$monthLower] ?? null;
    }

    private function cleanNumber($value)
    {
        $clean = str_replace(['%', ','], '', trim($value));
        return is_numeric($clean) ? $clean : 0;
    }

    public function startRow(): int
    {
        return 1;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function sheets(): array
    {
        return [
            0 => $this,
        ];
    }

    private function getMonthShort($monthNumber)
    {
        $monthsShort = [
            1 => 'Ene',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Abr',
            5 => 'May',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Ago',
            9 => 'Sep',
            10 => 'Oct',
            11 => 'Nov',
            12 => 'Dic'
        ];
        return $monthsShort[$monthNumber] ?? '';
    }
}
