<?php
require_once 'FrameWorkUraTelek.php';
require_once 'apis_hd.php';
require_once '0_PRC_MENU_URA.php';
$testeativo = 'N';
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
//1 - ERROS DE EXECUÇÃO 
//2 - ALERTAS
//3 - MENSAGENS INFORMATIVAS
//4 - DEBUG LEVE
//5 - DEBUG TOTAL

//INICIO DA URA
verbose(">>>>>>> MENU DE AUTORIZACAO FROTA",3); 

function M1_1_4_autorizacao_frota_pt1($uniqueid, $origem, $cnpjcpf, $cnpjcpfValidado){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    tracking_canal_ativo($canal, $ddr, $ticket, $indice);

    playback("uraHD/52");

    inicializa_ambiente_novo_menu();
    verbose("MANDANDO PARA O MENU AUTORIZACAO DE FROTA PT 2");
    M1_1_4_autorizacao_frota_pt2($uniqueid, $origem, $cnpjcpf, $cnpjcpfValidado);    
}

function M1_1_4_autorizacao_frota_pt2($uniqueid, $origem, $cnpjcpf, $cnpjcpfValidado){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    tracking_canal_ativo($canal, $ddr, $ticket, $indice);

    $conta=1;
    $contrato2[0]='';
    $cnpjcpfValidado2=get_object_vars($cnpjcpfValidado);
    foreach ($cnpjcpfValidado2['contratosFrota'][0] as $ctr0[$conta] => $value){
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

    //161
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'PERCURSO', 'SELECIONAR CONTRATO');
    
    $opcao='';
    $opcao =background($ContratoAudio,1);
    if($opcao == '-1'){hangup();break;exit;}

    //161
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'REPOSTA', 'OPCAO ESCOLHIDA PELO CLIENTE: '.$opcao);

    if($opcao > $qntdade){
        if(retentar_dado_invalido("AUTORIZACAO FROTA CNPJ","uraHD/5","OPCAO INVALIDA"))M1_1_4_autorizacao_frota_pt2($uniqueid, $origem, $cnpjcpf, $cnpjcpfValidado);
        else{
            //encerra_por_retentativas("AUTORIZACAO FROTA CNPJ","uraHD/26","OPCAO INVALIDA");
            encerra_com_tracking($canal, $ddr, $ticket, $indice, "AUTORIZACAO FROTA CNPJ","uraHD/26","OPCAO INVALIDA");
        }
    }elseif($opcao =='TIMEOUT'){
        if(retentar_dado_invalido("AUTORIZACAO FROTA CNPJ","uraHD/5","CLIENTE NAO DIGITOU OPCAO"))M1_1_4_autorizacao_frota_pt2($uniqueid, $origem, $cnpjcpf, $cnpjcpfValidado);
        else{
            //encerra_por_retentativas("AUTORIZACAO FROTA CNPJ","uraHD/26","CLIENTE NAO DIGITOU OPCAO");
            encerra_com_tracking($canal, $ddr, $ticket, $indice, "AUTORIZACAO FROTA CNPJ","uraHD/26","CLIENTE NAO DIGITOU OPCAO");
        }
    }elseif($opcao ==0){
        if(retentar_dado_invalido("AUTORIZACAO FROTA CNPJ","uraHD/5","OPCAO INVALIDA"))M1_1_4_autorizacao_frota_pt2($uniqueid, $origem, $cnpjcpf, $cnpjcpfValidado);
        else{
            //encerra_por_retentativas("AUTORIZACAO FROTA CNPJ","uraHD/26","OPCAO INVALIDA");
            encerra_com_tracking($canal, $ddr, $ticket, $indice, "AUTORIZACAO FROTA CNPJ","uraHD/26","OPCAO INVALIDA");
        }
    }else{
        $contrato=$contrato2[$opcao];
        inicializa_ambiente_novo_menu();
        M1_1_4_1_recebiveis_senha($uniqueid, $origem, $cnpjcpf, $contrato);
    }
}

function M1_1_4_1_recebiveis_senha($uniqueid, $origem, $cnpjcpf, $contrato){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    tracking_canal_ativo($canal, $ddr, $ticket, $indice);

    //169
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'PERCURSO', 'INFORMAR SENHA EC');

    $senha_estabelecimento='';
    //$senha_estabelecimento = coletar_dados_usuario("uraHD/86",5);
    $senha_estabelecimento = coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/86",5);
    if($senha_estabelecimento == '-1'){hangup();break;}

    //170
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'SENHA EC', 'INFORMACOES CONFIDENCIAIS');

    if(preg_match("/^(\d{5})$/",$senha_estabelecimento)){
        verbose("SENHA DIGITADA CONTÉM QUANTIDADE CORRETA DE DÍGITOS");

        if($GLOBALS["testeativo"] == 'Y'){
            verbose("Entrou no teste");
            $validasenha = true;
        }else{
        $validasenha=api_ucc_valida_senha($uniqueid, $cnpjcpf, $origem, $senha_estabelecimento);
        }

        if($validasenha){
            verbose("SENHA ENTROU COMO VALIDADA PELA API");
            //171
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'RETORNO', 'SENHA VALIDA');
            
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_cartao($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento);

        }else{
            //171
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'RETORNO', 'SENHA INVALIDA');

            if(retentar_dado_invalido("ANTECIPACAO DE RECEBIVEIS SENHA","uraHD/22","SENHA NAO VALIDADA PELA API"))M1_1_4_1_recebiveis_senha($uniqueid, $origem, $cnpjcpf, $contrato);
            else{
                //172
                $indice++;
                tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'PERCURSO', 'TENTATIVAS EXCEDIDAS SENHA INVALIDA');

                //encerra_por_retentativas("ANTECIPACAO DE RECEBIVEIS","uraHD/26","SENHA NAO VALIDADA PELA API");
                encerra_com_tracking($canal, $ddr, $ticket, $indice, "ANTECIPACAO DE RECEBIVEIS","uraHD/26","SENHA NAO VALIDADA PELA API");
            }
        }
    }else{
        //171
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'RETORNO', 'SENHA INVALIDA');

        if(retentar_dado_invalido("ANTECIPACAO DE RECEBIVEIS SENHA","uraHD/5","SENHA NAO DIGITADA CORRETAMENTE"))M1_1_4_1_recebiveis_senha($uniqueid, $origem, $cnpjcpf, $contrato);
        else{
            //172
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'PERCURSO', 'TENTATIVAS EXCEDIDAS SENHA INVALIDA');

            //encerra_por_retentativas("ANTECIPACAO DE RECEBIVEIS","uraHD/26","SENHA NAO DIGITADA CORRETAMENTE");
            encerra_com_tracking($canal, $ddr, $ticket, $indice, "ANTECIPACAO DE RECEBIVEIS","uraHD/26","SENHA NAO DIGITADA CORRETAMENTE");
        }
    }
}

