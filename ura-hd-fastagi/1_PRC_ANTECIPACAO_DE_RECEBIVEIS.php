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
//1 - ERROS DE EXECUCAO 
//2 - ALERTAS
//3 - MENSAGENS INFORMATIVAS
//4 - DEBUG LEVE
//5 - DEBUG TOTAL

//INICIO DA URA
verbose(">>>>>>> MENU DE ANTECIPACAO DE RECEBIVEIS",3); 

function Op01_1_1_antecipacao_de_recebiveis($uniqueid, $origem, $cnpjcpf,$etapa = '1'){
    global $canal, $ddr, $ticket, $indice, $horaAtual;

    if($etapa == '1'){
        //30
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'ANTECIPACAO DE RECEBIVEIS', 'PERCURSO', 'INFORMAR SENHA DO ESTABELECIMENTO');

        $senha='';
        //$senha = coletar_dados_usuario("uraHD/86",5);
        $senha = coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/86",5);
        if($senha == '-1'){hangup();break;}

        //31
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'ANTECIPACAO DE RECEBIVEIS', 'SENHA', 'DADOS CONFIDENCIAIS');
    }   

    if(preg_match("/^(\d{5})$/",$senha)){
   
        if($GLOBALS["testeativo"] == 'Y'){
            verbose("Entrou no teste");
            $validar_senha = true;
        }else{
            verbose("UNIQUE ID DO USUARIO : ".$uniqueid);
            verbose("ORIGEM DO USUARIO : ".$origem);
            tracking_canal_ativo($canal, $ddr, $ticket, $indice);
            $validar_senha =api_ucc_valida_senha($uniqueid, $cnpjcpf, $origem, $senha);
        }

        if($validar_senha){
            verbose("SENHA VALIDADA COM SUCESSO");
            if(!tracking_canal_ativo($canal, $ddr, $ticket, $indice)) exit();
            //32
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'ANTECIPACAO DE RECEBIVEIS', 'RETORNO', 'SENHA VALIDA');

            inicializa_ambiente_novo_menu();
            Op01_1_1_antecipacao_de_recebiveis_2($uniqueid, $origem, $cnpjcpf);
            
        }else{
            if(retentar_dado_invalido("Op01_1_1_antecipacao_de_recebiveis_valida_senha","uraHD/5","Senha invalida")){
                //32
                $indice++;
                tracking($canal, $ddr, $ticket, $indice, 'ANTECIPACAO DE RECEBIVEIS', 'RETORNO', 'SENHA INVALIDA');
                Op01_1_1_antecipacao_de_recebiveis($uniqueid, $origem, $cnpjcpf);
            }else{
                //33
                $indice++;
                tracking($canal, $ddr, $ticket, $indice, 'ANTECIPACAO DE RECEBIVEIS', 'PERCURSO', 'TENTATIVAS EXCEDIDAS SENHA INVALIDA');

                //encerra_por_retentativas("Op01_1_1_antecipacao_de_recebiveis_valida_senha","uraHD/26","MAX TENTATIVA");
                encerra_com_tracking($canal, $ddr, $ticket, $indice, "Op01_1_1_antecipacao_de_recebiveis_valida_senha","uraHD/26","MAX TENTATIVA");                
            }
        }
        
    }else{
        if(retentar_dado_invalido("Op01_1_1_antecipacao_de_recebiveis","uraHD/5","Senha nao digita ou digitado errado")){
            //32
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'ANTECIPACAO DE RECEBIVEIS', 'RETORNO', 'SENHA INVALIDA');

            Op01_1_1_antecipacao_de_recebiveis($uniqueid, $origem, $cnpjcpf);
        }
        else{
            //33
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'ANTECIPACAO DE RECEBIVEIS', 'PERCURSO', 'TENTATIVAS EXCEDIDAS SENHA INVALIDA');

            //encerra_por_retentativas("Op01_1_1_antecipacao_de_recebiveis","uraHD/26","MAX TENTATIVA");
            encerra_com_tracking($canal, $ddr, $ticket, $indice, "Op01_1_1_antecipacao_de_recebiveis","uraHD/26","MAX TENTATIVA");
        }
    }
}

