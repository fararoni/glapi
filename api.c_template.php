<?php
/**
* Procesador de plantillas con Google Docs API
*
* @package    API Google Template
* @author     Isaí Fararoni Ramírez
* @version    1 de enero de 2019
* @version    1.1.0
*/
include_once __DIR__ . '/vendor/autoload.php';
include_once __DIR__ . "/api.b_google.base.php";
class  ApiTemplate {

    const API_TEMPLATE_APP_NAME = 'Motor de plantillas via API Google Docs.';
    private $api;
    private $json_replace;
    private $result = array();
    private $error  = array();
    private $trace  = array();

    private $json_array         = false;
    private $orig_folder_id     = false;
    private $dest_folder_id     = false;
    private $orig_document_id   = false;
    private $dest_document_id   = false;
    private $dest_doc_title     = false;
    private $orig_folio_id      = false;
    private $reemplazos         = false;


    public function __construct( $json_replace ){
        $this->json_replace = $json_replace;
        $this->trace []     = array("ApiTemplate" => "passed");
        $this->api          = new ApiGoogle();
    }

    private function get_client()
    {
        $client = $this->api->get_client( self::API_TEMPLATE_APP_NAME );
        if ( $client ) {
            $this->trace [] = array("get_client" => "passed");
            return  $client;
        } else {
            $this->trace [] = array("get_client" => "failed");
            $this->error [] = array("credentials_file" => "No se encontró la credencial de Google.");
            return;
        }
    }

    private function parse_json(){
        $this->json_array = json_decode($this->json_replace, true);
        if ( is_array ( $this->json_array ) ) {
            if ( array_key_exists('orig_folder_id', $this->json_array ) ) {
                $this->orig_folder_id = $this->json_array ['orig_folder_id'];
            }
            if ( array_key_exists('dest_folder_id', $this->json_array ) ) {
                $this->dest_folder_id = $this->json_array ['dest_folder_id'];
            }
            if ( array_key_exists('orig_document_id', $this->json_array ) ) {
                $this->orig_document_id = $this->json_array ['orig_document_id'];
            }
            if ( array_key_exists('dest_document_id', $this->json_array ) ) {
                $this->dest_document_id = $this->json_array ['dest_document_id'];
            }
            if ( array_key_exists('dest_doc_title', $this->json_array ) ) {
                $this->dest_doc_title = $this->json_array ['dest_doc_title'];
            }
            if ( array_key_exists('orig_folio_id', $this->json_array ) ) {
                $this->orig_folio_id = $this->json_array ['orig_folio_id'];
            }
            if ( array_key_exists('reemplazos', $this->json_array ) ) {
                $this->reemplazos = $this->json_array ['reemplazos'];
            }
            return true ;
        }
        return false ;
    }

    public function create() {
        $this->trace [] = array("create" => "start");
        $this->parse_json();
        $resultado  = "error";
        if (       $this->json_array 
                && $this->dest_folder_id
                && $this->orig_document_id
                && $this->dest_doc_title
                && $this->orig_folio_id   ) {
            $client  = $this->get_client();
            if ( $client ) {
                $this->trace [] = array("orig_document_id" => $this->orig_document_id );    
                $documentId_copy= $this->api->copy_document($client, $this->dest_folder_id , $this->orig_document_id, $this->dest_doc_title);
                if ( $documentId_copy ) {
                    $this->dest_document_id = $documentId_copy->id;
                    $resultado = "success";
                } else {
                    $this->error [] = array("create" => 'No se obtuvo la copia de la plantilla.' );
                }
            }
        } else {
            $this->error [] = array("create" => 'Parametros incorrectos en el json, se requiere: dest_folder_id, orig_document_id ,dest_doc_title, orig_folio_id=['. $this->orig_folio_id .']' );
        }
       
        $result = array(
            "resultado"         => $resultado ,
            "documentId_src"    => $this->orig_document_id ,
            "documentId"        => $this->dest_document_id ,
            "error"             => $this->error ,
            "trace"             => $this->trace 
        );
        return $result;
    } //-- create

