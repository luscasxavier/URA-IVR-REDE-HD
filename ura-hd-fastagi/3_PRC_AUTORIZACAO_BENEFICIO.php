<?php
require_once 'FrameWorkUraTelek.php';
require_once 'apis_hd.php';
date_default_timezone_set('America/Sao_Paulo');

//INICIALIZACAO DE AMBIENTE
$copiar_audios_para_teste=true; //usar essa opcao somente para testar sem os audio pois ira copiar o audioteste.wav para os audios faltantes. 
$nomeURA = "URA_HD"; //nome da ura para aparece nos logs e mensagens
$extensao_audio=".wav"; //extensao do formato padrao de audios
$audio_lib="/var/lib/asterisk/sounds/"; //pasta padrao de audios
$audio_ura="uraHD/"; //pasta dos audios especificos da ura
$max_timeout=6000; //tempo maximo de timeout ao esperar entrada do usuario em milisegundos
$max_tentativas=3; //numero maxixo de retentavivas de input do usuario
$tentativas=0;//tentativas relaizadas
$debug_level=5; //nivel de log desejado  
$testeativo = 'N'; //ignora todas apis
//1 - ERROS DE EXECUÇÃO 
//2 - ALERTAS
//3 - MENSAGENS INFORMATIVAS
//4 - DEBUG LEVE
//5 - DEBUG TOTAL 

//INICIO DA URA
verbose(">>>>>>> MENU DE BENEFICIO",3); 
function M1_1_3_autorizacao_beneficio($uniqueid ,$origem ,$cnpjcpf, $cnpjcpfValidado){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    tracking_canal_ativo($canal, $ddr, $ticket, $indice);

    inicializa_ambiente_novo_menu();
    verbose("MANDANDO PARA O MENU AUTORIZACAO DE BENEFICIO PT 2");
    M1_1_3_autorizacao_beneficio_pt2($uniqueid, $origem, $cnpjcpf, $cnpjcpfValidado);
}

function M1_1_3_autorizacao_beneficio_pt2($uniqueid, $origem, $cnpjcpf, $cnpjcpfValidado){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    tracking_canal_ativo($canal, $ddr, $ticket, $indice);
    
    $conta=1;
    $contrato2[0]='';
    $cnpjcpfValidado2=get_object_vars($cnpjcpfValidado);
    foreach ($cnpjcpfValidado2['contratosBeneficio'][0] as $ctr0[$conta] => $value){
        if($value !=''){

            $audioDinamico='';
            $ContratoAudio.= "uraHD/57_".$conta."&uraHD/contrato&";
            $audioDinamico= retornar_alfa($value);
            $ContratoAudio= $ContratoAudio.$audioDinamico;

            $qntdade= $conta;
            $contrato2[$qntdade] =$value;
    
            $conta++;
        }
    }

    //66
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO', 'PERCURSO', 'SELECIONAR CONTRATO');

    $opcao='';
    $opcao =background($ContratoAudio,1);
    if($opcao == '-1'){hangup();break;exit;}

    //67
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO', 'RESPOSTA', 'OPCAO ESCOLHIDA PELO CLIENTE: '.$opcao);

    if($opcao > $qntdade){
        if(retentar_dado_invalido("AUTORIZACAO BENEFICIO CNPJ","uraHD/5","OPCAO INVALIDA"))M1_1_3_autorizacao_beneficio_pt2($uniqueid, $origem, $cnpjcpf, $cnpjcpfValidado);
        else{

            //encerra_por_retentativas("AUTORIZACAO BENEFICIO CNPJ","uraHD/26","OPCAO INVALIDA");
            encerra_com_tracking($canal, $ddr, $ticket, $indice, "AUTORIZACAO BENEFICIO CNPJ","uraHD/26","OPCAO INVALIDA");
        }
    }elseif($opcao =='TIMEOUT'){
        if(retentar_dado_invalido("AUTORIZACAO BENEFICIO CNPJ","uraHD/5","CLIENTE NAO DIGITOU OPCAO"))M1_1_3_autorizacao_beneficio_pt2($uniqueid, $origem, $cnpjcpf, $cnpjcpfValidado);
        else{

            //encerra_por_retentativas("AUTORIZACAO BENEFICIO CNPJ","uraHD/26","CLIENTE NAO DIGITOU OPCAO");
            encerra_com_tracking($canal, $ddr, $ticket, $indice, "AUTORIZACAO BENEFICIO CNPJ","uraHD/26","CLIENTE NAO DIGITOU OPCAO");
        }
    }elseif($opcao ==0){
        if(retentar_dado_invalido("AUTORIZACAO BENEFICIO CNPJ","uraHD/5","OPCAO INVALIDA"))M1_1_3_autorizacao_beneficio_pt2($uniqueid, $origem, $cnpjcpf, $cnpjcpfValidado);
        else{

            //encerra_por_retentativas("AUTORIZACAO BENEFICIO CNPJ","uraHD/26","OPCAO INVALIDA");
            encerra_com_tracking($canal, $ddr, $ticket, $indice, "AUTORIZACAO BENEFICIO CNPJ","uraHD/26","OPCAO INVALIDA");
        }
    }else{

        $contrato=$contrato2[$opcao];
        inicializa_ambiente_novo_menu();
        validar_senha_estabelecimento($uniqueid ,$origem ,$cnpjcpf ,$contrato);
    }
}

function validar_senha_estabelecimento($uniqueid ,$origem ,$cnpjcpf ,$contrato){ 
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    tracking_canal_ativo($canal, $ddr, $ticket, $indice);

    //73
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO', 'PERCURSO', 'INFORMAR SENHA EC');
    
    $senha_estabelecimento = '';
    //$senha_estabelecimento = coletar_dados_usuario("uraHD/86",5);
    $senha_estabelecimento = coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/86",5);
    if($senha_estabelecimento == '-1'){hangup();break;exit;}

    //74
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO', 'SENHA', 'INFORMACOES CONFIDENCIAIS');

    if (preg_match("/^(\d{5})$/",$senha_estabelecimento)){

        if($GLOBALS["testeativo"] == 'Y'){
            verbose("Entrou no teste");
            $validasenha = true;
        }else{
            $validasenha = api_ucc_valida_senha($uniqueid,$cnpjcpf, $origem, $senha_estabelecimento); 
        }

        if($validasenha){
            //74
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO', 'RETORNO', 'SENHA VALIDA');

            verbose("submenu de autorizacao de beneficio entrando");
            inicializa_ambiente_novo_menu();
            submenu_autorizacao_beneficio($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento);

        }else{
            //74
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO', 'RETORNO', 'SENHA INVALIDA');
            if(retentar_dado_invalido("validar_senha_estabelecimento","uraHD/22","SENHA ERRADA"))validar_senha_estabelecimento($uniqueid ,$origem ,$cnpjcpf ,$contrato);
            else{
                //76
                $indice++;
                tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO', 'PERCURSO', 'TENTATIVAS EXCEDIDAS SENHA INVALIDA');

                //encerra_por_retentativas("validar_senha_estabelecimento","uraHD/26","SENHA ERRADA");
                encerra_com_tracking($canal, $ddr, $ticket, $indice, "validar_senha_estabelecimento","uraHD/26","SENHA ERRADA");
            }
        }
    }else{
        if(retentar_dado_invalido("validar_senha_estabelecimento","uraHD/5","TIMEOUT"))validar_senha_estabelecimento($uniqueid ,$origem ,$cnpjcpf ,$contrato);
        else{
            //76
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO', 'PERCURSO', 'TENTATIVAS EXCEDIDAS SENHA INVALIDA');

            //encerra_por_retentativas("validar_senha_estabelecimento","uraHD/26","TIMEOUT");
            encerra_com_tracking($canal, $ddr, $ticket, $indice, "validar_senha_estabelecimento","uraHD/26","TIMEOUT");
        }
    }
}