function M1_1_4_1_recebiveis_cartao($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    tracking_canal_ativo($canal, $ddr, $ticket, $indice);

    //173
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'PERCURSO', 'INFORMAR NUMERO DO CARTAO');

    $numero_cartao='';
    //$numero_cartao = coletar_dados_usuario("uraHD/51",17);
    $numero_cartao = coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/51",17);
    if($numero_cartao == '-1'){hangup();break;}

    //174
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'NUM CARTAO', $numero_cartao);

    if (preg_match("/^(\d{17})$/",$numero_cartao)){
        
        if($GLOBALS["testeativo"] == 'Y'){
            verbose("Entrou no teste");
            $valida_cartao = array(
                "CartaoValido"=>"S", 
                "PlacaVeiculo"=>"IMV-8127"                 
            );
            $valida_cartao=json_encode($valida_cartao);
            $valida_cartao=json_decode($valida_cartao);
        }else{
            $valida_cartao=api_ufc_vld_cartao_frota($uniqueid, $origem, $numero_cartao);
        }

        if($valida_cartao->{'CartaoValido'}=='S'){
            //175
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'RETORNO', 'CARTAO VALIDO');

            verbose("CARTAO VALIDADO PELA API");
            verbose("ENCAMINHANDO PARA O MENU FROTA");
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_frota($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao);
        }else{
            //175
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'RETORNO', 'CARTAO INVALIDO');

            if(retentar_dado_invalido("ANTECIPACAO DE RECEBIVEIS CARTAO","uraHD/32","API NAO VALIDOU O CARTAO"))M1_1_4_1_recebiveis_cartao($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento);
            else encerra_por_retentativas("ANTECIPACAO DE RECEBIVEIS CARTAO","uraHD/26","API NAO VALIDOU O CARTAO");
        }
            
    }elseif('TIMEOUT'){
        //175
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'RETORNO', 'CARTAO INVALIDO');

        if(retentar_dado_invalido("ANTECIPACAO DE RECEBIVEIS CARTAO","uraHD/32","USUARIO NAO DIGITOU NENHUM DADO"))M1_1_4_1_recebiveis_cartao($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento);
        else{
            //176
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'PERCURSO', 'TENTATIVAS EXCEDIDAS CARTAO INVALIDO');

            //encerra_por_retentativas("ANTECIPACAO DE RECEBIVEIS CARTAO","uraHD/26","USUARIO NAO DIGITOU NENHUM DADO");
            encerra_com_tracking($canal, $ddr, $ticket, $indice, "ANTECIPACAO DE RECEBIVEIS CARTAO","uraHD/26","USUARIO NAO DIGITOU NENHUM DADO");
        }
    }else {
        //175
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'RETORNO', 'CARTAO INVALIDO');

        if(retentar_dado_invalido("ANTECIPACAO DE RECEBIVEIS CARTAO","uraHD/32","CARTAO NAO DIGITADO CORRETAMENTE"))M1_1_4_1_recebiveis_cartao($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento);
        else{
            //176
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'PERCURSO', 'TENTATIVAS EXCEDIDAS CARTAO INVALIDO');

            //encerra_por_retentativas("ANTECIPACAO DE RECEBIVEIS CARTAO","uraHD/26","CARTAO NAO DIGITADO CORRETAMENTE");
            encerra_com_tracking($canal, $ddr, $ticket, $indice, "ANTECIPACAO DE RECEBIVEIS CARTAO","uraHD/26","CARTAO NAO DIGITADO CORRETAMENTE");
        }
    }
}

function M1_1_4_1_recebiveis_frota($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    tracking_canal_ativo($canal, $ddr, $ticket, $indice);

    playback("uraHD/58");
    verbose("PLACA DO VEICULO : ".$valida_cartao->{'PlacaVeiculo'});
    falar_alfa($valida_cartao->{'PlacaVeiculo'});
    
    //177
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'RETORNO', 'PLACA CADASTRADA: '.$valida_cartao->{'PlacaVeiculo'});

    //178
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'PERCURSO', '1 - SIM OU 2 - NAO');

    $opcao='';
    //$opcao=coletar_dados_usuario("uraHD/59",1);
    $opcao=coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/59",1);
    if($opcao == '-1'){hangup();break;}

    //179
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'RESPOSTA', 'OPCAO ESCOLHIDA PELO CLIENTE: '.$opcao);

    if($opcao =='2'){
        
        verbose("RETORNANDO AO MENU ANTERIOR");
        inicializa_ambiente_novo_menu();
        M1_1_4_1_recebiveis_cartao($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento);
    }elseif($opcao =='1'){

        verbose("ENCAMINHANDO PARA O PROXIMO MENU");
        inicializa_ambiente_novo_menu();
        M1_1_4_1_recebiveis_km($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao);
    }else{
        if(retentar_dado_invalido("ANTECIPACAO DE RECEBIVEIS FROTA","uraHD/5","CARTAO NAO DIGITADO CORRETAMENTE"))M1_1_4_1_recebiveis_frota($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao);
        else{
            //180
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'PERCURSO', 'TENTATIVAS EXCEDIDAS (1 - SIM OU 2 - NAO)');

            //encerra_por_retentativas("ANTECIPACAO DE RECEBIVEIS FROTA","uraHD/26","CARTAO NAO DIGITADO CORRETAMENTE");
            encerra_com_tracking($canal, $ddr, $ticket, $indice, "ANTECIPACAO DE RECEBIVEIS FROTA","uraHD/26","CARTAO NAO DIGITADO CORRETAMENTE");
        }
    }
}

function M1_1_4_1_recebiveis_km($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    tracking_canal_ativo($canal, $ddr, $ticket, $indice);

    //181
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'PERCURSO', 'INFORMAR A KILOMETRAGEM');

    $km ='';
    //$km = coletar_dados_usuario("uraHD/60",10);
    $km = coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/60",10);
    if($km == '-1'){hangup();break;}
    verbose("QUILOMETRAGEM DIGITADA : ".$km);

    //182
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'KILOMETRAGEM', $km);

    if($km >= 1){
        //183
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'RETORNO', 'KILOMETRAGEM VALIDA');

        verbose("USUARIO DIGITOU QUILOMETRAGEM VALIDA");
        inicializa_ambiente_novo_menu();
        M1_1_4_1_recebiveis_matricula($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km);
    }else{
        //183
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'RETORNO', 'KILOMETRAGEM INVALIDA');

        if(retentar_dado_invalido("ANTECIPACAO DE RECEBIVEIS QUILOMETRAGEM","uraHD/5","KILOMETRAGEM DIGITADA INCORRETAMENTE"))M1_1_4_1_recebiveis_km($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao);
        else{
            //184
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'PERCURSO', 'TENTATIVAS EXCEDIDAS (INFORMAR A KILOMETRAGEM)');

            //encerra_por_retentativas("ANTECIPACAO DE RECEBIVEIS QUILOMETRAGEM","uraHD/26","KILOMETRAGEM DIGITADA INCORRETAMENTE");
            encerra_com_tracking($canal, $ddr, $ticket, $indice, "ANTECIPACAO DE RECEBIVEIS QUILOMETRAGEM","uraHD/26","KILOMETRAGEM DIGITADA INCORRETAMENTE");
        }
    }
}