    public function merge(){
        $this->trace [] = array("merge" => "start");
        $this->parse_json();
        $resultado  = "error";
        if (       $this->json_array 
                && $this->dest_folder_id
                && $this->orig_document_id

                && $this->dest_doc_title
                && $this->orig_folio_id
                && $this->reemplazos  
            ) {
                $client  = $this->get_client();
                if ( $client ) {
                    $service        = new Google_Service_Docs($client);
                    $this->trace [] = array("orig_document_id" => $this->orig_document_id );
                   
                   $documentId_copy= $this->api->copy_document($client, $this->dest_folder_id , $this->orig_document_id, $this->dest_doc_title );
                    if ( $documentId_copy ) {
                        $this->merge_document_table($client, $this->json_array , $documentId_copy->id);
                        $this->dest_document_id = $documentId_copy->id;
                        $resultado = "success";
                    } else {
                        $this->error [] = array("merge" => 'No se obtuvo la copia de la plantilla.' );
                    }
                }
        } else {
            $this->error [] = array("create" => 'Parametros incorrectos en el json, se requiere: dest_folder_id, orig_document_id ,dest_doc_title, orig_folio_id=['. $this->orig_folio_id .'], reemplazos' );
        }

       $result = array(
            "resultado"         => $resultado ,
            "documentId_src"    => $this->orig_document_id ,
            "documentId"        => $this->dest_document_id ,
            "error"             => $this->error ,
            "trace"             => $this->trace ,
        );
        return $result;
    } //-- merge

    public function getPdf($document_id){
        header('Content-Type: application/pdf');
        header('Content-disposition: inline; filename="' .  $nombre_archivo . '.pdf"');
        header('Cache-Control: public, must-revalidate, max-age=0');
        header('Pragma: public');
        echo( $this->api->get_content_pdf($this->get_client(), $document_id) );
    } //-- getPdf

    public function export_pdf(){
        $this->trace [] = array("export_pdf" => "start");
        $this->parse_json();
        $resultado  = "error";
        if (       $this->json_array 
                && $this->dest_folder_id
                && $this->orig_document_id
                && $this->dest_doc_title
                && $this->orig_folio_id
            ) {
                $client  = $this->get_client();
                if ( $client ) {
            //----
                    $service = new Google_Service_Drive($client);
                    $this->trace [] = array("orig_document_id" => $this->orig_document_id );
                    $fileMetadata = new Google_Service_Drive_DriveFile(array(
                        'name' => $this->dest_doc_title,
                        'parents' => array($this->dest_folder_id)
                    ));
            //----
                    $content = $this->api->get_content_pdf($client, $this->orig_document_id );
                    $file = new Google_Service_Drive_DriveFile();
                    $createdFile = $service->files->create($fileMetadata, array(
                        'data'      => $content,
                        'mimeType'  => 'application/pdf',
                        'uploadType' => 'multipart'
                    ));
            //--                echo ( 'https://drive.google.com/file/d/1rEcP_uWMLirb5MZ-DfGf-yHGQsPX0pka/view?usp=sharing' );
                    if ($createdFile ) {
                        $this->dest_document_id = $createdFile->id;
                        $resultado = "success";
                    } else {
                        $this->error [] = array("export_pdf" => 'Fallo al exportar a PDF.' );
                    }

                }
        } else {
            $this->error [] = array("export_pdf" => 'Parametros incorrectos en el json, se requiere: dest_folder_id, orig_document_id ,dest_doc_title, orig_folio_id=['. $this->orig_folio_id .']' );
        }

       $result = array(
            "resultado"         => $resultado ,
            "documentId_src"    => $this->orig_document_id ,
            "documentId"        => $this->dest_document_id ,
            "error"             => $this->error ,
            "trace"             => $this->trace 
        );
        return $result;
    } //-- export_pdf

