<?php 
///////////////////////////////////////////////////////////////////////////////////////////////////////////
//funcao que incializa ambiente em caso de troca de menu com sucesso
function inicializa_ambiente_novo_menu(){
    global $configs,$tentativas;
    verbose("INICIALIZANDO VARIAVEIS DE AMBIENTE PARA TROCA DE MENU COM SUCESSO.");
    $tentativas=0;

}


///////////////////////////////////////////////////////////////////////////////////////////////////////////
function retentar_dado_invalido($menu,$audio,$motivo){
    global $configs,$tentativas;

    $tentativas++;
    verbose($texto.$menu." TENTATIVAS REALIZADAS:".$tentativas." MAXIMO DE TENTATIVAS:".$configs['max_tentativas'],3);
    if($tentativas < $configs['max_tentativas']) {
    verbose("RETENTATIVA AUTORIZADA. RETORNANDO TRUE",3);
    playback($audio);
    return true;
    }
    else  
    {
        verbose("EXECEDEU O LIMITE DE RETENTATIVAS PERMITIDAS. RETORNANDO FALSE");
        return false;
    }
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////
function encerra_por_retentativas($menu,$audio,$motivo)
{
    global $configs; 
    verbose("DESLIGANDO CLIENTE POR ".$motivo,3);
    playback($audio);
    hangup();
    exit();
}

function encerra_com_tracking($canal, $ddr, $ticket, $indice, $menu,$audio,$motivo)
{
    global $configs; 

    tracking($canal, $ddr, $ticket, $indice, 'FINALIZACAO DA LIGACAO', 'PERCURSO', 'URA FINALIZOU A LIGACAO');
    
    verbose("DESLIGANDO CLIENTE POR ".$motivo,3);
    playback($audio);
    hangup();
    exit();
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////
function verbose($texto,$loglevel)
{
global $configs, $debug_level, $origem, $fastagi, $uniqueid;

    if($debug_level >= $loglevel)
    {
        $data = date("Y-m-d_His");
        $info = $configs['siglaUra']."_".$data."_".$uniqueid."_".$origem."->".$texto;
        $fastagi->verbose($info);
        syslog(LOG_INFO,$info);
    return true;
    }
    else return false;
 
 //NIVEIS DE DEBUG E REGISTRO DE LOG NO SYSLOG
 //1 - ERROS DE EXECUÇÃO 
 //2 - ALERTAS
 //3 - MENSAGENS INFORMATIVAS
 //4 - DEBUG LEVE
 //5 - DEBUG TOTAL
}


///////////////////////////////////////////////////////////////////////////////////////////////////////////
function playback($audio)
{
    global $configs,$fastagi;

    //copiar um audio padrao para a pasta especifica para teste
    if (!file_exists($configs['audio_lib'].$audio.$configs['extensao_audio']) && $configs['copiar_audios_para_teste'])verbose("favor copiar cp ".$configs['audio_lib']."audioteste.wav ".$configs['audio_lib'].$audio.$configs['extensao_audio']);

    if (file_exists($configs['audio_lib'].$audio.$configs['extensao_audio']))
    {
        $fastagi->exec("Playback",$audio);
        verbose("Audio ".$audio." encontrado e executado.",4);
        return true;
    }    
    else
    {
        if($configs['copiar_audios_para_teste']) copy($configs['audio_lib']."audioteste.wav", $configs['audio_lib'].$configs['audio_ura'].$audio);
        verbose("ERRO Audio ".$audio." não encontrado, favor verificar",1);  
        return false;
    }
}
///////////////////////////////////////////////////////////////////////////////////////
function background($audio,$qtdd)
{
    global $configs,$fastagi;
    
    verbose("COMEÇOU A LER OS DADOS");
    $fastagi->exec("Read","retorno,".$audio.",".$qtdd.",,,6");
    verbose("TERMINOU DE LER OS DADOS");
    $retorno =$fastagi->get_variable('retorno');
    verbose("DIGITOS INSERIDOS PELO USUARIO : ".$retorno['data']);
    return $retorno['data'];
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////
function hangup()
{
    global $configs,$fastagi;
    $fastagi->exec("hangup");
}


///////////////////////////////////////////////////////////////////////////////////////////////////////////
function coletar_dados_usuario($audio,$maxdigits){

    global $configs,$fastagi;

    //copiar um audio padrao para a pasta especifica para teste
    if (!file_exists($configs['audio_lib'].$audio.$configs['extensao_audio']) && $configs['copiar_audios_para_teste'])verbose("favor copiar cp ".$configs['audio_lib']."audioteste.wav ".$configs['audio_lib'].$audio.$configs['extensao_audio']);

    
    $opcao=$fastagi->get_data($audio, $configs['max_timeout'],$maxdigits);

    if(strlen($opcao{'result'}) == 11 || strlen($opcao{'result'}) == 14){
        if(!canal_ativo()) exit();
        return $opcao['result'];
    }
    
    //USUARIO DIGITOU UMA OPCAO


    if($opcao['result'] !='' && $opcao['data']=='')
    {
        //verbose("OPCAO DIGITADA PELO USUARIO ".$opcao['result'],3);
        if(!canal_ativo()) exit();
        return $opcao['result'];
    }   

    //USUARIO DIGITOU PARCIAL MENOS QUE O TOTAL DE DIGITOS ESPERADOS
    if($opcao['result'] !='' && $opcao['data']=='timeout')
    {
        //verbose("OPCAO DIGITADA PELO USUARIO PARCIALMENTE MENOS QUE O TOTAL DE DIGITOS ESPERADOS ".$opcao['result'],3);
        if(!canal_ativo()) exit();
        return $opcao['result'];
    } 

    // TIMEOUT SEM DIGITAR NADA
    if($opcao['result'] =='' && $opcao['data']=='timeout')
    {
        verbose("TIMEOUT PELO USUARIO",3);
        if(!canal_ativo()) exit();
        return "TIMEOUT";
    }
    
    //CASO DE ERRO NAO TRATADO NA FUNCAO DE COLETA DE DADOS.
    verbose("ERRO NAO TRATADO NA FUNCAO coletar_dados_usuario");
    verbose("RESULT|".$opcao['result']."|");
    verbose("DATA|".$opcao['data']."|");
    if(!canal_ativo()) exit();
    return "ERROR";

}

///////////////////////////////////////////////////////////////////////////////////////////////////////////
function falar_numero($numero)
{
    global $configs,$fastagi;


    verbose("EXECUTANDO FUNCAO FALAR NUMERO PARA O NUMERO ".$numero,4);
    $fastagi->exec("SayDigits",$numero);
    return true;


}

////////////////////////////////////////////////////////////////////gambi

function falar_data($data)
{
    global $configs,$fastagi;

    list ($dia, $mes, $ano) = split ('[/]', $data);

    $diaF= valorPorExtenso($dia, false, false);
    $mesF= valorPorExtenso($mes, false, false);
    $anoF= valorPorExtenso($ano, false, false);

    $data_formatada='';
    foreach ($diaF as $key => $value) {
	    if($value!=''){
		    $data_formatada.=$value;
	        $data_formatada.=' ';
	    }
    }
    $data_formatada.='do '; 
    foreach ($mesF as $key => $value) {
	    if($value!=''){
            $data_formatada.=$value;
            $data_formatada.=' ';
        }
    }
    $data_formatada.='de ';
    foreach ($anoF as $key => $value) {
	    if($value!=''){
	        $data_formatada.=' ';
            $data_formatada.=$value;
        }
    }
    $linha = explode(" ", $data_formatada);

    //echo $data_formatada."\n";
    //var_dump($linha);
    
    foreach ($linha as $key => $value) {
        $value = "uraTopBank/".$value;
        playback($value);
    }
}

function removerFormatacaoNumero($strNumero){
    $strNumero = trim( str_replace( "R$", null, $strNumero ) );
 
    $vetVirgula = explode( ",", $strNumero );
    if ( count( $vetVirgula ) == 1 )
    {
        $acentos = array(".");
        $resultado = str_replace( $acentos, "", $strNumero );
        return $resultado;
    }
    else if ( count( $vetVirgula ) != 2 )
    {
        return $strNumero;
    }

    $strNumero = $vetVirgula[0];
    $strDecimal = mb_substr( $vetVirgula[1], 0, 2 );

    $acentos = array(".");
    $resultado = str_replace( $acentos, "", $strNumero );
    $resultado = $resultado . "." . $strDecimal;

    return $resultado;
}
    
function valorPorExtenso($valor = 0, $bolExibirMoeda = true, $bolPalavraFeminina = false ){
 
    $valor = removerFormatacaoNumero($valor);

    $singular = null;
    $plural = null;

    if ( $bolExibirMoeda )
    {
        $singular = array("centavo", "real", "mil", "milhao", "bilhao", "trilhao", "quatrilhao");
        $plural = array("centavos", "reais", "mil", "milhoes", "bilhoes", "trilhoes","quatrilhoes");
    }
    else
    {
        $singular = array("", "", "mil", "milhao", "bilhao", "trilhao", "quatrilhao");
        $plural = array("", "", "mil", "milhoes", "bilhoes", "trilhoes","quatrilhoes");
    }

    $c = array("", "cem", "duzentos", "trezentos", "quatrocentos","quinhentos", "seiscentos", "setecentos", "oitocentos", "novecentos");
    $d = array("", "dez", "vinte", "trinta", "quarenta", "cinquenta","sessenta", "setenta", "oitenta", "noventa");
    $d10 = array("dez", "onze", "doze", "treze", "quatorze", "quinze","dezesseis", "dezessete", "dezoito", "dezenove");
    $u = array("", "um", "dois", "tres", "quatro", "cinco", "seis","sete", "oito", "nove");


    if ( $bolPalavraFeminina )
    {
    
        if ($valor == 1) 
        {
            $u = array("", "uma", "duas", "tres", "quatro", "cinco", "seis","sete", "oito", "nove");
        }
        else 
        {
            $u = array("", "um", "duas", "tres", "quatro", "cinco", "seis","sete", "oito", "nove");
        }
        
        
        $c = array("", "cem", "duzentas", "trezentas", "quatrocentas","quinhentas", "seiscentas", "setecentas", "oitocentas", "novecentas");
    }

    $z = 0;

    $valor = number_format( $valor, 2, ".", "." );
    $inteiro = explode( ".", $valor );

    for ( $i = 0; $i < count( $inteiro ); $i++ ) 
    {
        for ( $ii = mb_strlen( $inteiro[$i] ); $ii < 3; $ii++ ) 
        {
            $inteiro[$i] = "0" . $inteiro[$i];
        }
    }

    // $fim identifica onde que deve se dar junção de centenas por "e" ou por "," ;)
    $rt = null;
    $fim = count( $inteiro ) - ($inteiro[count( $inteiro ) - 1] > 0 ? 1 : 2);
    //echo $fim."\n";
    //echo count($inteiro)."\n";
    for ( $i = 0; $i < count( $inteiro ); $i++ )
    {
        $valor = $inteiro[$i];
        $rc = (($valor > 100) && ($valor < 200)) ? "cento" : $c[$valor[0]];
        $rd = ($valor[1] < 2) ? "" : $d[$valor[1]];
        $ru = ($valor > 0) ? (($valor[1] == 1) ? $d10[$valor[2]] : $u[$valor[2]]) : "";
        $r = $rc . (($rc && ($rd || $ru)) ? " ee " : "") . $rd . (($rd && $ru) ? " ee " : "") . $ru;
        $t = count( $inteiro ) - 1 - $i;
        $r .= $r ? " " . ($valor > 1 ? $plural[$t] : $singular[$t]) : "";
        if ( $valor == "000")
            $z++;
        elseif ( $z > 0 )
            $z--;
            
        if ( ($t == 1) && ($z > 0) && ($inteiro[0] > 0) )
            $r .= ( ($z > 1) ? " de " : "") . $plural[$t];
            
        if ($r)
            $rt = $rt.((($i > 0) && ($i <= $fim) && ($inteiro[0] > 0) && ($z < 1)) ? ( ($i < $fim) ? " " : " ee ") : " ").$r;
    }

    $rt = mb_substr( $rt, 1 );

    $linha = explode(" ", $rt);
    return $linha;
    //return($rt ? trim( $rt ) : "zero");
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////
function falar_valor($saldoTotal)
{
    if(is_null($saldoTotal)) $saldoTotal=0;
    $saldoTotal= strval($saldoTotal);
    $saldoTotal = str_replace(".", ",",$saldoTotal);
    $saldoTotalExplode = valorPorExtenso($saldoTotal, true, false);
    foreach ($saldoTotalExplode as $key => $value) {
        $value = "uraTopBank/".$value;
        playback($value);
    }
    //return true;
    return "PASSOU";
}

function falar_alfa($texto)
{
    global $configs,$fastagi;
    $fastagi->exec("SayAlpha",$valor);

    $i = 0;
    $tamanho = strlen($texto);
    while($i < $tamanho){
        if($texto[$i] =="/"){
            $i++;
            verbose("FUNÇÃO FALAR ALFA IGNORANDO CARACTER / ");
        } else {
            if($texto[$i] == "0") playback("uraTopBank/zero"); else 
            if($texto[$i] == "1") playback("uraTopBank/um"); else 
            if($texto[$i] == "2") playback("uraTopBank/dois"); else 
            if($texto[$i] == "3") playback("uraTopBank/tres"); else 
            if($texto[$i] == "4") playback("uraTopBank/quatro"); else 
            if($texto[$i] == "5") playback("uraTopBank/cinco"); else 
            if($texto[$i] == "6") playback("uraTopBank/seis"); else 
            if($texto[$i] == "7") playback("uraTopBank/sete"); else 
            if($texto[$i] == "8") playback("uraTopBank/oito"); else 
            if($texto[$i] == "9") playback("uraTopBank/nove"); else
            if($texto[$i] == ".") playback("uraTopBank/ponto"); else
            playback("uraTopBank/".strtolower($texto[$i]));
            verbose("FUNÇÃO FALAR ALFA FALANDO CARACTER ".$texto[$i],3);
            $i++;
            
            
        }
    }
    return true;

}

function retornar_alfa($texto)
{
    global $configs,$fastagi;
    $fastagi->exec("SayAlpha",$valor);

    $i = 0;
    $tamanho = strlen($texto);
    $retorno = '';

    while($i < $tamanho){
        if($texto[$i] =="/"){
            $i++;
            verbose("FUNÇÃO RETORNAR ALFA IGNORANDO CARACTER / ");
        } else {

            switch ($texto[$i]) {
                case '0':
                    $retorno .="uraTopBank/zero";
                    break;
                case '1':
                    $retorno .="uraTopBank/um";
                    break;
                case '2':
                    $retorno .="uraTopBank/dois";
                    break;
                case '3':
                    $retorno .="uraTopBank/tres";
                    break;
                case '4':
                    $retorno .="uraTopBank/quatro";
                    break;
                case '5':
                    $retorno .="uraTopBank/cinco";
                    break;
                case '6':
                    $retorno .="uraTopBank/seis";
                    break;
                case '7':
                    $retorno .="uraTopBank/sete";
                    break;
                case '8':
                    $retorno .="uraTopBank/oito";
                    break;
                case '9':
                    $retorno .="uraTopBank/nove";
                    break;
                case '.':
                    $retorno .="uraTopBank/ponto";
                    break;
                default:
                    $retorno .="uraTopBank/".strtolower($texto[$i]);
                    break;
            }
            verbose("FUNÇÃO RETORNAR ALFA FALANDO CARACTER ".$texto[$i],3);

            if($i!=0 && $i < $tamanho){
                $retorno .='&';
            } 
            if($i==0){
                $retorno =$retorno.'&';
            }
            $i++;            
        }
    }
    return $retorno;
}

function canal_ativo()
{
    global $configs,$fastagi;
    $status=$fastagi->channel_status();

    if($status["data"] == 'Line is up')return true;
    else
    {
        verbose("ENCERRANDO AGI POIS O CANAL DE ORIGEM NAO ESTA MAIS ATIVO, STATUS ATUAL DO CANAL :".$status["data"]);
        hangup();
        exit();
        return false;
    }
}


/////////////////////////////////////////////////////////////////
function coleta_dados2($canal, $ddr, $ticket, $indice, $audio, $maxdigits){

    global $configs,$fastagi;

    //copiar um audio padrao para a pasta especifica para teste
    if (!file_exists($configs['audio_lib'].$audio.$configs['extensao_audio']) && $configs['copiar_audios_para_teste'])verbose("favor copiar cp ".$configs['audio_lib']."audioteste.wav ".$configs['audio_lib'].$audio.$configs['extensao_audio']);

    
    $opcao=$fastagi->get_data($audio, $configs['max_timeout'],$maxdigits);

    if(strlen($opcao{'result'}) == 11 || strlen($opcao{'result'}) == 14){
        //if(!canal_ativo()) exit();
        if(!tracking_canal_ativo($canal, $ddr, $ticket, $indice, 'FINALIZACAO DA LIGACAO', 'PERCURSO', 'CLIENTE FINALIZOU A LIGACAO')) exit();
        return $opcao['result'];
    }
    
    //USUARIO DIGITOU UMA OPCAO


    if($opcao['result'] !='' && $opcao['data']=='')
    {
        //verbose("OPCAO DIGITADA PELO USUARIO ".$opcao['result'],3);
        //if(!canal_ativo()) exit();
        if(!tracking_canal_ativo($canal, $ddr, $ticket, $indice, 'FINALIZACAO DA LIGACAO', 'PERCURSO', 'CLIENTE FINALIZOU A LIGACAO')) exit();
        return $opcao['result'];
    }   

    //USUARIO DIGITOU PARCIAL MENOS QUE O TOTAL DE DIGITOS ESPERADOS
    if($opcao['result'] !='' && $opcao['data']=='timeout')
    {
        //verbose("OPCAO DIGITADA PELO USUARIO PARCIALMENTE MENOS QUE O TOTAL DE DIGITOS ESPERADOS ".$opcao['result'],3);
        //if(!canal_ativo()) exit();
        if(!tracking_canal_ativo($canal, $ddr, $ticket, $indice, 'FINALIZACAO DA LIGACAO', 'PERCURSO', 'CLIENTE FINALIZOU A LIGACAO')) exit();
        return $opcao['result'];
    } 

    // TIMEOUT SEM DIGITAR NADA
    if($opcao['result'] =='' && $opcao['data']=='timeout')
    {
        verbose("TIMEOUT PELO USUARIO",3);
        //if(!canal_ativo()) exit();
        if(!tracking_canal_ativo($canal, $ddr, $ticket, $indice, 'FINALIZACAO DA LIGACAO', 'PERCURSO', 'CLIENTE FINALIZOU A LIGACAO')) exit();
        return "TIMEOUT";
    }
    
    //CASO DE ERRO NAO TRATADO NA FUNCAO DE COLETA DE DADOS.
    verbose("ERRO NAO TRATADO NA FUNCAO coletar_dados_usuario");
    verbose("RESULT|".$opcao['result']."|");
    verbose("DATA|".$opcao['data']."|");
    //if(!canal_ativo()) exit();
    if(!tracking_canal_ativo($canal, $ddr, $ticket, $indice, 'FINALIZACAO DA LIGACAO', 'PERCURSO', 'CLIENTE FINALIZOU A LIGACAO')) exit();
    return "ERROR";

}

/////////////////////////////////////////////////////////////////
function tracking_canal_ativo($canal, $ddr, $ticket, $indice)
{
    global $configs,$fastagi;
    $status=$fastagi->channel_status();

    if($status["data"] == 'Line is up')return true;
    else
    {
        verbose("ENCERRANDO AGI POIS O CANAL DE ORIGEM NAO ESTA MAIS ATIVO, STATUS ATUAL DO CANAL :".$status["data"]);

        tracking($canal, $ddr, $ticket, $indice, 'FINALIZAÇÃO', 'PERCURSO', 'CLIENTE DESLIGOU A CHAMADA DURANTE A URA');

        hangup();
        exit();
        return false;
    }
}

/////////////////////////////////////////////////////////////////

function dial_fast($telefone){

    global $configs,$fastagi;

    $status=$fastagi->exec("Dial","SIP/".$telefone);
    return $status;

}

/////////////////////////////////////////////////////////////////

function dial_return($telefone){

    global $configs,$fastagi;

    $status=$fastagi->exec("Dial","SIP/".$telefone,"q");
    return $status;

}

/////////////////////////////////////////////////////////////////

function goto_fast($fila){

    global $configs,$fastagi;
	
    $status=$fastagi->exec("Dial","Local/".$fila."@ext-group");
    return $status;

}

/////////////////////////////////////////////////////////////////

function retenta_telefone($cnpjcpf){

    global $configs,$fastagi;

    verbose("USUÁRIO CONCORDOU COM A LEI LGPD");
    $fone='';
    $fone=coletar_dados_usuario("uraTopBank/audio17",12);
    if($fone == '-1'){hangup();break;}

    if(strlen($fone)> 12 || strlen($fone) < 8){
        if(retentar_dado_invalido("M2_2_Desejo_me_Credenciar","uraTopBank/audio24","NÚMERO INVÁLIDO"))retenta_telefone($cnpjcpf);
        else encerra_por_retentativas("M2_2_Desejo_me_Credenciar","uraTopBank/audio7","NÚMERO INVÁLIDO");
    }
    verbose("NUMERO DIGITADO PELO CLIENTE: ".$fone);
    if(strlen($fone)>=8 && strlen($fone)<=12){
        verbose("NUMERO DIGITADO ATENDE AOS REQUISITOS");

        $processId='WKF_Prospect';

        verbose("FUNCAO RETENTA TELEFONE CNPJ : ".$cnpjcpf);
        verbose("FUNCAO RETENTA TELEFONE FONE INFORMADO : ".$fone);

        $protocolo = protocolo_atendimento($cnpjcpf, $processId, $fone);
        verbose("PROTOCOLO GERADO PELA API:".$protocolo,3);
        if(isset($protocolo)){
            verbose("SOLICITAÇÃO REGISTRADA");
            playback("uraTopBank/audio19");
                
            verbose("PROTOCOLO GERADO PELA API: ".$protocolo,3);
            verbose("FALANDO O NÚMERO DE PROTOCOLO");
            playback("uraTopBank/audio20.1");
            falar_alfa($protocolo);                        
            playback("uraTopBank/audio20.2");
            playback("uraTopBank/encerramento");
            hangup();
        }
    }
}

////////////////////////////////////////////////////////////

function retorna_audio($retornoApi){
    $retornoApi= strtoupper($retornoApi);
    //$retornoApi = preg_replace('/\d[0-9]/', '', $retornoApi);
    $retornoApi=explode(' -', $retornoApi, 1);
    $retornoApi=$retornoApi[0];
    verbose("MENSAGEM DE ERRO PELA API : ".$retornoApi);
    $audio=str_replace(' ','_',$retornoApi);
    $audioArquivo='MSG_FROTA_NEGADA_'.$audio.'.wav';
    $audioNome='MSG_FROTA_NEGADA_'.$audio;
    //verbose("AUDIO FORMATADO : ".$audioArquivo);
    //verbose("AUDIO NOME : ".$audioNome);
    if(file_exists("/var/lib/asterisk/sounds/Fraseologia/".$audioArquivo)){
        verbose("ARQUIVO A SER REPRODUZIDO : ".$audioNome);
        playback("Fraseologia/".$audioNome);
    }else{
        verbose("ARQUIVO NÃO ENCONTRADO : ".$audioNome);
        verbose("REPRODUZINDO AUDIO PADRAO DE ERRO");
        playback("Fraseologia/MSG_FROTA_NEGADA_TRANSACAO_NEGADA_CODIGO_NAO_ENCONTRADO");
    }
}

////////////////////////////////////////////////////////////

function falar_texto($retornoApi, $diretorio){
    verbose("MENSAGEM DE ERRO PELA API : ".$retornoApi);
    $audio=str_replace(' ','_',$retornoApi);
    $audioArquivo= $audio.'.wav';
    $audioNome= $audio;
    verbose("AUDIO FORMATADO : ".$audioArquivo);
    verbose("AUDIO NOME : ".$audioNome);
    if(file_exists("/var/lib/asterisk/sounds/".$diretorio."/".$audioArquivo)){
        verbose("ARQUIVO A SER REPRODUZIDO : ".$audioNome);
        playback($diretorio."/".$audioNome);
    }else{
        playback("Fraseologia/MSG_FROTA_NEGADA_TRANSACAO_NEGADA_CODIGO_NAO_ENCONTRADO");
    }
}

////////////////////////////////////////////////////////////

class clsTexto 
{
    public static function removerFormatacaoNumero( $strNumero )
    {
 
        $strNumero = trim( str_replace( "R$", null, $strNumero ) );
 
        $vetVirgula = explode( ",", $strNumero );
        if ( count( $vetVirgula ) == 1 )
        {
            $acentos = array(".");
            $resultado = str_replace( $acentos, "", $strNumero );
            return $resultado;
        }
        else if ( count( $vetVirgula ) != 2 )
        {
            return $strNumero;
        }
 
        $strNumero = $vetVirgula[0];
        $strDecimal = mb_substr( $vetVirgula[1], 0, 2 );
 
        $acentos = array(".");
        $resultado = str_replace( $acentos, "", $strNumero );
        $resultado = $resultado . "." . $strDecimal;
 
        return $resultado;
 
    }
    
    public static function valorPorExtenso( $valor = 0, $bolExibirMoeda = true, $bolPalavraFeminina = false )
    {
 
        $valor = self::removerFormatacaoNumero($valor);
 
        $singular = null;
        $plural = null;
 
        if ( $bolExibirMoeda )
        {
            $singular = array("centavo", "real", "mil", "milhao", "bilhao", "trilhao", "quatrilhao");
            $plural = array("centavos", "reais", "mil", "milhoes", "bilhoes", "trilhoes","quatrilhoes");
        }
        else
        {
            $singular = array("", "", "mil", "milhao", "bilhao", "trilhao", "quatrilhao");
            $plural = array("", "", "mil", "milhoes", "bilhoes", "trilhoes","quatrilhoes");
        }
 
        $c = array("", "cem", "duzentos", "trezentos", "quatrocentos","quinhentos", "seiscentos", "setecentos", "oitocentos", "novecentos");
        $d = array("", "dez", "vinte", "trinta", "quarenta", "cinquenta","sessenta", "setenta", "oitenta", "noventa");
        $d10 = array("dez", "onze", "doze", "treze", "quatorze", "quinze","dezesseis", "dezessete", "dezoito", "dezenove");
        $u = array("", "um", "dois", "tres", "quatro", "cinco", "seis","sete", "oito", "nove");
 
        if ( $bolPalavraFeminina )
        {
        
            if ($valor == 1) 
            {
                $u = array("", "uma", "duas", "tres", "quatro", "cinco", "seis","sete", "oito", "nove");
            }
            else 
            {
                $u = array("", "um", "duas", "tres", "quatro", "cinco", "seis","sete", "oito", "nove");
            }
            
            
            $c = array("", "cem", "duzentas", "trezentas", "quatrocentas","quinhentas", "seiscentas", "setecentas", "oitocentas", "novecentas");               
        } 
 
        $z = 0;
 
        $valor = number_format( $valor, 2, ".", "." );
        $inteiro = explode( ".", $valor );
 
        for ( $i = 0; $i < count( $inteiro ); $i++ ) 
        {
            for ( $ii = mb_strlen( $inteiro[$i] ); $ii < 3; $ii++ ) 
            {
                $inteiro[$i] = "0" . $inteiro[$i];
            }
        }
 
        // $fim identifica onde que deve se dar junção de centenas por "e" ou por "," ;)
        $rt = null;
        $fim = count( $inteiro ) - ($inteiro[count( $inteiro ) - 1] > 0 ? 1 : 2);
        //echo $fim."\n";
        //echo count($inteiro)."\n";
        for ( $i = 0; $i < count( $inteiro ); $i++ )
        {
            $valor = $inteiro[$i];
            $rc = (($valor > 100) && ($valor < 200)) ? "cento" : $c[$valor[0]];
            $rd = ($valor[1] < 2) ? "" : $d[$valor[1]];
            $ru = ($valor > 0) ? (($valor[1] == 1) ? $d10[$valor[2]] : $u[$valor[2]]) : "";

            $r = $rc . (($rc && ($rd || $ru)) ? " ee " : "") . $rd . (($rd && $ru) ? " ee " : "") . $ru;
            $t = count( $inteiro ) - 1 - $i;
            $r .= $r ? " " . ($valor > 1 ? $plural[$t] : $singular[$t]) : "";
            if ( $valor == "000")
                $z++;
            elseif ( $z > 0 )
                $z--;
                
            if ( ($t == 1) && ($z > 0) && ($inteiro[0] > 0) )
                $r .= ( ($z > 1) ? " de " : "") . $plural[$t];
                
            if ($r)
                $rt = $rt.((($i > 0) && ($i <= $fim) && ($inteiro[0] > 0) && ($z < 1)) ? ( ($i < $fim) ? " " : " ee ") : " ").$r;
        }
 
        $rt = mb_substr( $rt, 1 );
    
        $linha = explode(" ", $rt);
        return $linha;
        //return($rt ? trim( $rt ) : "zero"); 
    }
}
///////////////////////////////////////////////////////////////////////

function tracking($canal, $ddr, $ticket, $indice, $nome_evento, $extras_key, $extras_value){

    verbose("------------INICIO DO TRACKING ------------");
    verbose("TRACKING CANAL : ".$canal);
    verbose("TRACKING DDR : ".$ddr);
    verbose("TRACKING TICKET : ".$ticket);
    verbose("TRACKING INDICE : ".$indice);
    verbose("TRACKING NOME EVENTO : ".$nome_evento);
    verbose("TRACKING EXTRA KEY : ".$extras_key);
    verbose("TRACKING EXTRA VALUE : ".$extras_value);
    verbose("------------FIM DO TRACKING ------------");

    require('conexaoMYSQL.php');

    $sql ="INSERT INTO Ura_Eventos set CANAL='$canal',DDR='$ddr',TICKET='$ticket',INDICE='$indice',NOME_EVENTO='$nome_evento',EXTRAS_KEY='$extras_key',EXTRAS_VALUE='$extras_value',DATA_HORA=now()";
    $comAcentos = array('à', 'á', 'â', 'ã', 'ä', 'å', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ù', 'ü', 'ú', 'ÿ', 'À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'O', 'Ù', 'Ü', 'Ú');
    $semAcentos = array('a', 'a', 'a', 'a', 'a', 'a', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'y', 'A', 'A', 'A', 'A', 'A', 'A', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U');
    $sql = str_replace($comAcentos, $semAcentos, $sql);    
   
    mysql_query($sql, $conn);
    
    }

function inserirprotocolobanco($telefone,$protocolo){
    
        require('conexaoMYSQL2.php');
        
        $sql ="INSERT INTO ura_simples_ryane set telefone='$telefone',coletado='$protocolo';";
        mysql_query($sql, $conn);
        
        }
?>
