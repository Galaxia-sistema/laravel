<?php
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\ApiGoogleController;
use Illuminate\Support\Facades\Route;
use App\Service\GoogleExcelService;

Route::prefix('api')->group(function () {

   Route::get('/excels/{file}', [ApiGoogleController::class, 'getExcel']);

   Route::get('/test', [GoogleExcelService::class, 'downloadLastFourExcels']);

});
#Route::delete('/usuarios/{id}/eliminado', [UsuariosController::class, 'eliminarUser'])->name('eliminar.usuario');


/*

GET https://www.googleapis.com/drive/v3/files?corpora=user&orderBy=recency&pageSize=3&q='1nx4XsplPRDp5bNplj_PzyuOf-ZFt5xAp'%20in%20parents%20and%20mimeType%3D'application%2Fvnd.openxmlformats-officedocument.spreadsheetml.sheet'&key=[YOUR_API_KEY] HTTP/1.1

Authorization: Bearer [YOUR_ACCESS_TOKEN]
Accept: application/json
dist/mac-indicadores/browser
npm run build
**/