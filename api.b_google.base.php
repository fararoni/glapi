<?php
/**
* Funciones básicas para interactuar con Google Docs API
*
* @package    API Google Template
* @author     Isaí Fararoni Ramírez
* @version    1 de enero de 2019
* @version    1.1.0
*/
    class ApiGoogle {
        const GOOGLE_API_CREDENTIAL_FILE = 'apeplazas-plantillas-6f95ba053718.json';

        function checkServiceAccountCredentialsFile() {
            $application_creds = __DIR__ . '/' .self::GOOGLE_API_CREDENTIAL_FILE ;
            return file_exists($application_creds) ? $application_creds : false;
        } //-- checkServiceAccountCredentialsFile
        public function get_client( $app_template_name )  {
            $client = new Google_Client();
            $client->setApplicationName( $app_template_name );
            $client->setScopes(Google_Service_Docs::DOCUMENTS);
            $client->addScope(Google_Service_Drive::DRIVE);
            $client->setAccessType('offline');
            $client->setIncludeGrantedScopes(true);
            $credentials_file = $this->checkServiceAccountCredentialsFile();
            if ( $credentials_file ) {
                $client->setAuthConfig($credentials_file);
            } elseif (getenv('GOOGLE_APPLICATION_CREDENTIALS')) {
                $client->useApplicationDefaultCredentials();
            } else {
                $logger  = new ApiLogger;
                $logger->error( "Error. get_client No se encontro la credencial de Google, ingresar a https://console.cloud.google.com/" ) ;
            }
            return $client;
        } //-- get_client

        public function copy_document( $client, $folderDestId, $documentId_src, $titulo ){
            $doc_src_service    = new Google_Service_Docs($client);
            try {
                $doc_src        = $doc_src_service->documents->get( $documentId_src );
                if ( is_null( $titulo ) or empty(  $titulo ) ) {
                    $copy_Title     = date("Ymd-His")." ". $doc_src->getTitle();
                } else {
                    $copy_Title     =$titulo;
                }
                $copy_file      = $this->copy_file($client,$folderDestId, $documentId_src, $copy_Title );
                return $copy_file;
            } catch (Exception $e){
                $logger  = new ApiLogger;
                $logger->error( "Excepion. copy_document " . $folderDestId. ' - ' . $documentId_src . ' - ' . $titulo . ' - ' . $e->getMessage() ) ;
                return false;
            }
        } //-- copy_document

        public function copy_file( $client,  $folderDestId, $originFileId, $titulo) {
            $copy_service   = new Google_Service_Drive($client);
            $copy_file      = new Google_Service_Drive_DriveFile(
                                array(  'name'      => $titulo,
                                        'parents'  => array($folderDestId) ) ) ;
            try {
                return $copy_service->files->copy($originFileId, $copy_file);
            } catch (Exception $e) {
                $logger  = new ApiLogger;
                $logger->error( "Excepion. copy_file " . $folderDestId. ' - ' . $originFileId . ' - ' . $titulo . ' - ' . $e->getMessage() ) ;
                return false;
            }
        } //-- copy_file

        public function get_content_pdf( $client, $document_id ){
            try {
                $drive_service  = new Google_Service_Drive($client);
                $file           = $drive_service->files->get($document_id);
                $nombre_archivo = $file->getName() ;
                $content = "";
                if ( strstr( $file->getMimeType(), 'application/pdf') ) {
                    $drive_service   = new Google_Service_Drive($client);
                    $response = $drive_service->files->get($document_id, array('alt' => 'media'));
                    $content = $response->getBody()->getContents();
                }
                else  if ( strstr( $file->getMimeType(), 'application/vnd.google-apps.document' ) )
                {
                    $file_pdf = $drive_service->files->export( $document_id,'application/pdf') ;
                    $content = (string) $file_pdf->getBody();
                }
                return $content;
            } catch (Exception $e) {
                $logger  = new ApiLogger;
                $logger->error( "Excepion. get_content_pdf " . $document_id . ' - ' . $e->getMessage() ) ;
                return false;
            }
        } //-- get_content_pdf

    }
?>