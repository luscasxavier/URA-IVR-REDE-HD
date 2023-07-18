<?php
$configs = include('config_hd.php');


//############################################################
//FUNÇÃO QUE RETORNA O TOKEN DE LOGIN PARA USO NAS DEMAIS APIS
function api_login_token()
{
    global $configs;
    $usuario=$configs['user_login'];
    $senha =$configs['pass_login'];
    
    $url = $configs['server_api'].$configs['url_login'];
    $ch = curl_init($url);
    $data = array(
        'login' => $usuario,
        'password' => $senha
    );

    $payload = json_encode($data);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 400);
    $result = curl_exec($ch);
    curl_close($ch);
    
    $obj = json_decode($result);    

    if(property_exists($obj,'token')) return $obj->{'token'};
    else return 'error';
}

//############################################################



//############################################################
// COMUM CONTROLER FUNÇÃO QUE RETORNA O HORÁRIO DE ATENDIMENTO
function api_horario_atendimento()
{
    global $configs;
    $token=api_login_token();
    
    if($token != 'error'){//token retornado com sucesso
    $url = $configs['server_api'].$configs['url_rede_horario'];//.$configs['siglaUra'];
    $ch = curl_init($url);
    //echo($url)."\n";

    $headers = array();
    $headers[] = 'Content-Type:application/json';
    $headers[] = 'Accept: application/json';
    $headers[] = 'X-Auth-Token: '.$token;

    $data = array(
        'equipe'=> '',
        'siglaUra'=> 'REDE'
    );

    $payload = json_encode($data);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    //echo $result."\n";
    curl_close($ch);    

    $obj = json_decode($result);
    //var_dump($obj);


    $horaInicio = $obj->{'horaInicio'};
    $horaFim = $obj->{'horaFim'};
    $diaSemanaInicio = $obj->{'diaSemanaInicio'};
    $diaSemanaFim = $obj->{'diaSemanaFim'};
    
    //Setando o time zone
    date_default_timezone_set('America/Sao_Paulo');
    //Definindo a hora e minuto atual
    $horaAtual = date('H:i');

    $data = date('Y-m-d');
    //Definindo o dia da semana atual como números
    $diaSemana_numero = date('w', strtotime($data));
    $diaSemana_numero = $diaSemana_numero +1;

    }
    
    //Variaveis para teste
    //$horaAtual = '20:00';
    //$diaSemana_numero = '7';
    
    //Comparação com os horarios de atendimento
    /*if($horaAtual >= $horaInicio && $horaAtual <= $horaFim && $diaSemana_numero >= $diaSemanaInicio && $diaSemana_numero <= $diaSemanaFim){
        return true;
    }   else return false;
    */
    if($obj->{'atendimentoDisponivel'}=='S') return true;
    else return false;
        
        

}
//############################################################




//############################################################
// COMUM CONTROLER ABRIR PROTOCOLO NO EASY
function api_ucc_protocolo_easy($processId, $cnpjcpf, $origem){

    global $configs;
    $token=api_login_token();
    
    $url = $configs['server_api'].$configs['url_ucc_abertura_ptcl_easy'];
    //echo $url."\n";
    $ch = curl_init($url);


    $headers = array();
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'Accept: text/plain';
    $headers[] = 'X-Auth-Token: '.$token;

    switch($processId){
        case 'WKF_Prospect':
        $entidade ='flag_entidade_pros_estab_oc';
        break;
        
        case 'WKF_PROTOCOLO_PAI':
        $entidade='flag_entidade_pros_clie_oc';
        break;

        default:
        $entidade='flag_entidade_pros_clie_oc';
        break;
    }

    $data = array(

        'processId'=>$processId,
        'origemSolicitacao'=>'URA',
        'empresa'=>'Agili',
        'cnpj'=>$cnpjcpf,
        'telefone'=>$origem,
        'flagEntidade'=>$entidade
    );

    $payload = json_encode($data);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);

    //var_dump($result);

    curl_close($ch);    

    $obj = json_decode($result);
    //var_dump($obj);

    if(isset($obj ->{'PROTOCOLO_ATENDIMENTO'})) return $obj ->{'PROTOCOLO_ATENDIMENTO'};
    else return 0;
    //return '255548';
}
//###########################################################




