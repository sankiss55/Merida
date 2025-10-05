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
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeSheet;

class InpcmeridaImport implements ToCollection, WithStartRow, WithMultipleSheets, WithEvents
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
        if ($collection->isEmpty()) {
            $this->errors[] = 'El archivo está vacío';
            return $this->errors;
        }

        // Validar estructura de encabezados
        $headers = $collection->first()->toArray();

        if (count($headers) < 2 || !isset($headers[0]) || !isset($headers[1])) {
            $this->errors[] = 'Formato de encabezado inválido. Debe tener al menos dos columnas.';
            return $this->errors;
        }

        if (strtolower(trim($headers[0])) !== 'fecha') {
            $this->errors[] = 'La primera columna del encabezado debe ser "Fecha"';
            return $this->errors;
        }

        // El segundo encabezado puede ser cualquier número o combinación de número y texto
        if (!preg_match('/\d+/', $headers[1])) {
            $this->errors[] = 'La segunda columna del encabezado debe contener un número';
            return $this->errors;
        }

        $headerId = trim($headers[1]);
        $rowsToInsert = [];

        // Procesar los datos de cada mes
        foreach ($collection->skip(1) as $i => $row) {
            if (!isset($row[0]) || !isset($row[1])) {
                continue; // Saltar filas sin datos suficientes
            }

            $monthYear = trim($row[0]);
            $value = trim($row[1]);

            // Validar que el valor sea un número flotante válido
            if (!is_numeric($value)) {
                $this->errors[] = "Fila " . ($i + 2) . ": valor inválido: $value - debe ser un número";
                continue;
            }

            // Parsear mes y año del formato "Mes YYYY" (por ejemplo "Ene 2020")
            if (!preg_match('/([a-zA-ZáéíóúÁÉÍÓÚ\.]+)\s*(\d{4})/', $monthYear, $matches)) {
                $this->errors[] = "Fila " . ($i + 2) . ": formato de mes/año inválido: $monthYear";
                continue;
            }

            $month = $matches[1];
            $year = $matches[2];

            // Convertir mes a número usando un diccionario directo
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
            $mesKey = mb_strtolower(trim($month));
            $mesNum = $mesesNumeros[$mesKey] ?? null;

            if ($mesNum === null) {
                $this->errors[] = "Fila " . ($i + 2) . ": mes inválido: $month";
                continue;
            }

            // Obtener nombre corto del mes (3 letras)
            $mesCorto = substr(ucfirst(mb_strtolower($month)), 0, 3);

            // Crear fechas de inicio y fin
            $startDate = "{$year}-{$mesNum}-01 00:00:00";
            $endMonth = $mesNum == '12' ? '01' : sprintf('%02d', intval($mesNum) + 1);
            $endYear = $mesNum == '12' ? (intval($year) + 1) : $year;
            $endDate = "{$endYear}-{$endMonth}-01 00:00:00";

            $rowsToInsert[] = [
                'value' => $value,
                'month' => $month,
                'year' => $year,
                'mesCorto' => $mesCorto,
                'date_start' => $startDate,
                'date_end' => $endDate
            ];
        }

        // Si hay errores, retornarlos sin insertar datos
        if (!empty($this->errors)) {
            return $this->errors;
        }


        // Insertar datos en la base de datos
        foreach ($rowsToInsert as $row) {
            try {
                Data::updateOrCreate([
                    'type_id' => $this->load->type_id,
                    'headline' => trim('INPC Merida'),
                    'key' => $row['mesCorto'] . ' ' . $row['year'], // Ejemplo: "Ene 2020"
                    'sub' => $row['year'], // Año
                    'date_start' => $row['date_start'],
                    'date_end' => $row['date_end']
                ], [
                    'attr' => json_encode([
                        'Fecha' => $row['year'],
                        $headerId => $row['value']
                    ]),
                    'load_id' => $this->load->id
                ]);

                // Registrar en log para debug
                Log::info("Fecha generada para {$row['month']} {$row['year']}: start={$row['date_start']}, end={$row['date_end']}");
            } catch (\Exception $e) {
                $this->errors[]="Revisa la documentacion para subir correctamente el documento";

                Log::error($e->getMessage());
                return $this->errors;
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