function M1_1_4_1_recebiveis_matricula($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    tracking_canal_ativo($canal, $ddr, $ticket, $indice);

    //185
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'PERCURSO', 'INFORMAR MATRICULA DO USUARIO');

    $matricula='';
    //$matricula=coletar_dados_usuario("uraHD/61",20);
    $matricula=coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/61",20);
    if($matricula == '-1'){hangup();break;}

    //186
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'MATRICULA', $matricula);

    if (strlen($matricula)<=20){
                
        if($GLOBALS["testeativo"] == 'Y'){
            verbose("Entrou no teste");
            $vld_mtcl = array ( 
                "MatriculaValida"=>"S", 
                "motorista"=>"VALDECI APARECIDO DE PAULA" 
            );
            
            $vld_mtcl=json_encode($vld_mtcl);
            $vld_mtcl=json_decode($vld_mtcl);

        }else{
            $vld_mtcl=api_ufc_vld_mtcl_motorista_frota($uniqueid, $origem, $matricula, $numero_cartao);
            verbose("RETORNO DA VALIDACAO : ".$vld_mtcl->{'MatriculaValida'});
        }

        if($vld_mtcl->{'MatriculaValida'}=='S'){
            //187
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'RETORNO', 'MATRICULA VALIDA');

            playback("uraHD/62_1");
            falar_alfa($matricula);
            playback("uraHD/62_2");
            //189
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'RETORNO', 'USUARIO CORRETO');

            verbose("ENCAMINHANDO PARA MENU CONFIRMA MATRICULA");
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_confirma_matricula($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula);

        }else{
            //187
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'RETORNO', 'MATRICULA INVALIDA');

            if(retentar_dado_invalido("ANTECIPACAO DE RECEBIVEIS MATRICULA","uraHD/5","MATRICULA NAO LOCALIZADA"))M1_1_4_1_recebiveis_matricula($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km);
            else{
                //188
                $indice++;
                tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'RETORNO', 'MATRICULA INVALIDA');

                //encerra_por_retentativas("ANTECIPACAO DE RECEBIVEIS MATRICULA","uraHD/26","MATRICULA NAO LOCALIZADA");
                encerra_com_tracking($canal, $ddr, $ticket, $indice, "ANTECIPACAO DE RECEBIVEIS MATRICULA","uraHD/26","MATRICULA NAO LOCALIZADA");
            }
        }

    }else{
        //187
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'RETORNO', 'MATRICULA INVALIDA');

        if(retentar_dado_invalido("ANTECIPACAO DE RECEBIVEIS MATRICULA","uraHD/5","MATRICULA NAO LOCALIZADA"))M1_1_4_1_recebiveis_matricula($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km);
        else{
            //188
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'RETORNO', 'MATRICULA INVALIDA');

            //encerra_por_retentativas("ANTECIPACAO DE RECEBIVEIS MATRICULA","uraHD/26","MATRICULA NAO LOCALIZADA");
            encerra_com_tracking($canal, $ddr, $ticket, $indice, "ANTECIPACAO DE RECEBIVEIS MATRICULA","uraHD/26","MATRICULA NAO LOCALIZADA");
        }
    }
}

function M1_1_4_1_recebiveis_confirma_matricula($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    tracking_canal_ativo($canal, $ddr, $ticket, $indice);

    //190
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'PERCURSO', '1 - SIM OU 2 - NAO');

    $opcao='';
    //$opcao=coletar_dados_usuario("uraHD/63",1);
    $opcao=coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/63",1);
    if($opcao == '-1'){hangup();break;}

    //191
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'RESPOSTA', 'OPCAO ESCOLHIDA PELO CLIENTE:'.$opcao);

    switch ($opcao) {
        case '1':
            inicializa_ambiente_novo_menu();
            verbose("ENCAMINHANDO PARA MENU DE OPCCOES");
            M1_1_4_1_recebiveis_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula);
            break;
        
        case '2':
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_matricula($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km);
            break;
        
        default:
            if(retentar_dado_invalido("ANTECIPACAO DE RECEBIVEIS CONFIRMA MATRICULA","uraHD/5","MATRICULA INCORRETA"))M1_1_4_1_recebiveis_confirma_matricula($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula);
            else{
                //192
                $indice++;
                tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'PERCURSO', 'TENTATIVAS EXCEDIDAS (1 - SIM OU 2 - NAO)');

                //encerra_por_retentativas("ANTECIPACAO DE RECEBIVEIS CONFIRMA MATRICULA","uraHD/26","MATRICULA INCORRETA");
                encerra_com_tracking($canal, $ddr, $ticket, $indice, "ANTECIPACAO DE RECEBIVEIS CONFIRMA MATRICULA","uraHD/26","MATRICULA INCORRETA");
            }
            break;
    }
}