//###########################################################
// API UCC DIRECIONAR LIGAÇÃO ATENDENTE
function api_ucc_direcionar_lig_atendente($uniqueid, $origem, $cnpjcpf, $protocolo){
    global $configs;
    $token=api_login_token();
        
    $url = $configs['server_api'].$configs['url_ucc_direciona_lig'];
    //echo $url."\n";
    $ch = curl_init($url);

    //$protocolo=api_ucc_protocolo_easy($processId, $cnpjcpf, $origem);  // PENDENCIA : PRECISA DO RETORNO DESSA API PRA EXECUTAR MAS
                                                                         // MAS ESSA API N PODE SER CHAMADA DUAS VEZES
    
    $headers = array();
    $headers[] = 'Content-Type:application/json';
    $headers[] = 'X-Auth-Token: '.$token;
    
    $data = array(
        'ligacaoId'=>$uniqueid,
        'numero_origem'=>$origem,
        'protocoloAtendimento'=>$protocolo,
        'cnpjCpf'=>$cnpjcpf,
        'ilhaAtendimento'=>'receptivoRede' 
    );
    
    $payload = json_encode($data);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    //echo $result."\n";
    curl_close($ch);    
     $obj = json_decode($result);

    //var_dump($obj);

    if($obj->{'url'} !='') return $obj->{'url'};
        else return false;

}
//############################################################
// API UCC DE VALIDAR SENHA
function api_ucc_valida_senha($uniqueid, $cnpjcpf, $origem, $senha){
    global $configs;
    $token=api_login_token();
        
    $url = $configs['server_api'].$configs['url_ucc_valida_senha'];
    //echo $url."\n";
    $ch = curl_init($url);
    
    
    $headers = array();
    $headers[] = 'Content-Type:application/json';
    $headers[] = 'Accept: text/plain';
    $headers[] = 'X-Auth-Token: '.$token;
    
    $data = array(
        'entidade'=>'CONV',
        'ligacaoId'=>$uniqueid,
        'login'=>$cnpjcpf,
        'numeroOrigem'=>$origem,
        'senha'=>$senha
    );
    
    $payload = json_encode($data);
    //echo $payload;
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    //echo $result."\n";
    curl_close($ch);    
     $obj = json_decode($result);

    //var_dump($obj);

     if($obj->{'senhaValidada'} == 'S') return true;
     else return false;

}
//############################################################




//############################################################
//FUNÇÃO Verifica se cpf/cnpj é credenciado na valecard api 02
function api_urc_valida_cnpj_cpf($uniqueid, $origem, $cnpjcpf){

    global $configs;
    $token=api_login_token();
    
    $url = $configs['server_api'].$configs['url_rede_valida_cnpjcpf'];
    //echo $url."\n";
    $ch = curl_init($url);


    $headers = array();
    $headers[] = 'Content-Type:application/json';
    $headers[] = 'Accept: application/json';
    $headers[] = 'X-Auth-Token: '.$token;

    $data = array(
        'cnpjCpf'=> $cnpjcpf,
        'ligacaoId'=> $uniqueid,
        'numeroOrigem'=> $origem
    );

    $payload = json_encode($data);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    //echo $result."\n";
    curl_close($ch);    

    $obj = json_decode($result);
    //var_dump($obj);

    //if($obj->{'possuiContrato'} =='S') return true;
    //else return false;

    return $obj;

}
//############################################################



//############################################################
// FUNÇÃO PARA SIMULAR ANTECIPAÇÃO DE RECEBÍVEIS
function api_urc_simula_ant_receb($cnpjcpf, $uniqueid, $origem){ // PENDENCIA : NO FLUXO COLOCAR O RESULTADO NUMA VARIÁVEL
    global $configs;
    $token=api_login_token();
    
    $url = $configs['server_api'].$configs['url_urc_ant_receb'];
    //echo $url."\n";
    $ch = curl_init($url);


    $headers = array();
    $headers[] = 'Content-Type:application/json';
    $headers[] = 'Accept: application/json';
    $headers[] = 'X-Auth-Token: '.$token;

    $data = array(
        'cnpjCpf'=> $cnpjcpf,
        'ligacaoId'=> $uniqueid,
        'numeroOrigem'=> $origem
    );


    $payload = json_encode($data);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    //echo $result."\n";
    curl_close($ch);    

    $obj = json_decode($result);
    //var_dump($obj);

    return $obj;
}
//############################################################



