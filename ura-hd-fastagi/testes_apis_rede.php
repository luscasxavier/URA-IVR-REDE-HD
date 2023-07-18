
<?php

require_once 'apis_hd.php';

$opcao = $argv[1];

switch ($opcao) {
    case 1:
        echo api_login_token();
        break;

    case 2:
        echo api_horario_atendimento();
        break;

    case 3:
        echo api_ucc_protocolo_easy($argv[2], $argv[3], $argv[4])."\n";
        break;

    case 4:
        echo api_abre_solicitacao_easy_emissor($argv[2], $argv[3])."\n";
        break;

    case 5:
        echo api_urc_valida_cnpj_cpf($argv[2], $argv[3], $argv[4])."\n";
        break;
    
    case 6:
        echo api_urc_simula_ant_receb($argv[2], $argv[3], $argv[4])."\n";
        break;

    case 7:
        echo api_urc_confirma_ant_receb($argv[2], $argv[3], $argv[4], $argv[5])."\n";
        break;

    case 8:
        echo api_ucc_valida_senha($argv[2], $argv[3], $argv[4], $argv[5])."\n";
        break;
    
    case 9:
        echo api_ucc_direcionar_lig_atendente($argv[2], $argv[3], $argv[4], $argv[5])."\n";
        break;

    case 10:
        echo api_tbc_valida_cartao($argv[2], $argv[3], $argv[4])."\n";
        break;

    case 11:
        echo api_ufc_vld_cartao_frota($argv[2], $argv[3], $argv[4])."\n";
        break;

    case 12:
        echo api_ufc_vld_mtcl_motorista_frota($argv[2], $argv[3], $argv[4], $argv[5])."\n";
        break;

    case 13:
        echo api_tbc_autoriza_transacao($argv[2], $argv[3], $argv[4], $argv[5], $argv[6], $argv[7], $argv[8], $argv[9], $argv[10])."\n";
        break;
    
    case 14:
        echo api_tbc_realiza_estorno($argv[2], $argv[3], $argv[4])."\n";
        break;

    case 15:
        echo api_tbc_autoriza_venda_frota($argv[2], $argv[3], $argv[4], $argv[5], $argv[6], $argv[7], $argv[8], $argv[9], $argv[10], $argv[11])."\n";
        break;

    case 16:
        echo api_tbc_bsc_transacao($argv[2], $argv[3], $argv[4], $argv[5], $argv[6], $argv[7])."\n";
        break;

    case 17:
        echo api_ucc_altera_senha($argv[2], $argv[3], $argv[4], $argv[5])."\n";
        break;

    case 18:
        echo api_ufc_autoriza_venda_frota($argv[2], $argv[3], $argv[4], $argv[5], $argv[6], $argv[7], $argv[8], $argv[9], $argv[10], $argv[11], $argv[12])."\n";
        break;

    default:
        echo 'OPÇÃO INVALIDA';
            break;
}


?>