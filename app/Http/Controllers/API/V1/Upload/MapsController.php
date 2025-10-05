<?php

namespace App\Http\Controllers\API\V1\Upload;

use App\Http\Controllers\API\BaseController;
use App\Imports\AirdnaImport;
use App\Models\Load;
use App\Models\Type;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;

class MapsController extends BaseController
{
    public $type_id;
    public $file;
    public $name;
    public $source_id;
    public $path;
    public $Load;

    public function airdna(Request $request)
    {
        ini_set('upload_max_filesize', '200M');
        ini_set('post_max_size', '210M');
        ini_set('max_execution_time', '3000');
        ini_set('max_input_time', '3000');

        $this->type_id = Type::where('name', 'REGEXP', "airdna")->firstOrFail()->id;
        $validated = validator($request->all(), [
            "name" => ["required", Rule::unique('loads', 'name')->where('type_id', $this->type_id)],
            "file" => 'required|file|mimes:csv|max:204800',
            "source_id" => 'required|exists:sources,id'
        ]);

        if ($validated->fails()) {
            return response($validated->errors(), 400);
        }

        if ($file = $request->file('file')) {
            $this->file = $file;
            $this->name = $request->name;
            $this->source_id = $request->source_id;
        }

        $this->uploadFile($request);

        try {
            $headings = (new HeadingRowImport)->toArray($this->path);
            Excel::import(new AirdnaImport($this->Load, $headings), $this->path);

            $this->response = [
                'body' => [$this->path],
                'message' => "Successful Filled"
            ];
            $this->code = 200;
        } catch (\Exception $e) {
            Log::error('Import: ' . $e->getMessage());

            $this->response = [
                'body' => [$e->getCode(), $e->getLine()],
                'message' => $e->getMessage()
            ];
            $this->code = 400;
        }

        return response($this->response, $this->code);
    }

    public function uploadFile(Request $request)
    {
        // Guardar archivo
        try {
            $this->file->storeAs('public/files', $this->name);
            $this->path = 'public/files/' . $this->name;
        } catch (\Exception $e) {
            Log::error('Store: ' . $e->getMessage());
            throw new \Exception('Error al guardar el archivo');
        }
        try {
            $this->Load = Load::create([
                'name' => $this->name,
                'file' => $this->name,
                'type_id' => $this->type_id,
                'source_id' => $this->source_id,
                'user_id' => 30,
            ]);
        } catch (\Exception $e) {
            Log::error('Load: ' . $e->getMessage());
            throw new \Exception('Error al crear Load: ' . $e->getMessage());
        }

        Log::info('Load creado correctamente', $this->Load->toArray());
    }
}
