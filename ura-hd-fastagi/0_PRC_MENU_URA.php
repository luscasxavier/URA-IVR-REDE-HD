<?php 
require_once 'apis_hd.php';
require_once 'FrameWorkUraTelek.php';
require_once '1_PRC_ANTECIPACAO_DE_RECEBIVEIS.php';
require_once '2_PRC_ALTERAR_SENHA_DE_EC.php';
require_once '3_PRC_AUTORIZACAO_BENEFICIO.php';
require_once '4_PRC_AUTORIZACAO_FROTA.php';

$testeativo = 'N';
date_default_timezone_set('America/Sao_Paulo');
global $fastagi;
global $testeativo;

$ddr='';
$ddr=$fastagi->request['agi_extension'];
if($ddr=='32938414') $ddr='40001573';
if($ddr=='32938405') $ddr='08009407676';

$timeStamp=time();
verbose("TIME STAMP : ".$timeStamp);
$canal= 'URA REDE';
//$ticket=$origem.'_'.$timeStamp;
$uniqueid = $fastagi->request['agi_uniqueid'];
$ticket=$uniqueid;
$indice=0;
$horaAtual = date('H:i');

$origem = preg_replace("#[^0-9]#","",$fastagi->request['agi_callerid']);
$uniqueid = $fastagi->request['agi_uniqueid'];
verbose('NÚMERO DO CLIENTE : '.$origem, 5);

global $horaAtual;
global $canal;
global $ddr;
global $ticket;
global $indice;

//01
tracking($canal, $ddr, $ticket, $indice, 'INICIO', 'PERCURSO', 'INICIO');
tracking($canal, $ddr, $ticket, $indice, 'INICIO', 'CONTATO', $origem);

function bdbtbn(){

    if(!tracking_canal_ativo($canal, $ddr, $ticket, $indice)) exit();
    if(date("H") < '12'){
  
      playback("uraHD/1dia");
  
    }elseif(date("H") > '11' && date("H") < '19'){
        playback("uraHD/1tarde");
  
    }elseif(date("H") > '17'){  
        playback("uraHD/1noite");
  
    }else{ hangup();break;}
}

$fastagi=$fastagi;

verbose("<<<<<<<<<<<<<INICIANDO URA HD FASTAGI>>>>>>>>>>>>>>>",5);
tracking_canal_ativo($canal, $ddr, $ticket, $indice);
bdbtbn();
Menu_Principal($uniqueid, $origem);

function Menu_Principal($uniqueid, $origem){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    if(!tracking_canal_ativo($canal, $ddr, $ticket, $indice)) exit();
        
    verbose(">>>>> INICIOU MENU PRINCIPAL",5);

    //03
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'MENU PRINCIPAL', 'PERCURSO', '1 - JA E CREDENCIADO OU 2 - DESEJA SE CREDENCIAR');

    //playback("uraHD/2");
    $menu = "";
    //$menu = coletar_dados_usuario("uraHD/3",1);
    $menu= coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/3",1);
    if($menu == '-1'){hangup();break;}

    //04
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'MENU PRINCIPAL', 'RESPOSTA', 'OPCAO ESCOLHIDA PELO CLIENTE: '.$menu);
  
    switch ($menu) {
        case 1:
            verbose(">>>>> ESCOLHEU OPCAO 1",3); 
            inicializa_ambiente_novo_menu();
            M1_Ja_e_credenciado($uniqueid, $origem);
            break;

        case 2:
            verbose(">>>>> ESCOLHEU OPCAO 2",3); 
            inicializa_ambiente_novo_menu();
            M2_Deseja_se_credenciar($uniqueid, $origem);
            break;

        default:
            if(retentar_dado_invalido("PRINCIPAL","uraHD/5","OPCAO INVALIDA")){
                //05
                $indice++;
                tracking($canal, $ddr, $ticket, $indice, 'MENU PRINCIPAL', 'PERCURSO', 'OPCAO INVALIDA (1 - JA E CREDENCIADO OU 2 - DESEJA SE CREDENCIAR)');
                Menu_Principal($uniqueid, $origem);
            }else{
                //06
                $indice++;
                tracking($canal, $ddr, $ticket, $indice, 'MENU PRINCIPAL', 'PERCURSO', 'TENTATIVAS EXCEDIDAS (1 - JA E CREDENCIADO OU 2 - DESEJA SE CREDENCIAR)');
                //encerra_por_retentativas("PRINCIPAL","uraHD/4","OPCAO INVALIDA");
                encerra_com_tracking($canal, $ddr, $ticket, $indice, "PRINCIPAL","uraHD/4","OPCAO INVALIDA");
            }
        break;
    }

}