    private function replace_single_text ($client, $documentId, $etiqueta, $valor)
    {
        $service    = new Google_Service_Docs($client);
        $req_reemplazos = array();
        $service_docs_request = new Google_Service_Docs_Request(
            array(
                'replaceAllText' => array(
                    'containsText' => array(
                        'text' => '{'.strtoupper($etiqueta).'}',
                        'matchCase' => true
                    ),
                    'replaceText' => $valor
                )
            )
        );
        array_push($req_reemplazos, $service_docs_request);
        $batchUpdateRequest = new Google_Service_Docs_BatchUpdateDocumentRequest(array(
            'requests' => $req_reemplazos
        ));
        $response = $service->documents->batchUpdate($documentId, $batchUpdateRequest);
        $this->trace [] = array("merge_document_table" => "Se procesaron las etiquetas single_text." );
    }
    private function merge_document_table ($client, $array, $documentId)
    {
        $service    = new Google_Service_Docs($client);
        $etiquetas = $this->reemplazos;
        $req_reemplazos = array();
        foreach ( $array["reemplazos"] as $etiqueta => $valor) {
              if ( is_array ( $valor ) == false ) {
                if ( substr( $etiqueta , 0, 8 ) == 'parrafo_' ) {
                    if ( $valor == "visible") {
                        $inicio_parrafo = '{INICIO:'. strtoupper( substr( $etiqueta, 8  ) ) . '}';
                        $fin_parrafo    = '{FIN:'   . strtoupper( substr( $etiqueta, 8  ) ) . '}';
                        $service_docs_request = new Google_Service_Docs_Request(
                            array(
                                'replaceAllText' => array(
                                    'containsText' => array(
                                        'text' => $inicio_parrafo,
                                        'matchCase' => true
                                    ),
                                    'replaceText' => ""
                                )
                            )
                        );
                        array_push($req_reemplazos, $service_docs_request);
                        $service_docs_request = new Google_Service_Docs_Request(
                            array(
                                'replaceAllText' => array(
                                    'containsText' => array(
                                        'text' => $fin_parrafo,
                                        'matchCase' => true
                                    ),
                                    'replaceText' => ""
                                )
                            )
                        );
                        array_push($req_reemplazos, $service_docs_request);
                    } else {
                        $this->suprimir_parrafo ($client, $documentId, substr( $etiqueta, 8  ) ) ;
                    }
                } else {
                  $service_docs_request = new Google_Service_Docs_Request(
                        array(
                            'replaceAllText' => array(
                                'containsText' => array(
                                    'text' => '{'.strtoupper($etiqueta).'}',
                                    'matchCase' => true
                                ),
                                'replaceText' => $valor
                            )
                        )
                    );
                  array_push($req_reemplazos, $service_docs_request);
                }
              }
        }
        $batchUpdateRequest = new Google_Service_Docs_BatchUpdateDocumentRequest(array(
              'requests' => $req_reemplazos
          ));
        $response = $service->documents->batchUpdate($documentId, $batchUpdateRequest);
        $this->trace [] = array("merge_document_table" => "Se procesaron las etiquetas simples." );
        //--- Procesar las tablas --------
        foreach ( $array["reemplazos"] as $etiqueta => $valor) {
            if ( is_array ( $valor ) == true ) {
                $nombre_tabla   = '{'.strtoupper($etiqueta).'}' ;
                $pos_element    = $this->table_search_pos($client, $documentId, $nombre_tabla) ;
                if ($pos_element) {
                    $tabla_doc      = $this->table_get_table( $client, $documentId, $pos_element) ;
                    $num_renglones = sizeof( $valor );
                    $this->trace [] = array("merge_document_table_reemplazos_table" =>  $nombre_tabla . '- renglones - ' . $num_renglones );

                    $encontradas = false;
                    if ( $tabla_doc ) {
                        $table_cols = $valor[0];
                        $col_etiquetas =[];
                        $tbl_renglon = 0;    
                        foreach ($tabla_doc->table->tableRows as $rowElement) {
                            foreach ($table_cols as $lbl_col => $value_col ){
                                $tbl_columna = 0;
                                foreach ($rowElement->tableCells as $cell ){
                                    if ( $this->table_search_content($cell->content,  $lbl_col ) ) {
                                        $col_etiquetas[ $lbl_col ] = $tbl_columna ;
                                        $encontradas = true ;
                                    }
                                    $tbl_columna = $tbl_columna  + 1 ;
                                }
                            }
                            if ( $encontradas )
                                break; 
                            $tbl_renglon = $tbl_renglon + 1;
                        }
                        if ( $encontradas ) {
                            $insertarRows = array();
                            for($i = 0; $i < $num_renglones ; $i++){
                                $service_docs_request = new Google_Service_Docs_Request( 
                                    array(
                                    'insertTableRow' => array(
                                    'insertBelow' => true,
                                        'tableCellLocation' => array(
                                            'tableStartLocation' => array(
                                                'index'  => $tabla_doc->startIndex
                                            ),       
                                            'rowIndex'      => 1,
                                            'columnIndex'   => 1
                                        )
                                    )
                                    ));
                                array_push($insertarRows, $service_docs_request);
                            } 
                            $batchUpdateRequestTbl = new Google_Service_Docs_BatchUpdateDocumentRequest(array(
                                'requests' => $insertarRows
                            ));
                            $response = $service->documents->batchUpdate($documentId, $batchUpdateRequestTbl);
                            $this->trace [] = array("merge_document_table_renglones" => "Renglones insertados " .  $num_renglones );
                            //------------------------------------
                            $renglon        = 0 ;
                            $reemplazosTbl  = array() ;
                            foreach ( $valor as $key1 => $table_cols) {
                                foreach ($table_cols as $lbl_col => $value_col ){
                                    $indice_celda = $tabla_doc->table->tableRows[$tbl_renglon + 1+ $renglon  ]->tableCells[ $col_etiquetas[ $lbl_col ] ]->content[0]->startIndex;
                                    $service_docs_request = new Google_Service_Docs_Request( 
                                        array(
                                        'insertText' => array (
                                            'location' => array(
                                                'index'  => $indice_celda
                                            ),
                                            'text' =>strval ($value_col)
                                            )));
                                    $reemplazosTbl = array();                                    
                                    array_push($reemplazosTbl, $service_docs_request);
                                    $batchUpdateRequestTbl = new Google_Service_Docs_BatchUpdateDocumentRequest(array(
                                        'requests' => $reemplazosTbl
                                    ));
                                    $response = $service->documents->batchUpdate($documentId, $batchUpdateRequestTbl);
                                    $tabla_doc = $this->table_get_table( $client, $documentId, $pos_element) ;
                                }
                                $renglon = $renglon + 1;
                            }
                            $this->trace [] = array("merge_document_table_rellenada" => "Tabla llenada con valores " );
                            //------------------------------------
                            $borrarRows = array();
                            $service_docs_request = new Google_Service_Docs_Request( 
                                array(
                                'deleteTableRow' => array(
                                    'tableCellLocation' => array(
                                        'tableStartLocation' => array(
                                            'index'  => $tabla_doc->startIndex
                                        ),       
                                        'rowIndex'      => 1,
                                        'columnIndex'   => 1
                                    )
                                )
                                ));
                            array_push($borrarRows, $service_docs_request);
                            $batchUpdateRequestTbl = new Google_Service_Docs_BatchUpdateDocumentRequest(array(
                                'requests' => $borrarRows
                            ));
                            $response = $service->documents->batchUpdate($documentId, $batchUpdateRequestTbl);
                            $this->trace [] = array("merge_document_encabezado" => "Encabezado borrado " );
                        } else {
                            $this->trace [] = array("merge_document_table_encabezados" =>  "No se encontraron los encabezados" );
                        }
                    } // End If Tabla encontrada
                } else {
                    $this->trace [] = array("merge_document_table_reemplazos_table" =>  $nombre_tabla . '- no encontrada' );
                }

                // --- Limpiar la etiqueta --
               
                $this->replace_single_text ($client, $documentId, $etiqueta, "");
            } // End IF procesar tablas
        }
        return ; // $response;
    } // merge_document_table

    
    private function suprimir_parrafo ( $client, $documentId, $etiquetaTbl ) {
        $service        = new Google_Service_Docs($client);
        $doc            = $service->documents->get($documentId);
        $encontrado_inicio  = false;
        $encontrado_fin     = false ;
        $pos_estructural_element   = 0;

        $inicio_parrafo = '{INICIO:'. strtoupper( $etiquetaTbl ) . '}';
        $fin_parrafo    = '{FIN:'   . strtoupper( $etiquetaTbl ) . '}';

        $paragraphElementInicio = 0;
        $paragraphElementFin    = 0;

        $req_parrafos = array();
        foreach ($doc->body->content as $structuralElement) {
           if ($structuralElement->paragraph && $encontrado_fin == false) {
                foreach ($structuralElement->paragraph->elements as $paragraphElement) {
                    if ($paragraphElement->textRun) {
                        if ( $encontrado_inicio == false &&  false !==  strpos( $paragraphElement->textRun->content,    $inicio_parrafo ) ) {
                            $encontrado_inicio = true ;
                            $paragraphElementInicio = $paragraphElement;
                        } 
                        if ( $encontrado_inicio == true &&  false !==  strpos( $paragraphElement->textRun->content,    $fin_parrafo ) ) {
                            $encontrado_fin = true ;
                            $paragraphElementFin    = $paragraphElement;
                        } 
                        if ( $encontrado_inicio == true && $encontrado_fin == true  ) {
                            $this->trace [] = array("suprimir_parrafo" => $etiquetaTbl );
                            $requests[] = new Google_Service_Docs_Request(array(
                                'deleteContentRange' => array(
                                    'range' => array(
                                        'startIndex' => $paragraphElementInicio->startIndex,
                                        'endIndex' => $paragraphElementFin->startIndex + strlen( $fin_parrafo)
                                    ),
                                ),
                            ));
                            $batchUpdateRequest = new Google_Service_Docs_BatchUpdateDocumentRequest(array(
                                'requests' => $requests
                            ));
                            $response = $service->documents->batchUpdate($documentId, $batchUpdateRequest);
                            return true;
                        }

                    }
                    $pos_estructural_element = $pos_estructural_element + 1;
                }
            } 
            
        } 
        return false; 
    } //-- suprimir_parrafo
    //----------------------

