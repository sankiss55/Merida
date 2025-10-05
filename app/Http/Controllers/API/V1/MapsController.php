<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\API\BaseController;
use App\Models\Area;
use App\Models\Load;
use App\Models\Type;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MapsController extends BaseController
{

    public $start;
    public $end;
    public $areas;
    public $locations = [];
    public $sql;

    public function __construct()
    {
        parent::__construct();
        $this->allowedFields = ['id', 'name', 'created_at', 'updated_at'];
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function airdnd(Request $request)
    {
        Log::info('Airdna request received');
        $validated = validator($request->all(), [
            'start' => 'required|date',
            'end' => 'required|date|after_or_equal:start',
            'areas' => 'sometimes|array',
        ]);

        if ($validated->fails())
            return response($validated->errors(), 400);

        $this->areas = $request->areas;

        $arrStart = explode('-', $request->start);
        $arrEnd   = explode('-', $request->end);

        /*$this->start=(new Carbon($request->start))->format('Y-m-d 00:00:00');
            $this->end=(new Carbon($request->end))->format('Y-m-d 23:59:59');*/

        $this->start = $arrStart[0] . '-' . str_pad($arrStart[1], 2, '0', STR_PAD_LEFT) . '-' . str_pad($arrStart[2], 2, '0', STR_PAD_LEFT) . ' 00:00:00';
        $this->end   = $arrEnd[0] . '-' . str_pad($arrEnd[1], 2, '0', STR_PAD_LEFT)  . '-' . str_pad($arrEnd[2], 2, '0', STR_PAD_LEFT) . ' 23:59:59';

        try {
            // El type_id para Airdna es 13
            $typeId = 13;

           //$query = DB::table('loads')
    //->join('data', 'loads.id', '=', 'data.load_id')
    //->join('area_location', 'data.id', '=', 'area_location.location_id')
    //->join('areas', 'area_location.area_id', '=', 'areas.id')
    //->where('loads.type_id', '=', $typeId)
    //->whereBetween('data.date_start', [$this->start, $this->end])
   // ->whereRaw('(CAST(JSON_EXTRACT(data.attr,"$.active") AS UNSIGNED) = 1 OR JSON_EXTRACT(data.attr,"$.active") = true)')
    //->whereRaw('(CAST(JSON_EXTRACT(data.attr,"$.scraped_during_month") AS UNSIGNED) = 1 OR JSON_EXTRACT(data.attr,"$.scraped_during_month") = true)')
   // ->when($request->areas, function ($query, $areas) {
   //     $query->whereIn('areas.id', array_values($areas));
   // })
   // ->orderBy('data.date_start', 'asc')
   // ->select(
    //    'data.id',
    //    'data.date_start',
    //    'data.attr',
      //  DB::raw('areas.id AS area_id'),
        //DB::raw('areas.name AS area_name')
    //);


            $query = DB::table('loads')
    ->join('data','loads.id','=','data.load_id')
    ->join('area_location','data.id','=','area_location.location_id')
    ->join('areas','area_location.area_id','=','areas.id')
    ->where('loads.type_id', 13)
    ->whereBetween('data.date_start', [$this->start, $this->end])
    ->whereRaw('JSON_EXTRACT(data.attr, "$.active") = "1"')
    ->whereRaw('JSON_EXTRACT(data.attr, "$.scraped_during_month") = "1"')
    ->when($request->areas, fn($query, $areas) => $query->whereIn('areas.id', array_values($areas)))
    ->orderBy('data.id', 'desc')
    ->select('data.*', DB::raw('areas.id AS area_id'), DB::raw('areas.name AS area_name'));


            $load = $query->get();

            $this->locations = collect($load)->map(function ($el) {
                return [
                    'id' => $el->id,
                    'json' => json_decode($el->attr),
                    'date_start' => $el->date_start,
                    'area_id' => $el->area_id,
                    'area_name' => $el->area_name,
                ];
            });

            if (!is_null($load)) {
                $this->response = [
                    'body' => [
                        'locations' => $this->locations,
                        'start' => "{$this->start}",
                        'end' => $this->end,
                    ],
                    'message' => 'Successfully'
                ];
                $this->code = 200;
            }
        } catch (\Exception $e) {
            Log::error('Error in airdna: ' . $e->getMessage());
            $this->response = [
                'body' => null,
                'message' => 'Error: ' . $e->getMessage()
            ];
            $this->code = 500;
        }

        return response($this->response, $this->code);
    }
public function statistics(Request $request)
{
    $validated = validator($request->all(), [
        'start' => 'required|date',
        'end'   => 'required|date|after_or_equal:start',
        'areas' => 'sometimes|array'
    ]);

    if ($validated->fails()) {
        return response($validated->errors(), 400);
    }

    $start = $request->start;
    $end   = $request->end;
    $areas = (isset($request->areas) && count($request->areas) > 0) ? implode(',', $request->areas) : [];

    $type_id = 13; // Fijo en 13

    $this->sql = "SELECT 
        DATE_FORMAT(D.date_start, '%Y-%m') AS sort,
        DATE_FORMAT(D.date_start, '%Y-%b') AS month_year,

        -- Tarifas
        ROUND(AVG(NULLIF(CAST(JSON_EXTRACT(D.attr,'$.adr_native') AS DECIMAL(10,2)),0)), 2) AS adr_native,
        ROUND(AVG(NULLIF(CAST(JSON_EXTRACT(D.attr,'$.adr_usd') AS DECIMAL(10,2)),0)), 2) AS adr_usd,

        -- Unidades activas
        COUNT(DISTINCT CASE WHEN CAST(JSON_EXTRACT(D.attr,'$.active') AS UNSIGNED) = 1 
            THEN JSON_UNQUOTE(JSON_EXTRACT(D.attr,'$.property_id')) END) AS assets,

        -- Ingreso promedio por día reservado
        ROUND(AVG(
            CASE 
                WHEN CAST(JSON_EXTRACT(D.attr,'$.reservation_days') AS UNSIGNED) > 0 
                THEN CAST(JSON_EXTRACT(D.attr,'$.revenue_native') AS DECIMAL(10,2)) 
                     / CAST(JSON_EXTRACT(D.attr,'$.reservation_days') AS UNSIGNED)
                ELSE 0
            END
        ), 2) AS fee_revenue_native,

        -- Ocupación promedio
        ROUND(AVG(CAST(JSON_EXTRACT(D.attr,'$.occupancy_rate') AS DECIMAL(10,3)) * 100), 2) AS ocupancy,

        -- Reservas y días reservados
AVG(CAST(JSON_EXTRACT(attr, '$.available_days') AS DECIMAL(10,2))) AS  reservations,
AVG(CAST(JSON_EXTRACT(attr, '$.reservation_days') AS DECIMAL(10,2))) AS  reservation_days,


        -- Revenue promedio
        ROUND(AVG(CAST(JSON_EXTRACT(D.attr,'$.revenue_native') AS DECIMAL(10,2))), 2) AS revenue_native,
        ROUND(AVG(
            (CAST(JSON_EXTRACT(D.attr,'$.adr_usd') AS DECIMAL(10,2)))
            * (CAST(JSON_EXTRACT(D.attr,'$.occupancy_rate') AS DECIMAL(10,3)))
            * (CAST(JSON_EXTRACT(D.attr,'$.available_days') AS UNSIGNED))
        ), 2) AS revenue_usd

    FROM loads AS L
    INNER JOIN data AS D ON L.id = D.load_id ";

    if (!empty($areas)) {
        $this->sql .= " INNER JOIN area_location AS AL
                        ON D.id = AL.location_id
                        INNER JOIN areas AS A
                        ON AL.area_id = A.id ";
    }

    $this->sql .= "WHERE L.type_id = $type_id
        AND (D.date_start BETWEEN DATE_FORMAT('$start','%Y-%m-%d %H:%i:%s')
                            AND DATE_FORMAT('$end','%Y-%m-%d %H:%i:%s')) 
        AND CAST(JSON_EXTRACT(D.attr,'$.active') AS UNSIGNED) = 1 
        AND CAST(JSON_EXTRACT(D.attr,'$.scraped_during_month') AS UNSIGNED) = 1 ";

    if (!empty($areas)) {
        $this->sql .= " AND A.id IN ($areas)";
    }

    $this->sql .= " GROUP BY month_year, sort
                    ORDER BY sort ASC";

    $result = DB::select(DB::raw($this->sql));

    if (!is_null($result)) {
        $this->response = [
            'body' => $result,
            'message' => 'Successfully'
        ];
        $this->code = 200;
    }

    return response($this->response, $this->code);
}






    public function areas(Request $request)
    {
        $validated = validator($request->all(), ['areas' => 'sometimes|array']);

        if ($validated->fails())
            return response($validated->errors(), 400);

        $Areas = Area::select('id', 'name')
            ->with(['coordinates' => function ($coordinates) {
                $coordinates->select('id', 'lat', 'lng', 'area_id');
            }])->when($request->areas, function ($query, $areas) {
                $query->whereIn('id', array_values($areas));
            })
            ->where('visible', '=', 1)
            ->orderBy('name', 'asc')
            ->get();



        if (!is_null($Areas)) {
            $this->response = [
                'areas' => $Areas,
                'message' => 'Successfully'
            ];
            $this->code = 200;
        }

        return response($this->response, $this->code);
    }
}