function M1_Ja_e_credenciado($uniqueid, $origem){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    if(!tracking_canal_ativo($canal, $ddr, $ticket, $indice)) exit();
    verbose(">>>>> ENTROU NA OPCAO 1 JÁ É CREDENCIADO",3);
    //07
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'MENU PRINCIPAL - JA E CREDENCIADO', 'PERCURSO', 'INFORMAR NUMERO DO CPF OU CNPJ ');

    $cnpjcpf ='';
    //$cnpjcpf = coletar_dados_usuario("uraHD/6",14);
    $cnpjcpf = coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/6",14);
    if($cnpjcpf == '-1'){hangup();break;}

    //08
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'MENU PRINCIPAL - JA E CREDENCIADO', 'CPF/ CNPJ', $cnpjcpf);

    verbose("CNPJ/CPF DIGITADO: ".$cnpjcpf);
    verbose('UNIQUE ID DO CLIENTE : '.$uniqueid);
    verbose('NÚMERO DO CLIENTE : '.$origem);

    if(preg_match("/^(\d{14})$|^(\d{11})$/",$cnpjcpf)){

        if($GLOBALS["testeativo"] == 'Y'){
            verbose("Entrou no teste");

            $cnpjcpfValidado = array(
                "possuiContrato" => "S",
                "contratosBeneficio" => array(
                    "ctr01" => "04306102",
                    "ctr02" => "",
                    "ctr03" => "",
                    "ctr04" => "",
                    "ctr05" => "",
                    "ctr06" => "",
                    "ctr07" => "",
                    "ctr08" => "",
                    "ctr09" => ""
                ),
                "contratosFrota" => array(
                    "ctr01" => "04306102",
                    "ctr02" => "",
                    "ctr03" => "",
                    "ctr04" => "",
                    "ctr05" => "",
                    "ctr06" => "",
                    "ctr07" => "",
                    "ctr08" => "",
                    "ctr09" => ""
                ),
                "opcoesMenu" => array(
                    "antecipacao" => "S",
                    "alteraSenha" => "S",
                    "autorizaBeneficio" => "S",
                    "autorizaFrota" => "S"
                )
            );

            $cnpjcpfValidado = json_encode($cnpjcpfValidado);
            $cnpjcpfValidado = json_decode($cnpjcpfValidado);
        }else{
            verbose("VALIDANDO OS DADOS ATRAVÉS DA API");
            $cnpjcpfValidado = api_urc_valida_cnpj_cpf($uniqueid, $origem, $cnpjcpf);
        }
        //09
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'MENU PRINCIPAL - JA E CREDENCIADO', 'RETORNO', 'NUMERO DIGITADO PELO USUARIO');
        verbose("RETORNO DA VALIDACAO : ".$cnpjcpfValidado->{'possuiContrato'});

        if($cnpjcpfValidado->{'possuiContrato'} =='S'){
            verbose("CNPJ/CPF DIGITADO ENTROU COMO VALIDADO");

            //11
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'MENU PRINCIPAL - JA E CREDENCIADO', 'RETORNO', 'CPF/ CNPJ LOCALIZADO');

            if($GLOBALS["testeativo"] == 'Y'){
                verbose("Entrou no teste");
                $protocoloEASY = '815880';
            }else{
            verbose('CNPJ OU CPF DO CLIENTE : '.$cnpjcpf);
            verbose('NUMERO DE ORIGEM DO CLIENTE : '.$origem);
            //$protocoloEASY=api_ucc_protocolo_easy($processId, $cnpjcpf, $origem);
            }
            /*
            verbose("PROTOCOLO EASY GERADO : ".$protocoloEASY);
            
            //12
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'MENU PRINCIPAL - JA E CREDENCIADO', 'PROTOCOLO', $protocoloEASY);
            
            playback('uraHD/88_1');
            falar_alfa($protocoloEASY);
            playback('uraHD/88_2');
            falar_alfa($protocoloEASY);
            */
            inicializa_ambiente_novo_menu();
            M1_1_Principal($uniqueid, $origem, $cnpjcpf, $cnpjcpfValidado);

        }elseif($cnpjcpf == 'TIMEOUT'){
            if(retentar_dado_invalido("M1_Ja_e_credenciado","uraHD/5","CPF/CNPJ NAO DIGITADO"))M1_Ja_e_credenciado($uniqueid, $origem);
            else{
                //encerra_por_retentativas("M1_Ja_e_credenciado","uraHD/4","MAX TENTATIVA");
                encerra_com_tracking($canal, $ddr, $ticket, $indice, "M1_Ja_e_credenciado","uraHD/4","MAX TENTATIVA");                
            }

        }else{
            //11
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'MENU PRINCIPAL - JA E CREDENCIADO', 'RETORNO', 'CPF/ CNPJ NAO LOCALIZADO');

            if(retentar_dado_invalido("M1_Ja_e_credenciado","uraHD/8","CPF/CNPJ NAO DIGITADO"))M1_Ja_e_credenciado($uniqueid, $origem);
            else{
                //encerra_por_retentativas("M1_Ja_e_credenciado","uraHD/4","MAX TENTATIVA");
                encerra_com_tracking($canal, $ddr, $ticket, $indice, "M1_Ja_e_credenciado","uraHD/4","MAX TENTATIVA");
            }
        }
    }else{
        //09
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'MENU PRINCIPAL - JA E CREDENCIADO', 'RETORNO', 'NUMERO NAO DIGITADO PELO USUARIO');

        if(retentar_dado_invalido("M1_Ja_e_credenciado","uraHD/8","CPF/CNPJ NAO DIGITADO"))M1_Ja_e_credenciado($uniqueid, $origem);
        else{
            //10
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'MENU PRINCIPAL - JA E CREDENCIADO', 'PERCURSO', 'TENTATIVAS EXCEDIDAS CPF/CNPJ NAO DIGITADOS');
            //encerra_por_retentativas("M1_Ja_e_credenciado","uraHD/4","MAX TENTATIVA");
            encerra_com_tracking($canal, $ddr, $ticket, $indice, "M1_Ja_e_credenciado","uraHD/4","MAX TENTATIVA");
        }
    }
}