function M1_1_4_1_recebiveis_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    tracking_canal_ativo($canal, $ddr, $ticket, $indice);

    //193
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'PERCURSO', '1 - COMBUSTIVEL OU 2 - ACESSORIOS OU 3 - BORRACHARIA OU 4 - FILTROS OU 5 - LIMPEZA OU 6 - LUBRIFICACAO OU 7 - OLEO OU 8 - MANUTENCAO 0U 9 - PORTARIA');

    $opcao='';
    //$opcao=coletar_dados_usuario("uraHD/64",1);
    $opcao=coletar_dados_usuario("uraHD/64",1);
    if($opcao == '-1'){hangup();break;}

    //194
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'RESPOSTA', 'OPCAO ESCOLHIDA PELO CLIENTE: '.$opcao);

    switch ($opcao) {
        case '1':
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_produto_combustivel($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula);
            break;

        case '2':
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_produto_acessorios($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula);
            break;

        case '3':
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_produto_borracharia($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula);
            break;
            
        case '4':
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_produto_filtros($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula);
            break;
            
        case '5':
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_produto_limpeza($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula);
            break;    

        case '6':
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_produto_lubrificacao($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula);
            break;
        
        case '7':
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_produto_oleos($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula);
            break;
        
        case '8':
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_produto_manutencao($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula);
            break;

        case '9':
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_produto_portaria($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula);
            break;
        
        default:
            if(retentar_dado_invalido("ANTECIPACAO DE RECEBIVEIS PRODUTOS","uraHD/5","OPCAO INVALIDA"))M1_1_4_1_recebiveis_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula);
            else{
                //195
                $indice++;
                tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'PERCURSO', 'TENTATIVAS EXCEDIDAS (SELECIONE O PRODUTO)');

                //encerra_por_retentativas("ANTECIPACAO DE RECEBIVEIS PRODUTOS","uraHD/26","OPCAO INVALIDA");
                encerra_com_tracking($canal, $ddr, $ticket, $indice, "ANTECIPACAO DE RECEBIVEIS PRODUTOS","uraHD/26","OPCAO INVALIDA");
            }
            break;
    }

    inicializa_ambiente_novo_menu();
    M1_1_4_1_recebiveis_qntd_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto);
}

function M1_1_4_1_recebiveis_qntd_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    tracking_canal_ativo($canal, $ddr, $ticket, $indice);

    //196
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'PERCURSO', 'INFORMAR QUANTIDADE');

    $qntd_produto='';
    //$qntd_produto=coletar_dados_usuario("uraHD/78",10);
    $qntd_produto=coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/78",10);
    if($qntd_produto == '-1'){hangup();break;}
    verbose("QUANTIDADE INSERIDA PELO USUARIO : ".$qntd_produto);

    //197
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'QUANTIDADE', $qntd_produto);

    if(strlen($qntd_produto)>=2 && $qntd_produto != 'TIMEOUT'){
        //198
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'RETORNO', 'QUANTIDADE INFORMADA');
        
        inicializa_ambiente_novo_menu();
        M1_1_4_1_recebiveis_produto_valor($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto, $qntd_produto);
    }else{
        //198
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'RETORNO', 'QUANTIDADE NAO INFORMADA');

        if(retentar_dado_invalido("ANTECIPACAO DE RECEBIVEIS QUANTIDADE DE PRODUTOS","uraHD/5","QUANTIDADE INCORRETA"))M1_1_4_1_recebiveis_qntd_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto);
        else{
            //199
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'PERCURSO', 'TENTATIVAS EXCEDIDAS (INFORMAR QUANTIDADE)');

            //encerra_por_retentativas("ANTECIPACAO DE RECEBIVEIS QUANTIDADE DE PRODUTOS","uraHD/26","QUANTIDADE INCORRETA");
            encerra_com_tracking($canal, $ddr, $ticket, $indice, "ANTECIPACAO DE RECEBIVEIS QUANTIDADE DE PRODUTOS","uraHD/26","QUANTIDADE INCORRETA");
        }
    }
}

function M1_1_4_1_recebiveis_produto_valor($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto, $qntd_produto){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    tracking_canal_ativo($canal, $ddr, $ticket, $indice);

    //200
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'PERCURSO', 'INFORMAR VALOR TOTAL');

    $valor_prod='';
    //$valor_prod=coletar_dados_usuario("uraHD/79",10);
    $valor_prod=coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/79",10);
    if($valor_prod == '-1'){hangup();break;}

    //201
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'VALOR TOTAL', $valor_prod);
    
    if($valor_prod>=100){
        $valor_prod = substr($valor_prod,0,(strlen($valor_prod) - 2)).'.'.substr($valor_prod,-2);
        verbose("QUANTIDADE DIGITADA MAIOR QUE A MINIMA NECESSARIA");

        //202
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'RETORNO', 'VALOR PREENCHIDO CORRETAMENTE');
        
        inicializa_ambiente_novo_menu();
        M1_1_4_1_recebiveis_confirma_venda($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto, $qntd_produto, $valor_prod);
    }else{
        //202
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'RETORNO', 'VALOR PREENCHIDO INCORRETAMENTE');

        if(retentar_dado_invalido("ANTECIPACAO DE RECEBIVEIS VALOR DE PRODUTOS","uraHD/5","VALOR INVALIDO"))M1_1_4_1_recebiveis_produto_valor($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto, $qntd_produto);
        else{
            //203
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'PERCURSO', 'TENTATIVAS EXCEDIDAS (INFORMAR VALOR TOTAL)');

            //encerra_por_retentativas("ANTECIPACAO DE RECEBIVEIS VALOR DE PRODUTOS","uraHD/26","VALOR INVALIDO");
            encerra_com_tracking($canal, $ddr, $ticket, $indice, "ANTECIPACAO DE RECEBIVEIS VALOR DE PRODUTOS","uraHD/26","VALOR INVALIDO");
        }
    }
}