function  submenu_autorizacao_beneficio($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    tracking_canal_ativo($canal, $ddr, $ticket, $indice);

    //77
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO', 'PERCURSO', '1 - VENDA A VISTA OU 2 - PARCELADA OU 3 - ESTORNO');

    $opcao = '';
    //$opcao = coletar_dados_usuario("uraHD/31",1);
    $opcao = coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/31",1);
    if($opcao == '-1'){hangup();break;exit;}

    //78
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO', 'RESPOSTA', 'OPCAO ESCOLHIDA PELO CLIENTE: '.$opcao);

    switch ($opcao) {
        case 1:
            inicializa_ambiente_novo_menu();
            verbose('SELECINOU A VISTA');
            $retorno='avista';
            autorizacao_beneficio($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$retorno);
            break;
        case 2:
            inicializa_ambiente_novo_menu();
            verbose('SELECINOU PARCELADO');
            $retorno='parcelado';
            autorizacao_beneficio($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$retorno);
            break;
        case 3:
            inicializa_ambiente_novo_menu();
            estorno_autorizacao_beneficio($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento);
            break;
        
        default:
            if(retentar_dado_invalido("OPCOES","uraHD/5","OPCAO INVALIDA"))submenu_autorizacao_beneficio($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento);
            else{
                //79
                $indice++;
                tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO', 'PERCURSO', 'TENTATIVAS EXCEDIDAS SENHA INVALIDA');

                //encerra_por_retentativas("OPCOES","uraHD/26","OPCAO INVALIDA");
                encerra_com_tracking($canal, $ddr, $ticket, $indice, "OPCOES","uraHD/26","OPCAO INVALIDA");
            } 
        break;
    }
}

function autorizacao_beneficio($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$retorno){ 
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    tracking_canal_ativo($canal, $ddr, $ticket, $indice);

    if($retorno == 'avista')$tipotransacao = 'A';
    if($retorno == 'parcelado')$tipotransacao = 'P';

    //103
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - PARCELADA/A VISTA', 'PERCURSO', 'INFORMAR NUMERO DO CARTAO');

    $numero_cartao = '';
    //$numero_cartao = coletar_dados_usuario("uraHD/51",17);
    $numero_cartao = coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/51",17);
    if($numero_cartao == '-1'){hangup();break;exit;}

    //104
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - PARCELADA/A VISTA', 'NUM CARTAO', $numero_cartao);

    if(preg_match("/^(\d{17})$/",$numero_cartao)){
        
        if($GLOBALS["testeativo"] == 'Y'){
            verbose("Entrou no teste");
            $validacartao = true;
        }else{
            $validacartao = api_tbc_valida_cartao($uniqueid, $origem, $numero_cartao);
        }
        verbose("aaaaaaaaaaaaaaaaaaaa ".$retorno);
        if($validacartao){
            //105
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - PARCELADA/A VISTA', 'RETORNO', 'CARTAO VALIDO');
            
            switch ($retorno) {
                case 'avista':
                    inicializa_ambiente_novo_menu();                    
                    segue_vendasavista($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$numero_cartao,$tipotransacao);
                    break;
                case 'parcelado':
                    inicializa_ambiente_novo_menu();
                    segue_parcelado($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$numero_cartao,$tipotransacao);
                    break;
                default:
                    verbose('n deiva entrar aqui n em');
                    hangup();break;exit;
                    break;
            }
        }else{
            //105
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - PARCELADA', 'RETORNO', 'CARTAO INVALIDO');

            if(retentar_dado_invalido("autorizacao_beneficio","uraHD/32","numero do cartao invalido"))autorizacao_beneficio($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$retorno);
            else{
                //106
                $indice++;
                tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - PARCELADA', 'PERCURSO', 'TENTATIVAS EXCEDIDAS CARTAO INVALIDO');

                //encerra_por_retentativas("autorizacao_beneficio","uraHD/26","numero do cartao invalido");
                encerra_com_tracking($canal, $ddr, $ticket, $indice, "autorizacao_beneficio","uraHD/26","numero do cartao invalido");
            }
        }
    }else{
        //105
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - PARCELADA', 'RETORNO', 'CARTAO INVALIDO');

        if(retentar_dado_invalido("autorizacao_beneficio","uraHD/5","numero do cartao digitado errado ou n digitado"))autorizacao_beneficio($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$retorno);
        // obs marco 40: achei muito estrado o audio de nao consegui indentificao opcao nesse ponto aqui ja q na verdade é o numero do cartao.
        else{
            //106
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - PARCELADA', 'PERCURSO', 'TENTATIVAS EXCEDIDAS CARTAO INVALIDO');

            //encerra_por_retentativas("autorizacao_beneficio","uraHD/26","numero do cartao digitado errado ou n digitado");
            encerra_com_tracking($canal, $ddr, $ticket, $indice, "autorizacao_beneficio","uraHD/26","numero do cartao digitado errado ou n digitado");
        }
    }
}