function M1_1_Principal($uniqueid, $origem, $cnpjcpf, $cnpjcpfValidado){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    if(!tracking_canal_ativo($canal, $ddr, $ticket, $indice)) exit();
    verbose("ENTROU NO MENU M1_1_Principal");

    //13
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'MENU PRINCIPAL - JA E CREDENCIADO', 'PERCURSO', '1 - ANTECIPACAO DE RECEBIVEIS OU 2 - ALTERAR SENHA DE EC OU 3 - AUTORIZACAO BENEFICIO OU 4 - AUTORIZACAO FROTA OU 9 - FALAR COM ATENDENTE');

    $menu ='';
    //$menu=coletar_dados_usuario("uraHD/7",1);
    $menu=coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/7",1);
    if($menu == '-1'){hangup();break;}

    //14
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'MENU PRINCIPAL - JA E CREDENCIADO', 'RESPOSTA', 'OPCAO ESCOLHIDA PELO CLIENTE: '.$menu);
  
    switch ($menu){
        case 1:
            inicializa_ambiente_novo_menu();
            Op01_1_1_antecipacao_de_recebiveis($uniqueid ,$origem ,$cnpjcpf);
        break;

        case 2:
            inicializa_ambiente_novo_menu();
            Op01_1_2_alterar_senha_de_ec($uniqueid ,$origem ,$cnpjcpf, $cnpjcpfValidado);
        break;

        case 3:
            inicializa_ambiente_novo_menu();
            M1_1_3_autorizacao_beneficio($uniqueid ,$origem ,$cnpjcpf, $cnpjcpfValidado);
        break;

        case 4:
            inicializa_ambiente_novo_menu();
            M1_1_4_autorizacao_frota_pt1($uniqueid, $origem, $cnpjcpf, $cnpjcpfValidado);
        break;

        case 5:
            inicializa_ambiente_novo_menu();
            conta_agili($uniqueid, $origem, $cnpjcpf, $cnpjcpfValidado);
        break;

        case 6:
            inicializa_ambiente_novo_menu();
            suporte_tecnico($uniqueid, $origem, $cnpjcpf, $cnpjcpfValidado);
        break;

        case 9:
            if($GLOBALS["testeativo"] == 'Y'){
                verbose("Entrou no teste");
                $validarhorario = true;
            }else{
                $validarhorario = api_horario_atendimento();
            }
            verbose("RETORNO DA API HORARIO DE ATENDIMENTO : ".$validarhorario);
            if($validarhorario){

                $protocoloEASY=api_ucc_protocolo_v2('', $cnpjcpf, '', '', $origem, '', $uniqueid);
                verbose("PROTOCOLO : ".$protocoloEASY);
                //16
                $indice++;
                tracking($canal, $ddr, $ticket, $indice, 'MENU PRINCIPAL - JA E CREDENCIADO', 'PROTOCOLO', $protocoloEASY);

                playback("uraHD/12_1");
                falar_alfa($protocoloEASY);
                playback("uraHD/12_2");
                falar_alfa($protocoloEASY);
                playback("uraHD/12_3");

                if($GLOBALS["testeativo"] == 'Y'){
                    hangup();break;
                }else{
                    //17
                    $indice++;
                    tracking($canal, $ddr, $ticket, $indice, 'MENU ATENDENTE - JA E CREDENCIADO', 'PERCURSO', 'LIGACAO DIRECIONADA PARA A FILA 213');

                    verbose('ENCAMINHANDO PARA O 213');
                    inserirprotocolobanco($origem,$protocoloEASY);
                    //dial_return("gw01-kontac33/999999");   //DISCAGEM DO SERVIDOR 30
                    dial_return("gw02-kontac33/213");   //DISCAGEM DO SERVIDOR 31

                    $indice++;
                    $uniqueId_kontac= get_uniqueId_kontac($origem, '33');
                    tracking($canal, $ddr, $ticket, $indice, 'PÓS ATENDIMENTO HUMANO', 'UNIQUEID KONTAC', $uniqueId_kontac);

                    $indice++;
                    tracking($canal, $ddr, $ticket, $indice, 'PÓS ATENDIMENTO HUMANO', 'PERCURSO', 'CLIENTE AINDA ESTÁ NA LINHA');

                    tracking_canal_ativo($canal, $ddr, $ticket, $indice);

                    inicializa_ambiente_novo_menu();
                    pesquisa_satisfacao($uniqueid, $origem);
                    hangup();
                }
            }else{
                //15
                $indice++;
                tracking($canal, $ddr, $ticket, $indice, 'FALAR COM ATENDENTE - JA E CREDENCIADO', 'PERCURSO', 'CLIENTE LIGOU FORA DO HORARIO DE ATENDIMENTO');

                playback("uraHD/15");
                hangup();
            }
        break;

        default:
            if(retentar_dado_invalido("M1_1_Principal","uraHD/5","Digitou opcao invalida"))M1_1_Principal($uniqueid, $origem, $cnpjcpf, $cnpjcpfValidado);
            else{
                //encerra_por_retentativas("M1_1_Principal","uraHD/4","MAX TENTATIVA");
                encerra_com_tracking($canal, $ddr, $ticket, $indice, "M1_1_Principal","uraHD/4","MAX TENTATIVA");
            }
        break;
    }
}

