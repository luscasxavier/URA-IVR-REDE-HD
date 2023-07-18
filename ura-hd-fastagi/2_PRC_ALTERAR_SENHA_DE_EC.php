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
$tentativas=0;//tentativas realizadas
$debug_level=5; //nivel de log desejado  
$testeativo = 'N'; //ignora todas apis
//1 - ERROS DE EXECUÇÃO 
//2 - ALERTAS
//3 - MENSAGENS INFORMATIVAS
//4 - DEBUG LEVE
//5 - DEBUG TOTAL

//INICIO DA URA
verbose(">>>>>>> MENU DE ALTERAR SENHA DE EC",3); 

function Op01_1_2_alterar_senha_de_ec($uniqueid ,$origem ,$cnpjcpf, $cnpjcpfValidado){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    verbose("FDJKSAHGSFDHFS : ".$cnpjcpf);
    //43
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'ALTERAR SENHA DE EC', 'PERCURSO', '1 - ALTERAR SENHA OU 9 - FALAR COM ATENDENTE');

    $opcao ='';
    //$opcao = coletar_dados_usuario("uraHD/19",1);
    $opcao = coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/19",1);
    if($opcao == '-1'){hangup();break;}

    //44
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'ALTERAR SENHA DE EC', 'RESPOSTA', 'OPCAO ESCOLHIDA PELO CLIENTE: '.$opcao);

    if ($opcao == 1){

        verbose(">>>>> CLIENTE ESCOLHEU ALTERAR A SENHA");
        inicializa_ambiente_novo_menu();
        Op01_1_2_alterar_senha_de_ec_opc1($uniqueid ,$origem ,$cnpjcpf, $cnpjcpfValidado);

    } elseif ($opcao == 9){

        if($GLOBALS["testeativo"] == 'Y'){
            verbose("Entrou no teste");
            $validarhorario = true;
        }else{
            $validarhorario = api_horario_atendimento();
        }

        if($validarhorario){

            $protocoloEASY=api_ucc_protocolo_v2('', $cnpjcpf, '', '', $origem, '', $uniqueid);

            playback("uraHD/27_1");
            falar_alfa($protocoloEASY);
            playback("uraHD/27_2");
            falar_alfa($protocoloEASY);
            playback("uraHD/27_3");

            //62
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'ALTERAR SENHA DE EC', 'PROTOCOLO', $protocoloEASY);

            verbose('ENCAMINHANDO PARA FILA 210');
            //63
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'MENU ATENDENTE - ALTERAR SENHA DE EC', 'PERCURSO', 'ENCAMINHANDO PARA A FILA 210');

            inserirprotocolobanco($origem,$protocolo);
            //dial_return("gw01-kontac33/999999");   //DISCAGEM DO SERVIDOR 30
            dial_return("gw02-kontac33/210");   //DISCAGEM DO SERVIDOR 31

            $indice++;
            $uniqueId_kontac= get_uniqueId_kontac($origem, '33');
            tracking($canal, $ddr, $ticket, $indice, 'PÓS ATENDIMENTO HUMANO', 'UNIQUEID KONTAC', $uniqueId_kontac);

            tracking_canal_ativo($canal, $ddr, $ticket, $indice);

            //64
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'PÓS ATENDIMENTO HUMANO', 'PERCURSO', 'CLIENTE AINDA ESTÁ NA LINHA');
            inicializa_ambiente_novo_menu();
            pesquisa_satisfacao($uniqueid, $origem);
            hangup();

        }else{
            //61
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'FALAR COM ATENDENTE - ALTERAR SENHA DE EC', 'PERCURSO', 'CLIENTE LIGOU FORA DO HORARIO DE ATENDIMENTO');

            playback("uraHD/28");
            hangup();break;
        }

    }else{
        if(retentar_dado_invalido("Op01_1_2_alterar_senha_de_ec","uraHD/5","OPCAO INVALIDA"))Op01_1_2_alterar_senha_de_ec($uniqueid ,$origem ,$cnpjcpf);
        else{
            //45
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'ALTERAR SENHA DE EC', 'PERCURSO', 'TENTATIVAS EXCEDIDAS (1 - ALTERAR SENHA OU 9 - FALAR COM ATENDENTE)');

            //encerra_por_retentativas("Op01_1_2_alterar_senha_de_ec","uraHD/4","OPCAO INVALIDA");
            encerra_com_tracking($canal, $ddr, $ticket, $indice, "Op01_1_2_alterar_senha_de_ec","uraHD/4","OPCAO INVALIDA");
        }
    }
}

