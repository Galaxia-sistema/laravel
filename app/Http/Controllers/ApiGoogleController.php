<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Service\GoogleExcelService;
/**
 * Controlador para interactuar con la API de Google Drive
 * Permite listar y descargar archivos Excel desde una carpeta específica en Google Drive.
 * 
 * Requiere las siguientes variables de entorno en el archivo .env:
 * - GOOGLE_API_KEY: La clave de API para acceder a Google Drive. esta se saca al crear un proyecto en Google Cloud Platform y habilitar la API de Google Drive.
 * - ID_CARPETA_DRIVE: El ID de la carpeta en Google Drive donde se encuentran los archivos Excel. Es el id de la carpeta de drive, debe ser publica o pedira autenticación. 
 * 
 * INSPECCIONES-704450845/Files/Export/
 **/
class ApiGoogleController extends Controller
{
  
  private $googleExcelService;

  public function __construct()
  {
      $this->googleExcelService = new GoogleExcelService();
  }

   public function getExcel($file)
    {
        $path = "files/$file";

        // 🔹 Verificar si existen archivos de hoy
        $files = Storage::disk('local')->files("private/files");
        $today = Carbon::today()->format('Ymd');

        $todayFiles = collect($files)->filter(function($f) use ($today) {
            $lastModified = Storage::disk('local')->lastModified($f);
            $lastModifiedDate = Carbon::createFromTimestamp($lastModified)->format('Ymd');
            return $lastModifiedDate === $today;
        });
        
        if ($todayFiles->isEmpty()) {
            $this->googleExcelService->downloadLastFourExcels();
        }

        if (!Storage::disk('local')->exists($path)) {
            return response()->json(['error' => 'Archivo no encontrado'], 404);
        }

        $content = Storage::disk('local')->get($path);
        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

}



/*
Respuesta con los id de diferentes archivos excel en la carpeta compartida
{
  "files": [
    {
      "id": "1Q4wTCNjUgYjaVpJtn1uNG1t5m0Pthxvh",
      "name": "Empalme20250923_194701_189.xlsx",
      "mimeType": "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
    },
    {
      "id": "1QYD18GG8TYSGntw7es8Sx__K7tiEEh5W",
      "name": "sitios20250923_194638_613.xlsx",
      "mimeType": "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
    },
    {
      "id": "1V4QlNbHBVQBwmfGPIKdrT_1O0dONK0j-",
      "name": "Acompañamiento20250923_194606_464.xlsx",
      "mimeType": "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
    }
  ]
}
*/