function segue_vendasavista($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$numero_cartao,$tipotransacao){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    tracking_canal_ativo($canal, $ddr, $ticket, $indice);

    //107
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - A VISTA', 'PERCURSO', 'INFORMAR O VALOR TOTAL DA VENDA');

    $valor_total_venda = '';
    //$valor_total_venda = coletar_dados_usuario("uraHD/33",6);
    $valor_total_venda = coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/33",6);
    if($valor_total_venda == '-1'){hangup();break;exit;}

    //108
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - A VISTA', 'VALOR TOTAL VENDA', $valor_total_venda);

    if(strlen($valor_total_venda) > 2 && $valor_total_venda > 100){
        //109
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - A VISTA', 'VALOR TOTAL VENDA', $valor_total_venda);

        $valor_total_venda_formatado = substr($valor_total_venda,0,(strlen($valor_total_venda) - 2)).'.'.substr($valor_total_venda,-2);
        
        playback("uraHD/35_1");
        falar_alfa($contrato);
        playback("uraHD/35_2");
        falar_alfa($numero_cartao);
        playback("uraHD/35_3");
        falar_valor($valor_total_venda_formatado);        

        $opcao = '';
        //$opcao = coletar_dados_usuario("uraHD/35_6",1);
        $opcao = coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/35_6",1);
        if($opcao == '-1'){hangup();break;exit;}

        if($opcao == 5){

            verbose("voltando ao submenu de autorizacao beneficio");
            inicializa_ambiente_novo_menu();
            submenu_autorizacao_beneficio($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento);

        }elseif($opcao == 'TIMEOUT'){

            verbose("segue para autorizacao");
            inicializa_ambiente_novo_menu();
            segue_autoriza_transacao($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$numero_cartao,$valor_total_venda,'N','1',$tipotransacao);
            
        }else{
            if(retentar_dado_invalido("segue_vendasavista","uraHD/5",""))segue_vendasavista($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$numero_cartao,$tipotransacao);
            else encerra_por_retentativas("segue_vendasavista","uraHD/26","menor que 1 real");
        }
    }else{
        //109
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - PARCELADA(A VISTA)', 'RETORNO', 'VALOR TOTAL MENOR QUE 1 REAL');

        if(retentar_dado_invalido("segue_vendasavista","uraHD/34","menor que 1 real"))segue_vendasavista($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$numero_cartao,$tipotransacao);
        else{
            //110
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - PARCELADA(A VISTA)', 'PERCURSO', 'TENTATIVAS EXCEDIDAS (INFORMAR O VALOR TOTAL DA VENDA)');

            //encerra_por_retentativas("segue_vendasavista","uraHD/26","menor que 1 real");
            encerra_com_tracking($canal, $ddr, $ticket, $indice, "segue_vendasavista","uraHD/26","menor que 1 real");
        }
    }
}

function segue_parcelado($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$numero_cartao,$tipotransacao,$etapa = '1',$valor_total_venda ='1'){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    tracking_canal_ativo($canal, $ddr, $ticket, $indice);
    
    if($etapa = 1){
        //107
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - PARCELADA', 'PERCURSO', 'INFORMAR O VALOR TOTAL DA VENDA');

        $valor_total_venda = '';
        //$valor_total_venda = coletar_dados_usuario("uraHD/33",7);
        $valor_total_venda = coletar_dados_usuario("uraHD/33",7);
        if($valor_total_venda == '-1'){hangup();break;exit;}

        //108
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - PARCELADA', 'VALOR TOTAL VENDA', $valor_total_venda);
    }

    if(strlen($valor_total_venda) > 2 && $valor_total_venda > 100){
        //109
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - PARCELADA', 'RETORNO', 'VALOR TOTAL MAIOR QUE 1 REAL');

        $valor_total_venda_formatado = substr($valor_total_venda,0,(strlen($valor_total_venda) - 2)).'.'.substr($valor_total_venda,-2);

        //111
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - PARCELADA', 'PERCURSO', '2 - VENDA COM ENTRADA OU 4 - VENDA SEM ENTRADA');

        $opcao = '';
        //$opcao = coletar_dados_usuario("uraHD/41",1);
        $opcao = coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/41",1);        
        if($opcao == '-1'){hangup();break;exit;}

        //112
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - PARCELADA', 'RESPOSTA', 'OPCAO ESCOLHIDA PELO CLIENTE: '.$opcao);
        
        switch ($opcao) {
            case '2':
                $vendacomentrada = 'S';
                inicializa_ambiente_novo_menu();
                segue_parcelado_2($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$numero_cartao,$tipotransacao,$vendacomentrada,$valor_total_venda);
                break;
            
            case '4':
                $vendacomentrada = 'N';
                inicializa_ambiente_novo_menu();
                segue_parcelado_2($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$numero_cartao,$tipotransacao,$vendacomentrada,$valor_total_venda);
                break;

            default:
                if(retentar_dado_invalido("segue_parcelado","uraHD/5","OPCAO NAO SELECIONADA")){segue_parcelado($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$numero_cartao,$tipotransacao,2,$valor_total_venda);
                }else encerra_por_retentativas("segue_parcelado","uraHD/4","OPCAO NAO SELECIONADA");
                break;
        }

    }else{
        //109
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - PARCELADA', 'RETORNO', 'VALOR TOTAL MENOR QUE 1 REAL');

        if(retentar_dado_invalido("segue_parcelado","uraHD/34","menor que 1 real"))segue_parcelado($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$numero_cartao,$tipotransacao);
        else{
            //110
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - PARCELADA', 'PERCURSO', 'TENTATIVAS EXCEDIDAS (INFORMAR O VALOR TOTAL DA VENDA)');

            //encerra_por_retentativas("segue_parcelado","uraHD/26","menor que 1 real");
            encerra_com_tracking($canal, $ddr, $ticket, $indice, "segue_parcelado","uraHD/26","menor que 1 real");
        }
    }
}

function segue_parcelado_2($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$numero_cartao,$tipotransacao,$vendacomentrada,$valor_total_venda){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    tracking_canal_ativo($canal, $ddr, $ticket, $indice);

    //113
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - PARCELADA', 'PERCURSO', 'INFORMAR QUANTIDADE DE PARCELAS');

    $parcelas = '';
    //$parcelas = coletar_dados_usuario("uraHD/42",2);
    $parcelas = coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/42",2);
    if($parcelas == '-1'){hangup();break;exit;}

    //113
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - PARCELADA', 'NUM PARCELAS', $parcelas);

    if($parcelas == 'TIMEOUT'){
        //113.1
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - PARCELADA', 'PERCURSO', 'PARCELAS NAO INFORMADAS');

        if(retentar_dado_invalido("segue_parcelado_2","uraHD/5","NAO DIGITOU QUANTIDADE DE PARCELAS"))segue_parcelado_2($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$numero_cartao,$tipotransacao,$vendacomentrada,$valor_total_venda);
        else{
            //113.2
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - PARCELADA', 'PERCURSO', 'TENTATIVAS EXCEDIDAS (INFORMAR QUANTIDADE DE PARCELAS)');

            //encerra_por_retentativas("segue_parcelado_2","uraHD/4","NAO DIGITOU QUANTIDADE DE PARCELAS");
            encerra_com_tracking($canal, $ddr, $ticket, $indice, "segue_parcelado_2","uraHD/4","NAO DIGITOU QUANTIDADE DE PARCELAS");
        }
       
    }else{
        inicializa_ambiente_novo_menu();
        segue_parcelado_3($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$numero_cartao,$tipotransacao,$vendacomentrada,$valor_total_venda,$parcelas);
    }    
}