function M1_1_4_1_recebiveis_confirma_venda($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto, $qntd_produto, $valor_prod){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    tracking_canal_ativo($canal, $ddr, $ticket, $indice);

    //202.1
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'PERCURSO', 'INFORMAR SENHA MOTORISTA');

    $senhaMt='';
    //$senhaMt=coletar_dados_usuario("uraHD/80_1",5);
    $senhaMt=coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/80_1",5);
    if($senhaMt == '-1'){hangup();break;}

    //202.2
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'SENHA DO MOTORISTA', 'DADOS CONFIDENCIAIS');

    //204
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'PERCURSO', '1 - CONFIRMAR VENDA OU 2 - CANCELAR');

    $opcao='';
    //$opcao=coletar_dados_usuario("uraHD/80",1);
    $opcao=coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/80",1);
    if($opcao == '-1'){hangup();break;}

    //205
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'RESPOSTA', 'OPCAO ESCOLHIDA PELO CLIENTE: '.$opcao);

    switch ($opcao) {
        case '1':            
            if($GLOBALS["testeativo"] == 'Y'){
                verbose("Entrou no teste");
                $autoriza_svc_vnd_frt = array(
                    "autorizada"=> "S",
                    "codigoRetornoAutorizacao"=> "Autorizada",
                    "codigoAutorizacao"=> "35053",
                    "valorTransacao"=> 41.50
                );
            }else{
                $qntd_produto2 = substr($qntd_produto,0,(strlen($qntd_produto) - 2)).'.'.substr($qntd_produto,-2);
                verbose("RETORNO DO ID : ".$uniqueid);
                verbose("NUMERO DE ORIGEM : ".$origem);
                verbose("PLACA DO VEICULO : ".$valida_cartao->{'PlacaVeiculo'});
                verbose("NUMERO DE KM : ".$km);
                verbose("VALOR DO PRODUTO : ".$valor_prod);
                verbose("CODIGO DO PRODUTO : ".$cod_produto);
                verbose("QUANTIDADE DO PRODUTO : ".$qntd_produto2);
                $autoriza_svc_vnd_frt =api_ufc_autoriza_venda_frota($uniqueid, $origem, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao->{'PlacaVeiculo'}, $km, $matricula, $valor_prod, $cod_produto, $qntd_produto2, $senhaMt);

                verbose("RETORNO PELA AUTORIZACAO : ".$autoriza_svc_vnd_frt->{'autorizada'});
                verbose("CODIGO DE RETORNO AUTORIZACAO : ".$autoriza_svc_vnd_frt->{'codigoRetornoAutorizacao'});
                verbose("CODIGO DA AUTORIZACAO : ".$autoriza_svc_vnd_frt->{'codigoAutorizacao'});
                verbose("VALOR DA TRANZACAO : ".$autoriza_svc_vnd_frt->{'valorTransacao'});
            }

            if($autoriza_svc_vnd_frt->{'autorizada'}=='S'){
                //207
                $indice++;
                tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'RETORNO', 'TRANSACAO APROVADA');

                inicializa_ambiente_novo_menu();
                M1_1_4_1_recebiveis_finaliza($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $valor_prod, $cod_produto, $qntd_produto, $autoriza_svc_vnd_frt, $senhaMt);
            }else{
                //207
                $indice++;
                tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'RETORNO', 'TRANSACAO NAO APROVADA');

                playback("uraHD/84");
                retorna_audio($autoriza_svc_vnd_frt->{'codigoRetornoAutorizacao'});

                //212
                $indice++;
                tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'RETORNO', $autoriza_svc_vnd_frt->{'codigoRetornoAutorizacao'});

                inicializa_ambiente_novo_menu();
                M1_1_4_1_recebiveis_erro_api($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $valor_prod, $cod_produto, $qntd_produto, $autoriza_svc_vnd_frt, $senhaMt);        
                
            }        
        break;

        case '2':
            //206.1
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'PERCURSO', 'URA ENCERRANDO A LIGACAO');

            playback("uraHD/81");
            hangup();
        break;
        
        default:
            if(retentar_dado_invalido("ANTECIPACAO DE RECEBIVEIS CONFIRMA VENDA","uraHD/5","VALOR INVALIDO"))M1_1_4_1_recebiveis_confirma_venda($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto, $qntd_produto, $valor_prod);
            else{
                //206
                $indice++;
                tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'PERCURSO', 'TENTATIVAS EXCEDIDAS (1 - CONFIRMAR VENDA OU 2 - CANCELAR)');

                //encerra_por_retentativas("ANTECIPACAO DE RECEBIVEIS CONFIRMA VENDA","uraHD/26","VALOR INVALIDO");
                encerra_com_tracking($canal, $ddr, $ticket, $indice, "ANTECIPACAO DE RECEBIVEIS CONFIRMA VENDA","uraHD/26","VALOR INVALIDO");
            }
        break;
    }
}

function M1_1_4_1_recebiveis_erro_api($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $valor_prod, $cod_produto, $qntd_produto, $autoriza_svc_vnd_frt){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    tracking_canal_ativo($canal, $ddr, $ticket, $indice);

    //214
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'PERCURSO', '0 - VOLTAR AO MENU PRINCIPAL OU DESLIGAR TELEFONE');
    
    $opcao='';
    //$opcao=coletar_dados_usuario("uraHD/85",1);
    $opcao=coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/85",1);
    if($opcao == '-1'){hangup();break;}

    //215
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'RESPOSTA', 'OPCAO ESCOLHIDA PELO CLIENTE: '.$opcao);

    switch ($opcao) {
        case '0':
            inicializa_ambiente_novo_menu();
            Menu_Principal($uniqueid, $origem);
        break;
        
        default:
            if(retentar_dado_invalido("ANTECIPACAO DE RECEBIVEIS ERRO API","uraHD/5","OPCAO INVALIDA"))M1_1_4_1_recebiveis_erro_api($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $valor_prod, $cod_produto, $qntd_produto, $autoriza_svc_vnd_frt);
            else{
                //216
                $indice++;
                tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'PERCURSO', 'TENTATIVAS EXCEDIDAS (0 - VOLTAR AO MENU PRINCIPAL OU DESLIGAR TELEFONE)');

                //encerra_por_retentativas("ANTECIPACAO DE RECEBIVEIS ERRO API","uraHD/26","OPCAO INVALIDA");
                encerra_com_tracking($canal, $ddr, $ticket, $indice, "ANTECIPACAO DE RECEBIVEIS ERRO API","uraHD/26","OPCAO INVALIDA");
            }
        break;
    }
}

function M1_1_4_1_recebiveis_finaliza($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $valor_prod, $cod_produto, $qntd_produto, $autoriza_svc_vnd_frt){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    tracking_canal_ativo($canal, $ddr, $ticket, $indice);

    //208
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'PERCURSO', 'VENDA REALIZADA COM SUCESSO');
    
    playback("uraHD/82_1");
    falar_alfa($autoriza_svc_vnd_frt->{'codigoAutorizacao'});
    playback("uraHD/82_2");
    playback("uraHD/82_3");
    falar_alfa($autoriza_svc_vnd_frt->{'codigoAutorizacao'});

    //205.1

    $categoria='AUTORIZACAO DE VENDA - FROTA';
    $subCat='VENDA A VISTA';
    $protocoloEASY=api_ucc_protocolo_v2($categoria, $cnpjcpf, '', $subCat, $origem, '', $uniqueid);

    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'PROTOCOLO', $protocoloEASY);
    
    playback("FroCli/18");
    falar_alfa($protocoloEASY);

    inicializa_ambiente_novo_menu();
    M1_1_4_1_recebiveis_encerra($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $valor_prod, $cod_produto, $qntd_produto, $autoriza_svc_vnd_frt);
}

