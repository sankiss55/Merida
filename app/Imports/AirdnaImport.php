<?php

namespace App\Imports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithStartRow;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeSheet;
use App\Models\Data;
use App\Models\AreaLocation;

class AirdnaImport implements ToCollection, WithStartRow, WithMultipleSheets, WithEvents
{
    
    public array $errors = [];
    public object $load;
    public array $headings;

    public function __construct($load, $headings)
    {
        $this->headings = $headings[0][0]; 
    $this->load = $load;
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
        foreach ($collection as $index => $row)  
        {
            $data = [];
            foreach ($this->headings as $k => $cell) {
                $data[trim($cell)] = trim($row[$k]);
            }

            try {
                $split = explode('/', $row[4]);
                $start = Carbon::create(
                    intval($split[2]),
                    intval($split[1]),
                    intval($split[0])
                )->format('Y-m-d H:i:s');

                $cDateSub = intval($split[0]) . '/' . intval($split[1]) . '/' . intval($split[2]);
                $end = (new Carbon($start))->endOfMonth()->format('Y-m-d H:i:s'); 

               $item = Data::updateOrCreate(
    [
        'type_id' => $this->load->type_id,
        'key'     => trim($row[0]),
        'load_id' => $this->load->id
    ],
    [
        'headline'   => '',
        'sub'        => trim($cDateSub),
        'date_start' => $start,
        'date_end'   => $end,
        'attr'       => json_encode($data)
    ]
);


                $cAux = strtolower($data["neighborhood"]);
                $aux = [];
                $aux['location_id'] = $item->id;
                $aux['area_id'] = 1;

                if (str_contains($cAux, 'merida')) {
                    if (str_contains($cAux, 'centro') || str_contains($cAux, 'center')) {
                        $aux['area_id'] = 2;
                    } else {
                        if (str_contains($cAux, 'nort') || str_contains($cAux, 'nord')) {
                            if (str_contains($cAux, 'este')) $aux['area_id'] = 3;
                            if (str_contains($cAux, 'oeste')) $aux['area_id'] = 6;
                        }
                        if (str_contains($cAux, 'sur') || str_contains($cAux, 'sud')) {
                            if (str_contains($cAux, 'este')) $aux['area_id'] = 4;
                            if (str_contains($cAux, 'oeste')) $aux['area_id'] = 5;
                        }
                    }
                }

                AreaLocation::where('location_id', $item->id)->delete();
                AreaLocation::insert($aux);

            } catch (\Exception $ex) {
                $this->errors[]="Revisa la documentacion para subir correctamente el documento";

                Log::error( $ex->getMessage());
                return $this->errors;
            }
        }

        Log::info('AirdnaImport finalizado.');
    }

    public function startRow(): int
    {
        return 2; 
    }

    public function batchSize(): int
    {
        return 500;
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