function segue_parcelado_3($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$numero_cartao,$tipotransacao,$vendacomentrada,$valor_total_venda,$parcelas){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    tracking_canal_ativo($canal, $ddr, $ticket, $indice);
    
    $valor_total_venda_formatado = substr($valor_total_venda,0,(strlen($valor_total_venda) - 2)).'.'.substr($valor_total_venda,-2);

    playback("uraHD/35_1");
    falar_alfa($contrato);
    playback("uraHD/35_2");
    falar_alfa($numero_cartao);
    playback("uraHD/35_3");
    falar_valor($valor_total_venda_formatado);

    //115
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - PARCELADA', 'PERCURSO', '5 - CORRIGIR DADOS OU AGUARDAR CONFIRMACAO');

    $opcao = '';
    //$opcao = coletar_dados_usuario("uraHD/35_6",1);
    $opcao = coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/35_6",1);
    if($opcao == '-1'){hangup();break;exit;}

    //116
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - PARCELADA', 'RESPOSTA', 'OPCAO ESCOLHIDA PELO CLIENTE: '.$opcao);

    switch ($opcao) {
        case '5':
            inicializa_ambiente_novo_menu();
            segue_parcelado($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$numero_cartao,$tipotransacao);
        break;

        case 'TIMEOUT':
            inicializa_ambiente_novo_menu();
            segue_autoriza_transacao($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$numero_cartao,$valor_total_venda,$vendacomentrada,$parcelas,$tipotransacao);
        break;

        default:
            if(retentar_dado_invalido("segue_parcelado_3","uraHD/5","NAO DIGITOU OPCAO VALIDA"))segue_parcelado_3($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$numero_cartao,$tipotransacao,$vendacomentrada,$valor_total_venda,$parcelas);
            else{
                //encerra_por_retentativas("segue_parcelado_3","uraHD/26","NAO DIGITOU OPCAO VALIDA");
                encerra_com_tracking($canal, $ddr, $ticket, $indice, "segue_parcelado_3","uraHD/26","NAO DIGITOU OPCAO VALIDA");
            }
        break;
    }
}

function segue_autoriza_transacao($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$numero_cartao,$valor_total_venda,$vendacomentrada,$parcelas,$tipotransacao){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    tracking_canal_ativo($canal, $ddr, $ticket, $indice);

    //117
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - PARCELADA/A VISTA', 'PERCURSO', 'INFORMAR SENHA ATUAL');

    $senha_atual = '';
    //$senha_atual = coletar_dados_usuario("uraHD/20",5);
    $senha_atual = coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/20",5);
    if($senha_atual == '-1'){hangup();break;exit;}

    //118
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - PARCELADA/A VISTA', 'SENHA', 'DADOS CONFIDENCIAIS');

    if(preg_match("/^(\d{5})$/",$senha_atual)){

        if($GLOBALS["testeativo"] == 'Y'){
            verbose("Entrou no teste");
            $validasenha = true;
        }else{
            $validasenha = api_ucc_valida_senha($uniqueid,$cnpjcpf, $origem, $senha_atual);
        }

        if($validasenha){
            //119
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - PARCELADA/A VISTA', 'RETORNO', 'SENHA VALIDA');

            $valor_total_venda_formatado = substr($valor_total_venda,0,(strlen($valor_total_venda) - 2)).'.'.substr($valor_total_venda,-2);

            if($GLOBALS["testeativo"] == 'Y'){
                verbose("Entrou no teste");
                $autorizatransacao = array(

                    "Autorizada" => "S",
                    "CodigoRetornoAutorizacao" => "Aprovada",
                    "CodigoAutorizacao" => "12345",
                    "CodigoPlano" => "B",
                    "Valor" => "222.12"
                );
                $autorizatransacao = json_encode($autorizatransacao);
                $autorizatransacao = json_decode($autorizatransacao);

            }else{               
                verbose("INFORMACOES API AUTORIZACAO uniqueid: ".$uniqueid);
                verbose("INFORMACOES API AUTORIZACAO origem: ".$origem);
                verbose("INFORMACOES API AUTORIZACAO numero_cartao: ".$numero_cartao);
                verbose("INFORMACOES API AUTORIZACAO contrato: ".$contrato);
                verbose("INFORMACOES API AUTORIZACAO valor_total_venda_formatado: ".$valor_total_venda_formatado);
                verbose("INFORMACOES API AUTORIZACAO parcelas: ".$parcelas);
                verbose("INFORMACOES API AUTORIZACAO tipotransacao: ".$tipotransacao);
                verbose("INFORMACOES API AUTORIZACAO vendacomentrada: ".$vendacomentrada);
                $autorizatransacao = api_tbc_autoriza_transacao($uniqueid, $origem, $numero_cartao, $senha_atual, $contrato, $valor_total_venda_formatado, $parcelas, $tipotransacao, $vendacomentrada);
                verbose("INFORMACOES API AUTORIZACAO autorizatransacao: ".$autorizatransacao);                 
            }

            if($autorizatransacao->{"autorizada"} =='S'){

                //tracking 92

                $categoria='AUTORIZACAO DE VENDA - BENEFICIO';
                $subCat='VENDA A VISTA/PARCELADA';
                $protocoloEASY=api_ucc_protocolo_v2($categoria, $cnpjcpf, '', $subCat, $origem, '', $uniqueid);

                $indice++;
                tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - PARCELADA/A VISTA', 'PROTOCOLO', $protocoloEASY);

                //121
                $indice++;
                tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - PARCELADA/A VISTA', 'RETORNO', 'AUTORIZACAO APROVADA');

                verbose("segue venda a vista 3");
                inicializa_ambiente_novo_menu();
                segue_finalizacao($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$numero_cartao,$valor_total_venda,$vendacomentrada,$parcelas,$tipotransacao,$valor_total_venda_formatado,$autorizatransacao, $protocoloEASY);
            }else{
                //121
                $indice++;
                tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - PARCELADA/A VISTA', 'RETORNO', 'AUTORIZACAO REPROVADA');
                
                //123
                $indice++;
                tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - PARCELADA/A VISTA', 'RETORNO', $autorizatransacao);

                playback("uraHD/39");
                retorna_audio($autorizatransacao);
                //playback("uraHD/26");
                encerra_com_tracking($canal, $ddr, $ticket, $indice, "segue_autoriza_transacao","uraHD/26","API NAO APROVOU");
                hangup();break;exit;
            }            
        }else{
            //119
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - PARCELADA/A VISTA', 'RETORNO', 'SENHA INVALIDA');

            if(retentar_dado_invalido("segue_autoriza_transacao","uraHD/22","SENHA ERRADA"))segue_autoriza_transacao($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$numero_cartao,$valor_total_venda,$vendacomentrada,$parcelas,$tipotransacao);
            else{
                //120
                $indice++;
                tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - PARCELADA/A VISTA', 'PERCURSO', 'TENTATIVAS EXCEDIDAS SENHA INVALIDA');

                //encerra_por_retentativas("segue_autoriza_transacao","uraHD/4","SENHA ERRADA");
                encerra_com_tracking($canal, $ddr, $ticket, $indice, "segue_autoriza_transacao","uraHD/4","SENHA ERRADA");
            }
        } 
    } else {
        //119
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - PARCELADA/A VISTA', 'RETORNO', 'SENHA INVALIDA');

        if(retentar_dado_invalido("segue_autoriza_transacao","uraHD/5","nao digitou senha"))segue_autoriza_transacao($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$numero_cartao,$valor_total_venda,$vendacomentrada,$parcelas,$tipotransacao);
        else{
            //120
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - PARCELADA/A VISTA', 'PERCURSO', 'TENTATIVAS EXCEDIDAS SENHA INVALIDA');

            //encerra_por_retentativas("segue_autoriza_transacao","uraHD/4","nao digitou senha");
            encerra_com_tracking($canal, $ddr, $ticket, $indice, "segue_autoriza_transacao","uraHD/4","nao digitou senha");
        }
    }
}