function M1_1_4_1_recebiveis_encerra($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $valor_prod, $cod_produto, $qntd_produto, $autoriza_svc_vnd_frt){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    tracking_canal_ativo($canal, $ddr, $ticket, $indice);
    
    //209
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'PERCURSO', '9 - NOVA TRANSACAO OU DESLIGUE O TELEFONE');
    
    $opcao='';
    //$opcao=coletar_dados_usuario("uraHD/83",1);
    $opcao=coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/83",1);
    if($opcao == '-1'){hangup();break;}

    //210
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'RESPOSTA', 'OPCAO ESCOLHIDA PELO CLIENTE: '.$opcao);

    if($opcao =='9'){

        inicializa_ambiente_novo_menu();
        M1_1_4_autorizacao_frota_pt1($uniqueid, $origem, $cnpjcpf, $cnpjcpfValidado);

    }else{
        if(retentar_dado_invalido("ANTECIPACAO DE RECEBIVEIS ENCERRA","uraHD/5","OPCAO INVALIDA"))M1_1_4_1_recebiveis_encerra($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $valor_prod, $cod_produto, $qntd_produto, $autoriza_svc_vnd_frt);
        else{
            //211
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'AUTORIZACAO FROTA', 'PERCURSO', 'TENTATIVAS EXCEDIDAS (9 - NOVA TRANSACAO OU DESLIGUE O TELEFONE)');

            //encerra_por_retentativas("ANTECIPACAO DE RECEBIVEIS ENCERRA","uraHD/26","OPCAO INVALIDA");
            encerra_com_tracking($canal, $ddr, $ticket, $indice, "ANTECIPACAO DE RECEBIVEIS ENCERRA","uraHD/26","OPCAO INVALIDA");
        }
    }
}


////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// SUBMENUS DE PRODUTOS COMBUSTÍVEL
function M1_1_4_1_recebiveis_produto_combustivel($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    tracking_canal_ativo($canal, $ddr, $ticket, $indice);

    $opcao='';
    //$opcao = coletar_dados_usuario("uraHD/66",1);
    $opcao = coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/66",1);
    if($opcao == '-1'){hangup();break;}

    switch ($opcao) {
        case '1':
            verbose("ETANOL");
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_produto_combustivel_etanol($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula);
            break;

        case '2':
            verbose("DIESEL");
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_produto_combustivel_diesel($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula);
            break;

        case '3':
            verbose("GASOLINA");
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_produto_combustivel_gasolina($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula);
            break;

        case '4':
            verbose("GAS NATURAL");
            $cod_produto='8';
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_qntd_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto);

            break;

        case '5':
            verbose("BIODISEL");
            $cod_produto='10';
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_qntd_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto);
            break;

        case '6':
            verbose("QUEROSENE");
            $cod_produto='9';
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_qntd_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto);
            break;
        
        default:
            if(retentar_dado_invalido("ANTECIPACAO DE RECEBIVEIS ESCOLHA DE PRODUTO","uraHD/5","OPCAO INVALIDA"))M1_1_4_1_recebiveis_produto_combustivel($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula);
            else{
                //encerra_por_retentativas("ANTECIPACAO DE RECEBIVEIS ESCOLHA DE PRODUTO","uraHD/26","OPCAO INVALIDA");
                encerra_com_tracking($canal, $ddr, $ticket, $indice, "ANTECIPACAO DE RECEBIVEIS ESCOLHA DE PRODUTO","uraHD/26","OPCAO INVALIDA");
            }
        break;
    }
}

function M1_1_4_1_recebiveis_produto_combustivel_etanol($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    tracking_canal_ativo($canal, $ddr, $ticket, $indice);

    $opcao='';
    //$opcao = coletar_dados_usuario("uraHD/67",1);
    $opcao = coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/67",1);
    if($opcao == '-1'){hangup();break;}

    switch ($opcao) {
        case '1':
            verbose("ETANOL ADITIVADO");
            $cod_produto='1';
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_qntd_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto);
        break;
        
        case '2':
            verbose("ETANOL COMUM");
            $cod_produto='2';
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_qntd_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto);
        break;        

        default:
            if(retentar_dado_invalido("ANTECIPACAO DE RECEBIVEIS ETANOL","uraHD/5","OPCAO INVALIDA"))M1_1_4_1_recebiveis_produto_combustivel_etanol($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula);
            else{
                //encerra_por_retentativas("ANTECIPACAO DE RECEBIVEIS ETANOL","uraHD/26","OPCAO INVALIDA");
                encerra_com_tracking($canal, $ddr, $ticket, $indice, "ANTECIPACAO DE RECEBIVEIS ETANOL","uraHD/26","OPCAO INVALIDA");
            }
        break;
    }
}

function M1_1_4_1_recebiveis_produto_combustivel_diesel($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    tracking_canal_ativo($canal, $ddr, $ticket, $indice);

    $opcao='';
    //$opcao = coletar_dados_usuario("uraHD/68",1);
    $opcao = coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/68",1);
    if($opcao == '-1'){hangup();break;}

    switch ($opcao) {
        case '1':
            verbose("DIESEL COMUM");
            $cod_produto='4';
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_qntd_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto);
        break;
        
        case '2':
            verbose("DIESEL ADITIVADO");
            $cod_produto='3';
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_qntd_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto);
        break;
        
        case '3':
            verbose("DIESEL S-5");
            $cod_produto='11';
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_qntd_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto);
        break;

        case '4':
            verbose("DIESEL S-10");
            $cod_produto='12';
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_qntd_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto);
        break;

        case '5':
            verbose("DIESEL S-50");
            $cod_produto='13';
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_qntd_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto);
        break;

        default:
            if(retentar_dado_invalido("ANTECIPACAO DE RECEBIVEIS DIESEL","uraHD/5","OPCAO INVALIDA"))M1_1_4_1_recebiveis_produto_combustivel_diesel($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula);
            else{
                //encerra_por_retentativas("ANTECIPACAO DE RECEBIVEIS DIESEL","uraHD/26","OPCAO INVALIDA");
                encerra_com_tracking($canal, $ddr, $ticket, $indice, "ANTECIPACAO DE RECEBIVEIS DIESEL","uraHD/26","OPCAO INVALIDA");
            }
        break;
    }
}

function M1_1_4_1_recebiveis_produto_combustivel_gasolina($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    tracking_canal_ativo($canal, $ddr, $ticket, $indice);

    $opcao='';
    //$opcao = coletar_dados_usuario("uraHD/69",1);
    $opcao = coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/69",1);
    if($opcao == '-1'){hangup();break;}

    switch ($opcao) {
        case '1':
            verbose("GASOLINA COMUM");
            $cod_produto='6';
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_qntd_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto);
        break;
        
        case '2':
            verbose("GASOLINA ADITIVADA");
            $cod_produto='5';
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_qntd_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto);
        break;
        
        case '3':
            verbose("GASOLINA PREMIUM");
            $cod_produto='7';
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_qntd_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto);
        break;

        default:
            if(retentar_dado_invalido("ANTECIPACAO DE RECEBIVEIS GASOLINA","uraHD/5","OPCAO INVALIDA"))M1_1_4_1_recebiveis_produto_combustivel_gasolina($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula);
            else{
                encerra_por_retentativas("ANTECIPACAO DE RECEBIVEIS GASOLINA","uraHD/26","OPCAO INVALIDA");
                encerra_com_tracking($canal, $ddr, $ticket, $indice, "ANTECIPACAO DE RECEBIVEIS GASOLINA","uraHD/26","OPCAO INVALIDA");
            }
        break;
    }
}