function conta_agili($uniqueid, $origem, $cnpjcpf, $cnpjcpfValidado){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    if($GLOBALS["testeativo"] == 'Y'){
        verbose("Entrou no teste");
        $validarhorario = true;
    }else{
        $validarhorario = api_horario_atendimento();
    }
    verbose("RETORNO DA API HORARIO DE ATENDIMENTO : ".$validarhorario);
    if($validarhorario){

        $protocoloEASY=api_ucc_protocolo_v2('', $cnpjcpf, '', '', $origem, '', $uniqueid);

        //263
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'MENU PRINCIPAL - JA E CREDENCIADO', 'PROTOCOLO', $protocoloEASY);

        playback("uraHD/12_1");
        falar_alfa($protocoloEASY);
        playback("uraHD/12_2");
        falar_alfa($protocoloEASY);
        playback("uraHD/12_3");

        if($GLOBALS["testeativo"] == 'Y'){
            hangup();break;
        }else{
            //264
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'MENU ATENDENTE - JA E CREDENCIADO', 'PERCURSO', 'LIGACAO DIRECIONADA PARA A FILA 1118');

            verbose('ENCAMINHANDO PARA O 1118');

            inserirprotocolobanco($origem,$protocoloEASY);
            //dial_return("gw01-kontac33/999999");    //DISCAGEM DO SERVIDOR 30
            dial_return("gw02-kontac33/1118");  //DISCAGEM DO SERVIDOR 31

            $indice++;
            $uniqueId_kontac= get_uniqueId_kontac($origem, '33');
            tracking($canal, $ddr, $ticket, $indice, 'PÓS ATENDIMENTO HUMANO', 'UNIQUEID KONTAC', $uniqueId_kontac);

            tracking_canal_ativo($canal, $ddr, $ticket, $indice);

            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'PÓS ATENDIMENTO HUMANO', 'PERCURSO', 'CLIENTE AINDA ESTÁ NA LINHA');

            inicializa_ambiente_novo_menu();
            pesquisa_satisfacao($uniqueid, $origem);
            hangup();
        }
    }else{
        //262
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'FALAR COM ATENDENTE - JA E CREDENCIADO', 'PERCURSO', 'CLIENTE LIGOU FORA DO HORARIO DE ATENDIMENTO');

        playback("uraHD/15");
        hangup();
    }
}

