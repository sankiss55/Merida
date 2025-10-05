<?php

namespace App\Imports;

use App\Models\Data;
use App\Traits\Translate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Events\BeforeSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
class InpcnacionalImport implements ToCollection,WithStartRow, WithMultipleSheets, WithEvents
{
    use Translate;
    public object $load;

    public function __construct($load)
    {
        $this->load=(object)$load;
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
    
    public $errors = [];
    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
         if( count($this->errors)>0){
            return $this->errors;
        }
        $row=$collection->first();
        $headlines=$row->toArray();
        $data=[];

        foreach ($collection->skip(1) as $row)
        {
            if(!is_null($row[0])) {
                foreach ($headlines as $k => $cell) {
                    $data[trim($cell)] = trim($row[$k]);
                }
                try {
                   
  $raw = trim($row[0]);
$raw = str_replace(['-', '/'], ' ', $raw);
$raw = preg_replace('/\s+/', ' ', $raw);
[$mesRaw, $anioRaw] = explode(' ', $raw);
$mes = ucfirst(mb_strtolower($mesRaw));

$anio = substr(trim($anioRaw), 0, 4);

$mesCompleto = "$mes $anio";
$star = (new Carbon($this->bothMonths($mesCompleto)))->firstOfMonth()->format('Y-m-d H:i:s');
$end   = (new Carbon($this->bothMonths($mesCompleto)))->addMonth()->firstOfMonth()->format('Y-m-d H:i:s');

                    Data::updateOrCreate([
                        'type_id' => $this->load->type_id,
                        'headline' => trim('INPC Merida'),
                        'key' => trim($row[0]),
                        'date_start' => $star,
                        'date_end' => $end],
                        [
                            'attr' => json_encode($data),
                            'load_id' => $this->load->id
                        ]);
                }catch (\Exception $e){
                    Log::error($e->getMessage());
                    $this->errors[]="Revisa la documentacion para subir correctamente el documento";

                return $this->errors;
                }
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
