<?php

namespace App\Service;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Http\Dto\MessageDto;

class GoogleExcelService
{
    private $apiKey;
    private $folderId;

    public function __construct()
    {
        $this->apiKey = env('GOOGLE_API_KEY');
        $this->folderId = env('ID_CARPETA_DRIVE');
    }

    /**
     * Punto de entrada principal:
     * - Si existen archivos de hoy → se usan
     * - Si no → se descargan y renombran
     * template: DocId=1A0j66N52HHYURiR7XWXvm-4piA0YIC8N
     * DocId=11QQhYJz5GC-bpnvCV-BD1iTHKWlStlFv
     * file folder path: "/Files/Export"
     */

    public function downloadLastFourExcels(){
        $today = Carbon::today()->format('Ymd');
        
        $files = Storage::disk('local')->files("files");        
        $todayFiles = collect($files)->filter(function($file) use ($today) {
            $lastModified = Storage::disk('local')->lastModified($file);
            $lastModifiedDate = \Carbon\Carbon::createFromTimestamp($lastModified)->format('Ymd');
            return $lastModifiedDate === $today;
        });      

        if ($todayFiles->isNotEmpty()) {
            return response()->json(['status' => 404, 'message' => "Se encontraron archivos del día actual en storage/app/files"]);
        }else{
            $this->getExcelByIdInDrive();                   
            $this->renameExcels();
        }        
        return response()->json(['status' => 200, 'message' => 'Se descargaron y renombraron los archivos correctamente']);        
    }
    
    private function renameExcels(){
        $files = Storage::disk('local')->files("private/files");    
        foreach ($files as $filePath) {           
            $fileName = basename($filePath);
            $baseName = preg_replace('/\d+(_\d+)*$/', '', pathinfo($fileName, PATHINFO_FILENAME));
            $newName = $baseName . ".xlsx";
            
            Storage::disk('local')->move($filePath, "files/$newName");
        }
    }


    /**
     * Obtener los archivos excel por id en la carpeta de drive
     * Obtiene los archivos mas recientes que hay en drive/files, (id, nombre y tipo)
     * solo descarga 3 archivos.     
     * Para mas informacion consultar https://developers.google.com/drive/api/v3/reference/files/list
     * Alli se pueden hacer pruebas sin costo alguno.
     *
     * @param  int $pageSize=3(cantidad de archivos) @param string $orderBy=recency(archivos más recientes).
    */
    private function getExcelByIdInDrive()
    {        
        $query = "'$this->folderId' in parents and mimeType='application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' and trashed = false";
        $response = Http::get('https://www.googleapis.com/drive/v3/files', [
            'q' => $query,
            'fields' => 'files(id, name, mimeType)',
            'orderBy' => 'recency',
            'pageSize' => 4,
            'corpora' => 'user',

            'key' => $this->apiKey,
        ]);

        $files = $response->json()['files'] ?? [];        
        
        $this->downloadExcelByIdStorage($files);

    }
    
    /**
     * Descargar los archivos excel por id y los guardar en storage/app/private/files
     * @param array $array Arreglo con los archivos obtenidos de getExcelByIdInDrive     
     */
    private function downloadExcelByIdStorage($array = []){
        
        echo "Se descagaron : " . count($array) . " archivos desde Google Drive. <br>";
        try{
            foreach ($array as $file) {
                $fileId = $file['id'];
                $fileName = $file['name'];
    
                $downloadUrl = "https://www.googleapis.com/drive/v3/files/".$fileId."?alt=media&key=".$this->apiKey;
    
                $fileContent = Http::get($downloadUrl)->body();
                Storage::disk('local')->put("private/files/$fileName", $fileContent);
            }
            return "Se descargaron " . count($array) . " archivos en storage/app/private/files";
        }catch(\Exception $e){
            return "Error al descargar archivos: " . $e->getMessage();
        }

    }
}