function segue_finalizacao($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$numero_cartao,$valor_total_venda,$vendacomentrada,$parcelas,$tipotransacao,$valor_total_venda_formatado,$autorizatransacao, $protocoloEASY,$etapa='1'){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    tracking_canal_ativo($canal, $ddr, $ticket, $indice);
    verbose("PROTOCOLO AQUI : ".$protocoloEASY);
    playback("FroCli/18");
    falar_alfa($protocoloEASY);

    if($etapa == 1){
        
        playback("uraHD/36_1");
        falar_alfa($autorizatransacao->{"codigoPlano"});
        
        //122
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - PARCELADA', 'RETORNO', 'PLANO: '.$autorizatransacao->{"codigoPlano"});

        playback("uraHD/36_2");
        falar_valor($valor_total_venda_formatado);

        //122.1
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - PARCELADA', 'RETORNO', 'VALOR: '.$valor_total_venda_formatado);

        playback("uraHD/36_3");
        falar_alfa($autorizatransacao->{"codigoAutorizacao"});

        //122.2
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - PARCELADA', 'RETORNO', 'NUM AUTORIZACAO: '.$autorizatransacao->{"codigoAutorizacao"});

        playback("uraHD/repetindo");

        playback("uraHD/36_1");
        falar_alfa($autorizatransacao->{"codigoPlano"});
        playback("uraHD/36_2");
        falar_valor($valor_total_venda_formatado);
        playback("uraHD/36_3");
        falar_alfa($autorizatransacao->{"codigoAutorizacao"});
    }

    //125
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - PARCELADA', 'PERCURSO', '1 - REPETIR AUTORIZACAO OU 2 NAO REPETIR AUTORIZACAO');

    $opcao = '';
    //$opcao = coletar_dados_usuario("uraHD/37",1);
    $opcao = coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/37",1);
    if($opcao == '-1'){hangup();break;exit;}

    //tracking 92

    $categoria='AUTORIZACAO DE VENDA - BENEFICIO';
    $subCat='VENDA A VISTA';
    $protocoloEASY=api_ucc_protocolo_v2($categoria, $cnpjcpf, '', $subCat, $origem, '', $uniqueid);

    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - PARCELADA', 'PROTOCOLO', $protocoloEASY);

    //126
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - PARCELADA', 'RESPOSTA', 'OPCAO ESCOLHIDA PELO CLIENTE: '.$opcao);

    switch ($opcao) {
        case '1':
            playback("FroCli/18");
            falar_alfa($protocoloEASY);
            
            playback("uraHD/36_1");
            falar_alfa($autorizatransacao->{"codigoPlano"});
            playback("uraHD/36_2");
            falar_valor($valor_total_venda_formatado);
            playback("uraHD/36_3");
            falar_alfa($autorizatransacao->{"codigoAutorizacao"});
    
            playback("uraHD/repetindo");
    
            playback("uraHD/36_1");
            falar_alfa($autorizatransacao->{"codigoPlano"});
            playback("uraHD/36_2");
            falar_valor($valor_total_venda_formatado);
            playback("uraHD/36_3");
            falar_alfa($autorizatransacao->{"codigoAutorizacao"});

            segue_finalizacao_2($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$numero_cartao,$valor_total_venda,$vendacomentrada,$parcelas,$tipotransacao,$valor_total_venda_formatado,$autorizatransacao);
        break;
        
        case '2':
            inicializa_ambiente_novo_menu();
            segue_finalizacao_2($uniqueid ,$origem ,$cnpjcpf, $contrato,$senha_estabelecimento,$numero_cartao,$valor_total_venda,$vendacomentrada,$parcelas,$tipotransacao,$valor_total_venda_formatado,$autorizatransacao);
        break;

        default:
            if(retentar_dado_invalido("OPCOES","uraHD/5","OPCAO INVALIDA"))segue_finalizacao($uniqueid ,$origem ,$cnpjcpf, $contrato,$senha_estabelecimento,$numero_cartao,$valor_total_venda,$vendacomentrada,$parcelas,$tipotransacao,$valor_total_venda_formatado,$autorizatransacao,2);
            else{

                //encerra_por_retentativas("OPCOES","uraHD/26","OPCAO INVALIDA");
                encerra_com_tracking($canal, $ddr, $ticket, $indice, "OPCOES","uraHD/26","OPCAO INVALIDA");
            }
        break;
    }
}