function Op01_1_2_alterar_senha_de_ec_opc1($uniqueid ,$origem ,$cnpjcpf, $cnpjcpfValidado){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    verbose("11111111111111111111 : ".$cnpjcpf);
    //46
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'ALTERAR SENHA DE EC', 'PERCURSO', 'INFORMAR SENHA ATUAL');

    $senha = '';
    //$senha = coletar_dados_usuario("uraHD/20",5);
    $senha = coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/20",5);
    if($senha == '-1'){hangup();break;}

    //47
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'ALTERAR SENHA DE EC', 'SENHA', 'INFORMACOES CONFIDENCIAIS');

    if(preg_match("/^(\d{5})$/",$senha)){

        if($GLOBALS["testeativo"] == 'Y'){
            verbose("Entrou no teste");
            $validar_senha = true;
        }else{
            verbose("UNIQUEID : ".$uniqueid);
            verbose("NÚMERO DE ORIGEM : ".$origem);

            $validar_senha = api_ucc_valida_senha($uniqueid, $cnpjcpf, $origem, $senha);
        }

        if($validar_senha){
            //49
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'ALTERAR SENHA DE EC', 'RETORNO', 'SENHA VALIDA');

            inicializa_ambiente_novo_menu();
            Op01_1_2_alterar_senha_de_ec_senha_validada($uniqueid ,$origem ,$cnpjcpf,$senha, $cnpjcpfValidado);

        }else{
            //49
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'ALTERAR SENHA DE EC', 'RETORNO', 'SENHA INVALIDA');

            if(retentar_dado_invalido("Op01_1_2_alterar_senha_de_ec_opc1","uraHD/22","senha incorreta"))Op01_1_2_alterar_senha_de_ec_opc1($uniqueid ,$origem ,$cnpjcpf);
            else{
                //48
                $indice++;
                tracking($canal, $ddr, $ticket, $indice, 'ALTERAR SENHA DE EC', 'PERCURSO', 'TENTATIVAS EXCEDIDAS SENHA INVALIDA');

                //encerra_por_retentativas("Op01_1_2_alterar_senha_de_ec_opc1","uraHD/4","senha incorreta");
                encerra_com_tracking($canal, $ddr, $ticket, $indice, "Op01_1_2_alterar_senha_de_ec_opc1","uraHD/4","senha incorreta");
            }
        }
        
    }else{
        //49
        $indice++;
        tracking($canal, $ddr, $ticket, $indice, 'ALTERAR SENHA DE EC', 'RETORNO', 'SENHA INVALIDA');

        if(retentar_dado_invalido("Op01_1_2_alterar_senha_de_ec_opc1","uraHD/5","nao digitou a senha"))Op01_1_2_alterar_senha_de_ec_opc1($uniqueid ,$origem ,$cnpjcpf);
        else{
            //48
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'ALTERAR SENHA DE EC', 'PERCURSO', 'TENTATIVAS EXCEDIDAS SENHA INVALIDA');

            //encerra_por_retentativas("Op01_1_2_alterar_senha_de_ec_opc1","uraHD/4","nao digitou a senha");
            encerra_com_tracking($canal, $ddr, $ticket, $indice, "Op01_1_2_alterar_senha_de_ec_opc1","uraHD/4","nao digitou a senha");
        }
    }
}