function Op01_1_1_antecipacao_de_recebiveis_2($uniqueid, $origem, $cnpjcpf){
    global $canal, $ddr, $ticket, $indice, $horaAtual;

    if($GLOBALS["testeativo"] == 'Y'){
        verbose("Entrou no teste");
        $confirm_simula = array(
            "codEstab" => "12345678",
            "taxaDia" => "4.75",
            "valorBruto" => "2350.58", 
            "valorLiquido" => "2000.00",
            "dataDiaAntecipacao" => "30/12/2021", 
            "seqAntecipacaoSimulado" => "440"
        );
        $confirm_simula = json_encode($confirm_simula);
        $confirm_simula = json_decode($confirm_simula);
    }else{
        tracking_canal_ativo($canal, $ddr, $ticket, $indice);
        $confirm_simula=api_urc_simula_ant_receb($cnpjcpf, $uniqueid, $origem);
    }
    if($confirm_simula->{'valorBruto'}!=''){
        if(!tracking_canal_ativo($canal, $ddr, $ticket, $indice)){
            hangup();
            exit();        
        }
        verbose("VALOR BRUTO DIFERENTE DE VAZIO : ".$confirm_simula->{'valorBruto'});
        playback("uraHD/13_1");
        verbose("FALANDO CODIGO DO ESTABELECIMENTO : ".$confirm_simula->{'codEstab'});
        falar_alfa($confirm_simula->{'codEstab'});

        //34
        //$indice++;
        tracking($canal, $ddr, $ticket, $indice, 'ANTECIPACAO DE RECEBIVEIS', 'RETORNO', 'COD ESTABELECIMENTO: '.$confirm_simula->{'codEstab'});

        playback("uraHD/13_2");
        verbose("FALANDO VALOR BRUTO : ".$confirm_simula->{'valorBruto'});
        falar_valor($confirm_simula->{'valorBruto'});

        //34
        //$indice++;
        tracking($canal, $ddr, $ticket, $indice, 'ANTECIPACAO DE RECEBIVEIS', 'RETORNO', 'VALOR BRUTO: '.$confirm_simula->{'valorBruto'});

        playback("uraHD/13_3");
        verbose("FALANDO VALOR LIQUIDO : ".$confirm_simula->{'valorLiquido'});
        falar_valor($confirm_simula->{'valorLiquido'});

        //34
        //$indice++;
        tracking($canal, $ddr, $ticket, $indice, 'ANTECIPACAO DE RECEBIVEIS', 'RETORNO', 'VALOR LIQUIDO: '.$confirm_simula->{'valorLiquido'});
    }else{
        verbose("VALOR BRUTO VAZIO");
        playback('uraHD/semSaldo');
        playback('uraHD/finalizacao');
        hangup();break;
    }

    //35
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'ANTECIPACAO DE RECEBIVEIS', 'PERCURSO', '1 - CONFIRMAR SOLICITACAO OU 2 - CANCELAR SOLICITACAO');

    $digitado ='';
    //$digitado = coletar_dados_usuario("uraHD/13_4",1);
    $digitado = coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/13_4",1);
    if($digitado == '-1'){hangup();break;}

    //36
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'ANTECIPACAO DE RECEBIVEIS', 'RESPOSTA', 'OPCAO ESCOLHIDA PELO CLIENTE: '.$digitado);

    $data = date('d/m/Y');
    if($digitado=='1'){
        if(!tracking_canal_ativo($canal, $ddr, $ticket, $indice)) exit();

        verbose("CLIENTE CONFIRMOU A ANTECIPACAO");

        $categoria='ANTECIPACAO';
        $subCat='ANTECIPACAO DE RECEBIVEIS';
        $protocoloEASY=api_ucc_protocolo_v2($categoria, $cnpjcpf, '', $subCat, $origem, '', $uniqueid);

        if($GLOBALS["testeativo"] == 'Y'){
            verbose("Entrou no teste");
            $confirm_ant = true;
        }else{
            verbose("UNIQUEID DO CLIENTE : ".$uniqueid);
            verbose("NUMERO DO CLIENTE : ".$origem);
            verbose("CONFIRMACAO DO CLIENTE : ".$confirm_simula->{'seqAntecipacaoSimulado'});
            if(!tracking_canal_ativo($canal, $ddr, $ticket, $indice)) exit();
            $confirm_ant=api_urc_confirma_ant_receb($cnpjcpf, $uniqueid, $origem, $confirm_simula->{'seqAntecipacaoSimulado'});
        }
        //37
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'ANTECIPACAO DE RECEBIVEIS', 'PERCURSO', 'VALIDAR AGENDA DE ANTECIPACAO');

        if($confirm_ant){
            
            if($data == $confirm_simula->{'dataDiaAntecipação'}){
                //38
                $indice++;
                tracking($canal, $ddr, $ticket, $indice, 'ANTECIPACAO DE RECEBIVEIS', 'RETORNO', 'VALOR ANTECIPADO HOJE');

                playback("uraHD/16_1");
                falar_valor($confirm_simula->{'valorBruto'});
                playback("uraHD/16_2");
                falar_valor($confirm_simula->{'valorLiquido'});
                playback("uraHD/16_3");
                falar_alfa($protocoloEASY);
                playback("uraHD/16_4");
                falar_alfa($protocoloEASY);
                playback("uraHD/16_5");
                hangup();break;
            } else{
                //38
                $indice++;
                tracking($canal, $ddr, $ticket, $indice, 'ANTECIPACAO DE RECEBIVEIS', 'RETORNO', 'VALOR ANTECIPADO PROXIMO DIA UTIL');

                playback("uraHD/17_1");
                falar_valor($confirm_simula->{'valorBruto'});
                playback("uraHD/17_2");
                falar_valor($confirm_simula->{'valorLiquido'});
                playback("uraHD/17_3");
                falar_alfa($protocoloEASY);
                playback("uraHD/17_4");
                falar_alfa($protocoloEASY);
                playback("uraHD/17_5");
                hangup();break;
            }
        }else {
            //38
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'ANTECIPACAO DE RECEBIVEIS', 'RETORNO', 'ANTECIPACAO NAO VALIDADA PELA API');

            playback("uraHD/26");
            hangup();break;
        }

    }elseif($digitado == '2'){
        verbose("CLIENTE CANCELOU A ANTECIPACAO");
        inicializa_ambiente_novo_menu();
        Op01_1_1_antecipacao_de_recebiveis_cancelar($uniqueid, $origem, $cnpjcpf);
    }else{
        if(retentar_dado_invalido("Op01_1_1_antecipacao_de_recebiveis_opcao_antecipacao","uraHD/5","opcao invalida para antecipacao")) Op01_1_1_antecipacao_de_recebiveis_2($uniqueid, $origem, $cnpjcpf,$protocoloEASY);
        else{
            //42
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'ANTECIPACAO DE RECEBIVEIS', 'PERCURSO', '1 - CONFIRMAR SOLICITACAO OU 2 - CANCELAR SOLICITACAO');

            //encerra_por_retentativas("Op01_1_1_antecipacao_de_recebiveis_opcao_antecipacao","uraHD/26","MAX TENTATIVA");
            encerra_com_tracking($canal, $ddr, $ticket, $indice, "Op01_1_1_antecipacao_de_recebiveis_opcao_antecipacao","uraHD/26","MAX TENTATIVA");
        }
    }
}