function segue_finalizacao_2($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$numero_cartao,$valor_total_venda,$vendacomentrada,$parcelas,$tipotransacao,$valor_total_venda_formatado,$autorizatransacao){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    tracking_canal_ativo($canal, $ddr, $ticket, $indice);

    //127
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - PARCELADA', 'PERCURSO', '9 - NOVA TRANSACAO OU DESLIGUE O TELEFONE');

    $opcao = '';
    //$opcao = coletar_dados_usuario("uraHD/40",1);
    $opcao = coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/40",1);
    if($opcao == '-1'){hangup();break;exit;}

    //128
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - PARCELADA', 'RESPOSTA', 'OPCAO ESCOLHIDA PELO CLIENTE: '.$opcao);

    switch ($opcao){
        case 9:
            inicializa_ambiente_novo_menu();
            if($tipotransacao == 'A'){
                segue_vendasavista($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$numero_cartao,$tipotransacao);
            }elseif($tipotransacao == 'P'){
                segue_parcelado($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$numero_cartao,$tipotransacao);
            }else{
                verbose('ERRO AO INDENTIFICAR ONDE DEVE VOLTAR A URA');
                hangup();break;exit;
            }
        break;
            
        case "TIMEOUT":
            if(retentar_dado_invalido("OPCOES","uraHD/5","TIMEOUT"))segue_finalizacao_2($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$numero_cartao,$valor_total_venda,$vendacomentrada,$parcelas,$tipotransacao,$valor_total_venda_formatado,$autorizatransacao);
            else{
                //129
                $indice++;
                tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - PARCELADA', 'PERCURSO', 'TENTATIVAS EXCEDIDAS (9 - NOVA TRANSACAO OU DESLIGUE O TELEFONE)');

                //encerra_por_retentativas("OPCOES","uraHD/26","TIMEOUT");
                encerra_com_tracking($canal, $ddr, $ticket, $indice, "OPCOES","uraHD/26","TIMEOUT");
            }
        break;
            
        default:
            if(retentar_dado_invalido("OPCOES","uraHD/5","OPCAO INVALIDA"))segue_finalizacao_2($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$numero_cartao,$valor_total_venda,$vendacomentrada,$parcelas,$tipotransacao,$valor_total_venda_formatado,$autorizatransacao);
            else{
                //129
                $indice++;
                tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - PARCELADA', 'PERCURSO', 'TENTATIVAS EXCEDIDAS (9 - NOVA TRANSACAO OU DESLIGUE O TELEFONE)');

                //encerra_por_retentativas("OPCOES","uraHD/26","OPCAO INVALIDA");
                encerra_com_tracking($canal, $ddr, $ticket, $indice, "OPCOES","uraHD/26","OPCAO INVALIDA");
            }
        break;
    }
}

function estorno_autorizacao_beneficio($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    tracking_canal_ativo($canal, $ddr, $ticket, $indice);

    //130
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - ESTORNO', 'PERCURSO', '1 - ESTORNO VENDA A VISTA OU 2 - ESTORNO VENDA PARCELADA');

    $opcao = '';
    //$opcao = coletar_dados_usuario("uraHD/43",1);
    $opcao = coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/43",1);
    if($opcao == '-1'){hangup();break;exit;}

    //131
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - ESTORNO', 'RESPOSTA', 'OPCAO ESCOLHIDA PELO CLIENTE: '.$opcao);

    switch ($opcao) {
        case '1':
            $tipotransacao='A';
            inicializa_ambiente_novo_menu();
            estorno_autorizacao_beneficio_2($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$tipotransacao);
        break;

        case '2':
            $tipotransacao='P';
            inicializa_ambiente_novo_menu();
            estorno_autorizacao_beneficio_2($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$tipotransacao);
        break;
        
        default:
            if(retentar_dado_invalido("estorno_autorizacao_beneficio","uraHD/5","TIMEOUT"))estorno_autorizacao_beneficio($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento);
            else{
                //132
                $indice++;
                tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - ESTORNO', 'PERCURSO', 'TENTATIVAS EXCEDIDAS (1 - ESTORNO VENDA A VISTA OU 2 - ESTORNO VENDA PARCELADA)');

                //encerra_por_retentativas("estorno_autorizacao_beneficio","uraHD/26","TIMEOUT");
                encerra_com_tracking($canal, $ddr, $ticket, $indice, "estorno_autorizacao_beneficio","uraHD/26","TIMEOUT");
            }
        break;
    }
}

function estorno_autorizacao_beneficio_2($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$tipotransacao,$etapa = '1',$numero_cartao = '1'){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    tracking_canal_ativo($canal, $ddr, $ticket, $indice);

    if($etapa == 1){
        //133
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - ESTORNO', 'PERCURSO', 'INFORMAR NUMERO DO CARTAO');

        $validacartao = '';
        $numero_cartao = '';
        //$numero_cartao = coletar_dados_usuario("uraHD/51",17);
        $numero_cartao = coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/51",17);
        if($numero_cartao == '-1'){hangup();break;exit;}

        //134
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - ESTORNO', 'NUM CARTAO', $numero_cartao);
    }   

    if(preg_match("/^(\d{17})$/",$numero_cartao)){

        if($GLOBALS["testeativo"] == 'Y'){
            verbose("Entrou no teste");
            $validacartao = true;
        }else{
            $validacartao = api_tbc_valida_cartao($uniqueid, $origem, $numero_cartao);
        }

        if($validacartao){
            //135
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - ESTORNO', 'RETORNO', 'CARTAO VALIDO');

            //137
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - ESTORNO', 'PERCURSO', 'INFORMAR O VALOR TOTAL DO DEBITO');

            $valor_total_venda = '';
            //$valor_total_venda = coletar_dados_usuario("uraHD/44",7);
            $valor_total_venda = coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/44",7);
            if($valor_total_venda == '-1'){hangup();break;exit;}

            //138
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - ESTORNO', 'VALOR TOTAL DEBITO', $valor_total_venda);

            if(strlen($valor_total_venda) > 2 && $valor_total_venda > 100){

                inicializa_ambiente_novo_menu();
                estorno_autorizacao_beneficio_3($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$tipotransacao,$valor_total_venda,$numero_cartao);

            }else{
                if(retentar_dado_invalido("estorno_autorizacao_beneficio","uraHD/5","TIMEOUT"))estorno_autorizacao_beneficio_2($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$tipotransacao,2,$numero_cartao);
                else{
                    //136
                    $indice++;
                    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - ESTORNO', 'PERCURSO', 'TENTATIVAS EXCEDIDAS CARTAO INVALIDO');

                    //encerra_por_retentativas("estorno_autorizacao_beneficio","uraHD/26","TIMEOUT");
                    encerra_com_tracking($canal, $ddr, $ticket, $indice, "estorno_autorizacao_beneficio","uraHD/26","TIMEOUT");
                }
            }
        }else{
            //135
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - ESTORNO', 'RETORNO', 'CARTAO INVALIDO');

            if(retentar_dado_invalido("estorno_autorizacao_beneficio","uraHD/33","TIMEOUT"))estorno_autorizacao_beneficio_2($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$tipotransacao);
            else{
                //136
                $indice++;
                tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - ESTORNO', 'PERCURSO', 'TENTATIVAS EXCEDIDAS CARTAO INVALIDO');

                //encerra_por_retentativas("estorno_autorizacao_beneficio","uraHD/26","TIMEOUT");
                encerra_com_tracking($canal, $ddr, $ticket, $indice, "estorno_autorizacao_beneficio","uraHD/26","TIMEOUT");
            }
        }
    }else{
        //135
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - ESTORNO', 'RETORNO', 'CARTAO INVALIDO');

        if(retentar_dado_invalido("estorno_autorizacao_beneficio","uraHD/5","TIMEOUT"))estorno_autorizacao_beneficio_2($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$tipotransacao);
        else{
            //136
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - ESTORNO', 'PERCURSO', 'TENTATIVAS EXCEDIDAS CARTAO INVALIDO');

            //encerra_por_retentativas("estorno_autorizacao_beneficio","uraHD/26","TIMEOUT");
            encerra_com_tracking($canal, $ddr, $ticket, $indice, "estorno_autorizacao_beneficio","uraHD/26","TIMEOUT");
        }
    }
}