// SUBMENU DE PRODUTOS ACESSÓRIOS
function M1_1_4_1_recebiveis_produto_acessorios($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    tracking_canal_ativo($canal, $ddr, $ticket, $indice);

    $opcao='';
    //$opcao = coletar_dados_usuario("uraHD/70",1);
    $opcao = coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/70",1);
    if($opcao == '-1'){hangup();break;}

    switch ($opcao) {
        case '1':
            verbose("EXTINTOR");
            $cod_produto='20';
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_qntd_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto);
        break;
        
        case '2':
            verbose("BATERIA");
            $cod_produto='21';
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_qntd_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto);
        break;        

        default:
            if(retentar_dado_invalido("ANTECIPACAO DE RECEBIVEIS ACESSORIOS","uraHD/5","OPCAO INVALIDA"))M1_1_4_1_recebiveis_produto_acessorios($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula);
            else{
                //encerra_por_retentativas("ANTECIPACAO DE RECEBIVEIS ACESSORIOS","uraHD/26","OPCAO INVALIDA");
                encerra_com_tracking($canal, $ddr, $ticket, $indice, "ANTECIPACAO DE RECEBIVEIS ACESSORIOS","uraHD/26","OPCAO INVALIDA");
            }
        break;
    }
}

// SUBMENU DE PRODUTOS BORRACHARIA
function M1_1_4_1_recebiveis_produto_borracharia($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    tracking_canal_ativo($canal, $ddr, $ticket, $indice);

    $opcao='';
    //$opcao = coletar_dados_usuario("uraHD/71",1);
    $opcao = coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/71",1);
    if($opcao == '-1'){hangup();break;}

    switch ($opcao) {
        case '1':
            verbose("CONSERTO DE PNEU");
            $cod_produto='30';
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_qntd_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto);
        break;
        
        case '2':
            verbose("MONTAGEM OU DESMONTAGEM DE PNEU");
            $cod_produto='31';
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_qntd_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto);
        break;        

        default:
            if(retentar_dado_invalido("ANTECIPACAO DE RECEBIVEIS BORRACHARIA","uraHD/5","OPCAO INVALIDA"))M1_1_4_1_recebiveis_produto_borracharia($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula);
            else{
                //encerra_por_retentativas("ANTECIPACAO DE RECEBIVEIS BORRACHARIA","uraHD/26","OPCAO INVALIDA");
                encerra_com_tracking($canal, $ddr, $ticket, $indice, "ANTECIPACAO DE RECEBIVEIS BORRACHARIA","uraHD/26","OPCAO INVALIDA");
            }
        break;
    }
}

// SUBMENU DE PRODUTOS FILTROS
function M1_1_4_1_recebiveis_produto_filtros($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    tracking_canal_ativo($canal, $ddr, $ticket, $indice);

    $opcao='';
    //$opcao = coletar_dados_usuario("uraHD/72",1);
    $opcao = coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/72",1);
    if($opcao == '-1'){hangup();break;}

    switch ($opcao) {
        case '1':
            verbose("FILTRO DE AR");
            $cod_produto='40';
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_qntd_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto);
        break;

        case '2':
            verbose("FILTRO DE OLEO");
            $cod_produto='41';
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_qntd_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto);
        break;

        case '3':
            verbose("FILTRO DE COMBUSTIVEL");
            $cod_produto='42';
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_qntd_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto);
        break;

        case '4':
            verbose("FILTRO HIDRAULICO");
            $cod_produto='43';
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_qntd_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto);
        break;

        case '5':
            verbose("FILTRO DE AGUA");
            $cod_produto='44';
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_qntd_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto);
        break;
        
        default:
            if(retentar_dado_invalido("ANTECIPACAO DE RECEBIVEIS ESCOLHA DE PRODUTO FILTROS","uraHD/5","OPCAO INVALIDA"))M1_1_4_1_recebiveis_produto_filtros($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula);
            else{
                //encerra_por_retentativas("ANTECIPACAO DE RECEBIVEIS ESCOLHA DE PRODUTO FILTROS","uraHD/26","OPCAO INVALIDA");
                encerra_com_tracking($canal, $ddr, $ticket, $indice, "ANTECIPACAO DE RECEBIVEIS ESCOLHA DE PRODUTO FILTROS","uraHD/26","OPCAO INVALIDA");
            }
        break;
    }
}

// SUBMENU DE PRODUTOS LIMPEZA
function M1_1_4_1_recebiveis_produto_limpeza($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    tracking_canal_ativo($canal, $ddr, $ticket, $indice);

    $opcao='';
    //$opcao = coletar_dados_usuario("uraHD/73",1);
    $opcao = coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/73",1);
    if($opcao == '-1'){hangup();break;}

    switch ($opcao) {
        case '1':
            verbose("LAVADA EXPRESSA");
            $cod_produto='50';
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_qntd_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto);
        break;

        case '2':
            verbose("LAVADA COMPLETA");
            $cod_produto='51';
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_qntd_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto);
        break;

        case '3':
            verbose("POLIMENTO CRISTALIZADO");
            $cod_produto='52';
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_qntd_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto);
        break;

        case '4':
            verbose("PULVERIZACAO ANTI-FERRUGEM");
            $cod_produto='53';
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_qntd_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto);
        break;

        case '5':
            verbose("LAVADA SIMPLES");
            $cod_produto='54';
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_qntd_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto);
        break;
        
        default:
            if(retentar_dado_invalido("ANTECIPACAO DE RECEBIVEIS ESCOLHA DE PRODUTO LIMPEZA","uraHD/5","OPCAO INVALIDA"))M1_1_4_1_recebiveis_produto_limpeza($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula);
            else{
                //encerra_por_retentativas("ANTECIPACAO DE RECEBIVEIS ESCOLHA DE PRODUTO LIMPEZA","uraHD/26","OPCAO INVALIDA");
                encerra_com_tracking($canal, $ddr, $ticket, $indice, "ANTECIPACAO DE RECEBIVEIS ESCOLHA DE PRODUTO LIMPEZA","uraHD/26","OPCAO INVALIDA");
            }
        break;
    }
}