//############################################################
// FUNÇÃO CONFIRMA ANTECIPAÇÃO DE RECEBÍVEIS
function api_urc_confirma_ant_receb($cnpjcpf, $uniqueid, $origem, $codigo){
    global $configs;
    $token=api_login_token();
    
    $url = $configs['server_api'].$configs['url_urc_confirma_ant_receb'];
    //echo $url."\n";
    $ch = curl_init($url);

    $headers = array();
    $headers[] = 'Content-Type:application/json';
    $headers[] = 'X-Auth-Token: '.$token;

    $data = array(
        'cnpjCpf'=> $cnpjcpf,
        'ligacaoId'=> $uniqueid,
        'numeroOrigem'=> $origem,
        'seqAntecipacaoSimulado'=> intval($codigo)
    );

    //intval($cod_autoriza),
    //floatval($valor_transacao),

    $payload = json_encode($data);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    //echo $result."\n";
    curl_close($ch);    

    $obj = json_decode($result);

    //var_dump($obj);

    if($obj->{'confirmaAntecipacao'} == 'S') return true;
    else return false;
}
//##########################################################




//#######################################################
// API TBC VALIDA CARTAO TRANSAÇÃO
function api_tbc_valida_cartao($uniqueid, $origem, $num_cartao){

    global $configs;
    $token=api_login_token();
    
    $url = $configs['server_api'].$configs['url_tbc_valida_cartao'];
    //echo $url."\n";
    $ch = curl_init($url);

    $headers = array();
    $headers[] = 'Content-Type:application/json';
    $headers[] = 'X-Auth-Token: '.$token;

    $data = array(
        'ligacaoId'=>$uniqueid,
        'numeroOrigem'=>$origem,
        'numeroCartao'=>$num_cartao
    );

    $payload = json_encode($data);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    //echo $result."\n";
    curl_close($ch);    

    $obj = json_decode($result);

    //var_dump($obj);

    if($obj->{"CartaoValido"} =='S') return true;
    else return false;
}
//######################################################


//#######################################################
// API TBC AUTORIZA TRANSAÇÃO
function api_tbc_autoriza_transacao($uniqueid, $origem, $num_cartao, $senha, $num_contrato, $valor_Tt, $parcelas, $tp_transacao, $entrada){

    global $configs;
    $token=api_login_token();
    
    $url = $configs['server_api'].$configs['url_tbc_autoriza_transacao'];
    //echo $url."\n";
    $ch = curl_init($url);

    $headers = array();
    $headers[] = 'Content-Type:application/json';
    $headers[] = 'X-Auth-Token: '.$token;

    $data = array(
        'ligacaoId'=>$uniqueid,
        'numeroCartao'=>$num_cartao,
        'numeroContrato'=>$num_contrato,
        'numeroOrigem'=>$origem,
        'qtdParcela'=>intval($parcelas),
        'senhaEC'=>$senha,
        'tipoTransacao'=>$tp_transacao,
        'valorTotal'=>floatval($valor_Tt),
        'vendaComEntrada'=>$entrada
    );

    $payload = json_encode($data);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    //echo $result."\n";
    curl_close($ch);    

    $obj = json_decode($result);

    //var_dump($obj);
    

    return $obj;
    //obs marco 51: certeza q isso daqui ta certo? esse autorizada com a minusculo ta diferente da documentacao lucas.(linha acima)
    //e o correto e retornar tudo q a api retorna nesse caso especifico aqui.
    //elseif($obj->{"autorizada"} =='N') return $obj->{'codigoRetornoAutorizacao'};

}
//######################################################



//######################################################
// API TBC AUTORIZA TRANSAÇÃO
function api_tbc_realiza_estorno($uniqueid, $origem, $nsu){

    global $configs;
    $token=api_login_token();
    
    $url = $configs['server_api'].$configs['url_tbc_realiza_estorno'];
    //echo $url."\n";
    $ch = curl_init($url);

    $headers = array();
    $headers[] = 'Content-Type:application/json';
    $headers[] = 'X-Auth-Token: '.$token;

    $data = array(
        'ligacaoId'=>$uniqueid,
        'numeroOrigem'=>$origem,
        'nsu'=>$nsu
    );


    $payload = json_encode($data);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    //echo $result."\n";
    curl_close($ch);    

    $obj = json_decode($result);

    //var_dump($obj);
    return $obj;

}
//######################################################