function estorno_autorizacao_beneficio_3($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$tipotransacao,$valor_total_venda,$numero_cartao){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    tracking_canal_ativo($canal, $ddr, $ticket, $indice);

    //139
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - ESTORNO', 'PERCURSO', 'INFORMAR O NUMERO DA AUTORIZACAO A SER ESTORNADA');
   
    $cod_autoriza = '';
    //$cod_autoriza = coletar_dados_usuario("uraHD/45",5);
    $cod_autoriza = coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/45",5);
    if($cod_autoriza == '-1'){hangup();break;exit;}

    //140
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - ESTORNO', 'NUM AUTORIZACAO', $cod_autoriza);

    if(preg_match("/^(\d{5})$/",$cod_autoriza)){

        $valor_total_venda_formatado = substr($valor_total_venda,0,(strlen($valor_total_venda) - 2)).'.'.substr($valor_total_venda,-2);

        if($GLOBALS["testeativo"] == 'Y'){
            verbose("Entrou no teste");
            $validaautorizacao = array(
                "transacaoLocalizada" => "S",
                "nsu"=> "412854577",
                "codigoEC"=> "10140006",
                "codigoAutorizacao"=> "60502"
            );
            $validaautorizacao = json_encode($validaautorizacao);
            $validaautorizacao = json_decode($validaautorizacao);
        }else{
            $validaautorizacao =  api_tbc_bsc_transacao($uniqueid, $origem, $numero_cartao, $cod_autoriza, $valor_total_venda_formatado, $contrato);
        }

        if($validaautorizacao->{'transacaoLocalizada'}=='S'){
            //141
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - ESTORNO', 'RETORNO', 'AUTORIZACAO VALIDA');

            inicializa_ambiente_novo_menu();
            estorno_autorizacao_beneficio_4($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$tipotransacao,$valor_total_venda,$numero_cartao,$validaautorizacao,$cod_autoriza,$valor_total_venda_formatado);

        }else{
            //141
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - ESTORNO', 'RETORNO', 'AUTORIZACAO INVALIDA');

            if(retentar_dado_invalido("estorno_autorizacao_beneficio_3","uraHD/46","DIGITOU NUMERO DA AUTORIZACAO ERRADO"))estorno_autorizacao_beneficio_3($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$tipotransacao,$valor_total_venda,$numero_cartao);
            else{
                //142
                $indice++;
                tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - ESTORNO', 'PERCURSO', 'TENTATIVAS EXCEDIDAS AUTORIZACAO INVALIDA');

                //encerra_por_retentativas("estorno_autorizacao_beneficio_3","uraHD/26","DIGITOU NUMERO DA AUTORIZACAO ERRADO");
                encerra_com_tracking($canal, $ddr, $ticket, $indice, "estorno_autorizacao_beneficio_3","uraHD/26","DIGITOU NUMERO DA AUTORIZACAO ERRADO");
            }
        }

    }else{
        //141
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - ESTORNO', 'RETORNO', 'AUTORIZACAO INVALIDA');

        if(retentar_dado_invalido("estorno_autorizacao_beneficio_3","uraHD/5","DIGITOU NUMERO DA AUTORIZACAO ERRADO"))estorno_autorizacao_beneficio_3($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$tipotransacao,$valor_total_venda,$numero_cartao);
        else{
            //142
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - ESTORNO', 'PERCURSO', 'TENTATIVAS EXCEDIDAS AUTORIZACAO INVALIDA');

            //encerra_por_retentativas("estorno_autorizacao_beneficio_3","uraHD/26","DIGITOU NUMERO DA AUTORIZACAO ERRADO");
            encerra_com_tracking($canal, $ddr, $ticket, $indice, "estorno_autorizacao_beneficio_3","uraHD/26","DIGITOU NUMERO DA AUTORIZACAO ERRADO");
        }
    }
}

