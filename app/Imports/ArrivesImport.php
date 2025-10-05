<?php

namespace App\Imports;

use App\Models\Data;
use App\Models\Load;
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

class ArrivesImport implements ToCollection, WithStartRow, WithMultipleSheets, WithEvents
{
    public array $errors = [];
    use Translate;

    public object $load;

    // Mapa de encabezados a claves internas
    protected array $headerMap = [
        'year' => 'Year',
        'año' => 'Year',
        'month' => 'Month',
        'mes' => 'Month',
        'domestic' => 'Domestic',
        'nacional' => 'Domestic',
        'domestico' => 'Domestic',
        'internacional' => 'International',
        'international' => 'International',
        'total' => 'Total'
    ];

    public function __construct($load)
    {
        $this->load = (object)$load;
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
     * Define las hojas que se procesarán
     */
    public function sheets(): array
    {
        return [
            0 => $this,
        ];
    }
    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {
        
        if( count($this->errors)>0){
            return $this->errors;
        }
        $this->errors = [];
        $headlines = array_filter($collection->first()->toArray());
        $data = [];

        // Función para normalizar encabezados
        $normalize = function ($header) {
            $header = mb_strtolower($header, 'UTF-8'); // minúsculas
            $header = str_replace(
                ['á', 'é', 'í', 'ó', 'ú',],
                ['a', 'e', 'i', 'o', 'u'],
                $header
            );
            return trim($header);
        };

        // Lista de conceptos 
        $requiredConcepts = [
            'year' => ['year', 'año'],
            'month' => ['month', 'mes'],
            'domestic' => ['domestic', 'domestico', 'nacional'],
            'international' => ['international', 'internacional'],
            'total' => ['total']
        ];

        // Normalizar encabezados del archivo
        $normalizedHeaders = [];
        foreach ($headlines as $cell) {
            $normalizedHeaders[] = $normalize($cell);
        }

        // Validar que todos los conceptos requeridos estén presentes una sola vez
        $found = [
            'year' => false,
            'month' => false,
            'domestic' => false,
            'international' => false,
            'total' => false
        ];
        foreach ($normalizedHeaders as $header) {
            foreach ($requiredConcepts as $concept => $options) {
                if (in_array($header, $options)) {
                    if ($found[$concept]) {
                        $this->errors[] = "Encabezado duplicado para '$concept'.";
                    }
                    $found[$concept] = true;
                }
            }
        }
        if (in_array(false, $found, true)) {
            $this->errors[] = 'Encabezado incorrecto. Debe contener exactamente una columna para cada uno: Year/Año, Month/Mes, Domestic/Doméstico/Nacional, International/Internacional, Total. Encabezado recibido: ' . implode(', ', $headlines);
        }

        // Validar filas solo si encabezado es correcto
        if (empty($this->errors)) {
            $rowNum = 2; // porque la primera es encabezado
            foreach ($collection->skip(1) as $row) {
                // Saltar filas vacías
                if (count(array_filter($row->toArray())) === 0) {
                    $rowNum++;
                    continue;
                }
                // Saltar totales
                if (!Str::contains(strtolower($row[0]), 'total') && !Str::contains(strtolower($row[1]), 'total')) {
                    $data = [];
                    foreach ($headlines as $k => $cell) {
                        $key = $normalize($cell);
                        if (isset($this->headerMap[$key])) {
                            $data[$this->headerMap[$key]] = trim($row[$k]);
                        }
                    }

                    $year = isset($data['Year']) ? $data['Year'] : null;
                    $month = isset($data['Month']) ? $data['Month'] : null;
                    $domestic = isset($data['Domestic']) ? $data['Domestic'] : null;
                    $international = isset($data['International']) ? $data['International'] : null;
                    $total = isset($data['Total']) ? $data['Total'] : null;

                    // Validar año: 4 dígitos
                    if (!preg_match('/^\d{4}$/', $year)) {
                        $this->errors[] = "Fila $rowNum: El año no es válido: $year";
                    }
                    // Validar mes: solo letras
                    if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ]+$/u', str_replace(' ', '', $month))) {
                        $this->errors[] = "Fila $rowNum: El mes contiene caracteres inválidos: $month";
                    } else {
                        // Validar que sea un mes válido (español o inglés, 3 letras o completo)
                        $mesValido = [
                            // Español abreviado y completo
                            'ene',
                            'enero',
                            'feb',
                            'febrero',
                            'mar',
                            'marzo',
                            'abr',
                            'abril',
                            'may',
                            'mayo',
                            'jun',
                            'junio',
                            'jul',
                            'julio',
                            'ago',
                            'agosto',
                            'sep',
                            'sept',
                            'septiembre',
                            'oct',
                            'octubre',
                            'nov',
                            'noviembre',
                            'dic',
                            'diciembre',
                            // Inglés abreviado y completo
                            'jan',
                            'january',
                            'feb',
                            'february',
                            'mar',
                            'march',
                            'apr',
                            'april',
                            'may',
                            'jun',
                            'june',
                            'jul',
                            'july',
                            'aug',
                            'august',
                            'sep',
                            'sept',
                            'september',
                            'oct',
                            'october',
                            'nov',
                            'november',
                            'dec',
                            'december'
                        ];
                        $mesNormalizado = mb_strtolower($month, 'UTF-8');
                        $mesNormalizado = str_replace(
                            ['á', 'é', 'í', 'ó', 'ú'],
                            ['a', 'e', 'i', 'o', 'u'],
                            $mesNormalizado
                        );
                        if (!in_array($mesNormalizado, $mesValido)) {
                            $this->errors[] = "Fila $rowNum: El mes no es un mes válido: $month";
                        }
                    }
                    // Validar números: pueden tener comas
                    foreach ([['Domestic', $domestic], ['International', $international], ['Total', $total]] as [$label, $value]) {
                        $val = str_replace([',', ' '], '', $value);
                        if (!is_numeric($val)) {
                            $this->errors[] = "Fila $rowNum: El campo $label no es numérico: $value";
                        }
                    }
                }
                $rowNum++;
            }
        }

        // Si hay errores, no insertar y devolverlos
        if (!empty($this->errors)) {
            return $this->errors;
        }

        // Si todo está bien, hacer la inserción
        $rowNum = 2;
        foreach ($collection->skip(1) as $row) {
            if (count(array_filter($row->toArray())) === 0) {
                $rowNum++;
                continue;
            }
            if (!Str::contains(strtolower($row[0]), 'total') && !Str::contains(strtolower($row[1]), 'total')) {
                try {
                    $data = [];
                    foreach ($headlines as $k => $cell) {
                        $key = $normalize($cell);
                        if (isset($this->headerMap[$key])) {
                            $data[$this->headerMap[$key]] = trim($row[$k]);
                        }
                    }

                    $mes = ucfirst(mb_strtolower($data['Month']));
                    $date = "{$mes} {$data['Year']}";

                    $start = (new Carbon($this->bothMonths($date)))->format('Y-m-d H:i:s');
                    $end = (new Carbon($this->bothMonths($date)))->addMonth()->format('Y-m-d H:i:s');

                    Data::updateOrCreate(
                        [
                            'type_id' => $this->load->type_id,
                            'headline' => trim($data['Year']),
                            'key' => trim($mes),
                            'sub' => trim($date),
                            'date_start' => $start,
                            'date_end' => $end
                        ],
                        [
                            'attr' => json_encode($data),
                            'load_id' => $this->load->id
                        ]
                    );
                } catch (\Exception $e) {
                     $this->errors[]="Revisa la documentacion para subir correctamente el documento";
      return $this->errors;
                }
            }
            $rowNum++;
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
}