function suporte_tecnico($uniqueid, $origem, $cnpjcpf, $cnpjcpfValidado){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    if($GLOBALS["testeativo"] == 'Y'){
        verbose("Entrou no teste");
        $validarhorario = true;
    }else{
        $validarhorario = api_horario_atendimento();
    }
    verbose("RETORNO DA API HORARIO DE ATENDIMENTO : ".$validarhorario);
    if($validarhorario){
        verbose("VERBOSE TESTE - uniqueid: ".$uniqueid);
        verbose("VERBOSE TESTE - origem: ".$origem);
        verbose("VERBOSE TESTE - cnpjcpf: ".$cnpjcpf);
        verbose("VERBOSE TESTE - cnpjcpfValidado:".$cnpjcpfValidado);
        $protocoloEASY=api_ucc_protocolo_v2('', $cnpjcpf, '', '', $origem, '', $uniqueid);
        
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'MENU PRINCIPAL - JA E CREDENCIADO', 'PROTOCOLO', $protocoloEASY);

        playback("uraHD/12_1");
        falar_alfa($protocoloEASY);
        playback("uraHD/12_2");
        falar_alfa($protocoloEASY);
        playback("uraHD/12_3");

        if($GLOBALS["testeativo"] == 'Y'){
            hangup();break;
        }else{
            //268
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'MENU ATENDENTE - JA E CREDENCIADO', 'PERCURSO', 'LIGACAO DIRECIONADA PARA A FILA 203');

            verbose('ENCAMINHANDO PARA O 203');
            inserirprotocolobanco($origem,$protocoloEASY);
            //dial_return("gw01-kontac33/999999");   //DISCAGEM DO SERVIDOR 30
            dial_return("gw02-kontac33/203");   //DISCAGEM DO SERVIDOR 31
            
            $uniqueId_kontac= get_uniqueId_kontac($origem, '33');
            tracking($canal, $ddr, $ticket, $indice, 'PÓS ATENDIMENTO HUMANO', 'UNIQUEID KONTAC', $uniqueId_kontac);
            
            tracking_canal_ativo($canal, $ddr, $ticket, $indice);

            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'PÓS ATENDIMENTO HUMANO', 'PERCURSO', 'CLIENTE AINDA ESTÁ NA LINHA');

            inicializa_ambiente_novo_menu();
            pesquisa_satisfacao($uniqueid, $origem);
            hangup();
        }
    }else{
        //266
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'FALAR COM ATENDENTE - JA E CREDENCIADO', 'PERCURSO', 'CLIENTE LIGOU FORA DO HORARIO DE ATENDIMENTO');

        playback("uraHD/15");
        hangup();
    }

}

function M2_Deseja_se_credenciar($uniqueid, $origem){    
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    if(!tracking_canal_ativo($canal, $ddr, $ticket, $indice)) exit();
    //19
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'MENU PRINCIPAL - DESEJA SE CREDENCIAR', 'PERCURSO', 'INFORMAR NUMERO DO CPF OU CNPJ');

    $digitado ='';
    //$digitado=coletar_dados_usuario("uraHD/10",14);
    $digitado=coleta_dados2($canal, $ddr, $ticket, $indice,"uraHD/10",14);
    if($digitado == '-1'){hangup();break;}
    
    if(strlen($digitado) == 14){
        $cnpjcpf = $digitado;

        verbose(">>>>> FOI DIGITADO CNPJ : ".$cnpjcpf);
        //20
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'MENU PRINCIPAL - DESEJA SE CREDENCIAR', 'RETORNO', 'CPF/ CNPJ VALIDO');

        inicializa_ambiente_novo_menu();
        M2_Deseja_se_credenciar_cnpj($uniqueid, $origem, $cnpjcpf);

    }elseif(strlen($digitado) == 11){
        $cnpjcpf = $digitado;
    
        verbose(">>>>> FOI DIGITADO CPF : ".$cnpjcpf);

        //20
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'MENU PRINCIPAL - DESEJA SE CREDENCIAR', 'RETORNO', 'CPF/ CNPJ VALIDO');

        //22
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'MENU PRINCIPAL - DESEJA SE CREDENCIAR', 'PERCURSO', 'PERGUNTAR LGPD');

        $digitado='';
        //$digitado = coletar_dados_usuario("uraHD/30",1);
        $digitado = coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/30",1);
        if($digitado == '-1'){hangup();break;}
    
        if($digitado == '1'){
            verbose("CLIENTE ACEITOU A LEI LGPD");
            //23
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'MENU PRINCIPAL - DESEJA SE CREDENCIAR', 'RESPOSTA', 'ACEITOU LGPD');                    
            inicializa_ambiente_novo_menu();
            M2_Deseja_se_credenciar_tel($uniqueid, $origem, $cnpjcpf);

        }elseif($digitado == '2'){
            verbose("CLIENTE RECUSOU A LEI LGPD");
            //23
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'MENU PRINCIPAL - DESEJA SE CREDENCIAR', 'RESPOSTA', 'NAO ACEITOU LGPD');

            verbose("GERANDO PROTOCOLO EASY");
            $processId='WKF_Prospect';
    
            if($GLOBALS["testeativo"] == 'Y'){
                verbose("Entrou no teste");
                $protocoloEASY = '815880';
            }else{
                verbose('PROCESS ID UTILIZADO : '.$processId);
                verbose('CNPJ OU CPF DO CLIENTE : '.$cnpjcpf);
                verbose('NUMERO DE ORIGEM DO CLIENTE : '.$origem);

                $categoria='CREDENCIAMENTO';
                $subCat='NAO ACEITOU A LGPD';
                $protocoloEASY=api_ucc_protocolo_v2($categoria, $cnpjcpf, '', $subCat, $origem, '', $uniqueid);

                //$protocoloEASY=api_ucc_protocolo_easy($processId, $cnpjcpf, $telefone);
            }
    
            verbose("PROTOCOLO EASY GERADO : ".$protocoloEASY);
            //24
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'MENU PRINCIPAL - DESEJA SE CREDENCIAR', 'PROTOCOLO', $protocoloEASY);

            playback('uraHD/90_1');
            falar_alfa($protocoloEASY);
            playback('uraHD/90_2');
            falar_alfa($protocoloEASY);
            playback('uraHD/90_3');
            hangup();break;
        }else{
            if(retentar_dado_invalido("M2_Deseja_se_credenciar","uraHD/5","Digitou cpf ou cnpj errado"))M2_Deseja_se_credenciar($uniqueid, $origem, 2, $cnpjcpf);
            else {
                //encerra_por_retentativas("M2_Deseja_se_credenciar","uraHD/4","MAX TENTATIVA");
                encerra_com_tracking($canal, $ddr, $ticket, $indice, "M2_Deseja_se_credenciar","uraHD/4","MAX TENTATIVA");
            }
        }
    }else{
        if(retentar_dado_invalido("M2_Deseja_se_credenciar","uraHD/5","Digitou cpf ou cnpj errado")){
            //20
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'MENU PRINCIPAL - DESEJA SE CREDENCIAR', 'RETORNO', 'CPF/ CNPJ NAO INVALIDO');
            M2_Deseja_se_credenciar($uniqueid, $origem);
        }else{
            //21
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'MENU PRINCIPAL - DESEJA SE CREDENCIAR', 'PERCURSO', 'TENTATIVAS EXCEDIDAS CPF/CNPJ INVALIDOS');
            //encerra_por_retentativas("M2_Deseja_se_credenciar","uraHD/4","MAX TENTATIVA");
            encerra_com_tracking($canal, $ddr, $ticket, $indice, "M2_Deseja_se_credenciar","uraHD/4","MAX TENTATIVA");
        }
    }
}

