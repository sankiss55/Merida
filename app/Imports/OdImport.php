<?php

namespace App\Imports;

use App\Models\Data;
use App\Models\Error;
use App\Models\Load;
use App\Traits\Translate;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Events\BeforeSheet;

class OdImport implements ToCollection, WithStartRow, WithMultipleSheets, WithEvents
{
    use Translate;

    public object $load;

    public $errors = [];
    public function __construct($load)
    {
        $this->load = (object)$load;
    }
    /**
     * @param Collection $collection
     */
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

                // Log para depuración
                Log::info("Total de hojas encontradas en el archivo: " . $totalSheets);

                if ($totalSheets > 1) {
                    $sheetNames = [];
                    foreach ($workbook->getAllSheets() as $sheet) {
                        $sheetNames[] = $sheet->getTitle();
                    }

                    $errorMessage = 'El archivo contiene ' . $totalSheets . ' hojas de trabajo. Solo se permite una hoja. Hojas encontradas: ' . implode(', ', $sheetNames);
                    $this->errors[] = $errorMessage;

                    Log::warning($errorMessage);

                    // Crear registro de error en la base de datos
                    Error::create([
                        'code' => 'MULTIPLE_SHEETS',
                        'message' => $errorMessage,
                        'load_id' => $this->load->id
                    ]);

                    // Actualizar el estado de la carga
                    $Load = Load::find($this->load->id);
                    $Load->status = 'Error';
                    $Load->save();

                    return;
                }

                Log::info("Validación de hojas completada: archivo válido con una sola hoja");
            },
        ];
    }
    public function collection(Collection $collection)
    {
        // Verificar si hay errores antes de procesar
        if (count($this->errors) > 0) {
            Log::error("Errores encontrados durante la validación: ", $this->errors);

            // Actualizar el estado de la carga si no se hizo antes
            $Load = Load::find($this->load->id);
            if ($Load && $Load->status !== 'Error') {
                $Load->status = 'Error';
                $Load->save();
            }

            return $this->errors;
        }

        Log::info("Iniciando procesamiento del archivo - Validación de hojas completada exitosamente");
        $months = array_filter($collection->first()->toArray());
        $intervals = [];
        foreach ($months as $m => $month) {
            try {
                // Depurar y convertir el mes a formato en ingl�s
                Log::info("Mes original: " . $month);
                $monthsMap = [
                    'Enero' => 'January',
                    'Febrero' => 'February',
                    'Marzo' => 'March',
                    'Abril' => 'April',
                    'Mayo' => 'May',
                    'Junio' => 'June',
                    'Julio' => 'July',
                    'Agosto' => 'August',
                    'Septiembre' => 'September',
                    'Octubre' => 'October',
                    'Noviembre' => 'November',
                    'Diciembre' => 'December'
                ];
                $monthCleaned = trim($month);
                $monthTranslated = $monthsMap[$monthCleaned] ?? '';
                Log::info("Mes traducido: " . $monthTranslated);

                // Asegurarnos de que el mes est� en el formato correcto
                if (empty($monthTranslated)) {
                    throw new \Exception("No se pudo traducir el mes: " . $month);
                }

                // Construir la fecha usando el formato est�ndar en ingl�s
                $dateString = "1 " . $monthTranslated . " " . date('Y');
                Log::info("Fecha a procesar: " . $dateString);

                $carbonDate = Carbon::createFromFormat('j F Y', $dateString);
                $intervals[$m]['start'] = $carbonDate->startOfMonth()->format('Y-m-d H:i:s');
                $intervals[$m]['end'] = $carbonDate->copy()->endOfMonth()->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                Log::error($e->getMessage());
                Error::create(['code' => $e->getCode(), 'message' => $e->getMessage(), 'load_id' => $this->load->id]);
                $Load = Load::find($this->load->id);
                $Load->status = 'Error';
                $Load->save();
            }
        }

        $headlines = collect(array_filter($collection->skip(1)->first()->toArray()));
        $headlines = array_unique($headlines->toArray());
        $index = 0;
        $type_old = '';
        $key_old = '';
        foreach ($collection->skip(2) as $r => $row) {

            foreach ($intervals as $i => $interval) {

                if (!Str::contains($row[0], 'Total')  &&  !Str::contains($row[1], 'Total')) {
                    try {

                        $type = $row[0] ?? $type_old;
                        $type_old = $type ? $type : $type_old;
                        $register[$index]['headline'] = $type;
                        $title = $row[1] ?? $key_old;
                        $key_old = $title ? $title : $key_old;
                        $register[$index]['key'] = $title;
                        $sub = trim(preg_replace('/\[[A-Z]{3}\]/', '', $row[2]));
                        $register[$index]['sub'] = $sub;
                        $register[$index]['date_start'] = $interval['start'];
                        $register[$index]['date_end'] = $interval['end'];
                        $data = [];
                        $c = $i;
                        foreach ($headlines as $headline) {
                            $data[$i][$headline] = $row[$c] ?? 0;
                            $data[$i]['province'] = $sub;
                            $data[$i]['type'] = $type;
                            if (preg_match('/\[[A-Z]{3}\]/', $row[2], $m)) {
                                $data[$i]['code'] = preg_replace('/\[|\]/', '', $m[0]);
                            }
                            ++$c;
                        }

                        $register[$index]['attr'] = json_encode($data[$i]);

                        Data::updateOrCreate([
                            'type_id' => $this->load->type_id,
                            'headline' => trim($register[$index]['headline']),
                            'key' => trim($register[$index]['key']),
                            'sub' => trim($register[$index]['sub']),
                            'date_start' => trim($register[$index]['date_start']),
                            'date_end' => trim($register[$index]['date_end'])
                        ], [
                            'attr' => trim($register[$index]['attr']),
                            'load_id' => $this->load->id
                        ]);
                    } catch (\Exception $e) {
                        Log::error($e->getMessage());
                        Error::create(['code' => $e->getCode(), 'message' => $e->getMessage(), 'load_id' => $this->load->id]);
                        $Load = Load::find($this->load->id);
                        $Load->status = 'Error';
                        $Load->save();$this->errors[]="Revisa la documentacion para subir correctamente el documento";

                Log::error($e->getMessage());
                return $this->errors;
                    }

                    ++$index;
                }
            }
        }
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