//######################################################
// API TBC AUTORIZA SERVIÇO VENDA FROTA -> AINDA ESTÁ INCOMPLETA PELO PESSOAL DA VALE
function api_ufc_autoriza_venda_frota($uniqueid, $origem, $cod_estab, $senha, $num_cartao, $placa_veic, $km_veic, $num_matricula, $valor_Tt, $cod_produto, $qnt_produto, $senhaMt){

    global $configs;
    $token=api_login_token();
    
    $url = $configs['server_api'].$configs['url_ufc_autoriza_venda_frota'];
    //echo $url."\n";
    $ch = curl_init($url);

    $headers = array();
    $headers[] = 'Content-Type:application/json';
    $headers[] = 'X-Auth-Token: '.$token;

    $data = array(

        'codigoProduto'=>$cod_produto,
        'kilometragem'=>$km_veic,
        'ligacaoId'=>$uniqueid,
        'numeroCartao'=>$num_cartao,
        'numeroEC'=>$cod_estab,
        'numeroMatricula'=>$num_matricula,
        'numeroOrigem'=>$origem,
        'placaVeiculo'=> $placa_veic,
        'quantidadeProduto'=>floatval($qnt_produto),
        'senhaEC'=>$senha,
        'senhaMotorista'=>$senhaMt,
        'valor'=>floatval($valor_Tt)       
    );


    $payload = json_encode($data);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    //echo $result."\n";
    curl_close($ch);    

    $obj = json_decode($result);

    //var_dump($obj);

    return $obj;
    //if($obj->{'autorizada'}=='S') return $obj;
    //else return $obj->{'codigoRetornoAutorizacao'};

}
//######################################################





//######################################################
//API UFC VALIDAR CARTAO FROTA
function api_ufc_vld_cartao_frota($uniqueid, $origem,$num_cartao){

    global $configs;
    $token=api_login_token();
    
    $url = $configs['server_api'].$configs['url_ufc_vld_cartao_frota'];
    //echo $url."\n";
    $ch = curl_init($url);

    $headers = array();
    $headers[] = 'Content-Type:application/json';
    $headers[] = 'Accept: text/plain';
    $headers[] = 'X-Auth-Token: '.$token;

    $data = array(
        'ligacaoId'=>$uniqueid,
        'numero_origem'=>$origem,
        'numeroCartao'=>$num_cartao
    );


    $payload = json_encode($data);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    //echo $result."\n";
    curl_close($ch);    

    $obj = json_decode($result);

    //var_dump($obj);

    if($obj->{'CartaoValido'}=='S') return $obj;
    else return false;

}
//######################################################



//######################################################
//API UFC VALIDAR CARTAO FROTA 
function api_ufc_vld_mtcl_motorista_frota($uniqueid, $origem, $num_matricula, $num_cartao){

    global $configs;
    $token=api_login_token();
    
    $url = $configs['server_api'].$configs['url_ufc_vld_mtcl_motorista'];
    //echo $url."\n";
    $ch = curl_init($url);

    $headers = array();
    $headers[] = 'Content-Type:application/json';
    $headers[] = 'Accept: text/plain';
    $headers[] = 'X-Auth-Token: '.$token;

    $data = array(
        'ligacaoId'=>$uniqueid,
        'numeroOrigem'=>$origem,
        'numeroMatricula'=>$num_matricula,
        'numeroCartao'=>$num_cartao,
    );


    $payload = json_encode($data);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    //echo $result."\n";
    curl_close($ch);    

    $obj = json_decode($result);

    //var_dump($obj);

    if($obj->{'MatriculaValida'}=='S') return $obj;
    else return false;

}
//######################################################