function M2_Deseja_se_credenciar_cnpj($uniqueid, $origem, $cnpjcpf){
    global $canal, $ddr, $ticket, $indice, $horaAtual;

    $digitado ='';
    //$digitado = coletar_dados_usuario("uraHD/11",11);
    $digitado = coleta_dados2($canal, $ddr, $ticket, $indice,"uraHD/11",11);
    if($digitado == '-1'){hangup();break;}
    $telefone='';
    $telefone = $digitado;
    verbose("TELEFONE DIGITADO PELO USUÁRIO : ".$telefone);

    //25
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'MENU PRINCIPAL - DESEJA SE CREDENCIAR', 'CONTATO', $telefone);

    if(strlen($telefone)>11 ||strlen($telefone)<10 || $telefone == 'TIMEOUT'){
        if(retentar_dado_invalido("M2_Deseja_se_credenciar_cnpj","uraHD/5","NAO DIGITOU O NUMERO DE CONTATO")){
            //26
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'MENU PRINCIPAL - DESEJA SE CREDENCIAR', 'RETORNO', 'TELEFONE INVALIDO');

            M2_Deseja_se_credenciar_cnpj($uniqueid, $origem, $cnpjcpf);
        }else{
            //27
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'MENU PRINCIPAL - DESEJA SE CREDENCIAR', 'PERCURSO', 'TENTATIVAS EXCEDIDAS (NUMERO DE TELEFONE)');
            //encerra_por_retentativas("M2_Deseja_se_credenciar_cnpj","uraHD/4","MAX TENTATIVA");
            encerra_com_tracking($canal, $ddr, $ticket, $indice, "M2_Deseja_se_credenciar_cnpj","uraHD/4","MAX TENTATIVA");
        }
    }else{
        //26
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'MENU PRINCIPAL - DESEJA SE CREDENCIAR', 'RETORNO', 'TELEFONE VALIDO');

        inicializa_ambiente_novo_menu();
        M2_Deseja_se_credenciar_p2($uniqueid, $origem, $cnpjcpf, $telefone);
    }
}