function Op01_1_1_antecipacao_de_recebiveis_cancelar($uniqueid, $origem, $cnpjcpf){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    //39
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'ANTECIPACAO DE RECEBIVEIS', 'PERCURSO', '0 - VOLTAR AO MENU PRINCIPAL OU DESLIGAR TELEFONE');

    $digitado='';
    //$digitado = coletar_dados_usuario("uraHD/18",1);
    $digitado = coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/18",1);
    if($digitado == '-1'){hangup();break;}

    //40
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'ANTECIPACAO DE RECEBIVEIS', 'RESPOSTA', 'OPCAO ESCOLHIDA PELO CLIENTE: '.$digitado);

    if($digitado == '0'){
        verbose("Mandando chamada de volta pro menu_principal");
        inicializa_ambiente_novo_menu();
        Menu_Principal($uniqueid, $origem);
        hangup();break;
    
    }else{
        if(retentar_dado_invalido("Op01_1_1_antecipacao_de_recebiveis_cancelar","uraHD/5","Cliente nao escolheu se vai voltar pro menu")){
            Op01_1_1_antecipacao_de_recebiveis_cancelar($uniqueid, $origem, $cnpjcpf);
        }
        else{
            //41
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'ANTECIPACAO DE RECEBIVEIS', 'PERCURSO', 'TENTATIVAS EXCEDIDAS (0 - VOLTAR AO MENU PRINCIPAL OU DESLIGAR TELEFONE)');

            //encerra_por_retentativas("Op01_1_1_antecipacao_de_recebiveis_cancelar","uraHD/26","MAX TENTATIVA");
            encerra_com_tracking($canal, $ddr, $ticket, $indice, "Op01_1_1_antecipacao_de_recebiveis_cancelar","uraHD/26","MAX TENTATIVA");
        }
    }
}
return 0;
hangup();
break;
exit();
?>