    private function table_search_pos ( $client, $documentId, $etiquetaTbl ) {
        $service        = new Google_Service_Docs($client);
        $doc            = $service->documents->get($documentId);
        $encontrado     = false;
        $pos_estructural_element   = 0;
        foreach ($doc->body->content as $structuralElement) {
           if ($structuralElement->paragraph && $encontrado == false) {
                foreach ($structuralElement->paragraph->elements as $paragraphElement) {
                    if ($paragraphElement->textRun) {
                        if ( strstr( $paragraphElement->textRun->content,    $etiquetaTbl ) ) {
                            $encontrado = true ;
                        } 
                    }
                }
            } else 
            if ( $structuralElement->table && $encontrado == true ) {
                $this->trace [] = array("table_search_table_element" => $etiquetaTbl.' startIndex:' .$structuralElement->startIndex . ' pos '. $pos_estructural_element. '  retornada' );
                return $pos_estructural_element;
            } 
            $pos_estructural_element = $pos_estructural_element + 1;
        } 
        return $encontrado; 
    } //-- table_search_pos

    private function table_get_table ( $client, $documentId, $pos_estructural_element ) {
        $service    = new Google_Service_Docs($client);
        $doc        = $service->documents->get($documentId);
        $structuralElement = $doc->body->content[$pos_estructural_element ];
        return $structuralElement; 
    } // table_get_table

    private function table_search_content ($contents , $search){
        $encontrado     = false;
        $search_column = '{'.strtoupper($search).'}' ;
        foreach ($contents as $structuralElement) {
            foreach ($structuralElement->paragraph->elements as $paragraphElement) {
                if ($paragraphElement->textRun) {
                    if ( strstr( $paragraphElement->textRun->content,    $search_column ) ) {
                        $encontrado = true ;
                        $this->trace [] = array("table_search_content" => $search_column. '  *encontrado*' );
                    } 
                }
            }
        }
        return $encontrado;
    } //-- table_search_content
};