function M2_Deseja_se_credenciar_tel($uniqueid, $origem, $cnpjcpf){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    
    $digitado='';
    //$digitado = coletar_dados_usuario("uraHD/89",11);
    $digitado = coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/89",11);
    if($digitado == '-1'){hangup();break;}
    $telefone = $digitado;

    //26
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'MENU PRINCIPAL - DESEJA SE CREDENCIAR', 'CONTATO', $telefone);

    if(strlen($telefone) == 11 || strlen($telefone) == 10){
        //26
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'MENU PRINCIPAL - DESEJA SE CREDENCIAR', 'RETORNO', 'TELEFONE VALIDO');
        
        inicializa_ambiente_novo_menu();
        M2_Deseja_se_credenciar_p2($uniqueid, $origem, $cnpjcpf, $telefone);

    }else{
        if(retentar_dado_invalido("M2_Deseja_se_credenciar_tel","uraHD/5","Digitou telefone errado")){
            //26
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'MENU PRINCIPAL - DESEJA SE CREDENCIAR', 'RETORNO', 'TELEFONE INVALIDO');
            M2_Deseja_se_credenciar_tel($uniqueid, $origem, $cnpjcpf);
        }else{
            //27
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'MENU PRINCIPAL - DESEJA SE CREDENCIAR', 'PERCURSO', 'TENTATIVAS EXCEDIDAS (NUMERO DE TELEFONE DIGITADO)');
            //encerra_por_retentativas("M2_Deseja_se_credenciar_tel","uraHD/4","MAX TENTATIVA");
            encerra_com_tracking($canal, $ddr, $ticket, $indice, "M2_Deseja_se_credenciar_tel","uraHD/4","MAX TENTATIVA");
        }
    }
}

function M2_Deseja_se_credenciar_p2($uniqueid, $origem, $cnpjcpf, $telefone){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    if(!tracking_canal_ativo($canal, $ddr, $ticket, $indice)) exit();

    if($GLOBALS["testeativo"] == 'Y'){
        verbose("Entrou no teste");
        $validarhorario = true;
    }else{
        $validarhorario = api_horario_atendimento();
    }
    verbose("RETORNO DA API HORARIO DE ATENDIMENTO : ".$validarhorario);
    if($validarhorario){

        if($GLOBALS["testeativo"] == 'Y'){
            verbose("Entrou no teste");
            $protocoloEASY = '815880';
        }else{
            verbose("GERANDO PROTOCOLO EASY");
            $processId='WKF_Prospect';            
            verbose('PROCESS ID UTILIZADO : '.$processId);
            verbose('CNPJ OU CPF DO CLIENTE : '.$cnpjcpf);
            verbose('NUMERO INFORMADO PELO CLIENTE : '.$telefone);
            //$protocoloEASY=api_ucc_protocolo_easy($processId, $cnpjcpf, $telefone);
    
            $protocoloEASY=api_ucc_protocolo_v2('', $cnpjcpf, '', '', $origem, $telefone, $uniqueid);
        }
        
        verbose("PROTOCOLO EASY GERADO : ".$protocoloEASY);
        //28
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'MENU PRINCIPAL - DESEJA SE CREDENCIAR', 'PROTOCOLO', $protocoloEASY);

        playback("uraHD/12_1");
        falar_alfa($protocoloEASY);
        playback("uraHD/12_2");
        falar_alfa($protocoloEASY);
        playback("uraHD/12_3");

        verbose('ENCAMINHANDO PARA O 204');
        //28.1
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'MENU PRINCIPAL - DESEJA SE CREDENCIAR', 'PERCURSO', 'ENCAMINHAMENTO DIRECIONADA PARA A FILA 204');

        inserirprotocolobanco($origem,$protocolo);
        //dial_return("gw01-kontac33/999999");   //DISCAGEM DO SERVIDOR 30
        dial_return("gw02-kontac33/204"); //DISCAGEM DO SERVIDOR 31

        $indice++;
        $uniqueId_kontac= get_uniqueId_kontac($origem, '33');
        tracking($canal, $ddr, $ticket, $indice, 'PÓS ATENDIMENTO HUMANO', 'UNIQUEID KONTAC', $uniqueId_kontac);

        tracking_canal_ativo($canal, $ddr, $ticket, $indice);

        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'PÓS ATENDIMENTO HUMANO', 'PERCURSO', 'CLIENTE AINDA ESTÁ NA LINHA');
                    
        inicializa_ambiente_novo_menu();
        pesquisa_satisfacao($uniqueid, $origem);
        hangup();
    }else{
        $categoria='CREDENCIAMENTO';
        $subCat='SOLICITAÇÃO DE CREDENCIAMENTO';
        $protocoloEASY=api_ucc_protocolo_v2($categoria, $cnpjcpf, '', $subCat, $origem, $telefone, $uniqueid);

        playback("uraHD/94_1");
        falar_alfa($protocoloEASY);
        //29
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'MENU PRINCIPAL - DESEJA SE CREDENCIAR', 'PERCURSO/ PROTOCOLO', 'CLIENTE LIGOU FORA DO HORARIO DE ATENDIMENTO');

        playback("uraHD/94_2");
        //verbose("PROTOCOLO EASY GERADO : ".$protocoloEASY);
        //falar_alfa($protocoloEASY);//obs marco 9: no audio fala pra anotar o protocolo porem no fluxo bizagi não tem pra falar o protrocolo então n falei e pra falar ou n?
        hangup();break;
    }
    
}

