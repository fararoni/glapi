<?php
/**
* Procesador de bitácora
*
* @package    API Google Template
* @author     Isaí Fararoni Ramírez
* @version    1 de enero de 2019
* @version    1.1.0
*/
    class ApiLogger {
        private $currentMilliSecond;
        private $filename_log;
        private $filename_json;

        public function __construct(){
            date_default_timezone_set('America/Mexico_City');
            $this->currentMilliSecond    = (int) (microtime(true) * 1000);
            $this->filename_log          = './logs/'.date('Y-m-d'). '-google_template.log';
            $this->filename_json         = './logs/'.date('Y-m-d') . '-' . $this->currentMilliSecond . '-google_template.json';
        }

        private function getTime(){
            $ip = "no.ip.a.b";
            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                $ip =' CLIENT_IP '. $_SERVER['HTTP_CLIENT_IP'];
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip =' X_FORWARD '. $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else {
                $ip = ' REM_ADDR '. $_SERVER['REMOTE_ADDR'];
            }
            $metodo = empty($_SERVER['REQUEST_METHOD'])  ? 'NOMETHOD' : $_SERVER['REQUEST_METHOD'] ;
            $uri    = empty($_SERVER['REQUEST_URI'])     ? 'NOURI' : $_SERVER['REQUEST_URI'] ;
            return  $this->currentMilliSecond.' - '. date("F j, Y, g:i a") . ' - ' .$ip. ' - ' .$metodo . ' - ' . $uri . ' - ' ;
        }
        public function log($msg){
            $log  = $this->getTime() . ' -LOG-' . $msg  . PHP_EOL;
            $this->writelog ( $log ) ;
        }
        public function error( $msg_error ) {
            $log  = $this->getTime() . ' -ERROR-' . $msg_error  . PHP_EOL;
            $this->writelog ( $log ) ;
        }
        public function debug( $msg_debug ){
            $log  = $this->getTime() . ' -DEBUG-' . $msg_debug . PHP_EOL;
            $this->writelog ( $log ) ;
        }
        
        private function getTimeStamp(){
            $file_date = date('Y-m-d H-i-s', time()) .'-'. $this->currentMilliSecond;
            return $file_date;
        }

        private function writelog ($msg_log ){
            file_put_contents( $this->filename_log , $msg_log , FILE_APPEND);
        }
        public function json_request  ( ){
            $json_req1 = trim( file_get_contents('php://input') );
            file_put_contents( $this->filename_json , var_export( $json_req1 , TRUE) );
        }

    }
?>