//######################################################
//API TBC BUSCAR TRANSAÇÃO
function api_tbc_bsc_transacao($uniqueid, $origem, $num_cartao, $cod_autoriza, $valor_transacao, $cod_estab){

    global $configs;
    $token=api_login_token();
    
    $url = $configs['server_api'].$configs['url_tbc_bsc_transacao'];
    //echo $url."\n";
    $ch = curl_init($url);

    $headers = array();
    $headers[] = 'Content-Type:application/json';
    $headers[] = 'Accept: application/json';
    $headers[] = 'X-Auth-Token: '.$token;

    $data = array(
        'ligacaoId'=>$uniqueid,
        'numeroOrigem'=>$origem,
        'numeroCartao'=>$num_cartao,
        'codigoAutorizacao'=>intval($cod_autoriza),
        'valorTransacao'=>floatval($valor_transacao),
        'codigoEC'=>$cod_estab
    );


    $payload = json_encode($data);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    //echo $result."\n";
    curl_close($ch);    

    $obj = json_decode($result);

    //var_dump($obj);

    return $obj;
}
//############################################################



//############################################################
//API UCC ALTERA SENHA 
function api_ucc_altera_senha($uniqueid, $origem, $cnpjcpf, $novaSenha){
    global $configs;
    $token=api_login_token();
        
    $url = $configs['server_api'].$configs['url_ucc_altera_senha'];
    //echo $url."\n";
    $ch = curl_init($url);
    
    
    $headers = array();
    $headers[] = 'Content-Type:application/json';
    $headers[] = 'Accept: text/plain';
    $headers[] = 'X-Auth-Token: '.$token;
    
    $data = array(
        'entidade'=>'CONV',
        'ligacaoId'=>$uniqueid,
        'login'=>$cnpjcpf,
        'novaSenha'=>$novaSenha,
        'numeroOrigem'=>$origem
    );
    
    $payload = json_encode($data);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    //echo $result."\n";
    curl_close($ch);    
     $obj = json_decode($result);
    
    // var_dump($obj);

    if($obj->{'senhaAlterada'}=='S') return true;
    else return false;
}
//########################################################

function get_uniqueId_kontac($origem, $servidor){
    $url = 'http://valecard-'.$servidor.'.kontac.com.br/apitelek/apiretornouniqueid.php?telefone='.$origem;
    //echo $url."\n";

    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST'
      
    ));
    
    $response = curl_exec($curl);
    curl_close($curl);
    $obj = json_decode($response);

    //var_dump($obj);    
    if($obj ->{'status'}=='success') return $obj ->{'retorno'};
    else return $obj ->{'message'};
}

//////////////////////////////////////////////////////////////////////////////
//API UCC PROTOCOLO EASY NOVA
function api_ucc_protocolo_v2($categoria, $cnpjCpf, $contrato_cartao, $subCat, $origem, $telInfo, $ligacaoId){
    global $configs;
    $token=api_login_token();
        
    $url = $configs['server_api'].$configs['url_ucc_protocolo_v2'];
    //echo $url."\n";
    $ch = curl_init($url);
    
    $headers = array();
    $headers[] = 'Content-Type:application/json';
    $headers[] = 'Accept: text/plain';
    $headers[] = 'X-Auth-Token: '.$token;

    if($subCat!=''){
        $origemSolicitacao= 'ura_automatico';
    }else $origemSolicitacao='URA-Atendente';

    verbose("CATEGORIA: ".$categoria);
    verbose("CNPJCPF: ".$cnpjCpf);
    verbose("CONTRATO CARTAO: ".$contrato_cartao);
    verbose("SUBCATEGORIA: ".$subCat);
    verbose("ORIGEM: ".$origem);
    verbose("TELEFONE INFORMADO: ".$telInfo);
    verbose("LIGACAO ID: ".$ligacaoId);
    verbose("ORIGEM SOLICITACAO: ".$origemSolicitacao);
    
    $data = array(        
        'categoria'=>$categoria,
        'cnpj_cpf'=>$cnpjCpf,
        'contrato_cartao'=>$contrato_cartao,
        'empresa'=>'VALECARD',
        'flagEntidade'=>'ESTABELECIMENTO',
        'ligacaoId'=>$ligacaoId,
        'origemSolicitacao'=>$origemSolicitacao,
        'processId'=>'WKF_Atendimento_Help_Desk',
        'subcategoria'=>$subCat,
        'telefone'=>$origem,
        'telefoneInformado'=>$telInfo,
    );
    
    $payload = json_encode($data);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    //echo $result."\n";
    curl_close($ch);    
    $obj = json_decode($result);
    
    //var_dump($obj);

    if(isset($obj ->{'PROTOCOLO_ATENDIMENTO'})) return $obj ->{'PROTOCOLO_ATENDIMENTO'};
    else return 0;
}

?>