function estorno_autorizacao_beneficio_4($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$tipotransacao,$valor_total_venda,$numero_cartao,$validaautorizacao,$cod_autoriza,$valor_total_venda_formatado){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    tracking_canal_ativo($canal, $ddr, $ticket, $indice);

    //143
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - ESTORNO', 'PERCURSO', 'INFORMAR A SENHA DO ESTABELECIMENTO');

    //143.1

    $categoria='AUTORIZACAO DE VENDA - BENEFICIO';
    $subCat='ESTORNO';
    $protocoloEASY=api_ucc_protocolo_v2($categoria, $cnpjcpf, '', $subCat, $origem, '', $uniqueid);

    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - ESTORNO', 'PROTOCOLO', $protocoloEASY);

    $senha_atual = '';
    //$senha_atual = coletar_dados_usuario("uraHD/20",5);
    $senha_atual = coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/20",5);
    if($senha_atual == '-1'){hangup();break;exit;}

    //143
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - ESTORNO', 'SENHA EC', 'INFORMACOES CONFIDENCIAIS');

    if(preg_match("/^(\d{5})$/",$senha_atual)){

        if($GLOBALS["testeativo"] == 'Y'){
            verbose("Entrou no teste");
            $validasenha = true;
        }else{
            verbose("VALIDA SENHA uniqueid: ".$uniqueid);
            verbose("VALIDA SENHA origem: ".$origem);
            $validasenha =  api_ucc_valida_senha($uniqueid, $cnpjcpf, $origem, $senha_atual);
        }

        if($validasenha){
            //145
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - ESTORNO', 'RETORNO', 'SENHA VALIDA');

            playback("uraHD/48_1");
            falar_alfa($validaautorizacao->{'codigoEC'});
            playback("uraHD/48_2");
            falar_alfa($numero_cartao);
            playback("uraHD/48_3");
            falar_alfa($cod_autoriza);
            playback("uraHD/48_4");
            falar_alfa($tipotransacao);
            playback("uraHD/48_5");
            falar_valor($valor_total_venda_formatado);

            //147
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - ESTORNO', 'PERCURSO', '5 - CORRIGIR DADOS OU AGUARDAR CONFIRMACAO');

            $opcao = '';
            //$opcao = coletar_dados_usuario("uraHD/48_8",1);
            $opcao = coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/48_8",1);
            if($opcao == '-1'){hangup();break;exit;}

            //148
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - ESTORNO', 'RESPOSTA', 'OPCAO ESCOLHIDA PELO CLIENTE: '.$opcao);

            switch ($opcao) {
                case '5':
                    inicializa_ambiente_novo_menu();
                    estorno_autorizacao_beneficio_2($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$tipotransacao);
                    break;

                case 'TIMEOUT':

                    playback("uraHD/49_1");
                    falar_alfa($validaautorizacao->{'nsu'});
                    playback("uraHD/49_2");
                    falar_valor($valor_total_venda_formatado);
                    
                    //149
                    $indice++;
                    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - ESTORNO', 'PERCURSO', 'VALOR ESTORNADO AO CLIENTE: '.$valor_total_venda_formatado);

                    playback("uraHD/49_3");

                    playback("uraHD/49_4");
                    falar_alfa($validaautorizacao->{'nsu'});
                    playback("uraHD/49_5");
                    falar_valor($valor_total_venda_formatado);

                    $nsu = $validaautorizacao->{'nsu'};

                    if($GLOBALS["testeativo"] == 'Y'){
                        verbose("Entrou no teste");
                        $estorno = true;
                    }else{
                        verbose("INDO PARA API DE ESTORNO NSU: ".$nsu);
                        $estorno =  api_tbc_realiza_estorno($uniqueid, $origem, $nsu);
                        verbose("RETORNO: ".$estorno->{'descricaoRetorno'});
                    }

                    if($estorno->{'descricaoRetorno'} == 'Estorno efetuado com sucesso!'){

                        playback("FroCli/18");
                        falar_alfa($protocoloEASY);
                        
                        inicializa_ambiente_novo_menu();
                        estorno_autorizacao_beneficio_5($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$tipotransacao);

                    }else{
                        //146.1
                        $indice++;
                        tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - ESTORNO', 'PERCURSO', 'EXTORNO NAO AUTORIZADO PELA API');

                        encerra_com_tracking($canal, $ddr, $ticket, $indice, "estorno_autorizacao_beneficio_4","uraHD/26","EXTORNO NAO VALIDADO PELA API");

                        //playback("uraHD/26");
                        hangup();break;exit; 
                    }
                    break;
                default:
                    verbose("OPCAO INVALIDA");
                    if(retentar_dado_invalido("estorno_autorizacao_beneficio_4","uraHD/5","DIGITOU OPCAO INVALIDA"))estorno_autorizacao_beneficio_4($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$tipotransacao,$valor_total_venda,$numero_cartao,$validaautorizacao,$cod_autoriza);
                    else{
                        //146
                        $indice++;
                        tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - ESTORNO', 'PERCURSO', 'TENTATIVAS EXCEDIDAS OPCAO INVALIDA');

                        //encerra_por_retentativas("estorno_autorizacao_beneficio_4","uraHD/26","DIGITOU SENHA ERRADA OU INVALIDA");
                        encerra_com_tracking($canal, $ddr, $ticket, $indice, "estorno_autorizacao_beneficio_4","uraHD/26","DIGITOU OPCAO INVALIDA");
                    }
                break;
            }

        }else{
            //145
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - ESTORNO', 'RETORNO', 'SENHA INVALIDA');

            if(retentar_dado_invalido("estorno_autorizacao_beneficio_4","uraHD/22","DIGITOU SENHA ERRADA OU INVALIDA"))estorno_autorizacao_beneficio_4($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$tipotransacao,$valor_total_venda,$numero_cartao,$validaautorizacao,$cod_autoriza);
            else{
                //146
                $indice++;
                tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - ESTORNO', 'PERCURSO', 'TENTATIVAS EXCEDIDAS SENHA INVALIDA');

                //encerra_por_retentativas("estorno_autorizacao_beneficio_4","uraHD/26","DIGITOU SENHA ERRADA OU INVALIDA");
                encerra_com_tracking($canal, $ddr, $ticket, $indice, "estorno_autorizacao_beneficio_4","uraHD/26","DIGITOU SENHA ERRADA OU INVALIDA");
            }
        }

    }else{
        //145
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - ESTORNO', 'RETORNO', 'SENHA INVALIDA');

        if(retentar_dado_invalido("estorno_autorizacao_beneficio_4","uraHD/5","DIGITOU SENHA ERRADA OU INVALIDA"))estorno_autorizacao_beneficio_4($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$tipotransacao,$valor_total_venda,$numero_cartao,$validaautorizacao,$cod_autoriza);
        else{
            //146
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - ESTORNO', 'PERCURSO', 'TENTATIVAS EXCEDIDAS SENHA INVALIDA');

            //encerra_por_retentativas("estorno_autorizacao_beneficio_4","uraHD/26","DIGITOU SENHA ERRADA OU INVALIDA");
            encerra_com_tracking($canal, $ddr, $ticket, $indice, "estorno_autorizacao_beneficio_4","uraHD/26","DIGITOU SENHA ERRADA OU INVALIDA");
        }
    }
}

function estorno_autorizacao_beneficio_5($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$tipotransacao){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    tracking_canal_ativo($canal, $ddr, $ticket, $indice);
    
    //150
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - ESTORNO', 'PERCURSO', '9 - NOVA TRANSACAO OU DESLIGUE O TELEFONE');

    $opcao = '';
    //$opcao = coletar_dados_usuario("uraHD/50",1);
    $opcao = coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/50",1);
    if($opcao == '-1'){hangup();break;exit;}

    //151
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - ESTORNO', 'PERCURSO', 'OPCAO ESCOLHIDA PELO CLIENTE: '.$opcao);

    switch ($opcao) {
        case '9':
            inicializa_ambiente_novo_menu();
            estorno_autorizacao_beneficio_2($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$tipotransacao);
        break;
        
        default:
            if(retentar_dado_invalido("estorno_autorizacao_beneficio_4","uraHD/5","DIGITOU SENHA ERRADA OU INVALIDA"))estorno_autorizacao_beneficio_5($uniqueid ,$origem ,$cnpjcpf ,$contrato,$senha_estabelecimento,$tipotransacao);
            else{
                //152
                $indice++;
                tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO BENEFICIO - ESTORNO', 'PERCURSO', 'TENTATIVAS EXCEDIDAS (9 - NOVA TRANSACAO OU DESLIGUE O TELEFONE)');

                //encerra_por_retentativas("estorno_autorizacao_beneficio_4","uraHD/26","DIGITOU SENHA ERRADA OU INVALIDA");
                encerra_com_tracking($canal, $ddr, $ticket, $indice, "estorno_autorizacao_beneficio_4","uraHD/26","DIGITOU SENHA ERRADA OU INVALIDA");
            }
        break;
    }
}
return 0;
hangup();
break;
exit();
?>