function pesquisa_satisfacao($uniqueid, $origem){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    tracking_canal_ativo($canal, $ddr, $ticket, $indice);
    //09
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'PESQUISA DE SATISFACAO', 'PERCURSO', 'LIGACAO CONTINUADA');

    //10
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'PESQUISA DE SATISFACAO', 'PERCURSO', 'ATENDIMENTO TRATOU A SOLICITACAO?');

    $opcao='';
    //$opcao= coletar_dados_usuario("Fraseologia/PS01",1);
    $opcao= coleta_dados2($canal, $ddr, $ticket, $indice, "Fraseologia/PS01",1);
    if($opcao == '-1'){hangup();break;exit;}
    
    if($opcao=='1' || $opcao=='2'){
        if($opcao=='1')$resp= 'SIM';
        if($opcao=='2')$resp= 'NÃO';

        //11
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'PESQUISA DE SATISFACAO', 'RESPOSTA', $resp);
        tracking_canal_ativo($canal, $ddr, $ticket, $indice);

        inicializa_ambiente_novo_menu();
        pesquisa_satisfacao2($uniqueid, $origem);

    }else{
        playback("FroCli/03");
        if(retentar_dado_invalido("pesquisa_satisfacao","Fraseologia/PS04","OPCAO INVALIDA")){
            //13
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'PESQUISA DE SATISFACAO', 'PERCURSO', 'OPCAO INVALIDA');
            pesquisa_satisfacao($uniqueid, $origem);
        }else{
            //12
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'PESQUISA DE SATISFACAO', 'PERCURSO', 'TENTATIVAS EXCEDIDAS SELECAO OPCAO');
            //encerra_por_retentativas("pesquisa_satisfacao","Fraseologia/PS05","OPCAO INVALIDA");
            encerra_com_tracking($canal, $ddr, $ticket, $indice, "pesquisa_satisfacao","Fraseologia/PS05","OPCAO INVALIDA");
        }         
    }

}

function pesquisa_satisfacao2($uniqueid, $origem){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    tracking_canal_ativo($canal, $ddr, $ticket, $indice);

    //14
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'PESQUISA DE SATISFACAO', 'PERCURSO', 'INFORMAR PERGUNTA(AVALIACAO 1 A 5)');

    $opcao='';
    //$opcao= coletar_dados_usuario("Fraseologia/PS02",1);
    $opcao= coleta_dados2($canal, $ddr, $ticket, $indice, "Fraseologia/PS02",1);
    if($opcao == '-1'){hangup();break;exit;}
    tracking_canal_ativo($canal, $ddr, $ticket, $indice);

    if($opcao>='1' && $opcao<='5'){
        //15
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'PESQUISA DE SATISFACAO', 'RESPOSTA', $opcao);

        playback("Fraseologia/PS03");

        //18
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'PESQUISA DE SATISFACAO', 'PERCURSO', 'FINALIZACAO DA PESQUISA DE SATISFACAO');
        hangup();

    }else{
        verbose("INFORMADO PELO CLIENTE : ".$opcao);
        playback("FroCli/03");
        if(retentar_dado_invalido("pesquisa_satisfacao2","Fraseologia/PS04","OPCAO INVALIDA")){
            //17
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'PESQUISA DE SATISFACAO', 'PERCURSO', 'OPCAO INVALIDA');
            pesquisa_satisfacao2($uniqueid, $origem);
        }else{
            //16
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'PESQUISA DE SATISFACAO', 'PERCURSO', 'TENTATIVAS EXCEDIDAS SELECAO OPCAO');
            //encerra_por_retentativas("pesquisa_satisfacao2","Fraseologia/PS05","OPCAO INVALIDA");
            encerra_com_tracking($canal, $ddr, $ticket, $indice, "pesquisa_satisfacao2","Fraseologia/PS05","OPCAO INVALIDA");
        } 
    }
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

/*REFERENCIAS INTERESSANTES
https://asterisk.ctiapps.pro/Asterisk/FastAGI.html

//INTEGRACAO VALIDA HORARIO
$horarioSevervico = true; //INTEGRAR CHAMADA REST  DA INTEGRACAO, PENDENTE VALECARD

if($horarioSevervico)Menu_Principal$uniqueid, $origem);
else Fora_Horario_Atendimento($origem);


*/
return 0;
hangup();
break;
exit();
?>

