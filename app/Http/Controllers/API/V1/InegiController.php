<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\API\BaseController;
use App\Http\Controllers\Controller;
use Faker\Provider\Base;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;


class InegiController extends BaseController
{
    protected $sql="";

    public function companies_init(Request $request)
    {


        $areas='';

        $validated=validator($request->all(),['areas'=>'sometimes|array']);

        if($request->areas) $areas = implode(',', $request->areas);

        if($validated->fails())
            return response($validated->errors(),400);


        $this->sql="SELECT 
            D.id as location_id,
            D.sub AS name,
            REPLACE(json_extract(D.`attr`,\"$.Latitud\"),'\"','') AS lat,
            REPLACE(json_extract(D.`attr`,\"$.Longitud\"),'\"','') AS lng,
            REPLACE(REPLACE(json_extract(D.`attr`,\"$.Estrato\"),'\"',''),'\\\u00e1','á') AS estrato
            FROM data AS D
            INNER JOIN area_location AS AL
            ON D.id=AL.location_id
            WHERE L.type_id IN (SELECT id FROM types WHERE NAME REGEXP 'INEGI Estratos')";

                // $result = DB::table('data')->where('type_id',17)
                // // ->join('area_location', function ($join) {
                // //     $join->on('data.id', '=', 'area_location.location_id');           
                // // })
                // ->selectRaw('sub as name')
                // ->selectRaw('JSON_UNQUOTE(JSON_EXTRACT(attr, "$.Latitud")) as lat')
                // ->selectRaw('JSON_UNQUOTE(JSON_EXTRACT(attr, "$.Longitud")) as lng')
                // ->selectRaw('JSON_UNQUOTE(JSON_EXTRACT(attr, "$.Estrato")) as estrato')
                // // ->selectRaw('area_location.location_id as id')
                // ->get();
            if(empty($areas)){
                $this->sql="SELECT 
                D.id as location_id,
                D.sub AS name,
                REPLACE(json_extract(D.`attr`,\"$.Latitud\"),'\"','') AS lat,
                REPLACE(json_extract(D.`attr`,\"$.Longitud\"),'\"','') AS lng,
                REPLACE(REPLACE(json_extract(D.`attr`,\"$.Estrato\"),'\"',''),'\\\u00e1','á') AS estrato
                FROM data AS D
                -- INNER JOIN area_location AS AL
                -- ON D.id=AL.location_id
                WHERE D.type_id IN (SELECT id FROM types WHERE NAME REGEXP 'INEGI Estratos')";

            }else{
                $this->sql="SELECT 
                D.id as location_id,
                D.sub AS name,
                REPLACE(json_extract(D.`attr`,\"$.Latitud\"),'\"','') AS lat,
                REPLACE(json_extract(D.`attr`,\"$.Longitud\"),'\"','') AS lng,
                REPLACE(REPLACE(json_extract(D.`attr`,\"$.Estrato\"),'\"',''),'\\\u00e1','á') AS estrato
                FROM data AS D
                INNER JOIN area_location AS AL
                ON D.id=AL.location_id
                WHERE D.type_id IN (SELECT id FROM types WHERE NAME REGEXP 'INEGI Estratos')";
                $this->sql.=($request->areas)?" AND AL.area_id IN ($areas)":"";
            }


            // $count = DB::select(DB::raw($this->sql));
            //var_dump($count);
            // die();

        $result=cache()->remember("companies-$areas",(60*60*24*30),function(){
            // echo 'dentro';
            return DB::select(DB::raw($this->sql));
        });
        //$articles = Cache::pull("companies-$areas");
        //var_dump("companies-$areas");

        if(!is_null($result)){
            //echo 'here';
            $this->response=[
                'body'=>[
                    'companies'=>$result,
                    'areas'=>$request->areas,
                ],
                'message' => 'Successfully'];
            $this->code=200;
        }


        return response($this->response,$this->code);
    }


    public function companies(Request $request)
    {
        try {
            // Validar la solicitud usando validator
            $validator = validator($request->all(), [
                'areas' => 'sometimes|array',
                'areas.*' => 'string',
                'colonia' => 'sometimes|array',
                'colonia.*' => 'string',
                'CP' => 'sometimes|array',
                'CP.*' => 'string',
                'Estrato' => 'sometimes|array',
                'Estrato.*' => 'string',
                'searcheconomyactivity' => 'nullable|array',
                'searcheconomyactivity.*' => 'string',
                'searchrama' => 'nullable|array',
                'searchrama.*' => 'string',
                'asentamiento' => 'sometimes|array',
                'asentamiento.*' => 'string',
                'vialidad' => 'sometimes|array',
                'vialidad.*' => 'string',
            ]);

            // Verificar si la validación falla
            if ($validator->fails()) {
                return response($validator->errors(), 400);
            }

            // Obtener áreas como un arreglo si están presentes
            $areas = $request->input('areas', []);
            $coloniaValues = $request->input('colonia', []);
            $cpValues = $request->input('CP', []);
            $estratoValues = $request->input('estrato', []);
            $searchQuery = $request->input('searcheconomyactivity', []);
            $searchQueryRama = $request->input('searchrama', []);
            $asentamientoValues = $request->input('asentamiento', []);
            $vialidadValues = $request->input('vialidad', []);

            // Obtener la fecha actual y formatear el mes y año
            $currentMonthYear = now()->format('Y-m');

            // Atributos estáticos definidos en el código
            $staticAttributes = ['Latitud', 'Longitud', 'Estrato'];

            // Construir dinámicamente la cláusula SELECT
            $selects = [
                'D.id AS location_id',
                'D.sub AS name',
            ];

            foreach ($staticAttributes as $attribute) {
                $selects[] = "REPLACE(json_extract(D.`attr`, \"\$.{$attribute}\"), '\"', '') AS {$attribute}";
            }

            // Construir la consulta SQL final
            $sql = "SELECT " . implode(', ', $selects) . "
                    FROM data AS D";

            // JOIN con area_location si hay áreas especificadas
            if (!empty($areas)) {
                $sql .= " INNER JOIN area_location AS AL ON D.id = AL.location_id";
                $sql .= " AND AL.area_id IN (" . implode(',', $areas) . ")";
            }

            // Aplicar restricción para el tipo específico de estrato
            $sql .= " WHERE D.type_id IN (SELECT id FROM types WHERE NAME REGEXP 'Estrato')";

            // Aplicar filtro para la fecha actual
           // $sql .= " AND DATE_FORMAT(D.updated_at, '%Y-%m') = '" . $currentMonthYear . "'";

            // Aplicar filtros dinámicos si están presentes
            if (!empty($coloniaValues)) {
                if (count($coloniaValues) === 1) {
                    $sql .= " AND JSON_UNQUOTE(JSON_EXTRACT(D.attr, '$.Colonia')) = '" . reset($coloniaValues) . "'";
                } else {
                    $sql .= " AND JSON_UNQUOTE(JSON_EXTRACT(D.attr, '$.Colonia')) IN ('" . implode("','", $coloniaValues) . "')";
                }
            }

            if (!empty($cpValues)) {
                $cpConditions = [];
                foreach ($cpValues as $cp) {
                    $cpConditions[] = "JSON_UNQUOTE(REPLACE(json_extract(D.`attr`, '$.CP'), '\"', '')) = '" . $cp . "'";
                }
                $sql .= " AND (" . implode(' OR ', $cpConditions) . ")";
            }

            if (!empty($estratoValues)) {
                $estratoConditions = [];
                foreach ($estratoValues as $estrato) {
                    $estratoConditions[] = "JSON_UNQUOTE(REPLACE(json_extract(D.`attr`, '$.Estrato'), '\"', '')) = '" . $estrato . "'";
                }
                $sql .= " AND (" . implode(' OR ', $estratoConditions) . ")";
            }

            // Búsqueda por palabras clave en searcheconomyactivity
            if (!empty($searchQuery)) {
                $claseActividadConditions = [];
                foreach ($searchQuery as $index => $keyword) {
                    if ($index === 0) {
                        $claseActividadConditions[] = "JSON_UNQUOTE(JSON_EXTRACT(D.attr, '$.Clase_actividad')) = '" . $keyword . "'";
                    } else {
                        $claseActividadConditions[] = "JSON_UNQUOTE(JSON_EXTRACT(D.attr, '$.Clase_actividad')) LIKE '%" . $keyword . "%'";
                    }
                }
                $sql .= " AND (" . implode(' OR ', $claseActividadConditions) . ")";
            }
            if (!empty($searchQueryRama)) {
                $RamaclaseActividadConditions = [];
                foreach ($searchQueryRama as $index => $keyword) {
                    if ($index === 0) {
                        $RamaclaseActividadConditions[] = "JSON_UNQUOTE(JSON_EXTRACT(D.attr, '$.RAMA_ACTIVIDAD_ID')) = '" . $keyword . "'";
                    } else {
                        $RamaclaseActividadConditions[] = "JSON_UNQUOTE(JSON_EXTRACT(D.attr, '$.RAMA_ACTIVIDAD_ID')) LIKE '%" . $keyword . "%'";
                    }
                }
                $sql .= " AND (" . implode(' OR ', $RamaclaseActividadConditions) . ")";
            }

            if (!empty($asentamientoValues)) {
                $asentamientoConditions = [];
                foreach ($asentamientoValues as $tipo_asentamiento) {
                    $asentamientoConditions[] = "JSON_UNQUOTE(REPLACE(json_extract(D.`attr`, '$.Tipo_Asentamiento'), '\"', '')) = '" . $tipo_asentamiento . "'";
                }
                $sql .= " AND (" . implode(' OR ', $asentamientoConditions) . ")";
            }

            if (!empty($vialidadValues)) {
                $vialidadConditions = [];
                foreach ($vialidadValues as $tipo_vialidad) {
                    $vialidadConditions[] = "JSON_UNQUOTE(REPLACE(json_extract(D.`attr`, '$.Tipo_vialidad'), '\"', '')) = '" . $tipo_vialidad . "'";
                }
                $sql .= " AND (" . implode(' OR ', $vialidadConditions) . ")";
            }

            // Ejecutar la consulta SQL
        \Log::info('Consulta generada en stratum: ' . $sql);
            $result = DB::select(DB::raw($sql));

            // Preparar la respuesta
            if (!empty($result)) {
                $response = [
                    'companies' => $result,
                    'areas' => $areas,
                    'sql' => $sql,
                    'message' => 'Successfully',
                ];
                $code = 200;
            } else {
                $response = "No hay coincidencias";
                $code = 404;
            }

            return response()->json($response, $code);

        } catch (\Exception $e) {
            // Capturar cualquier excepción de la base de datos y devolver un mensaje de error
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }    

    public function company($id)
    {

        $sql="SELECT ||
            D.id as location_id,
            D.sub AS name,
            REPLACE(json_extract(D.`attr`,\"$.Razon_social\"),'\"','') AS Razon_social,
            REPLACE(json_extract(D.`attr`,\"$.Clase_actividad\"),'\"','') AS Clase_actividad,
            REPLACE(json_extract(D.`attr`,\"$.Estrato\"),'\"','') AS Estrato,
            REPLACE(json_extract(D.`attr`,\"$.Fecha_Alta\"),'\"','') AS Fecha_Alta
            FROM data AS D
            WHERE D.type_id IN (SELECT id FROM types WHERE name REGEXP 'Estrato')
            AND D.id = $id";

        $result=DB::select(DB::raw($sql));

        if(!is_null($result)){
            $this->response=[
                'body'=> collect($result)->first(),
                'message' => 'Successfully'];
            $this->code=200;
        }


        return response($this->response,$this->code);
    }
public function stratum(Request $request)
{
    try {
        // Validar la solicitud usando validator
        $validator = validator($request->all(), [
            'areas' => 'sometimes|array',
            'areas.*' => 'string',
            'colonia' => 'sometimes|array',
            'colonia.*' => 'string',
            'estrato' => 'sometimes|array',
            'estrato.*' => 'string',
            'searcheconomyactivity' => 'nullable|array',
            'searcheconomyactivity.*' => 'string',
            'searchrama' => 'nullable|array',
            'searchrama.*' => 'string',
            'CP' => 'nullable|array',
            'CP.*' => 'string',
            'asentamiento' => 'sometimes|array',
            'asentamiento.*' => 'string',
            'vialidad' => 'sometimes|array',
            'vialidad.*' => 'string',
        ]);

        if ($validator->fails()) {
            return response($validator->errors(), 400);
        }

        // Inputs
        $areas = $request->input('areas', []);
        $coloniaValues = $request->input('colonia', []);
        $estratoValues = $request->input('estrato', []);
        $searchQuery = $request->input('searcheconomyactivity', []);
        $searchQueryRama = $request->input('searchrama', []);
        $postalCodeQuery = $request->input('CP', []);
        $asentamientoValues = $request->input('asentamiento', []);
        $vialidadValues = $request->input('vialidad', []);

        // Fecha actual
        $currentMonthYear = now()->format('Y-m');

        // SELECT dinámico
        $selects = [
            "REPLACE(REPLACE(JSON_UNQUOTE(JSON_EXTRACT(D.attr, '$.Estrato')), '\"', ''), 'm\\u00e1s', 'más') AS estrato",
            "COUNT(*) AS total",
            "CASE 
                WHEN REPLACE(JSON_UNQUOTE(JSON_EXTRACT(D.attr, '$.Estrato')), '\"', '') = '0 a 5 personas' THEN 1
                WHEN REPLACE(JSON_UNQUOTE(JSON_EXTRACT(D.attr, '$.Estrato')), '\"', '') = '6 a 10 personas' THEN 2
                WHEN REPLACE(JSON_UNQUOTE(JSON_EXTRACT(D.attr, '$.Estrato')), '\"', '') = '11 a 30 personas' THEN 3
                WHEN REPLACE(JSON_UNQUOTE(JSON_EXTRACT(D.attr, '$.Estrato')), '\"', '') = '31 a 50 personas' THEN 4
                WHEN REPLACE(JSON_UNQUOTE(JSON_EXTRACT(D.attr, '$.Estrato')), '\"', '') = '51 a 100 personas' THEN 5
                WHEN REPLACE(JSON_UNQUOTE(JSON_EXTRACT(D.attr, '$.Estrato')), '\"', '') = '101 a 250 personas' THEN 6
                WHEN REPLACE(JSON_UNQUOTE(JSON_EXTRACT(D.attr, '$.Estrato')), '\"', '') = '251 y más personas' THEN 7
                ELSE 100
            END AS orden"
        ];

        // Construir consulta
        $query = "
            SELECT " . implode(', ', $selects) . "
            FROM loads L
            JOIN data D ON L.id = D.load_id
            LEFT JOIN area_location AL ON D.id = AL.location_id
            WHERE L.type_id IN (SELECT id FROM types WHERE NAME REGEXP 'INEGI Estratos')
              AND AL.area_id IS NOT NULL
              AND JSON_UNQUOTE(JSON_EXTRACT(D.attr, '$.Estrato')) IS NOT NULL
        ";

        // Filtros dinámicos
        if (!empty($areas)) {
            $query .= " AND AL.area_id IN ('" . implode("','", $areas) . "')";
        }

        if (!empty($coloniaValues)) {
            if (count($coloniaValues) === 1) {
                $query .= " AND JSON_UNQUOTE(JSON_EXTRACT(D.attr, '$.Colonia')) = '" . reset($coloniaValues) . "'";
            } else {
                $query .= " AND JSON_UNQUOTE(JSON_EXTRACT(D.attr, '$.Colonia')) IN ('" . implode("','", $coloniaValues) . "')";
            }
        }

        if (!empty($estratoValues)) {
            if (count($estratoValues) === 1) {
                $query .= " AND JSON_UNQUOTE(JSON_EXTRACT(D.attr, '$.Estrato')) = '" . reset($estratoValues) . "'";
            } else {
                $query .= " AND JSON_UNQUOTE(JSON_EXTRACT(D.attr, '$.Estrato')) IN ('" . implode("','", $estratoValues) . "')";
            }
        }

        if (!empty($postalCodeQuery)) {
            $postalCodeConditions = [];
            foreach ($postalCodeQuery as $postalCode) {
                $postalCodeConditions[] = "JSON_UNQUOTE(JSON_EXTRACT(D.attr, '$.CP')) LIKE '%" . trim($postalCode) . "%'";
            }
            $query .= " AND (" . implode(' OR ', $postalCodeConditions) . ")";
        }

        if (!empty($searchQuery)) {
            $searchConditions = [];
            foreach ($searchQuery as $index => $keyword) {
                $operator = $index === 0 ? '=' : 'LIKE';
                $value = $index === 0 ? $keyword : "%$keyword%";
                $searchConditions[] = "JSON_UNQUOTE(JSON_EXTRACT(D.attr, '$.Clase_actividad')) $operator '$value'";
            }
            $query .= " AND (" . implode(' OR ', $searchConditions) . ")";
        }

        if (!empty($searchQueryRama)) {
            $ramaConditions = [];
            foreach ($searchQueryRama as $index => $keyword) {
                $operator = $index === 0 ? '=' : 'LIKE';
                $value = $index === 0 ? $keyword : "%$keyword%";
                $ramaConditions[] = "JSON_UNQUOTE(JSON_EXTRACT(D.attr, '$.RAMA_ACTIVIDAD_ID')) $operator '$value'";
            }
            $query .= " AND (" . implode(' OR ', $ramaConditions) . ")";
        }

        if (!empty($asentamientoValues)) {
            if (count($asentamientoValues) === 1) {
                $query .= " AND JSON_UNQUOTE(JSON_EXTRACT(D.attr, '$.Tipo_Asentamiento')) = '" . reset($asentamientoValues) . "'";
            } else {
                $query .= " AND JSON_UNQUOTE(JSON_EXTRACT(D.attr, '$.Tipo_Asentamiento')) IN ('" . implode("','", $asentamientoValues) . "')";
            }
        }

        if (!empty($vialidadValues)) {
            if (count($vialidadValues) === 1) {
                $query .= " AND JSON_UNQUOTE(JSON_EXTRACT(D.attr, '$.Tipo_vialidad')) = '" . reset($vialidadValues) . "'";
            } else {
                $query .= " AND JSON_UNQUOTE(JSON_EXTRACT(D.attr, '$.Tipo_vialidad')) IN ('" . implode("','", $vialidadValues) . "')";
            }
        }

        // Agrupar por estrato y ordenar
        $query .= " GROUP BY estrato, orden
                    ORDER BY orden ASC";

        // Log de la consulta
        \Log::info('Consulta generada en stratum: ' . $query);

        // Ejecutar consulta
        $result = DB::select(DB::raw($query));

        if (!empty($result)) {
            return response()->json([
                'stratum' => $result,
                'areas' => $areas,
                'message' => 'Successfully',
            ], 200);
        } else {
            return response()->json('No hay coincidencias', 404);
        }

    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}


    public function politicDivision()
    {

        $this->sql="SELECT * FROM (SELECT 
           D.headline,
            D.`key`,
            D.`sub`,
            json_extract(D.attr,'$.properties')AS properties,
            json_extract(D.attr,'$.coordinates')AS coordinates,
            D.`date_start`,
            D.`date_end`
             FROM 
            data AS D 
            INNER JOIN types AS T 
            ON D.`type_id`=T.`id`
            WHERE T.name REGEXP 'División Política'
            AND D.`date_start` >= DATE_FORMAT(CONCAT(YEAR(CURDATE()),'-01-01 00:00:00'), '%Y-%m-%d %H:%i:%s')
            ORDER BY D.created_at DESC) AS S
            GROUP BY S.headline";

        $result=cache()->remember("politicDivision",(60*60*24*30*365),function(){
            return DB::select(DB::raw($this->sql));
        });

        $body=collect($result)->map(function($item){
            return [
                'name'=>$item->headline,
                'id'=>$item->key,
                'abr'=>$item->sub,
                'properties'=>json_decode($item->properties),
                'coordinates'=>json_decode($item->coordinates),
                'date_start'=>$item->date_start,
                'date_end'=>$item->date_end
            ];
        });

        if(!is_null($result)){
            $this->response=[
                'body'=>$body,
                'message' => 'Successfully'];
            $this->code=200;
        }


        return response($this->response,$this->code);
    }
     public function economy_activity(Request $request)
    {
        try {
            // Definir la clave única para almacenar en caché
            $cacheKey = 'economy_activity_data';

            // Verificar si los datos están en caché
            $e_activity = Cache::remember($cacheKey, now()->addHours(6), function () {
                // Realizar la consulta SQL
                $result = DB::table('data')
                    ->select(DB::raw("DISTINCT JSON_UNQUOTE(REPLACE(json_extract(attr, '$.Clase_actividad'), '\"', '')) AS Clase_actividad"))
                    ->where('type_id', 17) // Filtrar por type_id = 17
                    ->get();

                // Extraer valores únicos de Clase_actividad
                return $result->pluck('Clase_actividad')->values()->all();
            });

            // Preparar la respuesta
            $response = [
                'Clase_actividad' => $e_activity,
                'message' => 'Successfully',
            ];
            $code = 200;

            return response()->json($response, $code);
        } catch (\Exception $e) {
            // Manejar errores
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    

    // Desarrollo MEX
    public function colonies(Request $request)
    {
        try {
            // Definir la clave única para almacenar en caché
            $cacheKey = 'colonies_data';
    
            // Verificar si los datos están en caché
            $colonies = Cache::remember($cacheKey, now()->addHours(6), function () {
                // Realizar la consulta SQL
                $result = DB::table('data')
                    ->select(DB::raw("DISTINCT JSON_UNQUOTE(REPLACE(json_extract(attr, '$.Colonia'), '\"', '')) AS Colonia"))
                    ->where('type_id', 17) // Filtrar por type_id = 17
                    ->get();
    
                // Extraer valores únicos de Colonia
                return $result->pluck('Colonia')->values()->all();
            });
    
            // Preparar la respuesta
            $response = [
                'colonias' => $colonies,
                'message' => 'Successfully',
            ];
            $code = 200;
    
            return response()->json($response, $code);
        } catch (\Exception $e) {
            // Manejar errores
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    
}