// SUBMENU DE PRODUTOS LUBRIFICACAO
function M1_1_4_1_recebiveis_produto_lubrificacao($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    tracking_canal_ativo($canal, $ddr, $ticket, $indice);

    $opcao='';
    //$opcao = coletar_dados_usuario("uraHD/74",1);
    $opcao = coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/74",1);
    if($opcao == '-1'){hangup();break;}

    switch ($opcao) {
        case '1':
            verbose("FLUIDO DE FREIO");
            $cod_produto='60';
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_qntd_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto);
        break;

        case '2':
            verbose("FLUIDO DE RADIADOR");
            $cod_produto='61';
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_qntd_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto);
        break;

        case '3':
            verbose("ARIA 32");
            $cod_produto='62';
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_qntd_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto);
        break;

        case '4':
            verbose("OXIDO DE NITROGENIO");
            $cod_produto='6';
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_qntd_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto);
        break;
        
        default:
            if(retentar_dado_invalido("ANTECIPACAO DE RECEBIVEIS ESCOLHA DE PRODUTO LUBRIFICACAO","uraHD/5","OPCAO INVALIDA"))M1_1_4_1_recebiveis_produto_lubrificacao($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula);
            else{
                //encerra_por_retentativas("ANTECIPACAO DE RECEBIVEIS ESCOLHA DE PRODUTO LUBRIFICACAO","uraHD/26","OPCAO INVALIDA");
                encerra_com_tracking($canal, $ddr, $ticket, $indice, "ANTECIPACAO DE RECEBIVEIS ESCOLHA DE PRODUTO LUBRIFICACAO","uraHD/26","OPCAO INVALIDA");
            }
        break;
    }
}

// SUBMENU DE PRODUTOS OLEOS
function M1_1_4_1_recebiveis_produto_oleos($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    tracking_canal_ativo($canal, $ddr, $ticket, $indice);

    $opcao='';
    //$opcao = coletar_dados_usuario("uraHD/75",1);
    $opcao = coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/75",1);
    if($opcao == '-1'){hangup();break;}

    switch ($opcao) {
        case '1':
            verbose("OLEO DE MOTOR");
            $cod_produto='70';
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_qntd_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto);
        break;

        case '2':
            verbose("OLEO DIFERENCIAL");
            $cod_produto='71';
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_qntd_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto);
        break;

        case '3':
            verbose("OLEO HIDRAULICO");
            $cod_produto='72';
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_qntd_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto);
        break;

        case '4':
            verbose("OLEO DE FREIO");
            $cod_produto='73';
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_qntd_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto);
        break;

        case '5':
            verbose("OLEO DE CAMBIO");
            $cod_produto='74';
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_qntd_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto);
        break;

        case '6':
            verbose("COMPLEMENTO DE OLEO");
            $cod_produto='75';
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_qntd_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto);
        break;
        
        default:
            if(retentar_dado_invalido("ANTECIPACAO DE RECEBIVEIS ESCOLHA DE PRODUTO LIMPEZA","uraHD/5","OPCAO INVALIDA"))M1_1_4_1_recebiveis_produto_oleos($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula);
            else{
                //encerra_por_retentativas("ANTECIPACAO DE RECEBIVEIS ESCOLHA DE PRODUTO LIMPEZA","uraHD/26","OPCAO INVALIDA");
                encerra_com_tracking($canal, $ddr, $ticket, $indice, "ANTECIPACAO DE RECEBIVEIS ESCOLHA DE PRODUTO LIMPEZA","uraHD/26","OPCAO INVALIDA");
            }
        break;
    }
}

// SUBMENU DE PRODUTOS MANUTENÇÃO
function M1_1_4_1_recebiveis_produto_manutencao($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    tracking_canal_ativo($canal, $ddr, $ticket, $indice);

    $opcao='';
    //$opcao = coletar_dados_usuario("uraHD/76",1);
    $opcao = coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/76",1);
    if($opcao == '-1'){hangup();break;}

    switch ($opcao) {
        case '1':
            verbose("MAO DE OBRA");
            $cod_produto='80';
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_qntd_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto);
        break;

        case '2':
            verbose("PECAS");
            $cod_produto='81';
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_qntd_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto);
        break;

        case '3':
            verbose("ENGRAXAMENTO");
            $cod_produto='82';
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_qntd_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto);
        break;

        case '4':
            verbose("ALINHAMENTO DE DIRECAO");
            $cod_produto='83';
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_qntd_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto);
        break;

        case '5':
            verbose("BALANCEAMENTO DE PNEU");
            $cod_produto='84';
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_qntd_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto);
        break;

        case '6':
            verbose("GEOMETRIA");
            $cod_produto='85';
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_qntd_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto);
        break;
        
        default:
            if(retentar_dado_invalido("ANTECIPACAO DE RECEBIVEIS ESCOLHA DE PRODUTO MANUTENCAO","uraHD/5","OPCAO INVALIDA"))M1_1_4_1_recebiveis_produto_manutencao($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula);
            else{
                //encerra_por_retentativas("ANTECIPACAO DE RECEBIVEIS ESCOLHA DE PRODUTO MANUTENCAO","uraHD/26","OPCAO INVALIDA");
                encerra_com_tracking($canal, $ddr, $ticket, $indice, "ANTECIPACAO DE RECEBIVEIS ESCOLHA DE PRODUTO MANUTENCAO","uraHD/26","OPCAO INVALIDA");
            }
        break;
    }
}

// SUBMENU DE PRODUTOS PORTARIA
function M1_1_4_1_recebiveis_produto_portaria($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    tracking_canal_ativo($canal, $ddr, $ticket, $indice);

    $opcao='';
    //$opcao = coletar_dados_usuario("uraHD/77",1);
    $opcao = coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/77",1);
    if($opcao == '-1'){hangup();break;}

    switch ($opcao) {
        case '1':
            verbose("PARA ENTRADA");
            $cod_produto='90';
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_qntd_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto);
        break;

        case '2':
            verbose("PARA SAIDA");
            $cod_produto='91';
            inicializa_ambiente_novo_menu();
            M1_1_4_1_recebiveis_qntd_produto($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula, $cod_produto);
        break;
        
        default:
            if(retentar_dado_invalido("ANTECIPACAO DE RECEBIVEIS ESCOLHA DE PRODUTO PORTARIA","uraHD/5","OPCAO INVALIDA"))M1_1_4_1_recebiveis_produto_portaria($uniqueid, $origem, $cnpjcpf, $contrato, $senha_estabelecimento, $numero_cartao, $valida_cartao, $km, $matricula);
            else{
                //encerra_por_retentativas("ANTECIPACAO DE RECEBIVEIS ESCOLHA DE PRODUTO PORTARIA","uraHD/26","OPCAO INVALIDA");
                encerra_com_tracking($canal, $ddr, $ticket, $indice, "ANTECIPACAO DE RECEBIVEIS ESCOLHA DE PRODUTO PORTARIA","uraHD/26","OPCAO INVALIDA");
            }
        break;
    }
}
return 0;
hangup();
break;
exit();
?>
