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

class EmploymentImport implements ToCollection, WithStartRow, WithMultipleSheets, WithEvents
{
    use Translate;
    public $load;
    public array $errors = [];

    public function __construct($load)
    {
        $this->load=(object)$load;
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
    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {

        $data=[];
        $dates=array_filter($collection->first()->toArray());
        $datas=array_filter($collection->skip(1)->first()->toArray());

        $dates = explode('-',current($dates));

        $star = (new Carbon($this->bothMonths($dates[0])))->format('Y-m-d H:i:s');
        $end = (new Carbon($this->bothMonths($dates[1])))->addMonth()->format('Y-m-d H:i:s');

        $headline=null;
        $key=null;
        $sub=null;
        foreach ($collection->skip(2) as $row)
        {
            try {
                $headline = $row[0] ?? $headline;
                if($row[0]){
                    $key = null;
                    $sub = null;
                }else{
                    $key = $row[1] ?? $key;
                    if($row[1]) {
                        $sub=null;
                    }else{
                        $sub = $row[2] ?? $sub;
                    }
                }
                $core= [
                    'Total'=>$row[4],
                    'Hombres'=>$row[5],
                    'Mujeres'=>$row[6]
                ];

                if($row[3]){
                    $data[$sub][$row[3]]= $core;
                }else{
                    $data= $core;
                }



                Data::updateOrCreate([
                    'type_id' => $this->load->type_id,
                    'headline' => $headline,
                    'key' => $key,
                    'sub' =>$sub,
                    'date_start' => $star,
                    'date_end' => $end,
                ], [
                    'attr' => json_encode($data),
                    'load_id' => $this->load->id
                ]);
            }catch (\Exception $e){
                $this->errors[]="Revisa la documentacion para subir correctamente el documento";

                Log::error($e->getMessage());
                return $this->errors;
            }
        }

    }

    public function startRow():int
    {
        return 1;
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