function Op01_1_2_alterar_senha_de_ec_senha_validada($uniqueid, $origem, $cnpjcpf, $senha, $cnpjcpfValidado){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    verbose("22222222222222 : ".$cnpjcpf);
    //50
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'ALTERAR SENHA DE EC', 'PERCURSO', 'INFORMAR SENHA NOVA');

    $senhanova = ''; 
    //$senhanova = coletar_dados_usuario("uraHD/21",5);
    $senhanova = coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/21",5);
    if($senhanova == '-1'){hangup();break;}

    //51
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'ALTERAR SENHA DE EC', 'SENHA NOVA', 'DADOS CONFIDENCIAIS');

    //52
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'ALTERAR SENHA DE EC', 'PERCURSO', 'INFORMAR SENHA NOVA NOVAMENTE');

    $senhanova2 = '';
    //$senhanova2 = coletar_dados_usuario("uraHD/23",5);
    $senhanova2 = coleta_dados2($canal, $ddr, $ticket, $indice, "uraHD/23",5);
    if($senhanova2 == '-1'){hangup();break;}

    //53
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'ALTERAR SENHA DE EC', 'SENHA NOVA', 'DADOS CONFIDENCIAIS');

    $categoria='PORTAL';
    $subCat='ALTERAR SENHA';
    verbose("3333333333333333 : ".$cnpjcpf);
    $protocoloEASY=api_ucc_protocolo_v2($categoria, $cnpjcpf, '', $subCat, $origem, '', $uniqueid);
    verbose("AAAAAAAAAAAAAAAA : ".$protocoloEASY);

    if(preg_match("/^(\d{5})$/",$senhanova2)){
        if($senhanova == $senhanova2){
            if($GLOBALS["testeativo"] == 'Y'){
                verbose("Entrou no teste");
                $alteracaosenha = true;
            }else{
                $alteracaosenha = api_ucc_altera_senha($uniqueid, $origem, $cnpjcpf, $senhanova2);
                verbose("OLHA SO OLHA LA O RESULTADO DA ALTERAÇÃO : ".$alteracaosenha); 
            }

            if($alteracaosenha){
                //54
                $indice++;
                tracking($canal, $ddr, $ticket, $indice, 'ALTERAR SENHA DE EC', 'RETORNO', 'SENHA VALIDA');

                playback("uraHD/24_1");
                falar_alfa($protocoloEASY);
                playback("uraHD/24_2");
                falar_alfa($protocoloEASY);
                Op01_1_2_alterar_senha_de_ec_senha_alterada($uniqueid ,$origem ,$cnpjcpf ,$protocoloEASY,$senhanova, $cnpjcpfValidado);

            }else{
                //54
                $indice++;
                tracking($canal, $ddr, $ticket, $indice, 'ALTERAR SENHA DE EC', 'RETORNO', 'SENHA INVALIDA');

                if(retentar_dado_invalido("Op01_1_2_alterar_senha_de_ec_senha_validada","uraHD/5","senhas digitadas diferentes ou invalidas"))Op01_1_2_alterar_senha_de_ec_senha_validada($uniqueid ,$origem ,$cnpjcpf ,$protocoloEASY,$senha);
                else{
                    //55
                    $indice++;
                    tracking($canal, $ddr, $ticket, $indice, 'ALTERAR SENHA DE EC', 'PERCURSO', 'TENTATIVAS EXCEDIDAS SENHA INVALIDA');

                    //encerra_por_retentativas("Op01_1_2_alterar_senha_de_ec_senha_validada","uraHD/4","senhas digitadas diferentes ou invalidas");
                    encerra_com_tracking($canal, $ddr, $ticket, $indice, "Op01_1_2_alterar_senha_de_ec_senha_validada","uraHD/4","senhas digitadas diferentes ou invalidas");
                }
            }

        }else{
            //54
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'ALTERAR SENHA DE EC', 'RETORNO', 'SENHA INVALIDA');

            if(retentar_dado_invalido("Op01_1_2_alterar_senha_de_ec_senha_validada","uraHD/5","senhas digitadas diferentes ou invalidas"))Op01_1_2_alterar_senha_de_ec_senha_validada($uniqueid ,$origem ,$cnpjcpf ,$protocoloEASY,$senha);
            else{
                //55
                $indice++;
                tracking($canal, $ddr, $ticket, $indice, 'ALTERAR SENHA DE EC', 'PERCURSO', 'TENTATIVAS EXCEDIDAS SENHA INVALIDA');

                //encerra_por_retentativas("Op01_1_2_alterar_senha_de_ec_senha_validada","uraHD/4","senhas digitadas diferentes ou invalidas");
                encerra_com_tracking($canal, $ddr, $ticket, $indice, "Op01_1_2_alterar_senha_de_ec_senha_validada","uraHD/4","senhas digitadas diferentes ou invalidas");
            }
        }
    }
}

function Op01_1_2_alterar_senha_de_ec_senha_alterada($uniqueid ,$origem ,$cnpjcpf ,$protocoloEASY,$senha, $cnpjcpfValidado){
    global $canal, $ddr, $ticket, $indice, $horaAtual;
    //58
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'ALTERAR SENHA DE EC', 'PERCURSO', '0 - VOLTAR AO MENU PRINCIPAL OU DESLIGAR TELEFONE');

    $digitado ='';
    //$digitado = coletar_dados_usuario("uraHD/25",1);
    $digitado = coletar_dados_usuario("uraHD/25",1);
    if($digitado == '-1'){hangup();break;}

    //59
    $indice++;
    tracking($canal, $ddr, $ticket, $indice, 'ALTERAR SENHA DE EC', 'RESPOSTA', 'OPCAO ESCOLHIDA PELO CLIENTE: '.$digitado);

    if($digitado == '0'){
        verbose("Mandando chamada de volta pro menu_principal");
        inicializa_ambiente_novo_menu();
        M1_1_Principal($uniqueid, $origem, $cnpjcpf,$protocoloEASY, $cnpjcpfValidado);
        hangup();break;
    }else{
        if(retentar_dado_invalido("Op01_1_2_alterar_senha_de_ec_senha_alterada","uraHD/5","Cliente nao escolheu se vai voltar pro menu")){
            Op01_1_2_alterar_senha_de_ec_senha_alterada($uniqueid ,$origem ,$cnpjcpf ,$protocoloEASY,$senha);
        }
        else{
            //60
            $indice++;
            tracking($canal, $ddr, $ticket, $indice, 'ALTERAR SENHA DE EC', 'RESPOSTA', 'OPCAO ESCOLHIDA PELO CLIENTE: '.$digitado);

            //encerra_por_retentativas("Op01_1_2_alterar_senha_de_ec_senha_alterada","uraHD/4","MAX TENTATIVA");
            encerra_com_tracking($canal, $ddr, $ticket, $indice, "Op01_1_2_alterar_senha_de_ec_senha_alterada","uraHD/4","MAX TENTATIVA");
        }
    }
}

return 0;
hangup();
break;
exit();
?>