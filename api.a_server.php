<?php
/**
* Procesador de llamadas REST del  API Google Template
*
* @package    API Google Template
* @author     Isaí Fararoni Ramírez
* @version    1 de enero de 2019
* @version    1.1.0
*/
include_once __DIR__ . '/api.d_logger.php';
include_once __DIR__ . '/api.c_template.php';
//-- Servicios REST
//--
//--     POST /doc/create                    POST {}
//--     POST /doc/merge                     POST {}
//--     POST /doc/export                    POST {}
//--     GET  /doc/download/{document_id}    GET 
//--     GET /doc/
//--

//-- Estructura JSON
/***
{
  "orig_folder_id": "1H2PolWOKPAgUJ3J2yyfv54D8i3u66UkZ",
  "orig_document_id": "1S-30QqtcNKXTl3C5MlruulUm8AxFcUsWqLi9ZOQO_t8",
  "dest_folder_id": "1H2PolWOKPAgUJ3J2yyfv54D8i3u66UkZ",
  "reemplazos": {
    "campo_uno": "Contenido",
    ...
    "campo_tabla": [
      {
        "concepto": "aceite",
        "importe": 30
      },
      ....
    ],
  }
}
 ***/

 class Server {
    private $currentMilliSecond;
    private $json_req;
    private $logger;

    private $method;
    private $uri;

    public function __construct(){
        $this->currentMilliSecond = (int) (microtime(true) * 1000);
        $this->uri                = $_SERVER['REQUEST_URI'];
        $this->method             = $_SERVER['REQUEST_METHOD'];

        $this->logger             = new ApiLogger;
        $this->logger->log   ('Server');
    }

    public function serve() {
        $this->logger->json_request();
        $this->json_req = trim( file_get_contents('php://input') );

        $step_debug     = "server";
       
        $paths          = explode('/', $this->paths( $this->uri ));
        array_shift($paths); 
        
        $resource   = array_shift($paths);
        $step_debug      = "parse_resource:" . $resource ;
        if ($resource == 'doc') {
            $doc_command = array_shift($paths);
            $step_debug    = "doc_command:" . $doc_command;
            switch($doc_command) { 
                case 'create':
                    $step_debug    = "doc_command::create" ;
                    return $this->handle_create ( );
                case 'merge':
                    $step_debug    = "doc_command::merge" ;
                    return $this->handle_merge  ( );
                case 'export' : /* Exporta a PDF - JSON*/
                    $step_debug = "doc_command::export";
                    return $this->handle_export ( );                         
                case 'download' : /* Descarga un PDF - PDF*/
                    $step_debug = "doc_command::download";
                    $document_id = array_shift($paths);
                    return $this->handle_download ( $document_id);
            }
        }
        header('HTTP/1.1 405 Metodo no permitido ' . $resource );
        header('Allow: GET, POST');
        $trace = 'ERROR.REST.' . $this->currentMilliSecond . ':' . $this->method . ',' . $this->uri . ',' . $step_debug . chr(13); 
        $this->logger->error( $trace ) ;
        $result = array(
            "result"            => "error",
            "error"             => $step_debug ,
            "trace"             => $trace,
        );
        print json_encode( $result ) ;
    }
    private function handle_create( ){
        $step_debug    = "handle_create" ;
        if ( $this->method = 'POST' && !is_null( $this->json_req ) ) {
            $step_debug    = "handle_create_post" ;
            $this->logger->log( $step_debug  );
            $api = new ApiTemplate( $this->json_req );
            header('Content-type: application/json');
            echo json_encode($api->create());
        } else {
            $this->logger->error( $step_debug ) ;
            header( 'HTTP/1.1 400 Bad Request handle_create ' . $this->method . ' json ' . is_null( $this->json_req ) );
            header( 'Allow: POST');
            return;
        }
    }   
    private function handle_merge( ){
        $step_debug    = "handle_merge" ;
        if ( $this->method = 'POST' && !is_null( $this->json_req ) ) {
            $step_debug    = "handle_merge_post" ;
            $this->logger->log( $step_debug  );
            $api = new ApiTemplate( $this->json_req );
            header('Content-type: application/json');
            echo json_encode($api->merge());
        } else {
            $this->logger->error( $step_debug ) ;
            header( 'HTTP/1.1 400 Bad Request handle_merge ' . $method . ' json ' . is_null( $this->json_req ) );
            header('Allow: POST');
        }
    }

    private function handle_export( ){
        $step_debug    = "handle_export" ;
        if ( $this->method = 'POST' && !is_null( $this->json_req ) ) {
            $step_debug    = "handle_export_post" ;
            $this->logger->log( $step_debug  );
            $api = new ApiTemplate( $this->json_req );
            header('Content-type: application/json');
            echo json_encode( $api->export_pdf() );
        } else {
            $this->logger->error( $step_debug ) ;
            header( 'HTTP/1.1 400 Bad Request handle_export ' . $method . ' json ' . is_null( $this->json_req ) );
            header('Allow: POST');
        }
    }

    public function handle_download( $document_id ){
        $step_debug    = "handle_download" ;
        if ( $this->method = 'GET' && !is_null( $document_id ) ) {
            $step_debug    = "handle_download_get" ;
            $this->logger->log( $step_debug  );
            $api = new ApiTemplate( $this->json_req );
            return $api->getPdf($document_id);
        } else {
            $this->logger->error( $step_debug ) ;
            header( 'HTTP/1.1 400 Bad Request handle_download ' . $method . ' document_id ' . document_id );
            header('Allow: GET');
        }
    }

    //--- Funciones auxiliares
    private function paths($url) {
        $uri = parse_url($url);
        return $uri['path'];
    }
}

$server = new Server;
$server->serve();
?>